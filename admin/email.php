<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/publish.php';
hm_boot();
header('X-Robots-Tag: noindex');
header('Cache-Control: no-store');
function eh($s): string { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
$user = hm_current_user();
if (!$user) { header('Location: ./'); exit; }
if (!hm_has_role($user, 'email_manager')) { http_response_code(403); exit('Email Manager permission required.'); }
$templates = [
 'pooja'=>['Monthly Poojai','Monthly Poojai – Hindu Mamanram',"Vanakkam,\n\nWe warmly invite you and your family to our upcoming monthly poojai.\n\nDate: [Date]\nTime: [Time]\nLocation: [Venue]\nUpayakarar: [Sponsor]\n\nRegards,\nHindu Mamanram"],
 'festival'=>['Festival / Event','Festival Announcement – Hindu Mamanram',"Vanakkam,\n\nYou are warmly invited to our upcoming festival.\n\nFestival: [Name]\nDate: [Date]\nTime: [Time]\nVenue: [Venue]\n\nRegards,\nHindu Mamanram"],
 'membership'=>['Membership','Membership Information – Hindu Mamanram',"Vanakkam,\n\n[Membership information]\n\nRegards,\nHindu Mamanram"],
 'general'=>['General Announcement','Announcement – Hindu Mamanram',"Vanakkam,\n\n[Your message]\n\nRegards,\nHindu Mamanram"]
];
function mail_settings_ready(): bool {
 return getenv('HM_TEMPLE_EMAIL_ENABLED') === '1'
  && getenv('HM_SMTP_HOST') !== false && getenv('HM_SMTP_USER') !== false
  && getenv('HM_SMTP_PASSWORD') !== false
  && strtolower((string)getenv('HM_SMTP_FROM')) === 'hindu@mamanram.no';
}
function smtp_send(array $recipients, string $subject, string $body): void {
 $host=(string)getenv('HM_SMTP_HOST'); $port=(int)(getenv('HM_SMTP_PORT') ?: 587);
 if (!preg_match('/^[a-zA-Z0-9.-]+$/D',$host) || $port !== 587) throw new RuntimeException('SMTP configuration invalid (STARTTLS port 587 required).');
 $user=(string)getenv('HM_SMTP_USER'); $pass=(string)getenv('HM_SMTP_PASSWORD'); $from=(string)getenv('HM_SMTP_FROM');
 $socket=@stream_socket_client('tcp://'.$host.':'.$port,$errno,$errstr,15,STREAM_CLIENT_CONNECT);
 if (!$socket) throw new RuntimeException('Cannot connect to SMTP server.');
 stream_set_timeout($socket,15);
 $read=function(array $expected) use ($socket): string {
  $reply='';
  do {
   $line=fgets($socket,2048);
   if ($line===false) throw new RuntimeException('SMTP connection failed.');
   $reply.=$line;
  } while (isset($line[3]) && $line[3]==='-');
  $code=(int)substr($line,0,3);
  if (!in_array($code,$expected,true)) throw new RuntimeException('SMTP rejected the request ('.$code.').');
  return $reply;
 };
 $cmd=function(string $command,array $expected) use ($socket,$read): string {
  if (fwrite($socket,$command."\r\n")===false) throw new RuntimeException('SMTP write failed.');
  return $read($expected);
 };
 try {
  $read([220]);
  $cmd('EHLO mamanram.no',[250]);
  $cmd('STARTTLS',[220]);
  if (!stream_socket_enable_crypto($socket,true,STREAM_CRYPTO_METHOD_TLS_CLIENT)) throw new RuntimeException('TLS negotiation failed.');
  $cmd('EHLO mamanram.no',[250]);
  $cmd('AUTH LOGIN',[334]);
  $cmd(base64_encode($user),[334]);
  $cmd(base64_encode($pass),[235]);
  $cmd('MAIL FROM:<'.$from.'>',[250]);
  $cmd('RCPT TO:<hindu@mamanram.no>',[250,251]);
  foreach($recipients as $address) $cmd('RCPT TO:<'.$address.'>',[250,251]);
  $cmd('DATA',[354]);
  $safeSubject='=?UTF-8?B?'.base64_encode($subject).'?=';
  $headers="From: Hindu Mamanram <".$from.">\r\nTo: hindu@mamanram.no\r\nSubject: ".$safeSubject."\r\nMIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n";
  $payload=$headers."\r\n".chunk_split(base64_encode($body),76,"\r\n");
  $payload=preg_replace('/^\./m','..',$payload);
  if (fwrite($socket,$payload."\r\n.\r\n")===false) throw new RuntimeException('SMTP message failed.');
  $read([250]);
  $cmd('QUIT',[221]);
 } finally { fclose($socket); }
}
$type=(string)($_POST['type']??'pooja'); if (!isset($templates[$type])) $type='pooja';
$subject=(string)($_POST['subject']??$templates[$type][1]);
$body=(string)($_POST['body']??$templates[$type][2]);
$to=(string)($_POST['bcc']??'');
$error='';$success='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
 if (!hm_csrf_ok()) $error='Session expired. Refresh the page.';
 elseif (!isset($_POST['send_email'])) $error='Unknown action.';
 elseif (!mail_settings_ready()) $error='Email sending is disabled until the hosting SMTP settings are configured.';
 elseif (strlen($subject)>200 || trim($subject)==='' || preg_match('/[\r\n]/',$subject) || trim($body)==='' || strlen($body)>20000) $error='Enter a valid subject and message.';
 else {
  $addresses=preg_split('/[,;\s]+/',trim($to),-1,PREG_SPLIT_NO_EMPTY);
  $addresses=array_values(array_unique(array_map('strtolower',$addresses)));
  if (!$addresses || count($addresses)>20 || count(array_filter($addresses,fn($a)=>filter_var($a,FILTER_VALIDATE_EMAIL)!==false))!==count($addresses)) $error='Enter 1–20 valid BCC recipient addresses.';
  elseif (!isset($_POST['confirm_recipients'])) $error='Please confirm the BCC recipient list before sending.';
  else {
   $bucket='temple-email:'.(string)($user['id']??'legacy');
   if (hm_attempt_count($bucket)>=5) $error='Sending limit reached. Try again later.';
   else {
    hm_attempt_mark($bucket,false);
    try { smtp_send($addresses,$subject,$body); $success='Email accepted by the mail server for the temple address and '.count($addresses).' BCC recipient(s).'; $to=''; }
    catch(Throwable $e) { error_log('Temple email SMTP error: '.$e->getMessage()); $error='Email could not be sent. Check server mail configuration or logs.'; }
   }
  }
 }
}
$ready=mail_settings_ready();
?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex"><title>Temple Email | Hindu Mamanram</title>
<link rel="stylesheet" href="../css/admin.css">
<style>
.email-panel{max-width:1100px;margin:20px auto}.email-panel .panel-body{padding:28px}
.email-panel .muted{color:#70655c}.email-panel .alert{padding:12px 16px;border-radius:10px;background:#fff0df;margin:14px 0}.email-panel .alert.ok{background:#e8f5e9}
.email-panel label{display:block;font-weight:600;margin:18px 0 7px}
.email-panel input:not([type=checkbox]),.email-panel textarea,.email-panel select{display:block;width:100%;padding:12px;border:1px solid #bfb4a9;border-radius:8px;font:inherit;background:white}
.email-panel textarea{min-height:220px;resize:vertical}.email-panel textarea#bcc{min-height:75px}
.email-panel button{background:#6e2c46;color:white;border:0;border-radius:8px;padding:12px 22px;font:inherit;cursor:pointer}
.email-panel button:disabled{opacity:.55;cursor:not-allowed}.email-panel .bar{display:flex;justify-content:space-between;gap:12px;align-items:center;flex-wrap:wrap}
.email-panel .check{display:flex;align-items:flex-start;gap:10px}.email-panel .check input{width:auto;margin-top:5px}
.email-panel .email-heading{margin:0 0 8px}
@media(max-width:800px){.email-panel .panel-body{padding:20px 16px}}
</style></head>
<body><div class="shell is-desk"><div class="desk-app">
<button type="button" class="sidebar-backdrop" data-close-menu aria-label="Close menu"></button>
<aside id="desk-sidebar" class="sidebar">
<a class="brand" href="../index.html"><img src="../assets/hm-seal.jpg" alt=""><span>Hindu Mamanram</span></a>
<nav class="side-nav" aria-label="Admin sections">
<p class="side-label">Overview</p><ul><li><a class="side-link" href="./#dashboard">Dashboard</a></li></ul>
<?php if (hm_can_publish()): ?>
<p class="side-label">Publishing</p><ul><li><a class="side-link" href="./#notices">Notices</a></li><li><a class="side-link" href="./#dates">Pooja Management</a></li></ul>
<?php endif; ?>
<p class="side-label">Communication</p><ul><li><a class="side-link" href="email.php" aria-current="page">Temple Email</a></li></ul>
<p class="side-label">Account</p><ul>
<?php if (hm_can_manage_users($user)): ?><li><a class="side-link" href="./#users">Users</a></li><?php endif; ?>
<li><a class="side-link" href="./#account">Settings</a></li>
</ul></nav>
<div class="side-user"><strong class="side-name"><?=eh($user['username']??'Administrator')?></strong><span class="badge"><?=hm_has_role($user,'super_admin')?'Super Admin':'Email Manager'?></span></div>
</aside>
<div class="desk-main">
<div class="desk-top"><button type="button" class="menu-btn" data-menu aria-expanded="false" aria-controls="desk-sidebar">☰ Menu</button><a class="btn btn-ghost" href="./#dashboard">Back to Dashboard</a></div>
<main class="panel email-panel"><div class="panel-body">
<p class="kicker">COMMUNICATION</p><h1 class="email-heading">Temple Email</h1>
<p class="muted">Compose a message using the temple email address. Only Super Admins and Email Managers can use this page.</p>
<?php if(!$ready):?><p class="alert" role="status">Development mode: Sending is disabled until secure SMTP configuration is added on the hosting server.</p><?php endif;?>
<?php if($error):?><p class="alert" role="alert"><?=eh($error)?></p><?php endif;?>
<?php if($success):?><p class="alert ok" role="status"><?=eh($success)?></p><?php endif;?>
<form method="post" id="composer"><input type="hidden" name="csrf" value="<?=eh(hm_csrf())?>">
<label for="type">Email type</label><select id="type" name="type"><?php foreach($templates as $key=>$tpl):?><option value="<?=eh($key)?>" <?=$key===$type?'selected':''?>><?=eh($tpl[0])?></option><?php endforeach;?></select>
<label>From</label><input value="hindu@mamanram.no" readonly aria-label="From">
<label for="to">To</label><input id="to" type="email" value="hindu@mamanram.no" readonly aria-label="To address">
<label for="bcc">BCC (email addresses separated by commas, maximum 20)</label><textarea id="bcc" name="bcc" rows="2" style="min-height:75px" placeholder="person@example.com" required><?=eh($to)?></textarea>
<p class="muted">BCC recipients are delivered privately and are not listed in the message headers. The temple address receives a copy.</p>
<label for="subject">Subject</label><input id="subject" name="subject" maxlength="200" value="<?=eh($subject)?>" required>
<label for="body">Message</label><textarea id="body" name="body" maxlength="20000" required><?=eh($body)?></textarea>
<label class="check"><input type="checkbox" name="confirm_recipients" value="1" required><span>I have reviewed the BCC recipients and the message, and I am authorized to contact them.</span></label>
<div class="bar"><button type="button" id="preview">Preview message</button><button type="submit" name="send_email" value="1" <?=$ready?'':'disabled'?>>Send Email</button></div>
</form>
</div></main></div></div></div>
<script>
const templates=<?=json_encode(array_map(fn($v)=>['subject'=>$v[1],'body'=>$v[2]],$templates),JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT)?>;
document.getElementById('type').addEventListener('change',e=>{let t=templates[e.target.value];document.getElementById('subject').value=t.subject;document.getElementById('body').value=t.body;});
document.getElementById('preview').addEventListener('click',()=>alert('Subject: '+document.getElementById('subject').value+'\n\n'+document.getElementById('body').value));
document.getElementById('composer').addEventListener('submit',e=>{if(!confirm('Send this email to the temple address and the reviewed BCC recipients?'))e.preventDefault();});
</script>
<script>
document.documentElement.classList.add('js-menu');
const menuButton=document.querySelector('[data-menu]');
const closeMenu=()=>{document.documentElement.classList.remove('is-menu');menuButton?.setAttribute('aria-expanded','false')};
menuButton?.addEventListener('click',()=>{const open=menuButton.getAttribute('aria-expanded')!=='true';document.documentElement.classList.toggle('is-menu',open);menuButton.setAttribute('aria-expanded',String(open))});
document.querySelectorAll('[data-close-menu],#desk-sidebar a').forEach(el=>el.addEventListener('click',closeMenu));
document.addEventListener('keydown',e=>{if(e.key==='Escape')closeMenu()});
</script></body></html>