<?php

require_once __DIR__ . "/paths.php";

const HM_ROLES = ["super_admin", "committee_admin", "email_manager"];

function hm_https(): bool {
  $https = (string) ($_SERVER["HTTPS"] ?? "");
  if ($https !== "" && strtolower($https) !== "off") return true;
  return (int) ($_SERVER["SERVER_PORT"] ?? 0) === 443;
}

function hm_boot(): void {
  if (session_status() === PHP_SESSION_ACTIVE) return;
  $sessionPath = getenv("HM_SESSION_PATH");
  if (is_string($sessionPath) && $sessionPath !== "") {
    if (hm_path_is_public($sessionPath)) {
      throw new RuntimeException("session_public");
    }
    if (!is_dir($sessionPath) && !mkdir($sessionPath, 0700, true) && !is_dir($sessionPath)) {
      throw new RuntimeException("session_create");
    }
    if (!hm_secure_directory($sessionPath)) {
      throw new RuntimeException("session_perms");
    }
    session_save_path($sessionPath);
  }
  session_start([
    "cookie_httponly" => true,
    "cookie_samesite" => "Lax",
    "cookie_secure" => hm_https(),
    "use_strict_mode" => true
  ]);
}

function hm_csrf(): string {
  if (empty($_SESSION["csrf"])) $_SESSION["csrf"] = bin2hex(random_bytes(16));
  return (string) $_SESSION["csrf"];
}

function hm_csrf_ok(): bool {
  return isset($_POST["csrf"]) && hash_equals(hm_csrf(), (string) $_POST["csrf"]);
}

function hm_username(string $value): string {
  $value = strtolower(trim($value));
  return preg_match("/^[a-z0-9][a-z0-9._-]{2,31}$/", $value) ? $value : "";
}

function hm_users_path(bool $create): string {
  return hm_private_dir($create) . DIRECTORY_SEPARATOR . "users.json";
}

function hm_load_users(): array {
  try {
    $path = hm_users_path(false);
  } catch (RuntimeException $error) {
    throw $error;
  }
  if (!is_file($path)) return ["users" => []];
  $data = json_decode((string) file_get_contents($path), true);
  if (!is_array($data) || !isset($data["users"]) || !is_array($data["users"])) {
    return ["users" => []];
  }
  return $data;
}

