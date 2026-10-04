<?php
session_start([
  "cookie_httponly" => true,
  "cookie_samesite" => "Lax",
  "use_strict_mode" => true
]);

const DEFAULTS = [
  ["jan", "2026-01-30", "மாதாந்த பூஜை", "ஈஸ்வரன் குடும்பத்தினர்", "ganesha", false],
  ["shivaratri", "2026-02-15", "மஹாசிவராத்திரி", "இந்து மாமன்றம் - பொது உபயம்", "shiva", true],
  ["feb", "2026-02-27", "மாதாந்த பூஜை", "ஐசிந்தா - காஞ்சனா குடும்பத்தினர்", "ganesha", false],
  ["mar", "2026-03-27", "மாதாந்த பூஜை", "நந்தரூபன் - கிரிதரன் குடும்பத்தினர்", "ganesha", false],
  ["apr", "2026-04-24", "மாதாந்த பூஜை", "ராமாஸ் குடும்பத்தினர்", "ganesha", false],
  ["may", "2026-05-29", "மாதாந்த பூஜை", "மணிவாசகன் - ஸ்ரீதரன் குடும்பத்தினர்", "ganesha", false],
  ["jun", "2026-06-12", "மாதாந்த பூஜை", "இந்திய சைவக் குடும்பங்கள்", "ganesha", false],
  ["varalakshmi", "2026-08-21", "வரலட்சுமி பூஜை", "இந்து மாமன்றம் - பொது உபயம்", "lakshmi", true],
  ["aug", "2026-08-28", "மாதாந்த பூஜை", "இலெம்போதரன் குடும்பத்தினர்", "ganesha", false],
  ["sep", "2026-09-25", "மாதாந்த பூஜை", "மகேந்திரன் குடும்பத்தினர்", "ganesha", false],
  ["mahalaya", "2026-10-10", "மஹாளயபக்ஷ பூஜை", "அடியார்கள் உபயம்", "mahalaya", true],
  ["saraswati", "2026-10-16", "சரஸ்வதி பூஜை", "இந்து மாமன்றம் - பொது உபயம்", "saraswati", true],
  ["oct", "2026-10-30", "மாதாந்த பூஜை", "சிவா சண்முகம் குடும்பத்தினர்", "ganesha", false],
  ["nov", "2026-11-20", "மாதாந்த பூஜை", "பொன் சிவா குடும்பத்தினர்", "ganesha", false],
  ["dec", "2026-12-18", "மாதாந்த பூஜை", "திருலோகநாதன் குடும்பத்தினர்", "ganesha", false]
];

const DEITIES = [
  "ganesha" => "விநாயகர்",
  "shiva" => "சிவன்",
  "lakshmi" => "வரலட்சுமி",
  "saraswati" => "சரஸ்வதி",
  "mahalaya" => "மஹாளய விளக்கு"
];

function data_dir() {
  return dirname(__DIR__) . "/data";
}

function site_path() {
  return data_dir() . "/site.json";
}

function auth_path() {
  return data_dir() . "/auth.json";
}

function h($value) {
  return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
}

function clip($value, $max) {
  if (function_exists("mb_substr")) return mb_substr($value, 0, $max, "UTF-8");
  return substr($value, 0, $max);
}

function clean_line($value, $max) {
  $value = trim(preg_replace("/\s+/u", " ", strip_tags((string) $value)));
  return clip($value, $max);
}

function clean_text($value, $max) {
  $value = str_replace(["\r\n", "\r"], "\n", strip_tags((string) $value));
  $value = trim(preg_replace("/\n{3,}/", "\n\n", $value));
  return clip($value, $max);
}

function clean_date($value) {
  if (!preg_match("/^(\d{4})-(\d{2})-(\d{2})$/", trim((string) $value), $match)) return "";
  if (!checkdate((int) $match[2], (int) $match[3], (int) $match[1])) return "";
  return $match[1] . "-" . $match[2] . "-" . $match[3];
}

function clean_id($value) {
  $value = strtolower(trim((string) $value));
  return preg_match("/^[a-z0-9_-]{1,40}$/", $value) ? $value : "";
}

