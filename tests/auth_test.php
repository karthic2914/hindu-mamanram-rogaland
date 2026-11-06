<?php

ini_set("session.use_cookies", "0");

$root = dirname(__DIR__);
$realAuth = $root . DIRECTORY_SEPARATOR . "data" . DIRECTORY_SEPARATOR . "auth.json";
$realSite = $root . DIRECTORY_SEPARATOR . "data" . DIRECTORY_SEPARATOR . "site.json";
$authBefore = is_file($realAuth) ? hash_file("sha256", $realAuth) : null;
$siteBefore = is_file($realSite) ? hash_file("sha256", $realSite) : null;

$base = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "hm-auth-" . bin2hex(random_bytes(4));
$private = $base . DIRECTORY_SEPARATOR . "private";
$data = $base . DIRECTORY_SEPARATOR . "data";
$sessions = $base . DIRECTORY_SEPARATOR . "sessions";
$legacy = $data . DIRECTORY_SEPARATOR . "legacy-auth.json";
mkdir($data, 0700, true);
mkdir($sessions, 0700, true);

putenv("HM_PRIVATE_DIR=" . $private);
putenv("HM_DATA_DIR=" . $data);
putenv("HM_LEGACY_AUTH_PATH=" . $legacy);
putenv("HM_SESSION_PATH=" . $sessions);

require $root . "/includes/publish.php";
require $root . "/router.php";

$failures = 0;
function check(bool $ok, string $label): void {
  global $failures;
  if ($ok) {
    fwrite(STDOUT, "ok  " . $label . PHP_EOL);
    return;
  }
  $failures++;
  fwrite(STDERR, "FAIL " . $label . PHP_EOL);
}

hm_boot();
check(session_status() === PHP_SESSION_ACTIVE, "session starts");

$inside = $root . DIRECTORY_SEPARATOR . "private";
$refused = false;
try {
  hm_private_dir(true);
} catch (RuntimeException $error) {
  $refused = false;
}
putenv("HM_PRIVATE_DIR=" . $inside);
try {
  hm_private_dir(true);
} catch (RuntimeException $error) {
  $refused = $error->getMessage() === "private_public";
}
putenv("HM_PRIVATE_DIR=" . $private);
check($refused && !is_dir($inside), "private directory inside the site is refused");

$usersPath = hm_users_path(true);
check(!hm_path_is_public($usersPath), "users file is outside the document root");
if (PHP_OS_FAMILY !== "Windows") {
  check((fileperms(dirname($usersPath)) & 0077) === 0, "private directory is not group or world accessible");
}

$publisher = hm_create_user("publisher", "publisher-pass", ["committee_admin"]);
check(password_verify("publisher-pass", $publisher["hash"]), "password_hash verifies");
check(!str_contains($publisher["hash"], "publisher-pass"), "stored password is only a hash");
check(!isset(hm_public_user($publisher)["hash"]), "public account view omits the hash");
$duplicate = false;
try {
  hm_create_user("Publisher", "another-pass", ["committee_admin"]);
} catch (RuntimeException $error) {
  $duplicate = $error->getMessage() === "user_exists";
}
check($duplicate, "usernames are unique regardless of case");

$unknown = hm_attempt_login("missinguser", "publisher-pass");
$wrong = hm_attempt_login("publisher", "wrong-password");
check($unknown === "bad_login" && $wrong === "bad_login", "unknown user and wrong password return the same result");

$_SESSION["fails"] = 8;
check(hm_attempt_login("publisher", "publisher-pass") === "locked", "session failure limit blocks sign-in");
$_SESSION["fails"] = 0;
$attempts = ["user:publisher" => ["count" => 8, "until" => time() + 900]];
file_put_contents(dirname($usersPath) . DIRECTORY_SEPARATOR . "attempts.json", json_encode($attempts));
check(hm_attempt_login("publisher", "publisher-pass") === "locked", "private failure record blocks a new session");
unlink(dirname($usersPath) . DIRECTORY_SEPARATOR . "attempts.json");

check(hm_attempt_login("publisher", "publisher-pass") === "", "committee admin can sign in");
check(hm_can_publish(), "committee admin can publish");
$saved = hm_publish_request([
  "save_pooja" => "1",
  "pooja_id" => "jan",
  "date" => "2026-02-01",
  "name" => "மாதாந்த பூஜை",
  "sponsor" => "சோதனை"
]);
$siteRaw = (string) file_get_contents(hm_site_path());
$site = json_decode($siteRaw, true);
check($saved["message"] === "pooja_ok" && ($site["pooja"]["jan"]["date"] ?? "") === "2026-02-01", "pooja date is published");
check(!str_contains($siteRaw, "publisher-pass") && !isset($site["hash"]) && !isset($site["users"]), "published pooja file has no account data");

