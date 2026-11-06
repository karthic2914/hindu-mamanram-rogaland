<?php

function hm_block_public_path(string $uri): bool {
  $path = parse_url($uri, PHP_URL_PATH);
  if (!is_string($path)) return false;
  return (bool) preg_match("#^/(?:data|private|hm-private|sessions)(?:/|$)#i", $path);
}

if (PHP_SAPI === "cli-server") {
  $uri = (string) ($_SERVER["REQUEST_URI"] ?? "/");
  if (hm_block_public_path($uri)) {
    http_response_code(404);
    header("Content-Type: text/plain; charset=utf-8");
    echo "Not found";
    return true;
  }
  return false;
}