function empty_site() {
  return ["notices" => [], "pooja" => [], "removed" => [], "extra" => []];
}

function load_site() {
  if (!is_file(site_path())) return empty_site();
  $data = json_decode((string) file_get_contents(site_path()), true);
  if (!is_array($data)) return empty_site();
  foreach (["notices", "removed", "extra"] as $key) {
    if (!isset($data[$key]) || !is_array($data[$key])) $data[$key] = [];
  }
  if (!isset($data["pooja"]) || !is_array($data["pooja"])) $data["pooja"] = [];
  return $data;
}

function save_site($data) {
  if (!is_dir(data_dir()) && !mkdir(data_dir(), 0755, true)) return false;
  $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
  if ($json === false) return false;
  return file_put_contents(site_path(), $json . "\n", LOCK_EX) !== false;
}

function auth_hash() {
  if (!is_file(auth_path())) return "";
  $data = json_decode((string) file_get_contents(auth_path()), true);
  return is_array($data) && isset($data["hash"]) ? (string) $data["hash"] : "";
}

function save_hash($hash) {
  if (!is_dir(data_dir()) && !mkdir(data_dir(), 0755, true)) return false;
  $json = json_encode(["hash" => $hash], JSON_UNESCAPED_UNICODE);
  return file_put_contents(auth_path(), $json . "\n", LOCK_EX) !== false;
}

function csrf() {
  if (empty($_SESSION["csrf"])) $_SESSION["csrf"] = bin2hex(random_bytes(16));
  return $_SESSION["csrf"];
}

function csrf_ok() {
  return isset($_POST["csrf"]) && hash_equals(csrf(), (string) $_POST["csrf"]);
}

function logged_in() {
  return !empty($_SESSION["hm_admin"]);
}

function default_by_id($id) {
  foreach (DEFAULTS as $row) {
    if ($row[0] === $id) return $row;
  }
  return null;
}