$mailer = hm_create_user("mailer", "mailer-pass-1", ["email_manager"]);
hm_clear_auth_session();
check(hm_attempt_login("mailer", "mailer-pass-1") === "", "email manager can sign in");
$beforeMail = $siteRaw;
$denied = hm_publish_request([
  "save_pooja" => "1",
  "pooja_id" => "jan",
  "date" => "2026-03-01",
  "name" => "மாற்றக்கூடாது",
  "sponsor" => "இல்லை"
]);
check($denied["error"] === "forbidden" && file_get_contents(hm_site_path()) === $beforeMail, "email manager cannot publish a pooja");

hm_clear_auth_session();
check(hm_attempt_login("publisher", "publisher-pass") === "", "publisher signs in again");
check(hm_set_enabled($publisher["id"], false), "committee account can be disabled");
check(hm_current_user() === null && empty($_SESSION["hm_admin"]), "disabled account session is cleared");
check(hm_publish_request(["save_pooja" => "1", "pooja_id" => "jan", "date" => "2026-04-01", "name" => "இல்லை", "sponsor" => "இல்லை"])["error"] === "forbidden", "disabled session cannot publish");
check(hm_attempt_login("publisher", "publisher-pass") === "bad_login", "disabled account cannot sign in");

$super = hm_create_user("owner", "owner-pass-1", ["super_admin"]);
$last = false;
try {
  hm_set_enabled($super["id"], false);
} catch (RuntimeException $error) {
  $last = $error->getMessage() === "last_super";
}
check($last && hm_find_user_id($super["id"])["enabled"] === true, "the last super admin stays enabled");
check(hm_has_role(hm_find_user_id($super["id"]), "committee_admin"), "super admin includes publishing permission");

file_put_contents($legacy, json_encode(["hash" => password_hash("legacy-secret-1", PASSWORD_DEFAULT)]) . "\n");
$legacyBefore = file_get_contents($legacy);
hm_clear_auth_session();
check(hm_attempt_login("", "legacy-secret-1") === "" && hm_can_publish(), "blank username still accepts the legacy password");
check(hm_attempt_login("publisher", "legacy-secret-1") === "bad_login", "a username does not fall back to the legacy password");
hm_clear_auth_session();
check(hm_attempt_login("owner", "owner-pass-1") === "", "owner signs in");
check(hm_change_password("owner-pass-1", "owner-pass-2", "owner-pass-2") === "", "named password change succeeds");
check(file_get_contents($legacy) === $legacyBefore, "named password change does not rewrite legacy auth.json");
check(password_verify("owner-pass-2", hm_find_user("owner")["hash"]), "new password uses password_verify");

$storedUsers = file_get_contents(hm_users_path(false));
hm_clear_auth_session();
$visitor = hm_account_request([
  "create_user" => "1",
  "username" => "sneaky",
  "password" => "sneaky-pass",
  "again" => "sneaky-pass",
  "roles" => ["super_admin"]
]);
check($visitor["error"] === "forbidden" && file_get_contents(hm_users_path(false)) === $storedUsers, "signed-out visitor cannot create an account");

check(hm_attempt_login("mailer", "mailer-pass-1") === "", "email manager signs in for account checks");
$mailCreate = hm_account_request(["create_user" => "1", "username" => "sneaky", "password" => "sneaky-pass", "again" => "sneaky-pass", "roles" => ["super_admin"]]);
$mailRoles = hm_account_request(["save_roles" => "1", "user_id" => $super["id"], "roles" => ["email_manager"]]);
$mailDisable = hm_account_request(["disable_user" => "1", "user_id" => $super["id"]]);
$mailReset = hm_account_request(["reset_password" => "1", "user_id" => $super["id"], "password" => "reset-pass-9", "again" => "reset-pass-9"]);
$mailDelete = hm_account_request(["delete_user" => "1", "user_id" => $super["id"]]);
check(
  $mailCreate["error"] === "forbidden"
  && $mailRoles["error"] === "forbidden"
  && $mailDisable["error"] === "forbidden"
  && $mailReset["error"] === "forbidden"
  && $mailDelete["error"] === "forbidden"
  && file_get_contents(hm_users_path(false)) === $storedUsers
  && password_verify("owner-pass-2", hm_find_user("owner")["hash"]),
  "email manager cannot create accounts, change roles, disable accounts, or reset passwords"
);

