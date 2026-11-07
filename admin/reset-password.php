<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/recovery.php';
hm_boot();
header('X-Robots-Tag: noindex, nofollow');
header('Referrer-Policy: no-referrer');
header('Cache-Control: no-store');
$token = (string)($_GET['token'] ?? $_POST['token'] ?? '');
$ok = false; $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!hm_csrf_ok()) $error = 'The form expired. Please refresh.';
  elseif (strlen((string)($_POST['password'] ?? '')) < 12) $error = 'Use at least 12 characters.';
  elseif ((string)($_POST['password'] ?? '') !== (string)($_POST['again'] ?? '')) $error = 'Passwords do not match.';
  else {
    try { $ok = hm_recovery_reset($token, (string)$_POST['password']); } catch (Throwable $e) {}
    if (!$ok) $error = 'The link is invalid, expired, or already used.';
  }
}
function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Set new password · Hindu Mamanram</title><link rel="stylesheet" href="../css/admin-login.css"></head>
<body style="margin:0;background:#fff8ee;font-family:system-ui,sans-serif;color:#542039">
<main style="max-width:470px;margin:10vh auto;padding:32px;background:white;border:1px solid #e6d1bc;border-radius:18px;box-shadow:0 12px 40px #50203413">
<h1>Set a new password</h1>
<?php if ($ok): ?><p role="status">Your password has been updated. You can now sign in.</p><p><a href="./">Go to login</a></p>
<?php else: ?>
<?php if ($error): ?><p role="alert" style="color:#9a253f"><?= e($error) ?></p><?php endif; ?>
<form method="post" style="display:grid;gap:16px">
<input type="hidden" name="csrf" value="<?= e(hm_csrf()) ?>"><input type="hidden" name="token" value="<?= e($token) ?>">
<label>New password (12+ characters)<input type="password" name="password" autocomplete="new-password" minlength="12" required style="display:block;width:100%;padding:12px;box-sizing:border-box"></label>
<label>Confirm new password<input type="password" name="again" autocomplete="new-password" minlength="12" required style="display:block;width:100%;padding:12px;box-sizing:border-box"></label>
<button type="submit" style="padding:14px;background:#702b48;color:white;border:0;border-radius:9px">Update password</button></form>
<?php endif; ?>
</main></body></html>