function hm_update_users(callable $change): bool {
  $dir = hm_private_dir(true);
  $path = $dir . DIRECTORY_SEPARATOR . "users.json";
  $lock = $dir . DIRECTORY_SEPARATOR . "users.lock";
  $handle = fopen($lock, "c");
  if ($handle === false || !flock($handle, LOCK_EX)) {
    if (is_resource($handle)) fclose($handle);
    return false;
  }
  hm_secure_file($lock);
  try {
    $data = ["users" => []];
    if (is_file($path)) {
      $parsed = json_decode((string) file_get_contents($path), true);
      if (is_array($parsed) && isset($parsed["users"]) && is_array($parsed["users"])) $data = $parsed;
    }
    $next = $change($data);
    if (!is_array($next) || !isset($next["users"]) || !is_array($next["users"])) return false;
    $json = json_encode($next, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    if ($json === false) return false;
    return hm_atomic_write($path, $json . "\n");
  } finally {
    flock($handle, LOCK_UN);
    fclose($handle);
  }
}

function hm_find_user(string $username): ?array {
  $username = hm_username($username);
  if ($username === "") return null;
  foreach (hm_load_users()["users"] as $user) {
    if (is_array($user) && hm_username((string) ($user["username"] ?? "")) === $username) return $user;
  }
  return null;
}

function hm_find_user_id(string $id): ?array {
  if ($id === "") return null;
  foreach (hm_load_users()["users"] as $user) {
    if (is_array($user) && (string) ($user["id"] ?? "") === $id) return $user;
  }
  return null;
}

function hm_public_user(array $user): array {
  $roles = $user["roles"] ?? [];
  return [
    "id" => (string) ($user["id"] ?? ""),
    "username" => (string) ($user["username"] ?? ""),
    "roles" => hm_valid_roles(is_array($roles) ? $roles : []),
    "enabled" => !empty($user["enabled"])
  ];
}

function hm_public_users(): array {
  $list = [];
  foreach (hm_load_users()["users"] as $user) {
    if (is_array($user)) $list[] = hm_public_user($user);
  }
  return $list;
}

function hm_valid_roles(array $roles): array {
  $clean = [];
  foreach ($roles as $role) {
    if (in_array($role, HM_ROLES, true) && !in_array($role, $clean, true)) $clean[] = $role;
  }
  return $clean;
}

function hm_create_user(string $username, string $password, array $roles): array {
  $username = hm_username($username);
  $roles = hm_valid_roles($roles);
  if ($username === "" || strlen($password) < 8 || $roles === []) {
    throw new RuntimeException("user_invalid");
  }
  $created = null;
  $saved = hm_update_users(function ($data) use ($username, $password, $roles, &$created) {
    foreach ($data["users"] as $user) {
      if (is_array($user) && hm_username((string) ($user["username"] ?? "")) === $username) {
        throw new RuntimeException("user_exists");
      }
    }
    $created = [
      "id" => "u" . bin2hex(random_bytes(8)),
      "username" => $username,
      "hash" => password_hash($password, PASSWORD_DEFAULT),
      "roles" => $roles,
      "enabled" => true
    ];
    $data["users"][] = $created;
    return $data;
  });
  if (!$saved || !is_array($created)) throw new RuntimeException("user_save");
  return $created;
}

function hm_count_enabled_supers(array $users, string $exceptId = ""): int {
  $count = 0;
  foreach ($users as $user) {
    if (!is_array($user) || empty($user["enabled"])) continue;
    if ($exceptId !== "" && (string) ($user["id"] ?? "") === $exceptId) continue;
    $roles = $user["roles"] ?? [];
    if (is_array($roles) && in_array("super_admin", $roles, true)) $count++;
  }
  return $count;
}

function hm_is_last_enabled_super(array $users, array $user): bool {
  if (empty($user["enabled"])) return false;
  $roles = $user["roles"] ?? [];
  if (!is_array($roles) || !in_array("super_admin", $roles, true)) return false;
  return hm_count_enabled_supers($users, (string) ($user["id"] ?? "")) === 0;
}

function hm_set_enabled(string $id, bool $enabled): bool {
  return hm_update_users(function ($data) use ($id, $enabled) {
    $found = false;
    foreach ($data["users"] as $index => $user) {
      if (!is_array($user) || (string) ($user["id"] ?? "") !== $id) continue;
      if (!$enabled && hm_is_last_enabled_super($data["users"], $user)) {
        throw new RuntimeException("last_super");
      }
      $data["users"][$index]["enabled"] = $enabled;
      $found = true;
    }
    if (!$found) throw new RuntimeException("user_missing");
    return $data;
  });
}

function hm_set_password(string $id, string $password): bool {
  if (strlen($password) < 8) return false;
  return hm_update_users(function ($data) use ($id, $password) {
    $found = false;
    foreach ($data["users"] as $index => $user) {
      if (is_array($user) && (string) ($user["id"] ?? "") === $id) {
        $data["users"][$index]["hash"] = password_hash($password, PASSWORD_DEFAULT);
        $found = true;
      }
    }
    if (!$found) throw new RuntimeException("user_missing");
    return $data;
  });
}

function hm_set_roles(string $id, array $roles): bool {
  $roles = hm_valid_roles($roles);
  if ($id === "" || $roles === []) throw new RuntimeException("user_invalid");
  return hm_update_users(function ($data) use ($id, $roles) {
    $found = false;
    foreach ($data["users"] as $index => $user) {
      if (!is_array($user) || (string) ($user["id"] ?? "") !== $id) continue;
      if (!in_array("super_admin", $roles, true) && hm_is_last_enabled_super($data["users"], $user)) {
        throw new RuntimeException("last_super");
      }
      $data["users"][$index]["roles"] = $roles;
      $found = true;
    }
    if (!$found) throw new RuntimeException("user_missing");
    return $data;
  });
}

function hm_delete_user(string $id): bool {
  if ($id === "") throw new RuntimeException("user_missing");
  return hm_update_users(function ($data) use ($id) {
    $kept = [];
    $found = null;
    foreach ($data["users"] as $user) {
      if (is_array($user) && (string) ($user["id"] ?? "") === $id) {
        $found = $user;
        continue;
      }
      $kept[] = $user;
    }
    if ($found === null) throw new RuntimeException("user_missing");
    if (hm_is_last_enabled_super($data["users"], $found)) throw new RuntimeException("last_super");
    $data["users"] = array_values($kept);
    return $data;
  });
}

function hm_legacy_hash(): string {
  $path = hm_legacy_auth_path();
  if (!is_file($path)) return "";
  $data = json_decode((string) file_get_contents($path), true);
  return is_array($data) && isset($data["hash"]) ? (string) $data["hash"] : "";
}

function hm_save_legacy_hash(string $hash): bool {
  $path = hm_legacy_auth_path();
  $dir = dirname($path);
  if (!is_dir($dir) && !mkdir($dir, 0755, true)) return false;
  $json = json_encode(["hash" => $hash], JSON_UNESCAPED_UNICODE);
  if ($json === false) return false;
  return file_put_contents($path, $json . "\n", LOCK_EX) !== false;
}

function hm_clear_auth_session(): void {
  unset($_SESSION["hm_admin"], $_SESSION["hm_user_id"], $_SESSION["hm_legacy"]);
}

function hm_current_user(): ?array {
  if (!empty($_SESSION["hm_legacy"])) {
    if (hm_legacy_hash() === "") {
      hm_clear_auth_session();
      return null;
    }
    return [
      "id" => "",
      "username" => "",
      "roles" => ["super_admin"],
      "enabled" => true,
      "legacy" => true
    ];
  }
  $id = (string) ($_SESSION["hm_user_id"] ?? "");
  if ($id === "") return null;
  try {
    $user = hm_find_user_id($id);
  } catch (RuntimeException $error) {
    hm_clear_auth_session();
    return null;
  }
  if (!is_array($user) || empty($user["enabled"])) {
    hm_clear_auth_session();
    return null;
  }
  $user["legacy"] = false;
  return $user;
}

function hm_logged_in(): bool {
  return hm_current_user() !== null;
}

function hm_has_role(array $user, string $role): bool {
  $roles = $user["roles"] ?? [];
  if (!is_array($roles)) return false;
  if (in_array("super_admin", $roles, true)) return true;
  return in_array($role, $roles, true);
}

function hm_can_publish(?array $user = null): bool {
  $user = $user ?? hm_current_user();
  if ($user === null) return false;
  return hm_has_role($user, "committee_admin");
}

function hm_can_manage_users(?array $user = null): bool {
  $user = $user ?? hm_current_user();
  if ($user === null) return false;
  return hm_has_role($user, "super_admin");
}

function hm_account_post(array $post): bool {
  foreach (["create_user", "save_roles", "disable_user", "enable_user", "reset_password", "delete_user"] as $key) {
    if (isset($post[$key])) return true;
  }
  return false;
}

function hm_account_request(array $post): array {
  if (!hm_can_manage_users()) return ["message" => "", "error" => "forbidden"];
  try {
    if (isset($post["create_user"])) {
      $username = hm_username((string) ($post["username"] ?? ""));
      $password = (string) ($post["password"] ?? "");
      $again = (string) ($post["again"] ?? "");
      $roles = hm_valid_roles(is_array($post["roles"] ?? null) ? $post["roles"] : []);
      if ($password !== $again) return ["message" => "", "error" => "mismatch"];
      if (strlen($password) < 8) return ["message" => "", "error" => "too_short"];
      if ($username === "" || $roles === []) return ["message" => "", "error" => "user_invalid"];
      hm_create_user($username, $password, $roles);
      return ["message" => "user_created", "error" => ""];
    }
    if (isset($post["save_roles"])) {
      $roles = hm_valid_roles(is_array($post["roles"] ?? null) ? $post["roles"] : []);
      if ($roles === []) return ["message" => "", "error" => "user_invalid"];
      if (!hm_set_roles((string) ($post["user_id"] ?? ""), $roles)) return ["message" => "", "error" => "store_fail"];
      return ["message" => "roles_ok", "error" => ""];
    }
    if (isset($post["disable_user"]) || isset($post["enable_user"])) {
      $enable = isset($post["enable_user"]) && !isset($post["disable_user"]);
      if (!hm_set_enabled((string) ($post["user_id"] ?? ""), $enable)) return ["message" => "", "error" => "store_fail"];
      return ["message" => $enable ? "user_enabled" : "user_disabled", "error" => ""];
    }
    if (isset($post["reset_password"])) {
      $password = (string) ($post["password"] ?? "");
      $again = (string) ($post["again"] ?? "");
      if (strlen($password) < 8 || $password !== $again) return ["message" => "", "error" => "bad_new"];
      if (!hm_set_password((string) ($post["user_id"] ?? ""), $password)) return ["message" => "", "error" => "store_fail"];
      return ["message" => "reset_ok", "error" => ""];
    }
    if (isset($post["delete_user"])) {
      $id = (string) ($post["user_id"] ?? "");
      $actor = hm_current_user();
      if (!hm_delete_user($id)) return ["message" => "", "error" => "store_fail"];
      if ($actor !== null && (string) ($actor["id"] ?? "") === $id) hm_clear_auth_session();
      return ["message" => "user_deleted", "error" => ""];
    }
  } catch (RuntimeException $error) {
    $code = $error->getMessage();
    if ($code === "user_save") return ["message" => "", "error" => "store_fail"];
    if (in_array($code, ["user_invalid", "user_exists", "user_missing", "last_super"], true)) {
      return ["message" => "", "error" => $code];
    }
    return ["message" => "", "error" => "unavailable"];
  }
  return ["message" => "", "error" => "forbidden"];
}

function hm_login_named(array $user): void {
  session_regenerate_id(true);
  $_SESSION["hm_user_id"] = (string) $user["id"];
  $_SESSION["hm_legacy"] = false;
  $_SESSION["hm_admin"] = true;
  $_SESSION["fails"] = 0;
}

function hm_login_legacy(): void {
  session_regenerate_id(true);
  unset($_SESSION["hm_user_id"]);
  $_SESSION["hm_legacy"] = true;
  $_SESSION["hm_admin"] = true;
  $_SESSION["fails"] = 0;
}

function hm_logout(): void {
  $_SESSION = [];
  if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), "", time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
  }
  session_destroy();
}

