<?php
header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store");
$file = __DIR__ . "/data/site.json";
$empty = ["notices" => [], "pooja" => new stdClass(), "removed" => [], "extra" => []];
if (!is_file($file)) {
  echo json_encode($empty, JSON_UNESCAPED_UNICODE);
  exit;
}
$raw = file_get_contents($file);
$data = json_decode($raw, true);
if (!is_array($data)) {
  echo json_encode($empty, JSON_UNESCAPED_UNICODE);
  exit;
}
echo json_encode([
  "notices" => isset($data["notices"]) && is_array($data["notices"]) ? array_values($data["notices"]) : [],
  "pooja" => isset($data["pooja"]) && is_array($data["pooja"]) ? $data["pooja"] : new stdClass(),
  "removed" => isset($data["removed"]) && is_array($data["removed"]) ? array_values($data["removed"]) : [],
  "extra" => isset($data["extra"]) && is_array($data["extra"]) ? array_values($data["extra"]) : []
], JSON_UNESCAPED_UNICODE);