check(hm_set_enabled($publisher["id"], true), "committee account is available for an authorization check");
$storedUsers = file_get_contents(hm_users_path(false));
hm_clear_auth_session();
check(hm_attempt_login("publisher", "publisher-pass") === "", "committee admin signs in for account checks");
$committeeDenied = hm_account_request([
  "create_user" => "1",
  "username" => "sneaky",
  "password" => "sneaky-pass",
  "again" => "sneaky-pass",
  "roles" => ["super_admin"],
  "hash" => "not-a-hash"
]);
check($committeeDenied["error"] === "forbidden" && file_get_contents(hm_users_path(false)) === $storedUsers, "committee admin cannot create an account");
check(hm_set_enabled($publisher["id"], false) && empty(hm_find_user_id($publisher["id"])["enabled"]), "committee account returns to disabled");

hm_clear_auth_session();
check(hm_attempt_login("owner", "owner-pass-2") === "", "owner signs in for account management");
$mismatch = hm_account_request(["create_user" => "1", "username" => "helper", "password" => "helper-pass-1", "again" => "other-pass", "roles" => ["committee_admin"]]);
$short = hm_account_request(["create_user" => "1", "username" => "helper", "password" => "short", "again" => "short", "roles" => ["committee_admin"]]);
check($mismatch["error"] === "mismatch" && $short["error"] === "too_short" && hm_find_user("helper") === null, "account creation rejects a mismatched or short password");
$created = hm_account_request([
  "create_user" => "1",
  "username" => "Helper",
  "password" => "helper-pass-1",
  "again" => "helper-pass-1",
  "roles" => ["committee_admin", "email_manager", "not-a-role"],
  "hash" => "plaintext"
]);
$helper = hm_find_user("helper");
$publicUsers = json_encode(hm_public_users());
check(
  $created["message"] === "user_created"
  && is_array($helper)
  && $helper["roles"] === ["committee_admin", "email_manager"]
  && $helper["hash"] !== "plaintext"
  && password_verify("helper-pass-1", $helper["hash"])
  && !isset(hm_public_user($helper)["hash"])
  && is_string($publicUsers)
  && !str_contains($publicUsers, (string) $helper["hash"]),
  "super admin can create an account without exposing the password hash"
);
$duplicate = hm_account_request(["create_user" => "1", "username" => "helper", "password" => "helper-pass-1", "again" => "helper-pass-1", "roles" => ["committee_admin"]]);
check($duplicate["error"] === "user_exists", "duplicate usernames are rejected");
$feed = (string) shell_exec(escapeshellarg(PHP_BINARY) . " " . escapeshellarg($root . DIRECTORY_SEPARATOR . "content.php"));
check(is_array($helper) && !str_contains($feed, (string) $helper["hash"]) && !str_contains($feed, "helper-pass-1"), "public content feed does not return account secrets");

$roleChange = hm_account_request(["save_roles" => "1", "user_id" => $helper["id"], "roles" => ["email_manager"]]);
$helper = hm_find_user_id($helper["id"]);
check($roleChange["message"] === "roles_ok" && is_array($helper) && $helper["roles"] === ["email_manager"], "super admin can change assigned roles");
hm_clear_auth_session();
check(hm_attempt_login("helper", "helper-pass-1") === "" && !hm_can_publish() && !hm_can_manage_users(), "changed roles are revalidated on the next request");
hm_clear_auth_session();
check(hm_attempt_login("owner", "owner-pass-2") === "", "owner signs in after a role check");

$reset = hm_account_request(["reset_password" => "1", "user_id" => $helper["id"], "password" => "helper-pass-2", "again" => "helper-pass-2"]);
$helper = hm_find_user_id($helper["id"]);
check(
  $reset === ["message" => "reset_ok", "error" => ""]
  && !str_contains((string) json_encode($reset), "helper-pass-2")
  && is_array($helper)
  && password_verify("helper-pass-2", $helper["hash"])
  && !password_verify("helper-pass-1", $helper["hash"]),
  "super admin can reset another password without returning it"
);
$disabledAccount = hm_account_request(["disable_user" => "1", "user_id" => $helper["id"]]);
check($disabledAccount["message"] === "user_disabled" && hm_find_user_id($helper["id"])["enabled"] === false, "super admin can disable an account");
hm_clear_auth_session();
check(hm_attempt_login("helper", "helper-pass-2") === "bad_login", "disabled account cannot sign in after a reset");
check(hm_attempt_login("owner", "owner-pass-2") === "", "owner signs in after disabling an account");

