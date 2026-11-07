<?php
require dirname(__DIR__) . "/includes/publish.php";
hm_boot();

function h($value) {
  return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
}

function icon(string $name): void {
  $paths = [
    "home" => '<path d="M4 11.2 12 4l8 7.2"/><path d="M7 10.8V20h10v-9.2"/>',
    "notice" => '<path d="M7 4.5h10v15H7z"/><path d="M9.5 8.5h5M9.5 12h5M9.5 15.5h3"/>',
    "pooja" => '<circle cx="12" cy="12" r="7"/><path d="M12 8.5V12l2.5 2"/>',
    "users" => '<circle cx="9" cy="8.5" r="2.6"/><path d="M4.5 19c.7-2.8 2.6-4.2 4.5-4.2s3.8 1.4 4.5 4.2"/><circle cx="16.2" cy="9" r="2"/><path d="M15 14.8c1.5.2 2.8 1.1 3.6 4.2"/>',
    "settings" => '<circle cx="12" cy="12" r="3"/><path d="M12 3.8v2.2M12 18v2.2M3.8 12h2.2M18 12h2.2M6.2 6.2l1.6 1.6M16.2 16.2l1.6 1.6M17.8 6.2l-1.6 1.6M7.8 16.2l-1.6 1.6"/>'
  ];
  echo '<svg class="ico" viewBox="0 0 24 24" aria-hidden="true" focusable="false">' . ($paths[$name] ?? "") . '</svg>';
}

function csrf() {
  return hm_csrf();
}

function logged_in() {
  return hm_logged_in();
}

function role_choices(array $selected): void {
  foreach (ROLE_UI as $key => $label) {
    $checked = in_array($key, $selected, true) ? " checked" : "";
    echo '<label class="check"><input type="checkbox" name="roles[]" value="' . h($key) . '"' . $checked . '><span data-ta="' . h($label[0]) . '" data-en="' . h($label[1]) . '">' . h($label[0]) . '</span></label>';
  }
}

const UI = [
  "csrf" => ["பக்கம் பழையது. மீண்டும் முயற்சிக்கவும்.", "This page is out of date. Try again."],
  "hash_exists" => ["கடவுச்சொல் ஏற்கெனவே உள்ளது.", "A password already exists."],
  "too_short" => ["கடவுச்சொல் குறைந்தது 8 எழுத்துகள் வேண்டும்.", "The password must be at least 8 characters."],
  "mismatch" => ["இரண்டு கடவுச்சொற்களும் ஒன்றாக இல்லை.", "The two passwords do not match."],
  "hash_save" => ["கடவுச்சொல்லைச் சேமிக்க முடியவில்லை.", "The password could not be saved."],
  "setup_ok" => ["கடவுச்சொல் சேமிக்கப்பட்டது. இதைக் குழுவினருடன் பகிருங்கள்.", "The password is saved. Share it with the committee."],
  "locked" => ["பல முறை தவறானது. சிறிது நேரம் கழித்து முயற்சிக்கவும்.", "Too many attempts. Try again after a short while."],
  "bad_login" => ["கடவுச்சொல் தவறு.", "The password is incorrect."],
  "need_login" => ["முதலில் நுழையுங்கள்.", "Sign in first."],
  "bad_current" => ["இப்போதைய கடவுச்சொல் தவறு.", "The current password is incorrect."],
  "bad_new" => ["புதிய கடவுச்சொல் 8 எழுத்துகள், இரண்டும் ஒன்றாக இருக்க வேண்டும்.", "The new password must be 8 characters, and both entries must match."],
  "password_ok" => ["கடவுச்சொல் மாற்றப்பட்டது.", "The password has been changed."],
  "need_notice" => ["தலைப்பும் செய்தியும் வேண்டும்.", "A title and a message are required."],
  "notice_limit" => ["அறிவிப்புகள் 40 வரை.", "The notice limit is 40."],
  "notice_ok" => ["அறிவிப்பு வெளியிடப்பட்டது.", "The notice is published."],
  "store_fail" => ["சேமிக்க முடியவில்லை.", "This could not be saved."],
  "notice_deleted" => ["அறிவிப்பு நீக்கப்பட்டது.", "The notice was removed."],
  "need_date" => ["நாள் மற்றும் பூஜைப் பெயர் வேண்டும்.", "A date and a pooja name are required."],
  "pooja_ok" => ["பூஜை நாள் சேமிக்கப்பட்டது.", "The pooja date is saved."],
  "pooja_hidden" => ["பூஜை நாள் மறைக்கப்பட்டது.", "The pooja date is hidden."],
  "pooja_shown" => ["பூஜை நாள் மீண்டும் காட்டப்படும்.", "The pooja date is visible again."],
  "need_new" => ["புதிய பூஜைக்கு நாள் மற்றும் பெயர் வேண்டும்.", "A new pooja needs a date and a name."],
  "extra_limit" => ["புதிய பூஜைகள் 30 வரை.", "The limit for added poojas is 30."],
  "pooja_added" => ["புதிய பூஜை சேர்க்கப்பட்டது.", "The pooja was added."],
  "missing_pooja" => ["இந்தப் பூஜையைக் காணவில்லை.", "This pooja could not be found."],
  "pooja_removed" => ["பூஜை நீக்கப்பட்டது.", "The pooja was removed."],
  "forbidden" => ["இந்தச் செயலுக்கு அனுமதி இல்லை.", "You do not have permission for this action."],
  "unavailable" => ["நிர்வாகக் கோப்பைத் திறக்க முடியவில்லை.", "The administrator store is unavailable."],
  "setup_closed" => ["முதல் கணக்கு கட்டளை வழியாக மட்டுமே உருவாக்கப்படும்.", "The first account can only be created from the command line."],
  "user_created" => ["கணக்கு உருவாக்கப்பட்டது.", "The account was created."],
  "roles_ok" => ["பொறுப்புகள் சேமிக்கப்பட்டன.", "The roles were saved."],
  "user_disabled" => ["கணக்கு நிறுத்தப்பட்டது.", "The account was disabled."],
  "user_enabled" => ["கணக்கு இயக்கப்பட்டது.", "The account was enabled."],
  "reset_ok" => ["புதிய கடவுச்சொல் அமைக்கப்பட்டது.", "The new password was set."],
  "user_deleted" => ["கணக்கு நீக்கப்பட்டது.", "The account was removed."],
  "user_exists" => ["இந்தப் பயனர் பெயர் ஏற்கெனவே உள்ளது.", "That username already exists."],
  "user_invalid" => ["சரியான பயனர் பெயர், 8 எழுத்துக் கடவுச்சொல், மற்றும் ஒரு பொறுப்பு வேண்டும்.", "A valid username, an 8-character password, and one role are required."],
  "user_missing" => ["கணக்கைக் காணவில்லை.", "The account could not be found."],
  "last_super" => ["கடைசி முதன்மை நிர்வாகியை நிறுத்தவோ, நீக்கவோ, பொறுப்பு மாற்றவோ முடியாது.", "The last Super Admin cannot be disabled, removed, or demoted."]
];

const ROLE_UI = [
  "super_admin" => ["முதன்மை நிர்வாகி", "Super Admin"],
  "committee_admin" => ["குழு நிர்வாகி", "Committee Admin"],
  "email_manager" => ["அஞ்சல் பொறுப்பு", "Email Manager"]
];

const DEITY_EN = [
  "ganesha" => "Vinayagar",
  "shiva" => "Shiva",
  "lakshmi" => "Varalakshmi",
  "saraswati" => "Saraswati",
  "mahalaya" => "Mahalaya lamp"
];