if (empty($_SESSION["fails"])) $_SESSION["fails"] = 0;
$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  if (!csrf_ok()) {
    $error = "பக்கம் பழையது. மீண்டும் முயற்சிக்கவும்.";
  } elseif (isset($_POST["setup"])) {
    $password = (string) ($_POST["password"] ?? "");
    $again = (string) ($_POST["again"] ?? "");
    if (auth_hash() !== "") {
      $error = "கடவுச்சொல் ஏற்கெனவே உள்ளது.";
    } elseif (strlen($password) < 8) {
      $error = "கடவுச்சொல் குறைந்தது 8 எழுத்துகள் வேண்டும்.";
    } elseif ($password !== $again) {
      $error = "இரண்டு கடவுச்சொற்களும் ஒன்றாக இல்லை.";
    } elseif (!save_hash(password_hash($password, PASSWORD_DEFAULT))) {
      $error = "கடவுச்சொல்லைச் சேமிக்க முடியவில்லை.";
    } else {
      session_regenerate_id(true);
      $_SESSION["hm_admin"] = true;
      $message = "கடவுச்சொல் சேமிக்கப்பட்டது. இதைக் குழுவினருடன் பகிருங்கள்.";
    }
  } elseif (isset($_POST["login"])) {
    if ($_SESSION["fails"] >= 8) {
      $error = "பல முறை தவறானது. சிறிது நேரம் கழித்து முயற்சிக்கவும்.";
    } elseif (!password_verify((string) ($_POST["password"] ?? ""), auth_hash())) {
      $_SESSION["fails"]++;
      $error = "கடவுச்சொல் தவறு.";
    } else {
      session_regenerate_id(true);
      $_SESSION["hm_admin"] = true;
      $_SESSION["fails"] = 0;
    }
  } elseif (!logged_in()) {
    $error = "முதலில் நுழையுங்கள்.";
  } elseif (isset($_POST["logout"])) {
    $_SESSION = [];
    session_destroy();
    header("Location: ./");
    exit;
  } elseif (isset($_POST["password_change"])) {
    $current = (string) ($_POST["current"] ?? "");
    $password = (string) ($_POST["password"] ?? "");
    $again = (string) ($_POST["again"] ?? "");
    if (!password_verify($current, auth_hash())) {
      $error = "இப்போதைய கடவுச்சொல் தவறு.";
    } elseif (strlen($password) < 8 || $password !== $again) {
      $error = "புதிய கடவுச்சொல் 8 எழுத்துகள், இரண்டும் ஒன்றாக இருக்க வேண்டும்.";
    } elseif (save_hash(password_hash($password, PASSWORD_DEFAULT))) {
      $message = "கடவுச்சொல் மாற்றப்பட்டது.";
    } else {
      $error = "கடவுச்சொல்லைச் சேமிக்க முடியவில்லை.";
    }
  } else {
    $site = load_site();
    if (isset($_POST["save_notice"])) {
      $title = clean_line($_POST["title_ta"] ?? "", 140);
      $body = clean_text($_POST["body_ta"] ?? "", 2000);
      $id = clean_id($_POST["notice_id"] ?? "");
      if ($title === "" || $body === "") {
        $error = "தலைப்பும் செய்தியும் வேண்டும்.";
      } else {
        $notice = [
          "id" => $id !== "" ? $id : bin2hex(random_bytes(4)),
          "titleTa" => $title,
          "titleEn" => clean_line($_POST["title_en"] ?? "", 140),
          "bodyTa" => $body,
          "bodyEn" => clean_text($_POST["body_en"] ?? "", 2000),
          "date" => clean_date($_POST["notice_date"] ?? "")
        ];
        $found = false;
        foreach ($site["notices"] as $index => $item) {
          if (($item["id"] ?? "") === $notice["id"]) {
            $site["notices"][$index] = $notice;
            $found = true;
          }
        }
        if (!$found) {
          if (count($site["notices"]) >= 40) {
            $error = "அறிவிப்புகள் 40 வரை.";
          } else {
            array_unshift($site["notices"], $notice);
          }
        }
        if ($error === "" && save_site($site)) $message = "அறிவிப்பு வெளியிடப்பட்டது.";
        elseif ($error === "") $error = "சேமிக்க முடியவில்லை.";
      }
    } elseif (isset($_POST["delete_notice"])) {
      $id = clean_id($_POST["notice_id"] ?? "");
      $site["notices"] = array_values(array_filter($site["notices"], function ($item) use ($id) {
        return ($item["id"] ?? "") !== $id;
      }));
      $message = save_site($site) ? "அறிவிப்பு நீக்கப்பட்டது." : "சேமிக்க முடியவில்லை.";
      if ($message === "சேமிக்க முடியவில்லை.") { $error = $message; $message = ""; }
    } elseif (isset($_POST["save_pooja"])) {
      $id = clean_id($_POST["pooja_id"] ?? "");
      $date = clean_date($_POST["date"] ?? "");
      $name = clean_line($_POST["name"] ?? "", 80);
      $base = default_by_id($id);
      if (!$base || $date === "" || $name === "") {
        $error = "நாள் மற்றும் பூஜைப் பெயர் வேண்டும்.";
      } else {
        $site["pooja"][$id] = [
          "date" => $date,
          "name" => $name,
          "sponsor" => clean_line($_POST["sponsor"] ?? "", 120)
        ];
        $site["removed"] = array_values(array_filter($site["removed"], function ($item) use ($id) {
          return $item !== $id;
        }));
        $message = save_site($site) ? "பூஜை நாள் சேமிக்கப்பட்டது." : "சேமிக்க முடியவில்லை.";
        if ($message === "சேமிக்க முடியவில்லை.") { $error = $message; $message = ""; }
      }
    } elseif (isset($_POST["hide_pooja"])) {
      $id = clean_id($_POST["pooja_id"] ?? "");
      if (default_by_id($id) && !in_array($id, $site["removed"], true)) $site["removed"][] = $id;
      $message = save_site($site) ? "பூஜை நாள் மறைக்கப்பட்டது." : "சேமிக்க முடியவில்லை.";
      if ($message === "சேமிக்க முடியவில்லை.") { $error = $message; $message = ""; }
    } elseif (isset($_POST["restore_pooja"])) {
      $id = clean_id($_POST["pooja_id"] ?? "");
      $site["removed"] = array_values(array_filter($site["removed"], function ($item) use ($id) {
        return $item !== $id;
      }));
      $message = save_site($site) ? "பூஜை நாள் மீண்டும் காட்டப்படும்." : "சேமிக்க முடியவில்லை.";
      if ($message === "சேமிக்க முடியவில்லை.") { $error = $message; $message = ""; }
    } elseif (isset($_POST["add_pooja"])) {
      $date = clean_date($_POST["date"] ?? "");
      $name = clean_line($_POST["name"] ?? "", 80);
      $deity = array_key_exists($_POST["deity"] ?? "", DEITIES) ? $_POST["deity"] : "ganesha";
      if ($date === "" || $name === "") {
        $error = "புதிய பூஜைக்கு நாள் மற்றும் பெயர் வேண்டும்.";
      } elseif (count($site["extra"]) >= 30) {
        $error = "புதிய பூஜைகள் 30 வரை.";
      } else {
        $site["extra"][] = [
          "id" => "x" . bin2hex(random_bytes(4)),
          "date" => $date,
          "name" => $name,
          "sponsor" => clean_line($_POST["sponsor"] ?? "", 120),
          "deity" => $deity,
          "common" => isset($_POST["common"]),
          "special" => isset($_POST["common"])
        ];
        $message = save_site($site) ? "புதிய பூஜை சேர்க்கப்பட்டது." : "சேமிக்க முடியவில்லை.";
        if ($message === "சேமிக்க முடியவில்லை.") { $error = $message; $message = ""; }
      }
    } elseif (isset($_POST["save_extra"])) {
      $id = clean_id($_POST["pooja_id"] ?? "");
      $date = clean_date($_POST["date"] ?? "");
      $name = clean_line($_POST["name"] ?? "", 80);
      $deity = array_key_exists($_POST["deity"] ?? "", DEITIES) ? $_POST["deity"] : "ganesha";
      $updated = false;
      if ($date === "" || $name === "") {
        $error = "நாள் மற்றும் பூஜைப் பெயர் வேண்டும்.";
      } else {
        foreach ($site["extra"] as $index => $item) {
          if (($item["id"] ?? "") === $id) {
            $site["extra"][$index] = [
              "id" => $id,
              "date" => $date,
              "name" => $name,
              "sponsor" => clean_line($_POST["sponsor"] ?? "", 120),
              "deity" => $deity,
              "common" => isset($_POST["common"]),
              "special" => isset($_POST["common"])
            ];
            $updated = true;
          }
        }
        if (!$updated) $error = "இந்தப் பூஜையைக் காணவில்லை.";
        elseif (save_site($site)) $message = "பூஜை நாள் சேமிக்கப்பட்டது.";
        else $error = "சேமிக்க முடியவில்லை.";
      }
    } elseif (isset($_POST["delete_extra"])) {
      $id = clean_id($_POST["pooja_id"] ?? "");
      $site["extra"] = array_values(array_filter($site["extra"], function ($item) use ($id) {
        return ($item["id"] ?? "") !== $id;
      }));
      unset($site["pooja"][$id]);
      $message = save_site($site) ? "பூஜை நீக்கப்பட்டது." : "சேமிக்க முடியவில்லை.";
      if ($message === "சேமிக்க முடியவில்லை.") { $error = $message; $message = ""; }
    }
  }
}