$keepEnabled = hm_account_request(["disable_user" => "1", "user_id" => $super["id"]]);
$keepRoles = hm_account_request(["save_roles" => "1", "user_id" => $super["id"], "roles" => ["committee_admin"]]);
$keepAccount = hm_account_request(["delete_user" => "1", "user_id" => $super["id"]]);
$ownerNow = hm_find_user_id($super["id"]);
check(
  $keepEnabled["error"] === "last_super"
  && $keepRoles["error"] === "last_super"
  && $keepAccount["error"] === "last_super"
  && is_array($ownerNow)
  && !empty($ownerNow["enabled"])
  && in_array("super_admin", $ownerNow["roles"], true),
  "the last super admin cannot be disabled, demoted, or deleted"
);

$deputyMade = hm_account_request(["create_user" => "1", "username" => "deputy", "password" => "deputy-pass-1", "again" => "deputy-pass-1", "roles" => ["super_admin"]]);
$deputy = hm_find_user("deputy");
$demoted = is_array($deputy)
  ? hm_account_request(["save_roles" => "1", "user_id" => $deputy["id"], "roles" => ["committee_admin"]])
  : ["message" => "", "error" => "missing"];
$deputyNow = hm_find_user("deputy");
check(
  $deputyMade["message"] === "user_created"
  && $demoted["message"] === "roles_ok"
  && is_array($deputyNow)
  && $deputyNow["roles"] === ["committee_admin"],
  "a super admin can be demoted when another remains"
);
if (is_array($deputy)) {
  hm_clear_auth_session();
  check(hm_attempt_login("deputy", "deputy-pass-1") === "", "demoted account signs in");
  hm_set_roles($deputy["id"], ["email_manager"]);
  $lostDesk = hm_account_request(["create_user" => "1", "username" => "later", "password" => "later-pass-1", "again" => "later-pass-1", "roles" => ["committee_admin"]]);
  check($lostDesk["error"] === "forbidden" && hm_find_user("later") === null, "a demoted session loses account management on the next action");
}
hm_clear_auth_session();
check(hm_attempt_login("owner", "owner-pass-2") === "", "owner signs in to remove temporary accounts");
foreach (["helper", "deputy", "sneaky", "later"] as $temporaryName) {
  $temporary = hm_find_user($temporaryName);
  if (!is_array($temporary)) continue;
  try {
    hm_delete_user($temporary["id"]);
  } catch (RuntimeException $error) {
  }
}
$ownerNow = hm_find_user("owner");
if (!is_array($ownerNow)) {
  $super = hm_create_user("owner", "owner-pass-2", ["super_admin"]);
} elseif (empty($ownerNow["enabled"]) || !in_array("super_admin", $ownerNow["roles"], true)) {
  hm_set_enabled($ownerNow["id"], true);
  hm_set_roles($ownerNow["id"], ["super_admin"]);
}
if (!empty(hm_find_user_id($publisher["id"])["enabled"])) hm_set_enabled($publisher["id"], false);
check(
  hm_find_user("helper") === null
  && hm_find_user("deputy") === null
  && !empty(hm_find_user_id($super["id"])["enabled"])
  && in_array("super_admin", hm_find_user_id($super["id"])["roles"], true)
  && empty(hm_find_user_id($publisher["id"])["enabled"]),
  "temporary accounts are removed and the super admin remains"
);

$_POST["csrf"] = "not-the-token";
check(!hm_csrf_ok(), "CSRF token must match");
$_POST["csrf"] = hm_csrf();
check(hm_csrf_ok(), "matching CSRF token is accepted");

check(hm_block_public_path("/data/auth.json"), "router blocks data/auth.json");
check(hm_block_public_path("/private/users.json"), "router blocks private/users.json");
check(!hm_block_public_path("/content.php"), "router allows the public content feed");

