<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';

/* Password recovery is disabled until mail transport and verified account emails are configured. */
function hm_recovery_ready(): bool {
  return getenv('HM_RECOVERY_ENABLED') === '1' && filter_var(getenv('HM_RECOVERY_FROM'), FILTER_VALIDATE_EMAIL) !== false;
}
function hm_recovery_store(callable $change): array {
  $dir = hm_private_dir(true);
  $lock = fopen($dir . '/recovery.lock', 'c');
  if (!$lock || !flock($lock, LOCK_EX)) throw new RuntimeException('recovery_lock');
  try {
    $path = $dir . '/recovery.json';
    $data = ['requests' => []];
    if (is_file($path)) {
      $decoded = json_decode((string) file_get_contents($path), true);
      if (is_array($decoded) && isset($decoded['requests']) && is_array($decoded['requests'])) $data = $decoded;
    }
    $next = $change($data);
    if (!is_array($next) || !isset($next['requests'])) throw new RuntimeException('recovery_data');
    if (!hm_atomic_write($path, json_encode($next, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n")) throw new RuntimeException('recovery_write');
    return $next;
  } finally {
    flock($lock, LOCK_UN);
    fclose($lock);
  }
}
function hm_recovery_requests(): array {
  $dir = hm_private_dir(false);
  $path = $dir . '/recovery.json';
  $data = is_file($path) ? json_decode((string) file_get_contents($path), true) : [];
  return is_array($data['requests'] ?? null) ? $data['requests'] : [];
}
function hm_recovery_mail(string $to, string $subject, string $body): bool {
  if (!hm_recovery_ready() || !filter_var($to, FILTER_VALIDATE_EMAIL)) return false;
  $from = (string) getenv('HM_RECOVERY_FROM');
  return mail($to, $subject, $body, 'From: ' . $from . "\r\nContent-Type: text/plain; charset=UTF-8");
}
function hm_recovery_request(string $email): void {
  if (!hm_recovery_ready() || !filter_var($email, FILTER_VALIDATE_EMAIL)) return;
  $email = strtolower(trim($email));
  $users = hm_load_users()['users'];
  $found = null;
  foreach ($users as $u) {
    if (!empty($u['enabled']) && !empty($u['email_verified']) && strtolower((string)($u['email'] ?? '')) === $email) { $found = $u; break; }
  }
  if (!$found) return;
  $now = time();
  hm_recovery_store(function($data) use ($email, $found, $now) {
    foreach ($data['requests'] as $r) {
      if (($r['user_id'] ?? '') === $found['id'] && in_array($r['status'] ?? '', ['pending','sent'], true) && ($now - (int)($r['created'] ?? 0)) < 3600) return $data;
    }
    $data['requests'][] = ['id' => bin2hex(random_bytes(12)), 'user_id' => $found['id'], 'email' => $email, 'created' => $now, 'status' => 'pending', 'token_hash' => '', 'expires' => 0];
    $data['requests'] = array_slice($data['requests'], -300);
    return $data;
  });
  $admin = getenv('HM_RECOVERY_ADMIN_EMAIL');
  if (filter_var($admin, FILTER_VALIDATE_EMAIL)) hm_recovery_mail($admin, 'Temple admin password reset requested', 'A verified account has requested a password reset. Sign in to the temple administration dashboard to review it.');
}
function hm_recovery_issue(string $id, string $baseUrl): bool {
  if (!hm_recovery_ready() || !filter_var($baseUrl, FILTER_VALIDATE_URL) || !str_starts_with($baseUrl, 'https://')) return false;
  $token = bin2hex(random_bytes(32));
  $sent = false;
  hm_recovery_store(function($data) use ($id, $token, $baseUrl, &$sent) {
    foreach ($data['requests'] as &$r) {
      if (($r['id'] ?? '') !== $id || ($r['status'] ?? '') !== 'pending') continue;
      $url = rtrim($baseUrl, '/') . '/admin/reset-password.php?token=' . $token;
      if (!hm_recovery_mail($r['email'], 'Hindu Mamanram password reset', "Use this link to set a new password (valid for 30 minutes):\n" . $url . "\nIf you did not request this, ignore this email.")) break;
      $r['status'] = 'sent'; $r['token_hash'] = hash('sha256', $token); $r['expires'] = time() + 1800; $sent = true; break;
    }
    unset($r);
    return $data;
  });
  return $sent;
}
function hm_recovery_reject(string $id): void {
  hm_recovery_store(function($data) use ($id) {
    foreach ($data['requests'] as &$r) if (($r['id'] ?? '') === $id && ($r['status'] ?? '') === 'pending') $r['status'] = 'rejected';
    unset($r); return $data;
  });
}
function hm_recovery_reset(string $token, string $password): bool {
  if (strlen($token) !== 64 || !ctype_xdigit($token) || strlen($password) < 12) return false;
  $hash = hash('sha256', $token); $ok = false;
  hm_recovery_store(function($data) use ($hash, $password, &$ok) {
    foreach ($data['requests'] as &$r) {
      if (($r['status'] ?? '') !== 'sent' || ($r['expires'] ?? 0) < time() || !hash_equals((string)($r['token_hash'] ?? ''), $hash)) continue;
      $u = hm_find_user_id((string)$r['user_id']);
      if (!$u || empty($u['enabled']) || empty($u['email_verified']) || strtolower((string)($u['email'] ?? '')) !== $r['email']) break;
      if (hm_set_password((string)$u['id'], $password)) { $r['status'] = 'used'; $r['token_hash'] = ''; $ok = true; }
      break;
    }
    unset($r); return $data;
  });
  return $ok;
}