if (empty($_SESSION["fails"])) $_SESSION["fails"] = 0;
$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  if (!hm_csrf_ok()) {
    $error = "csrf";
  } elseif (isset($_POST["setup"])) {
    $error = "setup_closed";
  } elseif (isset($_POST["login"])) {
    $error = hm_attempt_login((string) ($_POST["username"] ?? ""), (string) ($_POST["password"] ?? ""));
  } elseif (!logged_in()) {
    $error = "need_login";
  } elseif (isset($_POST["logout"])) {
    hm_logout();
    header("Location: ./");
    exit;
  } elseif (isset($_POST["password_change"])) {
    $changed = hm_change_password(
      (string) ($_POST["current"] ?? ""),
      (string) ($_POST["password"] ?? ""),
      (string) ($_POST["again"] ?? "")
    );
    if ($changed === "") $message = "password_ok";
    else $error = $changed;
  } elseif (hm_account_post($_POST)) {
    $managed = hm_account_request($_POST);
    $message = $managed["message"];
    $error = $managed["error"];
  } else {
    $published = hm_publish_request($_POST);
    $message = $published["message"];
    $error = $published["error"];
  }
}

function pair($key) {
  return UI[$key] ?? ["", ""];
}

$site = hm_load_site();
$manager = hm_current_user();
$noticeCount = count($site["notices"]);
$hiddenPooja = 0;
foreach (DEFAULTS as $defaultRow) {
  if (in_array($defaultRow[0], $site["removed"], true)) $hiddenPooja++;
}
$visiblePooja = count(DEFAULTS) - $hiddenPooja + count($site["extra"]);
$accounts = [];
if ($manager !== null && hm_can_manage_users($manager)) {
  try {
    $accounts = hm_public_users();
  } catch (RuntimeException $accountsError) {
    if ($error === "") $error = "unavailable";
  }
}
$enabledAccounts = 0;
foreach ($accounts as $accountRow) {
  if (!empty($accountRow["enabled"])) $enabledAccounts++;
}
$signedInName = $manager !== null ? (string) ($manager["username"] ?? "") : "";
$token = h(csrf());
$screen = $manager !== null ? "desk" : "login";
$titles = [
  "login" => ["நிர்வாக நுழைவு — இந்து மாமன்றம்", "Administrator sign in — Hindu Mamanram"],
  "desk" => ["குழு பதிவு — இந்து மாமன்றம்", "Committee desk — Hindu Mamanram"]
];
$title = $titles[$screen];
header("X-Robots-Tag: noindex");
?>
<!DOCTYPE html>
<html lang="ta" data-title-ta="<?= h($title[0]) ?>" data-title-en="<?= h($title[1]) ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex">
  <title><?= h($title[0]) ?></title>
  <link rel="icon" href="../assets/favicon.svg" type="image/svg+xml">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600&family=Hind+Madurai:wght@400;500;600&family=Tiro+Tamil&display=swap">
  <link rel="stylesheet" href="../css/admin.css?v=20261107-login">
  <link rel="stylesheet" href="../css/admin-login.css?v=20261107d">
  <script>document.documentElement.classList.add("js-menu");</script>
</head>
<body>
<?php if ($screen === "login"): ?>
  <main class="temple-auth">
    <section class="temple-auth-brand" aria-label="Hindu Mamanram Temple Management">
      <div class="temple-auth-image" aria-hidden="true"></div>
      <div class="temple-auth-brand-content">
        <a class="temple-auth-logo" href="../index.html"><img src="../assets/hm-seal.jpg" alt=""><span data-ta="இந்து மாமன்றம்" data-en="Hindu Mamanram">இந்து மாமன்றம்</span></a>
        <div class="temple-auth-brand-copy">
          <p class="temple-auth-eyebrow" data-ta="ஆலய நிர்வாகம்" data-en="TEMPLE ADMINISTRATION">ஆலய நிர்வாகம்</p>
          <h2 data-ta="ஆலய நிர்வாகத் தளம்" data-en="Temple Management Portal">ஆலய நிர்வாகத் தளம்</h2>
          <p data-ta="பூஜைகள், அறிவிப்புகள் மற்றும் சமூக நிகழ்வுகளை ஒரே இடத்தில் நிர்வகிக்கவும்." data-en="Manage poojas, announcements and community activities in one place.">பூஜைகள், அறிவிப்புகள் மற்றும் சமூக நிகழ்வுகளை ஒரே இடத்தில் நிர்வகிக்கவும்.</p>
        </div>
        <span class="temple-auth-brand-footer">Hindu Mamanram · Rogaland</span>
      </div>
    </section>
    <section class="temple-auth-form-side">
      <div class="temple-auth-top">
        <a class="temple-auth-home" href="../index.html" data-ta="← இணையதளம்" data-en="← Back to website">← இணையதளம்</a>
        <div class="lang" role="group" data-label-ta="மொழி" data-label-en="Language" aria-label="மொழி">
          <button type="button" class="is-on" data-set-lang="ta" aria-pressed="true">த</button>
          <button type="button" data-set-lang="en" aria-pressed="false">EN</button>
        </div>
      </div>
      <div class="temple-auth-form-wrap">
        <div class="temple-auth-ornament" aria-hidden="true"><span>ॐ</span></div>
        <p class="temple-auth-kicker" data-ta="பாதுகாப்பான நிர்வாக நுழைவு" data-en="SECURE ADMIN ACCESS">பாதுகாப்பான நிர்வாக நுழைவு</p>
        <h1 data-ta="மீண்டும் வரவேற்கிறோம்" data-en="Welcome back">மீண்டும் வரவேற்கிறோம்</h1>
        <p class="temple-auth-subtitle" data-ta="உங்கள் நிர்வாகக் கணக்கில் உள்நுழையுங்கள்." data-en="Sign in to your management account.">உங்கள் நிர்வாகக் கணக்கில் உள்நுழையுங்கள்.</p>
        <?php if ($message !== ""): $pair = pair($message); ?>
          <p class="banner" role="status" data-ta="<?= h($pair[0]) ?>" data-en="<?= h($pair[1]) ?>"><?= h($pair[0]) ?></p>
        <?php endif; ?>
        <?php if ($error !== ""): $pair = pair($error); ?>
          <p class="banner bad" role="alert" data-ta="<?= h($pair[0]) ?>" data-en="<?= h($pair[1]) ?>"><?= h($pair[0]) ?></p>
        <?php endif; ?>
        <form class="temple-auth-form" method="post">
          <input type="hidden" name="csrf" value="<?= $token ?>">
          <label class="temple-auth-field temple-auth-user"><span data-ta="பயனர் பெயர்" data-en="Username">பயனர் பெயர்</span><input name="username" autocomplete="username" maxlength="32" spellcheck="false" placeholder="Username" data-placeholder-ta="பயனர் பெயர்" data-placeholder-en="Username" required></label>
          <label class="temple-auth-field temple-auth-pass"><span data-ta="கடவுச்சொல்" data-en="Password">கடவுச்சொல்</span><span class="temple-auth-password"><input id="temple-login-password" type="password" name="password" required autocomplete="current-password" placeholder="Password" data-placeholder-ta="கடவுச்சொல்" data-placeholder-en="Password"><button class="temple-auth-reveal" type="button" aria-controls="temple-login-password" aria-pressed="false" data-show-password><span data-ta="காட்டு" data-en="Show">காட்டு</span></button></span></label>
          <details class="temple-auth-recovery">
            <summary><span data-ta="கடவுச்சொல் மறந்துவிட்டதா?" data-en="Forgot password?">கடவுச்சொல் மறந்துவிட்டதா?</span></summary>
            <div class="temple-auth-recovery-help">
              <p data-ta="கடவுச்சொல்லை மாற்ற, அங்கீகரிக்கப்பட்ட முதன்மை நிர்வாகியைத் தொடர்புகொள்ளுங்கள். பாதுகாப்பிற்காக, இந்தப் பக்கத்தில் நேரடியாக கடவுச்சொல்லை மீட்டமைக்க முடியாது." data-en="Contact an authorized Super Admin to reset your password. For security, passwords cannot be reset directly from this page.">Contact an authorized Super Admin to reset your password. For security, passwords cannot be reset directly from this page.</p>
              <p data-ta="நீங்களே ஒரே நிர்வாகியாக இருந்தால், பாதுகாப்பான உள்ளூர் நிர்வாக மீட்பு நடைமுறையைப் பயன்படுத்துங்கள்." data-en="If you are the only administrator, use the secure local administrator recovery procedure.">If you are the only administrator, use the secure local administrator recovery procedure.</p>
            </div>
          </details>
          <button class="temple-auth-submit" name="login" value="1" type="submit"><span data-ta="உள்நுழைக" data-en="Sign In">உள்நுழைக</span><span aria-hidden="true">→</span></button>
        </form>
        <p class="temple-auth-secure">🔒 <span data-ta="அங்கீகரிக்கப்பட்ட நிர்வாக உறுப்பினர்களுக்கு மட்டும்" data-en="Authorized committee members only">அங்கீகரிக்கப்பட்ட நிர்வாக உறுப்பினர்களுக்கு மட்டும்</span></p>
      </div>
      <footer class="temple-auth-footer" aria-label="Login page footer">
        <span>© <?= date("Y") ?> Hindu Mamanram · Rogaland</span>
        <a href="../index.html" data-ta="இணையதளத்திற்குச் செல்லவும்" data-en="Back to website">இணையதளத்திற்குச் செல்லவும்</a>
      </footer>
    </section>
  </main>
  <script>
    (function () {
      var toggle = document.querySelector("[data-show-password]");
      var field = document.getElementById("temple-login-password");
      if (!toggle || !field) return;
      toggle.addEventListener("click", function () {
        var showing = field.type === "password";
        field.type = showing ? "text" : "password";
        toggle.setAttribute("aria-pressed", showing ? "true" : "false");
        var label = toggle.querySelector("span");
        if (label) {
          label.setAttribute("data-ta", showing ? "மறை" : "காட்டு");
          label.setAttribute("data-en", showing ? "Hide" : "Show");
          var english = document.documentElement.lang === "en";
          label.textContent = english ? label.getAttribute("data-en") : label.getAttribute("data-ta");
        }
      });
    })();
  </script>