$createDir = $base . DIRECTORY_SEPARATOR . "cli-private";
$cliEnv = [];
foreach (["PATH", "SystemRoot", "SYSTEMROOT", "WINDIR", "COMSPEC", "TEMP", "TMP", "PATHEXT"] as $key) {
  $value = getenv($key);
  if (is_string($value) && $value !== "") $cliEnv[$key] = $value;
}
$cliEnv["HM_PRIVATE_DIR"] = $createDir;
$php = escapeshellarg(PHP_BINARY);
$script = escapeshellarg($root . DIRECTORY_SEPARATOR . "bin" . DIRECTORY_SEPARATOR . "create-admin.php");
$proc = proc_open($php . " " . $script . " --password secret", [0 => ["pipe", "r"], 1 => ["pipe", "w"], 2 => ["pipe", "w"]], $pipes, $root, $cliEnv);
$cliCode = 1;
if (is_resource($proc)) {
  fclose($pipes[0]);
  stream_get_contents($pipes[1]);
  stream_get_contents($pipes[2]);
  fclose($pipes[1]);
  fclose($pipes[2]);
  $cliCode = proc_close($proc);
}
check($cliCode !== 0 && !is_file($createDir . DIRECTORY_SEPARATOR . "users.json"), "create-admin rejects a password argument and writes nothing");

$content = shell_exec($php . " " . escapeshellarg($root . DIRECTORY_SEPARATOR . "content.php"));
$contentData = json_decode((string) $content, true);
$contentKeys = is_array($contentData) ? array_keys($contentData) : [];
sort($contentKeys);
check($contentKeys === ["extra", "notices", "pooja", "removed"], "public content feed exposes only pooja and notices");

$server = null;
$log = $base . DIRECTORY_SEPARATOR . "server.log";
$port = 0;
for ($candidate = 8110; $candidate <= 8199; $candidate++) {
  $socket = @fsockopen("127.0.0.1", $candidate, $errno, $errstr, 0.05);
  if (is_resource($socket)) {
    fclose($socket);
    continue;
  }
  $port = $candidate;
  break;
}
$serverEnv = $cliEnv;
$serverEnv["HM_PRIVATE_DIR"] = $private;
$serverEnv["HM_DATA_DIR"] = $data;
$serverEnv["HM_LEGACY_AUTH_PATH"] = $legacy;
$serverEnv["HM_SESSION_PATH"] = $base . DIRECTORY_SEPARATOR . "http-sessions";
mkdir($serverEnv["HM_SESSION_PATH"], 0700, true);
$stopServer = function () use (&$server): void {
  if (!is_resource($server)) return;
  $status = proc_get_status($server);
  if (!empty($status["running"])) proc_terminate($server);
  proc_close($server);
  $server = null;
};
if ($port !== 0) {
  $server = proc_open(
    [PHP_BINARY, "-S", "127.0.0.1:" . $port, "-t", $root, $root . DIRECTORY_SEPARATOR . "router.php"],
    [0 => ["pipe", "r"], 1 => ["file", $log, "w"], 2 => ["file", $log, "a"]],
    $serverPipes,
    $root,
    $serverEnv
  );
}
$ready = false;
if (is_resource($server)) {
  fclose($serverPipes[0]);
  for ($try = 0; $try < 40; $try++) {
    $socket = @fsockopen("127.0.0.1", $port, $errno, $errstr, 0.2);
    if (is_resource($socket)) {
      fclose($socket);
      $ready = true;
      break;
    }
    usleep(100000);
  }
}
check($ready, "local admin server starts");

function hm_http(string $method, string $url, ?string $body, array &$cookies): array {
  $header = "Connection: close\r\n";
  if ($cookies !== []) {
    $pairs = [];
    foreach ($cookies as $name => $value) $pairs[] = $name . "=" . $value;
    $header .= "Cookie: " . implode("; ", $pairs) . "\r\n";
  }
  if ($body !== null) $header .= "Content-Type: application/x-www-form-urlencoded\r\n";
  $context = stream_context_create(["http" => [
    "method" => $method,
    "header" => $header,
    "content" => $body ?? "",
    "ignore_errors" => true,
    "timeout" => 5
  ]]);
  $response = file_get_contents($url, false, $context);
  $responseHeaders = function_exists("http_get_last_response_headers") ? http_get_last_response_headers() : [];
  if (!is_array($responseHeaders)) $responseHeaders = [];
  foreach ($responseHeaders as $line) {
    if (stripos($line, "Set-Cookie:") !== 0) continue;
    $pair = explode(";", trim(substr($line, 11)), 2)[0];
    $eq = strpos($pair, "=");
    if ($eq !== false) $cookies[substr($pair, 0, $eq)] = substr($pair, $eq + 1);
  }
  $status = 0;
  if (isset($responseHeaders[0]) && preg_match("/\s(\d{3})\s/", $responseHeaders[0], $match)) {
    $status = (int) $match[1];
  }
  return [$status, (string) $response];
}

