<?php

require_once __DIR__ . "/auth.php";

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

function hm_clip(string $value, int $max): string {
  if (function_exists("mb_substr")) return mb_substr($value, 0, $max, "UTF-8");
  return substr($value, 0, $max);
}

function hm_clean_line($value, int $max): string {
  $value = trim(preg_replace("/\s+/u", " ", strip_tags((string) $value)));
  return hm_clip($value, $max);
}

function hm_clean_text($value, int $max): string {
  $value = str_replace(["\r\n", "\r"], "\n", strip_tags((string) $value));
  $value = trim(preg_replace("/\n{3,}/", "\n\n", $value));
  return hm_clip($value, $max);
}

function hm_clean_date($value): string {
  if (!preg_match("/^(\d{4})-(\d{2})-(\d{2})$/", trim((string) $value), $match)) return "";
  if (!checkdate((int) $match[2], (int) $match[3], (int) $match[1])) return "";
  return $match[1] . "-" . $match[2] . "-" . $match[3];
}

function hm_clean_id($value): string {
  $value = strtolower(trim((string) $value));
  return preg_match("/^[a-z0-9_-]{1,40}$/", $value) ? $value : "";
}

function hm_empty_site(): array {
  return ["notices" => [], "pooja" => [], "removed" => [], "extra" => []];
}

function hm_site_path(): string {
  return hm_data_dir() . DIRECTORY_SEPARATOR . "site.json";
}

function hm_load_site(): array {
  if (!is_file(hm_site_path())) return hm_empty_site();
  $data = json_decode((string) file_get_contents(hm_site_path()), true);
  if (!is_array($data)) return hm_empty_site();
  foreach (["notices", "removed", "extra"] as $key) {
    if (!isset($data[$key]) || !is_array($data[$key])) $data[$key] = [];
  }
  if (!isset($data["pooja"]) || !is_array($data["pooja"])) $data["pooja"] = [];
  unset($data["users"], $data["contacts"], $data["hash"]);
  return $data;
}

function hm_save_site(array $data): bool {
  unset($data["users"], $data["contacts"], $data["hash"]);
  $dir = hm_data_dir();
  if (!is_dir($dir) && !mkdir($dir, 0755, true)) return false;
  $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
  if ($json === false) return false;
  return file_put_contents(hm_site_path(), $json . "\n", LOCK_EX) !== false;
}

function hm_default_by_id(string $id): ?array {
  foreach (DEFAULTS as $row) {
    if ($row[0] === $id) return $row;
  }
  return null;
}