$needsSetup = auth_hash() === "";
$site = load_site();
$token = h(csrf());
header("X-Robots-Tag: noindex");
?>
<!DOCTYPE html>
<html lang="ta">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex">
  <title>குழு பதிவு — இந்து மாமன்றம்</title>
  <link rel="icon" href="../assets/favicon.svg" type="image/svg+xml">
  <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
  <main class="wrap page-body" style="padding-top:32px">
    <p class="kicker">நிர்வாகம்</p>
    <h1>குழு பதிவு</h1>
    <p class="lede">இங்கே சேமித்த அறிவிப்பும் பூஜை நாளும் mamanram.no-வில் அனைவருக்கும் தெரியும். குழுவினர் இதே பக்கத்திலும் ஒரே கடவுச்சொல்லிலும் நுழையலாம்.</p>
    <?php if ($message !== ""): ?><p class="note"><?= h($message) ?></p><?php endif; ?>
    <?php if ($error !== ""): ?><p class="note"><?= h($error) ?></p><?php endif; ?>

    <?php if ($needsSetup): ?>
      <form class="admin-form" method="post">
        <input type="hidden" name="csrf" value="<?= $token ?>">
        <h2>முதல் கடவுச்சொல்</h2>
        <label>கடவுச்சொல் <input type="password" name="password" required minlength="8" autocomplete="new-password"></label>
        <label>மீண்டும் <input type="password" name="again" required minlength="8" autocomplete="new-password"></label>
        <button class="btn btn-primary" name="setup" value="1" type="submit">சேமி</button>
      </form>
    <?php elseif (!logged_in()): ?>
      <form class="admin-form" method="post">
        <input type="hidden" name="csrf" value="<?= $token ?>">
        <label>கடவுச்சொல் <input type="password" name="password" required autocomplete="current-password"></label>
        <button class="btn btn-primary" name="login" value="1" type="submit">நுழை</button>
      </form>
    <?php else: ?>
      <form method="post"><input type="hidden" name="csrf" value="<?= $token ?>"><button class="btn btn-ghost" name="logout" value="1" type="submit">வெளியேறு</button></form>

      <section class="desk-block">
        <h2>புதிய அறிவிப்பு</h2>
        <form class="admin-form" method="post">
          <input type="hidden" name="csrf" value="<?= $token ?>">
          <label>தலைப்பு <input name="title_ta" required maxlength="140"></label>
          <label>English title <input name="title_en" maxlength="140"></label>
          <label>செய்தி <textarea name="body_ta" required maxlength="2000" rows="4"></textarea></label>
          <label>English text <textarea name="body_en" maxlength="2000" rows="3"></textarea></label>
          <label>நாள் <input type="date" name="notice_date"></label>
          <button class="btn btn-primary" name="save_notice" value="1" type="submit">வெளியிடு</button>
        </form>
        <?php foreach ($site["notices"] as $notice): ?>
          <form class="admin-form" method="post">
            <input type="hidden" name="csrf" value="<?= $token ?>">
            <input type="hidden" name="notice_id" value="<?= h($notice["id"] ?? "") ?>">
            <label>தலைப்பு <input name="title_ta" required maxlength="140" value="<?= h($notice["titleTa"] ?? "") ?>"></label>
            <label>English title <input name="title_en" maxlength="140" value="<?= h($notice["titleEn"] ?? "") ?>"></label>
            <label>செய்தி <textarea name="body_ta" required maxlength="2000" rows="4"><?= h($notice["bodyTa"] ?? "") ?></textarea></label>
            <label>English text <textarea name="body_en" maxlength="2000" rows="3"><?= h($notice["bodyEn"] ?? "") ?></textarea></label>
            <label>நாள் <input type="date" name="notice_date" value="<?= h($notice["date"] ?? "") ?>"></label>
            <div class="actions">
              <button class="btn btn-primary" name="save_notice" value="1" type="submit">சேமி</button>
              <button class="btn btn-ghost" name="delete_notice" value="1" type="submit">நீக்கு</button>
            </div>
          </form>
        <?php endforeach; ?>
      </section>

      <section class="desk-block">
        <h2>பூஜை நாட்கள்</h2>
        <?php foreach (DEFAULTS as $row):
          $id = $row[0];
          $over = $site["pooja"][$id] ?? [];
          $hidden = in_array($id, $site["removed"], true);
          $date = $over["date"] ?? $row[1];
          $name = $over["name"] ?? $row[2];
          $sponsor = array_key_exists("sponsor", $over) ? $over["sponsor"] : $row[3];
        ?>
          <form class="admin-form" method="post">
            <input type="hidden" name="csrf" value="<?= $token ?>">
            <input type="hidden" name="pooja_id" value="<?= h($id) ?>">
            <label>நாள் <input type="date" name="date" required value="<?= h($date) ?>"></label>
            <label>பூஜை <input name="name" required maxlength="80" value="<?= h($name) ?>"></label>
            <label>உபயதாரர் <input name="sponsor" maxlength="120" value="<?= h($sponsor) ?>"></label>
            <div class="actions">
              <?php if ($hidden): ?>
                <button class="btn btn-primary" name="restore_pooja" value="1" type="submit">மீண்டும் காட்டு</button>
              <?php else: ?>
                <button class="btn btn-primary" name="save_pooja" value="1" type="submit">சேமி</button>
                <button class="btn btn-ghost" name="hide_pooja" value="1" type="submit">மறை</button>
              <?php endif; ?>
            </div>
          </form>
        <?php endforeach; ?>

        <?php foreach ($site["extra"] as $extra): ?>
          <form class="admin-form" method="post">
            <input type="hidden" name="csrf" value="<?= $token ?>">
            <input type="hidden" name="pooja_id" value="<?= h($extra["id"] ?? "") ?>">
            <label>நாள் <input type="date" name="date" required value="<?= h($extra["date"] ?? "") ?>"></label>
            <label>பூஜை <input name="name" required maxlength="80" value="<?= h($extra["name"] ?? "") ?>"></label>
            <label>உபயதாரர் <input name="sponsor" maxlength="120" value="<?= h($extra["sponsor"] ?? "") ?>"></label>
            <label>இறைவன்
              <select name="deity">
                <?php foreach (DEITIES as $key => $label): ?>
                  <option value="<?= h($key) ?>"<?= ($extra["deity"] ?? "") === $key ? " selected" : "" ?>><?= h($label) ?></option>
                <?php endforeach; ?>
              </select>
            </label>
            <label><input type="checkbox" name="common" value="1"<?= !empty($extra["common"]) ? " checked" : "" ?>> பொதுப் பூஜை (பிரசாதம் மற்றும் அஞ்சல்)</label>
            <div class="actions">
              <button class="btn btn-primary" name="save_extra" value="1" type="submit">சேமி</button>
              <button class="btn btn-ghost" name="delete_extra" value="1" type="submit">நீக்கு</button>
            </div>
          </form>
        <?php endforeach; ?>

        <h2>புதிய பூஜை</h2>
        <form class="admin-form" method="post">
          <input type="hidden" name="csrf" value="<?= $token ?>">
          <label>நாள் <input type="date" name="date" required></label>
          <label>பூஜை <input name="name" required maxlength="80"></label>
          <label>உபயதாரர் <input name="sponsor" maxlength="120"></label>
          <label>இறைவன்
            <select name="deity">
              <?php foreach (DEITIES as $key => $label): ?>
                <option value="<?= h($key) ?>"><?= h($label) ?></option>
              <?php endforeach; ?>
            </select>
          </label>
          <label><input type="checkbox" name="common" value="1"> பொதுப் பூஜை (பிரசாதம் மற்றும் அஞ்சல்)</label>
          <button class="btn btn-primary" name="add_pooja" value="1" type="submit">சேர்</button>
        </form>
      </section>

      <section class="desk-block">
        <h2>கடவுச்சொல்லை மாற்று</h2>
        <form class="admin-form" method="post">
          <input type="hidden" name="csrf" value="<?= $token ?>">
          <label>இப்போதைய கடவுச்சொல் <input type="password" name="current" required autocomplete="current-password"></label>
          <label>புதிய கடவுச்சொல் <input type="password" name="password" required minlength="8" autocomplete="new-password"></label>
          <label>மீண்டும் <input type="password" name="again" required minlength="8" autocomplete="new-password"></label>
          <button class="btn btn-ghost" name="password_change" value="1" type="submit">மாற்று</button>
        </form>
      </section>
    <?php endif; ?>
  </main>
</body>
</html>