function hm_attempts_path(): string {
  return hm_private_dir(false) . DIRECTORY_SEPARATOR . "attempts.json";
}

function hm_attempt_count(string $bucket): int {
  try {
    $path = hm_attempts_path();
  } catch (RuntimeException $error) {
    return 0;
  }
  if (!is_file($path)) return 0;
  $data = json_decode((string) file_get_contents($path), true);
  if (!is_array($data) || !isset($data[$bucket]) || !is_array($data[$bucket])) return 0;
  if ((int) ($data[$bucket]["until"] ?? 0) < time()) return 0;
  return (int) ($data[$bucket]["count"] ?? 0);
}

function hm_attempt_mark(string $bucket, bool $clear): void {
  try {
    $dir = hm_private_dir(false);
  } catch (RuntimeException $error) {
    return;
  }
  if (!is_dir($dir)) return;
  $path = $dir . DIRECTORY_SEPARATOR . "attempts.json";
  $data = [];
  if (is_file($path)) {
    $parsed = json_decode((string) file_get_contents($path), true);
    if (is_array($parsed)) $data = $parsed;
  }
  if ($clear) {
    unset($data[$bucket]);
  } else {
    $current = (isset($data[$bucket]) && is_array($data[$bucket]) && (int) ($data[$bucket]["until"] ?? 0) >= time())
      ? (int) $data[$bucket]["count"]
      : 0;
    $data[$bucket] = ["count" => $current + 1, "until" => time() + 900];
  }
  $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
  if ($json !== false) hm_atomic_write($path, $json . "\n");
}