function hm_publish_request(array $post): array {
  if (!hm_can_publish()) return ["message" => "", "error" => "forbidden"];
  $site = hm_load_site();
  $message = "";
  $error = "";
  if (isset($post["save_notice"])) {
    $title = hm_clean_line($post["title_ta"] ?? "", 140);
    $body = hm_clean_text($post["body_ta"] ?? "", 2000);
    $language = (string) ($post["notice_language"] ?? "ta");
    $englishTitle = hm_clean_line($post["title_en"] ?? "", 140);
    $englishBody = hm_clean_text($post["body_en"] ?? "", 2000);
    // The public website uses Tamil fields as its default fallback.
    // For English-only notices, duplicate the supplied English text into that fallback.
    if ($language === "en") {
      $englishTitle = $title;
      $englishBody = $body;
    }
    $id = hm_clean_id($post["notice_id"] ?? "");
    if ($title === "" || $body === "") {
      $error = "need_notice";
    } else {
      $notice = [
        "id" => $id !== "" ? $id : bin2hex(random_bytes(4)),
        "titleTa" => $title,
        "titleEn" => $englishTitle,
        "bodyTa" => $body,
        "bodyEn" => $englishBody,
        "date" => hm_clean_date($post["notice_date"] ?? "")
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
          $error = "notice_limit";
        } else {
          array_unshift($site["notices"], $notice);
        }
      }
      if ($error === "" && hm_save_site($site)) $message = "notice_ok";
      elseif ($error === "") $error = "store_fail";
    }
  } elseif (isset($post["delete_notice"])) {
    $id = hm_clean_id($post["notice_id"] ?? "");
    $site["notices"] = array_values(array_filter($site["notices"], function ($item) use ($id) {
      return ($item["id"] ?? "") !== $id;
    }));
    $message = hm_save_site($site) ? "notice_deleted" : "store_fail";
    if ($message === "store_fail") { $error = $message; $message = ""; }
  } elseif (isset($post["save_pooja"])) {
    $id = hm_clean_id($post["pooja_id"] ?? "");
    $date = hm_clean_date($post["date"] ?? "");
    $name = hm_clean_line($post["name"] ?? "", 80);
    $base = hm_default_by_id($id);
    if (!$base || $date === "" || $name === "") {
      $error = "need_date";
    } else {
      $site["pooja"][$id] = [
        "date" => $date,
        "name" => $name,
        "sponsor" => hm_clean_line($post["sponsor"] ?? "", 120)
      ];
      $site["removed"] = array_values(array_filter($site["removed"], function ($item) use ($id) {
        return $item !== $id;
      }));
      $message = hm_save_site($site) ? "pooja_ok" : "store_fail";
      if ($message === "store_fail") { $error = $message; $message = ""; }
    }
  } elseif (isset($post["hide_pooja"])) {
    $id = hm_clean_id($post["pooja_id"] ?? "");
    if (hm_default_by_id($id) && !in_array($id, $site["removed"], true)) $site["removed"][] = $id;
    $message = hm_save_site($site) ? "pooja_hidden" : "store_fail";
    if ($message === "store_fail") { $error = $message; $message = ""; }
  } elseif (isset($post["restore_pooja"])) {
    $id = hm_clean_id($post["pooja_id"] ?? "");
    $site["removed"] = array_values(array_filter($site["removed"], function ($item) use ($id) {
      return $item !== $id;
    }));
    $message = hm_save_site($site) ? "pooja_shown" : "store_fail";
    if ($message === "store_fail") { $error = $message; $message = ""; }
  } elseif (isset($post["add_pooja"])) {
    $date = hm_clean_date($post["date"] ?? "");
    $name = hm_clean_line($post["name"] ?? "", 80);
    $deity = array_key_exists($post["deity"] ?? "", DEITIES) ? $post["deity"] : "ganesha";
    if ($date === "" || $name === "") {
      $error = "need_new";
    } elseif (count($site["extra"]) >= 30) {
      $error = "extra_limit";
    } else {
      $site["extra"][] = [
        "id" => "x" . bin2hex(random_bytes(4)),
        "date" => $date,
        "name" => $name,
        "sponsor" => hm_clean_line($post["sponsor"] ?? "", 120),
        "deity" => $deity,
        "common" => isset($post["common"]),
        "special" => isset($post["common"])
      ];
      $message = hm_save_site($site) ? "pooja_added" : "store_fail";
      if ($message === "store_fail") { $error = $message; $message = ""; }
    }
  } elseif (isset($post["save_extra"])) {
    $id = hm_clean_id($post["pooja_id"] ?? "");
    $date = hm_clean_date($post["date"] ?? "");
    $name = hm_clean_line($post["name"] ?? "", 80);
    $deity = array_key_exists($post["deity"] ?? "", DEITIES) ? $post["deity"] : "ganesha";
    $updated = false;
    if ($date === "" || $name === "") {
      $error = "need_date";
    } else {
      foreach ($site["extra"] as $index => $item) {
        if (($item["id"] ?? "") === $id) {
          $site["extra"][$index] = [
            "id" => $id,
            "date" => $date,
            "name" => $name,
            "sponsor" => hm_clean_line($post["sponsor"] ?? "", 120),
            "deity" => $deity,
            "common" => isset($post["common"]),
            "special" => isset($post["common"])
          ];
          $updated = true;
        }
      }
      if (!$updated) $error = "missing_pooja";
      elseif (hm_save_site($site)) $message = "pooja_ok";
      else $error = "store_fail";
    }
  } elseif (isset($post["delete_extra"])) {
    $id = hm_clean_id($post["pooja_id"] ?? "");
    $site["extra"] = array_values(array_filter($site["extra"], function ($item) use ($id) {
      return ($item["id"] ?? "") !== $id;
    }));
    unset($site["pooja"][$id]);
    $message = hm_save_site($site) ? "pooja_removed" : "store_fail";
    if ($message === "store_fail") { $error = $message; $message = ""; }
  }
  return ["message" => $message, "error" => $error];
}