<?php else: ?>
  <main class="shell is-desk">
    <div class="desk-app">
      <button type="button" class="sidebar-backdrop" data-close-menu tabindex="-1" data-label-ta="பட்டியை மூடு" data-label-en="Close menu" aria-label="பட்டியை மூடு"></button>
      <aside id="desk-sidebar" class="sidebar">
        <a class="brand" href="../index.html">
          <img src="../assets/hm-seal.jpg" alt="">
          <span data-ta="இந்து மாமன்றம்" data-en="Hindu Mamanram">இந்து மாமன்றம்</span>
        </a>
        <nav class="side-nav" data-label-ta="பகுதிகள்" data-label-en="Sections" aria-label="பகுதிகள்">
          <p class="side-label" data-ta="மேலோட்டம்" data-en="Overview">மேலோட்டம்</p>
          <ul>
            <li><a class="side-link" href="#dashboard"><?php icon("home"); ?><span data-ta="முகப்பு" data-en="Dashboard">முகப்பு</span></a></li>
          </ul>
          <?php if (hm_can_publish()): ?>
          <p class="side-label" data-ta="வெளியீடு" data-en="Publishing">வெளியீடு</p>
          <ul>
            <li><a class="side-link" href="#notices"><?php icon("notice"); ?><span data-ta="அறிவிப்புகள்" data-en="Notices">அறிவிப்புகள்</span></a></li>
            <li><a class="side-link" href="#dates"><?php icon("pooja"); ?><span data-ta="பூஜை மேலாண்மை" data-en="Pooja Management">பூஜை மேலாண்மை</span></a></li>
          </ul>
          <?php endif; ?>
          <p class="side-label" data-ta="கணக்கு" data-en="Account">கணக்கு</p>
          <ul>
            <?php if ($manager !== null && hm_can_manage_users($manager)): ?>
            <li><a class="side-link" href="#users"><?php icon("users"); ?><span data-ta="பயனர்கள்" data-en="Users">பயனர்கள்</span></a></li>
            <?php endif; ?>
            <li><a class="side-link" href="#account"><?php icon("settings"); ?><span data-ta="அமைப்புகள்" data-en="Settings">அமைப்புகள்</span></a></li>
          </ul>
        </nav>
        <div class="side-user">
          <?php if ($signedInName !== ""): ?>
            <strong class="side-name"><?= h($signedInName) ?></strong>
          <?php elseif ($manager !== null && !empty($manager["legacy"])): ?>
            <strong class="side-name" data-ta="பழைய கணக்கு" data-en="Shared sign-in">பழைய கணக்கு</strong>
          <?php endif; ?>
          <?php if ($manager !== null): ?>
          <p class="role-row">
            <?php foreach ($manager["roles"] ?? [] as $role):
              $label = ROLE_UI[$role] ?? null;
              if ($label === null) continue;
            ?>
              <span class="badge" data-ta="<?= h($label[0]) ?>" data-en="<?= h($label[1]) ?>"><?= h($label[0]) ?></span>
            <?php endforeach; ?>
          </p>
          <?php endif; ?>
        </div>
      </aside>
      <div class="desk-main">
        <div class="desk-top">
          <button type="button" class="menu-btn" data-menu aria-expanded="false" aria-controls="desk-sidebar" data-label-ta="பட்டி" data-label-en="Menu" aria-label="பட்டி"><span data-ta="பட்டி" data-en="Menu">பட்டி</span></button>
          <div class="lang" role="group" data-label-ta="மொழி" data-label-en="Language" aria-label="மொழி">
            <button type="button" class="is-on" data-set-lang="ta" aria-pressed="true">த</button>
            <button type="button" data-set-lang="en" aria-pressed="false">EN</button>
          </div>
          <form method="post">
            <input type="hidden" name="csrf" value="<?= $token ?>">
            <button class="btn btn-ghost" name="logout" value="1" type="submit"><span data-ta="வெளியேறு" data-en="Sign out">வெளியேறு</span></button>
          </form>
        </div>
        <?php if ($message !== ""): $pair = pair($message); ?>
          <p class="banner" role="status" data-ta="<?= h($pair[0]) ?>" data-en="<?= h($pair[1]) ?>"><?= h($pair[0]) ?></p>
        <?php endif; ?>
        <?php if ($error !== ""): $pair = pair($error); ?>
          <p class="banner bad" role="alert" data-ta="<?= h($pair[0]) ?>" data-en="<?= h($pair[1]) ?>"><?= h($pair[0]) ?></p>
        <?php endif; ?>

        <section class="panel desk-view" id="dashboard" data-title-ta="முகப்பு — இந்து மாமன்றம்" data-title-en="Dashboard — Hindu Mamanram">
          <div class="panel-body">
            <p class="kicker" data-ta="குழு நிர்வாகம்" data-en="Committee administration">குழு நிர்வாகம்</p>
            <h1><span data-ta="வணக்கம்" data-en="Welcome">வணக்கம்</span><?php if ($signedInName !== ""): ?>, <?= h($signedInName) ?><?php endif; ?></h1>
            <p class="lede" data-ta="இன்றைய பணியைக் கீழுள்ள பகுதியிலிருந்து தொடங்குங்கள்." data-en="Start with one of the sections below.">இன்றைய பணியைக் கீழுள்ள பகுதியிலிருந்து தொடங்குங்கள்.</p>
            <?php if (hm_can_publish() || ($manager !== null && hm_can_manage_users($manager))): ?>
            <ul class="facts">
              <?php if (hm_can_publish()): ?>
              <li><b><?= (int) $noticeCount ?></b> <span data-ta="அறிவிப்புகள்" data-en="notices">அறிவிப்புகள்</span></li>
              <li><b><?= (int) $visiblePooja ?></b> <span data-ta="காட்டப்படும் பூஜைகள்" data-en="visible pooja dates">காட்டப்படும் பூஜைகள்</span></li>
              <li><b><?= (int) $hiddenPooja ?></b> <span data-ta="மறைக்கப்பட்ட பூஜைகள்" data-en="hidden pooja dates">மறைக்கப்பட்ட பூஜைகள்</span></li>
              <?php endif; ?>
              <?php if ($manager !== null && hm_can_manage_users($manager)): ?>
              <li><b><?= (int) $enabledAccounts ?></b> <span data-ta="செயலில் உள்ள கணக்குகள்" data-en="enabled accounts">செயலில் உள்ள கணக்குகள்</span></li>
              <?php endif; ?>
            </ul>
            <?php elseif (!hm_can_publish()): ?>
              <p class="hint" data-ta="இந்தக் கணக்கால் அறிவிப்பையோ பூஜை நாளையோ வெளியிட முடியாது." data-en="This account cannot publish notices or pooja dates.">இந்தக் கணக்கால் அறிவிப்பையோ பூஜை நாளையோ வெளியிட முடியாது.</p>
            <?php endif; ?>
            <div class="dash-grid">
              <?php if (hm_can_publish()): ?>
              <a class="dash-card" href="#notices"><?php icon("notice"); ?><strong data-ta="அறிவிப்புகள்" data-en="Notices">அறிவிப்புகள்</strong><span class="metric"><?= (int) $noticeCount ?></span><span data-ta="அறிவிப்புகளை வெளியிடவும்." data-en="Publish notices.">அறிவிப்புகளை வெளியிடவும்.</span></a>
              <a class="dash-card" href="#dates"><?php icon("pooja"); ?><strong data-ta="பூஜை மேலாண்மை" data-en="Pooja Management">பூஜை மேலாண்மை</strong><span class="metric"><?= (int) $visiblePooja ?></span><span data-ta="பூஜை நாட்களை நிர்வகிக்கவும்." data-en="Manage pooja dates.">பூஜை நாட்களை நிர்வகிக்கவும்.</span></a>
              <?php endif; ?>
              <?php if ($manager !== null && hm_can_manage_users($manager)): ?>
              <a class="dash-card" href="#users"><?php icon("users"); ?><strong data-ta="பயனர்கள்" data-en="Users">பயனர்கள்</strong><span class="metric"><?= (int) count($accounts) ?></span><span data-ta="கணக்குகளையும் பொறுப்புகளையும் நிர்வகிக்கவும்." data-en="Manage accounts and roles.">கணக்குகளையும் பொறுப்புகளையும் நிர்வகிக்கவும்.</span></a>
              <?php endif; ?>
              <a class="dash-card" href="#account"><?php icon("settings"); ?><strong data-ta="அமைப்புகள்" data-en="Settings">அமைப்புகள்</strong><span data-ta="என் கணக்கில் கடவுச்சொல்லை மாற்றவும்." data-en="Change the password on my account.">என் கணக்கில் கடவுச்சொல்லை மாற்றவும்.</span></a>
            </div>
          </div>
        </section>

      <?php if (hm_can_publish()): ?>
      <section class="panel desk-view" id="notices" data-title-ta="அறிவிப்புகள் — இந்து மாமன்றம்" data-title-en="Notices — Hindu Mamanram">
        <div class="panel-body">
          <div class="view-head">
            <div>
              <h2 data-ta="அறிவிப்புகள்" data-en="Notices">அறிவிப்புகள்</h2>
              <p class="hint" data-ta="இங்கே வெளியிடுவது mamanram.no-வில் அனைவருக்கும் தெரியும்." data-en="What you publish here is visible to everyone on mamanram.no.">இங்கே வெளியிடுவது mamanram.no-வில் அனைவருக்கும் தெரியும்.</p>
            </div>
            <button type="button" class="btn btn-primary" data-open="notice-new" aria-haspopup="dialog" aria-controls="notice-new"><span data-ta="புதிய அறிவிப்பு" data-en="Create notice">புதிய அறிவிப்பு</span></button>
          </div>
          <dialog class="sheet" id="notice-new" aria-labelledby="notice-new-title">
            <form method="post">
              <div class="sheet-head">
                <h3 id="notice-new-title" data-ta="புதிய அறிவிப்பு" data-en="New notice">புதிய அறிவிப்பு</h3>
                <button type="button" class="icon-btn" data-close data-label-ta="மூடு" data-label-en="Close" aria-label="மூடு">×</button>
              </div>
              <div class="sheet-body">
                <input type="hidden" name="csrf" value="<?= $token ?>">
                <div class="form-grid">
                  <label class="field"><span data-ta="தலைப்பு" data-en="Title">தலைப்பு</span><input name="title_ta" required maxlength="140"></label>
                  <label class="field"><span data-ta="ஆங்கிலத் தலைப்பு" data-en="English title">ஆங்கிலத் தலைப்பு</span><input name="title_en" maxlength="140"></label>
                  <label class="field wide"><span data-ta="செய்தி" data-en="Message">செய்தி</span><textarea name="body_ta" required maxlength="2000" rows="4"></textarea></label>
                  <label class="field wide"><span data-ta="ஆங்கிலச் செய்தி" data-en="English text">ஆங்கிலச் செய்தி</span><textarea name="body_en" maxlength="2000" rows="3"></textarea></label>
                  <label class="field"><span data-ta="நாள்" data-en="Date">நாள்</span><input type="date" name="notice_date"></label>
                </div>
                <div class="actions">
                  <button class="btn btn-primary" name="save_notice" value="1" type="submit"><span data-ta="வெளியிடு" data-en="Publish">வெளியிடு</span></button>
                </div>
              </div>
            </form>
          </dialog>
          <?php if (!$site["notices"]): ?>
            <p class="empty" data-ta="இன்னும் அறிவிப்பு இல்லை." data-en="There are no notices yet.">இன்னும் அறிவிப்பு இல்லை.</p>
          <?php else: ?>
          <div class="table-wrap">
            <table class="data">
              <thead>
                <tr>
                  <th data-ta="நாள்" data-en="Date">நாள்</th>
                  <th data-ta="தலைப்பு" data-en="Title">தலைப்பு</th>
                  <th data-ta="செய்தி" data-en="Message">செய்தி</th>
                  <th data-ta="செயல்கள்" data-en="Actions">செயல்கள்</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($site["notices"] as $noticeIndex => $notice):
                  $noticeDialog = "notice-" . ($noticeIndex + 1);
                ?>
                <tr>
                  <td><?= h(($notice["date"] ?? "") !== "" ? $notice["date"] : "—") ?></td>
                  <td><?= h($notice["titleTa"] ?? "") ?></td>
                  <td class="clip"><?= h($notice["bodyTa"] ?? "") ?></td>
                  <td><button type="button" class="btn btn-ghost btn-small" data-open="<?= h($noticeDialog) ?>" aria-haspopup="dialog" aria-controls="<?= h($noticeDialog) ?>"><span data-ta="திருத்து" data-en="Edit">திருத்து</span></button></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php foreach ($site["notices"] as $noticeIndex => $notice):
            $noticeDialog = "notice-" . ($noticeIndex + 1);
          ?>
          <dialog class="sheet" id="<?= h($noticeDialog) ?>" aria-labelledby="<?= h($noticeDialog) ?>-title">
            <form method="post">
              <div class="sheet-head">
                <h3 id="<?= h($noticeDialog) ?>-title" data-ta="அறிவிப்பைத் திருத்து" data-en="Edit notice">அறிவிப்பைத் திருத்து</h3>
                <button type="button" class="icon-btn" data-close data-label-ta="மூடு" data-label-en="Close" aria-label="மூடு">×</button>
              </div>
              <div class="sheet-body">
                <input type="hidden" name="csrf" value="<?= $token ?>">
                <input type="hidden" name="notice_id" value="<?= h($notice["id"] ?? "") ?>">
                <div class="form-grid">
                  <label class="field"><span data-ta="தலைப்பு" data-en="Title">தலைப்பு</span><input name="title_ta" required maxlength="140" value="<?= h($notice["titleTa"] ?? "") ?>"></label>
                  <label class="field"><span data-ta="ஆங்கிலத் தலைப்பு" data-en="English title">ஆங்கிலத் தலைப்பு</span><input name="title_en" maxlength="140" value="<?= h($notice["titleEn"] ?? "") ?>"></label>
                  <label class="field wide"><span data-ta="செய்தி" data-en="Message">செய்தி</span><textarea name="body_ta" required maxlength="2000" rows="4"><?= h($notice["bodyTa"] ?? "") ?></textarea></label>
                  <label class="field wide"><span data-ta="ஆங்கிலச் செய்தி" data-en="English text">ஆங்கிலச் செய்தி</span><textarea name="body_en" maxlength="2000" rows="3"><?= h($notice["bodyEn"] ?? "") ?></textarea></label>
                  <label class="field"><span data-ta="நாள்" data-en="Date">நாள்</span><input type="date" name="notice_date" value="<?= h($notice["date"] ?? "") ?>"></label>
                </div>
                <div class="actions">
                  <button class="btn btn-primary" name="save_notice" value="1" type="submit"><span data-ta="சேமி" data-en="Save">சேமி</span></button>
                  <button class="btn btn-ghost" name="delete_notice" value="1" type="submit"><span data-ta="நீக்கு" data-en="Remove">நீக்கு</span></button>
                </div>
              </div>
            </form>
          </dialog>
          <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </section>

      <section class="panel desk-view" id="dates" data-title-ta="பூஜை மேலாண்மை — இந்து மாமன்றம்" data-title-en="Pooja Management — Hindu Mamanram">
        <div class="panel-body">
          <?php
            $poojaRows = [];
            foreach (DEFAULTS as $row) {
              $id = $row[0];
              $over = $site["pooja"][$id] ?? [];
              $poojaRows[] = [
                "kind" => "default",
                "id" => $id,
                "date" => $over["date"] ?? $row[1],
                "name" => $over["name"] ?? $row[2],
                "sponsor" => array_key_exists("sponsor", $over) ? $over["sponsor"] : $row[3],
                "hidden" => in_array($id, $site["removed"], true)
              ];
            }
            foreach ($site["extra"] as $extra) {
              $poojaRows[] = [
                "kind" => "extra",
                "id" => (string) ($extra["id"] ?? ""),
                "date" => (string) ($extra["date"] ?? ""),
                "name" => (string) ($extra["name"] ?? ""),
                "sponsor" => (string) ($extra["sponsor"] ?? ""),
                "hidden" => false,
                "extra" => $extra
              ];
            }
            usort($poojaRows, function ($left, $right) {
              return strcmp((string) $left["date"], (string) $right["date"]);
            });
          ?>
          <div class="view-head">
            <div>
              <h2 data-ta="பூஜை மேலாண்மை" data-en="Pooja Management">பூஜை மேலாண்மை</h2>
              <p class="hint" data-ta="நாள், பூஜை, உபயதாரரைத் தேடி, வரிசையைத் திருத்துங்கள்." data-en="Search by date, pooja, or sponsor, then edit a row.">நாள், பூஜை, உபயதாரரைத் தேடி, வரிசையைத் திருத்துங்கள்.</p>
            </div>
            <button type="button" class="btn btn-primary" data-open="pooja-add" aria-haspopup="dialog" aria-controls="pooja-add"><span data-ta="புதிய பூஜை" data-en="Add a pooja">புதிய பூஜை</span></button>
          </div>
          <label class="finder">
            <span data-ta="தேடு" data-en="Search">தேடு</span>
            <input type="search" id="pooja-search" autocomplete="off" data-label-ta="பூஜை தேடல்" data-label-en="Search pooja dates" aria-label="பூஜை தேடல்">
          </label>
          <p class="empty" id="pooja-empty" hidden data-ta="பொருத்தமான பூஜை இல்லை." data-en="No matching pooja dates.">பொருத்தமான பூஜை இல்லை.</p>
          <div class="table-wrap">
            <table class="data" id="pooja-table">
              <thead>
                <tr>
                  <th data-ta="நாள்" data-en="Date">நாள்</th>
                  <th data-ta="பூஜை" data-en="Pooja">பூஜை</th>
                  <th data-ta="உபயதாரர்" data-en="Sponsor">உபயதாரர்</th>
                  <th data-ta="நிலை" data-en="Status">நிலை</th>
                  <th data-ta="செயல்கள்" data-en="Actions">செயல்கள்</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($poojaRows as $poojaIndex => $poojaRow):
                  $poojaDialog = "pooja-" . ($poojaIndex + 1);
                  $poojaFind = $poojaRow["date"] . " " . $poojaRow["name"] . " " . $poojaRow["sponsor"] . " " . ($poojaRow["hidden"] ? "மறைக்கப்பட்டது Hidden" : "காட்டப்படுகிறது Visible");
                ?>
                <tr data-find="<?= h($poojaFind) ?>">
                  <td><?= h($poojaRow["date"]) ?></td>
                  <td><?= h($poojaRow["name"]) ?></td>
                  <td><?= h($poojaRow["sponsor"]) ?></td>
                  <td>
                    <?php if ($poojaRow["hidden"]): ?>
                      <span class="badge off" data-ta="மறைக்கப்பட்டது" data-en="Hidden">மறைக்கப்பட்டது</span>
                    <?php else: ?>
                      <span class="badge" data-ta="காட்டப்படுகிறது" data-en="Visible">காட்டப்படுகிறது</span>
                    <?php endif; ?>
                  </td>
                  <td><button type="button" class="btn btn-ghost btn-small" data-open="<?= h($poojaDialog) ?>" aria-haspopup="dialog" aria-controls="<?= h($poojaDialog) ?>"><span data-ta="திருத்து" data-en="Edit">திருத்து</span></button></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <dialog class="sheet" id="pooja-add" aria-labelledby="pooja-add-title">
            <form method="post">
              <div class="sheet-head">
                <h3 id="pooja-add-title" data-ta="புதிய பூஜை" data-en="Add a pooja">புதிய பூஜை</h3>
                <button type="button" class="icon-btn" data-close data-label-ta="மூடு" data-label-en="Close" aria-label="மூடு">×</button>
              </div>
              <div class="sheet-body">
                <input type="hidden" name="csrf" value="<?= $token ?>">
                <div class="form-grid">
                  <label class="field"><span data-ta="நாள்" data-en="Date">நாள்</span><input type="date" name="date" required></label>
                  <label class="field"><span data-ta="பூஜை" data-en="Pooja">பூஜை</span><input name="name" required maxlength="80"></label>
                  <label class="field wide"><span data-ta="உபயதாரர்" data-en="Sponsor">உபயதாரர்</span><input name="sponsor" maxlength="120"></label>
                  <label class="field"><span data-ta="இறைவன்" data-en="Deity">இறைவன்</span>
                    <select name="deity">
                      <?php foreach (DEITIES as $key => $label): ?>
                        <option value="<?= h($key) ?>" data-ta="<?= h($label) ?>" data-en="<?= h(DEITY_EN[$key] ?? $label) ?>"><?= h($label) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </label>
                  <label class="check wide"><input type="checkbox" name="common" value="1"><span data-ta="பொதுப் பூஜை (பிரசாதம் மற்றும் அஞ்சல்)" data-en="Common pooja (prasadam and mail)">பொதுப் பூஜை (பிரசாதம் மற்றும் அஞ்சல்)</span></label>
                </div>
                <div class="actions">
                  <button class="btn btn-primary" name="add_pooja" value="1" type="submit"><span data-ta="சேர்" data-en="Add">சேர்</span></button>
                </div>
              </div>
            </form>
          </dialog>
          <?php foreach ($poojaRows as $poojaIndex => $poojaRow):
            $poojaDialog = "pooja-" . ($poojaIndex + 1);
          ?>
          <dialog class="sheet" id="<?= h($poojaDialog) ?>" aria-labelledby="<?= h($poojaDialog) ?>-title">
            <form method="post">
              <div class="sheet-head">
                <h3 id="<?= h($poojaDialog) ?>-title" data-ta="பூஜையைத் திருத்து" data-en="Edit pooja">பூஜையைத் திருத்து</h3>
                <button type="button" class="icon-btn" data-close data-label-ta="மூடு" data-label-en="Close" aria-label="மூடு">×</button>
              </div>
              <div class="sheet-body">
                <input type="hidden" name="csrf" value="<?= $token ?>">
                <input type="hidden" name="pooja_id" value="<?= h($poojaRow["id"]) ?>">
                <div class="form-grid">
                  <label class="field"><span data-ta="நாள்" data-en="Date">நாள்</span><input type="date" name="date" required value="<?= h($poojaRow["date"]) ?>"></label>
                  <label class="field"><span data-ta="பூஜை" data-en="Pooja">பூஜை</span><input name="name" required maxlength="80" value="<?= h($poojaRow["name"]) ?>"></label>
                  <label class="field wide"><span data-ta="உபயதாரர்" data-en="Sponsor">உபயதாரர்</span><input name="sponsor" maxlength="120" value="<?= h($poojaRow["sponsor"]) ?>"></label>
                  <?php if ($poojaRow["kind"] === "extra"): $extra = $poojaRow["extra"]; ?>
                  <label class="field"><span data-ta="இறைவன்" data-en="Deity">இறைவன்</span>
                    <select name="deity">
                      <?php foreach (DEITIES as $key => $label): ?>
                        <option value="<?= h($key) ?>" data-ta="<?= h($label) ?>" data-en="<?= h(DEITY_EN[$key] ?? $label) ?>"<?= ($extra["deity"] ?? "") === $key ? " selected" : "" ?>><?= h($label) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </label>
                  <label class="check wide"><input type="checkbox" name="common" value="1"<?= !empty($extra["common"]) ? " checked" : "" ?>><span data-ta="பொதுப் பூஜை (பிரசாதம் மற்றும் அஞ்சல்)" data-en="Common pooja (prasadam and mail)">பொதுப் பூஜை (பிரசாதம் மற்றும் அஞ்சல்)</span></label>
                  <?php endif; ?>
                </div>
                <div class="actions">
                  <?php if ($poojaRow["kind"] === "default" && $poojaRow["hidden"]): ?>
                    <button class="btn btn-primary" name="restore_pooja" value="1" type="submit"><span data-ta="மீண்டும் காட்டு" data-en="Show again">மீண்டும் காட்டு</span></button>
                  <?php elseif ($poojaRow["kind"] === "default"): ?>
                    <button class="btn btn-primary" name="save_pooja" value="1" type="submit"><span data-ta="சேமி" data-en="Save">சேமி</span></button>
                    <button class="btn btn-ghost" name="hide_pooja" value="1" type="submit"><span data-ta="மறை" data-en="Hide">மறை</span></button>
                  <?php else: ?>
                    <button class="btn btn-primary" name="save_extra" value="1" type="submit"><span data-ta="சேமி" data-en="Save">சேமி</span></button>
                    <button class="btn btn-ghost" name="delete_extra" value="1" type="submit"><span data-ta="நீக்கு" data-en="Remove">நீக்கு</span></button>
                  <?php endif; ?>
                </div>
              </div>
            </form>
          </dialog>
          <?php endforeach; ?>
        </div>
      </section>
      <?php endif; ?>

      <section class="panel desk-view" id="account" data-title-ta="அமைப்புகள் — இந்து மாமன்றம்" data-title-en="Settings — Hindu Mamanram">
        <div class="panel-body">
          <h2 data-ta="அமைப்புகள்" data-en="Settings">அமைப்புகள்</h2>
          <div class="account-card">
            <h3 data-ta="என் கணக்கு" data-en="My account">என் கணக்கு</h3>
            <?php if ($signedInName !== ""): ?>
              <p class="hint"><?= h($signedInName) ?></p>
            <?php elseif ($manager !== null && !empty($manager["legacy"])): ?>
              <p class="hint" data-ta="பழைய கணக்கு" data-en="Shared sign-in">பழைய கணக்கு</p>
            <?php endif; ?>
            <h3 data-ta="பாதுகாப்பு" data-en="Security">பாதுகாப்பு</h3>
            <p class="hint" data-ta="உங்கள் கடவுச்சொல்லை இங்கே மாற்றலாம்." data-en="You can change your password here.">உங்கள் கடவுச்சொல்லை இங்கே மாற்றலாம்.</p>
            <form method="post">
              <input type="hidden" name="csrf" value="<?= $token ?>">
              <label class="field"><span data-ta="இப்போதைய கடவுச்சொல்" data-en="Current password">இப்போதைய கடவுச்சொல்</span><input type="password" name="current" required autocomplete="current-password"></label>
              <div class="form-grid">
                <label class="field"><span data-ta="புதிய கடவுச்சொல்" data-en="New password">புதிய கடவுச்சொல்</span><input type="password" name="password" required minlength="8" autocomplete="new-password"></label>
                <label class="field"><span data-ta="மீண்டும்" data-en="Repeat password">மீண்டும்</span><input type="password" name="again" required minlength="8" autocomplete="new-password"></label>
              </div>
              <button class="btn btn-primary" name="password_change" value="1" type="submit"><span data-ta="மாற்று" data-en="Change">மாற்று</span></button>
            </form>
          </div>
        </div>
      </section>

      <?php if ($manager !== null && hm_can_manage_users($manager)): ?>
      <section class="panel desk-view" id="users" data-title-ta="பயனர்கள் — இந்து மாமன்றம்" data-title-en="Users — Hindu Mamanram">
        <div class="panel-body">
          <div class="view-head">
            <div>
              <h2 data-ta="பயனர்கள்" data-en="Users">பயனர்கள்</h2>
              <p class="hint" data-ta="ஒவ்வொரு குழு உறுப்பினருக்கும் தனிக் கணக்கு. கடவுச்சொற்கள் இங்கே காட்டப்படாது." data-en="Each committee member has a separate account. Passwords are never shown here.">ஒவ்வொரு குழு உறுப்பினருக்கும் தனிக் கணக்கு. கடவுச்சொற்கள் இங்கே காட்டப்படாது.</p>
            </div>
            <button type="button" class="btn btn-primary" data-open="user-create" aria-haspopup="dialog" aria-controls="user-create"><span data-ta="புதிய கணக்கு" data-en="New account">புதிய கணக்கு</span></button>
          </div>
          <dialog class="sheet" id="user-create" aria-labelledby="user-create-title">
            <form method="post">
              <div class="sheet-head">
                <h3 id="user-create-title" data-ta="புதிய கணக்கு" data-en="New account">புதிய கணக்கு</h3>
                <button type="button" class="icon-btn" data-close data-label-ta="மூடு" data-label-en="Close" aria-label="மூடு">×</button>
              </div>
              <div class="sheet-body">
                <input type="hidden" name="csrf" value="<?= $token ?>">
                <label class="field"><span data-ta="பயனர் பெயர்" data-en="Username">பயனர் பெயர்</span><input name="username" required maxlength="32" autocomplete="off" spellcheck="false" autocapitalize="none"></label>
                <p class="hint" data-ta="3 முதல் 32 எழுத்துகள். முதல் எழுத்து ஆங்கில எழுத்தோ எண்ணோ. பின்னர் புள்ளி, அடிக்கோடு, இடைக்கோடு சேரலாம். கடவுச்சொல் குறைந்தது 8 எழுத்துகள்." data-en="3 to 32 characters. Start with a letter or number; then a dot, underscore, or hyphen is allowed. The password must be at least 8 characters.">3 முதல் 32 எழுத்துகள். முதல் எழுத்து ஆங்கில எழுத்தோ எண்ணோ. பின்னர் புள்ளி, அடிக்கோடு, இடைக்கோடு சேரலாம். கடவுச்சொல் குறைந்தது 8 எழுத்துகள்.</p>
                <div class="form-grid">
                  <label class="field"><span data-ta="கடவுச்சொல்" data-en="Password">கடவுச்சொல்</span><input type="password" name="password" required minlength="8" autocomplete="new-password"></label>
                  <label class="field"><span data-ta="மீண்டும்" data-en="Repeat password">மீண்டும்</span><input type="password" name="again" required minlength="8" autocomplete="new-password"></label>
                </div>
                <fieldset class="roles">
                  <legend data-ta="பொறுப்புகள்" data-en="Roles">பொறுப்புகள்</legend>
                  <?php role_choices(["committee_admin"]); ?>
                </fieldset>
                <button class="btn btn-primary" name="create_user" value="1" type="submit"><span data-ta="உருவாக்கு" data-en="Create">உருவாக்கு</span></button>
              </div>
            </form>
          </dialog>
          <?php if (!$accounts): ?>
            <p class="empty" data-ta="இன்னும் தனிக் கணக்கு இல்லை." data-en="There are no individual accounts yet.">இன்னும் தனிக் கணக்கு இல்லை.</p>
          <?php else: ?>
          <div class="table-wrap">
            <table class="data">
              <thead>
                <tr>
                  <th data-ta="பயனர் பெயர்" data-en="Username">பயனர் பெயர்</th>
                  <th data-ta="பொறுப்புகள்" data-en="Roles">பொறுப்புகள்</th>
                  <th data-ta="நிலை" data-en="Status">நிலை</th>
                  <th data-ta="செயல்கள்" data-en="Actions">செயல்கள்</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($accounts as $accountIndex => $account):
                  $userKey = "user-" . ($accountIndex + 1);
                ?>
                <tr>
                  <td><?= h($account["username"]) ?><?php if ($manager && (string) $manager["id"] === $account["id"]): ?> <span class="badge" data-ta="நீங்கள்" data-en="You">நீங்கள்</span><?php endif; ?></td>
                  <td>
                    <?php foreach ($account["roles"] as $role):
                      $label = ROLE_UI[$role] ?? null;
                      if ($label === null) continue;
                    ?>
                      <span class="badge" data-ta="<?= h($label[0]) ?>" data-en="<?= h($label[1]) ?>"><?= h($label[0]) ?></span>
                    <?php endforeach; ?>
                  </td>
                  <td>
                    <?php if (!empty($account["enabled"])): ?>
                      <span class="badge" data-ta="செயலில்" data-en="Enabled">செயலில்</span>
                    <?php else: ?>
                      <span class="badge off" data-ta="நிறுத்தப்பட்டது" data-en="Disabled">நிறுத்தப்பட்டது</span>
                    <?php endif; ?>
                  </td>
                  <td class="row-actions">
                    <button type="button" class="btn btn-ghost btn-small" data-open="<?= h($userKey) ?>-roles" aria-haspopup="dialog"><span data-ta="பொறுப்புகள்" data-en="Roles">பொறுப்புகள்</span></button>
                    <button type="button" class="btn btn-ghost btn-small" data-open="<?= h($userKey) ?>-password" aria-haspopup="dialog"><span data-ta="கடவுச்சொல்" data-en="Password">கடவுச்சொல்</span></button>
                    <button type="button" class="btn btn-ghost btn-small" data-open="<?= h($userKey) ?>-state" aria-haspopup="dialog"><span data-ta="<?= !empty($account["enabled"]) ? "நிறுத்து" : "இயக்கு" ?>" data-en="<?= !empty($account["enabled"]) ? "Disable" : "Enable" ?>"><?= !empty($account["enabled"]) ? "நிறுத்து" : "இயக்கு" ?></span></button>
                    <button type="button" class="btn btn-ghost btn-small" data-open="<?= h($userKey) ?>-remove" aria-haspopup="dialog"><span data-ta="நீக்கு" data-en="Remove">நீக்கு</span></button>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php foreach ($accounts as $accountIndex => $account):
            $userKey = "user-" . ($accountIndex + 1);
          ?>
          <dialog class="sheet" id="<?= h($userKey) ?>-roles" aria-labelledby="<?= h($userKey) ?>-roles-title">
            <form method="post">
              <div class="sheet-head">
                <h3 id="<?= h($userKey) ?>-roles-title"><?= h($account["username"]) ?></h3>
                <button type="button" class="icon-btn" data-close data-label-ta="மூடு" data-label-en="Close" aria-label="மூடு">×</button>
              </div>
              <div class="sheet-body">
                <input type="hidden" name="csrf" value="<?= $token ?>">
                <input type="hidden" name="user_id" value="<?= h($account["id"]) ?>">
                <fieldset class="roles">
                  <legend data-ta="பொறுப்புகள்" data-en="Roles">பொறுப்புகள்</legend>
                  <?php role_choices($account["roles"]); ?>
                </fieldset>
                <button class="btn btn-primary" name="save_roles" value="1" type="submit"><span data-ta="பொறுப்புகளைச் சேமி" data-en="Save roles">பொறுப்புகளைச் சேமி</span></button>
              </div>
            </form>
          </dialog>
          <dialog class="sheet" id="<?= h($userKey) ?>-password" aria-labelledby="<?= h($userKey) ?>-password-title">
            <form method="post">
              <div class="sheet-head">
                <h3 id="<?= h($userKey) ?>-password-title" data-ta="கடவுச்சொல்லை அமை" data-en="Set password">கடவுச்சொல்லை அமை</h3>
                <button type="button" class="icon-btn" data-close data-label-ta="மூடு" data-label-en="Close" aria-label="மூடு">×</button>
              </div>
              <div class="sheet-body">
                <input type="hidden" name="csrf" value="<?= $token ?>">
                <input type="hidden" name="user_id" value="<?= h($account["id"]) ?>">
                <p class="hint"><?= h($account["username"]) ?></p>
                <div class="form-grid">
                  <label class="field"><span data-ta="புதிய கடவுச்சொல்" data-en="New password">புதிய கடவுச்சொல்</span><input type="password" name="password" required minlength="8" autocomplete="new-password"></label>
                  <label class="field"><span data-ta="மீண்டும்" data-en="Repeat password">மீண்டும்</span><input type="password" name="again" required minlength="8" autocomplete="new-password"></label>
                </div>
                <button class="btn btn-primary" name="reset_password" value="1" type="submit"><span data-ta="கடவுச்சொல்லை அமை" data-en="Set password">கடவுச்சொல்லை அமை</span></button>
              </div>
            </form>
          </dialog>
          <dialog class="sheet" id="<?= h($userKey) ?>-state" aria-labelledby="<?= h($userKey) ?>-state-title">
            <form method="post">
              <div class="sheet-head">
                <h3 id="<?= h($userKey) ?>-state-title"><?= h($account["username"]) ?></h3>
                <button type="button" class="icon-btn" data-close data-label-ta="மூடு" data-label-en="Close" aria-label="மூடு">×</button>
              </div>
              <div class="sheet-body">
                <input type="hidden" name="csrf" value="<?= $token ?>">
                <input type="hidden" name="user_id" value="<?= h($account["id"]) ?>">
                <?php if (!empty($account["enabled"])): ?>
                  <p class="hint" data-ta="இந்தக் கணக்கு அடுத்த கோரிக்கையில் நிறுத்தப்படும்." data-en="This account will be disabled on its next request.">இந்தக் கணக்கு அடுத்த கோரிக்கையில் நிறுத்தப்படும்.</p>
                  <button class="btn btn-primary" name="disable_user" value="1" type="submit"><span data-ta="நிறுத்து" data-en="Disable">நிறுத்து</span></button>
                <?php else: ?>
                  <p class="hint" data-ta="இந்தக் கணக்கை மீண்டும் இயக்கலாம்." data-en="This account can be enabled again.">இந்தக் கணக்கை மீண்டும் இயக்கலாம்.</p>
                  <button class="btn btn-primary" name="enable_user" value="1" type="submit"><span data-ta="இயக்கு" data-en="Enable">இயக்கு</span></button>
                <?php endif; ?>
              </div>
            </form>
          </dialog>
          <dialog class="sheet" id="<?= h($userKey) ?>-remove" aria-labelledby="<?= h($userKey) ?>-remove-title">
            <form method="post">
              <div class="sheet-head">
                <h3 id="<?= h($userKey) ?>-remove-title" data-ta="கணக்கை நீக்கு" data-en="Remove account">கணக்கை நீக்கு</h3>
                <button type="button" class="icon-btn" data-close data-label-ta="மூடு" data-label-en="Close" aria-label="மூடு">×</button>
              </div>
              <div class="sheet-body">
                <input type="hidden" name="csrf" value="<?= $token ?>">
                <input type="hidden" name="user_id" value="<?= h($account["id"]) ?>">
                <p class="hint"><?= h($account["username"]) ?></p>
                <button class="btn btn-ghost" name="delete_user" value="1" type="submit"><span data-ta="நீக்கு" data-en="Remove">நீக்கு</span></button>
              </div>
            </form>
          </dialog>
          <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </section>
      <?php endif; ?>
      </div>
    </div>
  </main>