if ($ready) {
  $baseUrl = "http://127.0.0.1:" . $port;
  $cookies = [];
  [$status, $page] = hm_http("GET", $baseUrl . "/admin/index.php", null, $cookies);
  check($status === 200 && str_contains($page, 'data-ta="பயனர் பெயர்"') && str_contains($page, 'data-en="Username"'), "admin login is bilingual");
  check(!str_contains($page, 'name="setup"'), "the public setup form is not offered");
  $hash = (string) hm_find_user("owner")["hash"];
  check(!str_contains($page, $hash), "login page does not reveal a password hash");
  [$dataStatus, $dataBody] = hm_http("GET", $baseUrl . "/data/auth.json", null, $cookies);
  check($dataStatus === 404 && !str_contains($dataBody, $hash), "auth.json is not served");
  $token = "";
  if (preg_match('/name="csrf" value="([a-f0-9]+)"/', $page, $match)) $token = $match[1];
  $legacyHash = hash_file("sha256", $legacy);
  hm_http("POST", $baseUrl . "/admin/index.php", http_build_query(["csrf" => $token, "setup" => "1", "password" => "new-secret", "again" => "new-secret"]), $cookies);
  check(hash_file("sha256", $legacy) === $legacyHash, "web setup does not write the legacy password file");
  $siteFile = $data . DIRECTORY_SEPARATOR . "site.json";
  $siteHash = hash_file("sha256", $siteFile);
  hm_http("POST", $baseUrl . "/admin/index.php", http_build_query([
    "csrf" => $token,
    "save_pooja" => "1",
    "pooja_id" => "jan",
    "date" => "2026-05-01",
    "name" => "அனுமதியில்லை",
    "sponsor" => "இல்லை"
  ]), $cookies);
  check(hash_file("sha256", $siteFile) === $siteHash, "anonymous publishing does not change pooja data");
  $usersFile = hm_users_path(false);
  $usersHash = hash_file("sha256", $usersFile);
  [, $blockedPage] = hm_http("POST", $baseUrl . "/admin/index.php", http_build_query([
    "csrf" => $token,
    "create_user" => "1",
    "username" => "sneaky",
    "password" => "sneaky-pass",
    "again" => "sneaky-pass",
    "roles" => ["super_admin"]
  ]), $cookies);
  check(hash_file("sha256", $usersFile) === $usersHash && str_contains($blockedPage, "முதலில் நுழையுங்கள்"), "anonymous account creation is rejected");

  $loginCookies = [];
  [, $loginPage] = hm_http("GET", $baseUrl . "/admin/index.php", null, $loginCookies);
  preg_match('/name="csrf" value="([a-f0-9]+)"/', $loginPage, $match);
  [, $desk] = hm_http("POST", $baseUrl . "/admin/index.php", http_build_query([
    "csrf" => $match[1] ?? "",
    "username" => "owner",
    "password" => "owner-pass-2",
    "login" => "1"
  ]), $loginCookies);
  preg_match('/name="csrf" value="([a-f0-9]+)"/', $desk, $match);
  hm_http("POST", $baseUrl . "/admin/index.php", http_build_query([
    "csrf" => $match[1] ?? "",
    "save_pooja" => "1",
    "pooja_id" => "feb",
    "date" => "2026-02-27",
    "name" => "மாதாந்த பூஜை",
    "sponsor" => "வலை சோதனை"
  ]), $loginCookies);
  $published = json_decode((string) file_get_contents($siteFile), true);
  check(($published["pooja"]["feb"]["sponsor"] ?? "") === "வலை சோதனை", "signed-in super admin can publish a pooja");

  $pageHidesHashes = function (string $page): bool {
    foreach (hm_load_users()["users"] as $user) {
      if (is_array($user) && isset($user["hash"]) && $user["hash"] !== "" && str_contains($page, (string) $user["hash"])) return false;
    }
    return true;
  };
  [, $ownerDesk] = hm_http("GET", $baseUrl . "/admin/index.php", null, $loginCookies);
  check(str_contains($ownerDesk, 'id="users"') && str_contains($ownerDesk, 'name="create_user"') && $pageHidesHashes($ownerDesk), "super admin desk lists accounts without password hashes");
  check(str_contains($ownerDesk, 'id="desk-sidebar"') && str_contains($ownerDesk, 'id="dashboard"') && str_contains($ownerDesk, 'href="#account"') && str_contains($ownerDesk, 'href="#notices"') && !str_contains($ownerDesk, 'class="desk-nav"'), "the desk uses a sidebar and a dashboard");
  preg_match('/name="csrf" value="([a-f0-9]+)"/', $ownerDesk, $match);
  $usersFile = hm_users_path(false);
  $usersHash = hash_file("sha256", $usersFile);
  [, $badCsrfPage] = hm_http("POST", $baseUrl . "/admin/index.php", http_build_query([
    "csrf" => "not-the-token",
    "create_user" => "1",
    "username" => "clerk",
    "password" => "clerk-pass-1",
    "again" => "clerk-pass-1",
    "roles" => ["committee_admin", "email_manager"]
  ]), $loginCookies);
  check(hash_file("sha256", $usersFile) === $usersHash && str_contains($badCsrfPage, "பக்கம் பழையது"), "account creation requires the CSRF token");
  [, $createdPage] = hm_http("POST", $baseUrl . "/admin/index.php", http_build_query([
    "csrf" => $match[1] ?? "",
    "create_user" => "1",
    "username" => "clerk",
    "password" => "clerk-pass-1",
    "again" => "clerk-pass-1",
    "roles" => ["committee_admin", "email_manager"]
  ]), $loginCookies);
  $clerk = hm_find_user("clerk");
  check(
    is_array($clerk)
    && $clerk["roles"] === ["committee_admin", "email_manager"]
    && str_contains($createdPage, "கணக்கு உருவாக்கப்பட்டது")
    && str_contains($createdPage, ">clerk<")
    && $pageHidesHashes($createdPage)
    && !str_contains($createdPage, "clerk-pass-1"),
    "signed-in super admin can create an account from the desk"
  );
  $clerkCookies = [];
  [, $clerkLogin] = hm_http("GET", $baseUrl . "/admin/index.php", null, $clerkCookies);
  preg_match('/name="csrf" value="([a-f0-9]+)"/', $clerkLogin, $clerkMatch);
  [, $clerkDesk] = hm_http("POST", $baseUrl . "/admin/index.php", http_build_query([
    "csrf" => $clerkMatch[1] ?? "",
    "username" => "clerk",
    "password" => "clerk-pass-1",
    "login" => "1"
  ]), $clerkCookies);
  check(str_contains($clerkDesk, 'name="save_pooja"') && str_contains($clerkDesk, 'href="#notices"') && str_contains($clerkDesk, 'href="#dates"') && !str_contains($clerkDesk, 'name="create_user"') && !str_contains($clerkDesk, 'href="#users"'), "a committee account can publish and cannot manage users");
  preg_match('/name="csrf" value="([a-f0-9]+)"/', $createdPage, $match);
  [, $rolesPage] = hm_http("POST", $baseUrl . "/admin/index.php", http_build_query([
    "csrf" => $match[1] ?? "",
    "save_roles" => "1",
    "user_id" => $clerk["id"],
    "roles" => ["email_manager"]
  ]), $loginCookies);
  [, $clerkAfterRoles] = hm_http("GET", $baseUrl . "/admin/index.php", null, $clerkCookies);
  check(
    hm_find_user_id($clerk["id"])["roles"] === ["email_manager"]
    && str_contains($rolesPage, "பொறுப்புகள் சேமிக்கப்பட்டன")
    && !str_contains($clerkAfterRoles, 'name="save_pooja"')
    && !str_contains($clerkAfterRoles, 'name="create_user"')
    && !str_contains($clerkAfterRoles, 'href="#notices"')
    && !str_contains($clerkAfterRoles, 'href="#users"')
    && str_contains($clerkAfterRoles, 'id="dashboard"')
    && str_contains($clerkAfterRoles, 'href="#account"'),
    "a role change is enforced on the account's open session"
  );
  preg_match('/name="csrf" value="([a-f0-9]+)"/', $clerkAfterRoles, $clerkMatch);
  $usersHash = hash_file("sha256", $usersFile);
  [, $clerkForbidden] = hm_http("POST", $baseUrl . "/admin/index.php", http_build_query([
    "csrf" => $clerkMatch[1] ?? "",
    "create_user" => "1",
    "username" => "sneaky",
    "password" => "sneaky-pass",
    "again" => "sneaky-pass",
    "roles" => ["super_admin"]
  ]), $clerkCookies);
  check(hash_file("sha256", $usersFile) === $usersHash && str_contains($clerkForbidden, "அனுமதி இல்லை"), "a signed-in account without super admin cannot create a user");
  preg_match('/name="csrf" value="([a-f0-9]+)"/', $rolesPage, $match);
  [, $resetPage] = hm_http("POST", $baseUrl . "/admin/index.php", http_build_query([
    "csrf" => $match[1] ?? "",
    "reset_password" => "1",
    "user_id" => $clerk["id"],
    "password" => "clerk-pass-2",
    "again" => "clerk-pass-2"
  ]), $loginCookies);
  $clerk = hm_find_user_id($clerk["id"]);
  check(
    str_contains($resetPage, "புதிய கடவுச்சொல் அமைக்கப்பட்டது")
    && !str_contains($resetPage, "clerk-pass-2")
    && $pageHidesHashes($resetPage)
    && is_array($clerk)
    && password_verify("clerk-pass-2", $clerk["hash"])
    && !password_verify("clerk-pass-1", $clerk["hash"]),
    "signed-in super admin can reset a password without revealing it"
  );
  preg_match('/name="csrf" value="([a-f0-9]+)"/', $resetPage, $match);
  hm_http("POST", $baseUrl . "/admin/index.php", http_build_query([
    "csrf" => $match[1] ?? "",
    "disable_user" => "1",
    "user_id" => $clerk["id"]
  ]), $loginCookies);
  [, $clerkAfterDisable] = hm_http("GET", $baseUrl . "/admin/index.php", null, $clerkCookies);
  check(
    hm_find_user_id($clerk["id"])["enabled"] === false
    && str_contains($clerkAfterDisable, 'name="username"')
    && !str_contains($clerkAfterDisable, 'name="save_pooja"'),
    "disabling an account closes its open session"
  );
  preg_match('/name="csrf" value="([a-f0-9]+)"/', $ownerDesk, $match);
  [, $lastDisable] = hm_http("POST", $baseUrl . "/admin/index.php", http_build_query([
    "csrf" => $match[1] ?? "",
    "disable_user" => "1",
    "user_id" => $super["id"]
  ]), $loginCookies);
  [, $lastRoles] = hm_http("POST", $baseUrl . "/admin/index.php", http_build_query([
    "csrf" => $match[1] ?? "",
    "save_roles" => "1",
    "user_id" => $super["id"],
    "roles" => ["committee_admin"]
  ]), $loginCookies);
  [, $lastDelete] = hm_http("POST", $baseUrl . "/admin/index.php", http_build_query([
    "csrf" => $match[1] ?? "",
    "delete_user" => "1",
    "user_id" => $super["id"]
  ]), $loginCookies);
  $ownerAfterGuard = hm_find_user_id($super["id"]);
  check(
    str_contains($lastDisable, "கடைசி முதன்மை நிர்வாகியை")
    && str_contains($lastRoles, "கடைசி முதன்மை நிர்வாகியை")
    && str_contains($lastDelete, "கடைசி முதன்மை நிர்வாகியை")
    && is_array($ownerAfterGuard)
    && !empty($ownerAfterGuard["enabled"])
    && in_array("super_admin", $ownerAfterGuard["roles"], true),
    "the desk refuses to disable, demote, or delete the last super admin"
  );

  hm_set_enabled($publisher["id"], true);
  $second = hm_create_user("secondowner", "second-pass-1", ["super_admin"]);
  hm_set_enabled($super["id"], false);
  [, $afterDisable] = hm_http("GET", $baseUrl . "/admin/index.php", null, $loginCookies);
  check(str_contains($afterDisable, 'name="username"') && !str_contains($afterDisable, 'name="save_pooja"'), "disabled super admin session loses the desk");
  check(!empty($second["id"]), "a second super admin can exist before a disable");
  hm_set_enabled($super["id"], true);
}

$stopServer();

check($authBefore === (is_file($realAuth) ? hash_file("sha256", $realAuth) : null), "existing data/auth.json is unchanged");
check($siteBefore === (is_file($realSite) ? hash_file("sha256", $realSite) : null), "existing data/site.json is unchanged");

if ($failures === 0) {
  fwrite(STDOUT, "All auth tests passed." . PHP_EOL);
  exit(0);
}
fwrite(STDERR, $failures . " auth test(s) failed." . PHP_EOL);
exit(1);