function hm_dummy_hash(): string {
  static $hash = null;
  if ($hash === null) $hash = password_hash("hm-dummy-not-a-login", PASSWORD_DEFAULT);
  return $hash;
}

function hm_attempt_login(string $username, string $password): string {
  hm_clear_auth_session();
  if (($_SESSION["fails"] ?? 0) >= 8) return "locked";
  $named = trim($username) !== "";
  if ($named) {
    $normal = hm_username($username);
    try {
      $user = $normal === "" ? null : hm_find_user($normal);
    } catch (RuntimeException $error) {
      return "unavailable";
    }
    $bucket = "user:" . ($normal === "" ? "invalid" : $normal);
    if (hm_attempt_count($bucket) >= 8) return "locked";
    $hash = is_array($user) ? (string) ($user["hash"] ?? "") : hm_dummy_hash();
    $checked = $hash !== "" && password_verify($password, $hash);
    $valid = $checked && is_array($user) && !empty($user["enabled"]);
    if (!$valid) {
      $_SESSION["fails"] = (int) ($_SESSION["fails"] ?? 0) + 1;
      hm_attempt_mark($bucket, false);
      return "bad_login";
    }
    hm_attempt_mark($bucket, true);
    hm_login_named($user);
    return "";
  }
  $hash = hm_legacy_hash();
  if ($hash === "" || !password_verify($password, $hash)) {
    $_SESSION["fails"] = (int) ($_SESSION["fails"] ?? 0) + 1;
    hm_attempt_mark("legacy", false);
    return "bad_login";
  }
  hm_attempt_mark("legacy", true);
  hm_login_legacy();
  return "";
}

function hm_change_password(string $current, string $password, string $again): string {
  $user = hm_current_user();
  if ($user === null) return "need_login";
  if (!empty($user["legacy"])) {
    if (!password_verify($current, hm_legacy_hash())) return "bad_current";
    if (strlen($password) < 8 || $password !== $again) return "bad_new";
    return hm_save_legacy_hash(password_hash($password, PASSWORD_DEFAULT)) ? "" : "hash_save";
  }
  if (!password_verify($current, (string) ($user["hash"] ?? ""))) return "bad_current";
  if (strlen($password) < 8 || $password !== $again) return "bad_new";
  try {
    return hm_set_password((string) $user["id"], $password) ? "" : "hash_save";
  } catch (RuntimeException $error) {
    return "unavailable";
  }
}
