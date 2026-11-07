<?php
declare(strict_types=1);
// Run via scheduler/cron only, never from a browser.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/includes/recovery.php';
if (!hm_recovery_ready()) exit(0);
$base = (string)getenv('HM_RECOVERY_BASE_URL');
if (!str_starts_with($base, 'https://')) exit(1);
foreach (hm_recovery_requests() as $request) {
  if (($request['status'] ?? '') === 'pending' && time() - (int)($request['created'] ?? 0) >= 1800) {
    try { hm_recovery_issue((string)$request['id'], $base); } catch (Throwable $ignored) {}
  }
}