<?php endif; ?>
  <script>
    (function () {
      function preferred() {
        try {
          return localStorage.getItem("hm-lang") === "en" ? "en" : "ta";
        } catch (error) {
          return "ta";
        }
      }
      function activeView() {
        var id = (location.hash || "").replace(/^#/, "");
        var view = id ? document.getElementById(id) : null;
        if (!view || !view.classList.contains("desk-view")) view = document.getElementById("dashboard");
        return view;
      }
      function apply(code) {
        var root = document.documentElement;
        root.lang = code;
        document.querySelectorAll("[data-ta]").forEach(function (el) {
          if (el.children.length) return;
          var text = el.getAttribute(code === "en" ? "data-en" : "data-ta");
          if (text !== null) el.textContent = text;
        });
        document.querySelectorAll("[data-label-ta]").forEach(function (el) {
          el.setAttribute("aria-label", el.getAttribute(code === "en" ? "data-label-en" : "data-label-ta") || "");
        });
        document.querySelectorAll("[data-set-lang]").forEach(function (button) {
          var on = button.getAttribute("data-set-lang") === code;
          button.classList.toggle("is-on", on);
          button.setAttribute("aria-pressed", on ? "true" : "false");
        });
        var view = activeView();
        var title = view ? view.getAttribute(code === "en" ? "data-title-en" : "data-title-ta") : "";
        if (!title) title = root.getAttribute(code === "en" ? "data-title-en" : "data-title-ta");
        if (title) document.title = title;
      }
      function markNav() {
        var view = activeView();
        var id = view ? view.id : "";
        document.querySelectorAll(".side-link").forEach(function (link) {
          if (link.getAttribute("href") === "#" + id) link.setAttribute("aria-current", "page");
          else link.removeAttribute("aria-current");
        });
      }
      function syncView() {
        markNav();
        apply(document.documentElement.lang === "en" ? "en" : "ta");
      }
      document.querySelectorAll("[data-set-lang]").forEach(function (button) {
        button.addEventListener("click", function () {
          var code = button.getAttribute("data-set-lang") === "en" ? "en" : "ta";
          try { localStorage.setItem("hm-lang", code); } catch (error) {}
          apply(code);
        });
      });
      document.querySelectorAll(".desk-view form").forEach(function (form) {
        var view = form.closest(".desk-view");
        if (view && view.id) form.setAttribute("action", "#" + view.id);
      });
      var dialogOpener = null;
      document.querySelectorAll("[data-open]").forEach(function (button) {
        button.addEventListener("click", function () {
          var dialog = document.getElementById(button.getAttribute("data-open"));
          if (!dialog || typeof dialog.showModal !== "function") return;
          dialogOpener = button;
          var field = dialog.querySelector("input:not([type=hidden]), textarea, select");
          dialog.showModal();
          if (field) field.focus();
          window.setTimeout(function () {
            if (dialog.open && field) field.focus();
          }, 0);
        });
      });
      document.querySelectorAll("dialog").forEach(function (dialog) {
        dialog.addEventListener("close", function () {
          if (dialogOpener) dialogOpener.focus();
        });
        dialog.addEventListener("click", function (event) {
          if (event.target === dialog) dialog.close();
        });
        dialog.querySelectorAll("[data-close]").forEach(function (button) {
          button.addEventListener("click", function () { dialog.close(); });
        });
      });
      var poojaSearch = document.getElementById("pooja-search");
      if (poojaSearch) {
        poojaSearch.addEventListener("input", function () {
          var query = poojaSearch.value.trim().toLowerCase();
          var shown = 0;
          document.querySelectorAll("#pooja-table tbody tr").forEach(function (row) {
            var hay = (row.getAttribute("data-find") || "").toLowerCase();
            var match = query === "" || hay.indexOf(query) !== -1;
            row.hidden = !match;
            if (match) shown++;
          });
          var empty = document.getElementById("pooja-empty");
          if (empty) empty.hidden = query === "" || shown !== 0;
        });
      }
      var menuButton = document.querySelector("[data-menu]");
      var sidebar = document.getElementById("desk-sidebar");
      function setMenu(open) {
        if (!menuButton) return;
        document.documentElement.classList.toggle("is-menu", open);
        menuButton.setAttribute("aria-expanded", open ? "true" : "false");
      }
      if (menuButton && sidebar) {
        menuButton.addEventListener("click", function () {
          var open = menuButton.getAttribute("aria-expanded") !== "true";
          setMenu(open);
          if (open) {
            var first = sidebar.querySelector(".side-link");
            if (first) first.focus();
          }
        });
        document.querySelectorAll("[data-close-menu]").forEach(function (button) {
          button.addEventListener("click", function () {
            setMenu(false);
            menuButton.focus();
          });
        });
        sidebar.querySelectorAll("a").forEach(function (link) {
          link.addEventListener("click", function () {
            if (!window.matchMedia("(max-width: 800px)").matches) return;
            setMenu(false);
            var id = (link.getAttribute("href") || "").replace("#", "");
            var view = document.getElementById(id);
            var heading = view ? view.querySelector("h1, h2") : null;
            if (heading) {
              heading.setAttribute("tabindex", "-1");
              heading.focus();
            }
          });
        });
        document.addEventListener("keydown", function (event) {
          if (event.key !== "Escape") return;
          var openDialog = document.querySelector("dialog[open]");
          if (openDialog) {
            openDialog.close();
            return;
          }
          if (menuButton.getAttribute("aria-expanded") !== "true") return;
          setMenu(false);
          menuButton.focus();
        });
        window.addEventListener("resize", function () {
          if (window.matchMedia("(min-width: 801px)").matches) setMenu(false);
        });
      }
      window.addEventListener("hashchange", syncView);
      window.addEventListener("load", syncView);
      apply(preferred());
      markNav();
    })();
  </script>
</body>
</html>
