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
  && filter_var(getenv('HM_SMTP_FROM'), FILTER_VALIDATE_EMAIL) !== false;
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
  foreach($recipients as $address) $cmd('RCPT TO:<'.$address.'>',[250,251]);
  $cmd('DATA',[354]);
  $safeSubject='=?UTF-8?B?'.base64_encode($subject).'?=';
  $headers="From: Hindu Mamanram <".$from.">\r\nTo: undisclosed-recipients:;\r\nSubject: ".$safeSubject."\r\nMIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n";
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
$to=(string)($_POST['recipients']??'');
$error='';$success='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
 if (!hm_csrf_ok()) $error='Session expired. Refresh the page.';
 elseif (!isset($_POST['send_email'])) $error='Unknown action.';
 elseif (!mail_settings_ready()) $error='Email sending is disabled until the hosting SMTP settings are configured.';
 elseif (strlen($subject)>200 || trim($subject)==='' || preg_match('/[\r\n]/',$subject) || trim($body)==='' || strlen($body)>20000) $error='Enter a valid subject and message.';
 else {
  $addresses=preg_split('/[,;\s]+/',trim($to),-1,PREG_SPLIT_NO_EMPTY);
  $addresses=array_values(array_unique(array_map('strtolower',$addresses)));
  if (!$addresses || count($addresses)>20 || count(array_filter($addresses,fn($a)=>filter_var($a,FILTER_VALIDATE_EMAIL)!==false))!==count($addresses)) $error='Enter 1–20 valid recipient addresses.';
  elseif (!isset($_POST['confirm_recipients'])) $error='Please confirm the recipient list before sending.';
  else {
   $bucket='temple-email:'.(string)($user['id']??'legacy');
   if (hm_attempt_count($bucket)>=5) $error='Sending limit reached. Try again later.';
   else {
    hm_attempt_mark($bucket,false);
    try { smtp_send($addresses,$subject,$body); $success='Email accepted by the mail server for '.count($addresses).' recipient(s).'; $to=''; }
    catch(Throwable $e) { error_log('Temple email SMTP error: '.$e->getMessage()); $error='Email could not be sent. Check server mail configuration or logs.'; }
   }
  }
 }
}
$ready=mail_settings_ready();
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex"><title>Temple Email | Hindu Mamanram</title>
<style>
:root{font-family:system-ui,-apple-system,sans-serif;color:#332c26;background:#f7f3ed}*{box-sizing:border-box}body{margin:0}main{max-width:850px;margin:35px auto;padding:0 18px}.card{background:white;border:1px solid #e5d9ca;border-radius:14px;padding:25px;box-shadow:0 5px 20px #372d2110}h1{margin:8px 0}p{line-height:1.5}.muted{color:#70655c}label{display:block;font-weight:600;margin:18px 0 7px}input,textarea,select{width:100%;padding:12px;border:1px solid #bfb4a9;border-radius:8px;font:inherit}textarea{min-height:280px;resize:vertical}button{background:#704b31;color:white;border:0;border-radius:8px;padding:12px 22px;font:inherit;cursor:pointer}button:disabled{opacity:.55;cursor:not-allowed}.alert{padding:12px;border-radius:8px;background:#fff0df}.ok{background:#e8f5e9}.bar{display:flex;justify-content:space-between;gap:12px;align-items:center;flex-wrap:wrap}.check{display:flex;align-items:flex-start;gap:10px}.check input{width:auto;margin-top:5px}a{color:#704b31}
</style></head><body><main><div class="bar"><a href="./">← Back to Admin Dashboard</a><span>Signed in: <?=eh($user['username']?:'Administrator')?></span></div>
<div class="card"><h1>Temple Email</h1><p class="muted">Compose a message using the temple email address. Only Super Admins and Email Managers can use this page.</p>
<?php if(!$ready):?><p class="alert" role="status">Development mode: Sending is disabled until secure SMTP configuration is added on the hosting server.</p><?php endif;?>
<?php if($error):?><p class="alert" role="alert"><?=eh($error)?></p><?php endif;?>
<?php if($success):?><p class="alert ok" role="status"><?=eh($success)?></p><?php endif;?>
<form method="post" id="composer"><input type="hidden" name="csrf" value="<?=eh(hm_csrf())?>">
<label for="type">Email type</label><select id="type" name="type"><?php foreach($templates as $key=>$tpl):?><option value="<?=eh($key)?>" <?=$key===$type?'selected':''?>><?=eh($tpl[0])?></option><?php endforeach;?></select>
<label>From</label><input value="hindu@mamanram.no" readonly aria-label="From">
<label for="recipients">Recipients (email addresses separated by commas, maximum 20)</label><textarea id="recipients" name="recipients" rows="2" style="min-height:75px" placeholder="person@example.com" required><?=eh($to)?></textarea>
<p class="muted">Recipient addresses are sent privately (BCC-style). Each recipient sees only the temple sender.</p>
<label for="subject">Subject</label><input id="subject" name="subject" maxlength="200" value="<?=eh($subject)?>" required>
<label for="body">Message</label><textarea id="body" name="body" maxlength="20000" required><?=eh($body)?></textarea>
<label class="check"><input type="checkbox" name="confirm_recipients" value="1" required><span>I have reviewed the recipients and the message, and I am authorized to contact them.</span></label>
<div class="bar"><button type="button" id="preview">Preview message</button><button type="submit" name="send_email" value="1" <?=$ready?'':'disabled'?>>Send Email</button></div>
</form></div></main>
<script>
const templates=<?=json_encode(array_map(fn($v)=>['subject'=>$v[1],'body'=>$v[2]],$templates),JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT)?>;
document.getElementById('type').addEventListener('change',e=>{let t=templates[e.target.value];document.getElementById('subject').value=t.subject;document.getElementById('body').value=t.body;});
document.getElementById('preview').addEventListener('click',()=>alert('Subject: '+document.getElementById('subject').value+'\n\n'+document.getElementById('body').value));
document.getElementById('composer').addEventListener('submit',e=>{if(!confirm('Send this email to the reviewed recipients?'))e.preventDefault();});
</script></body></html>