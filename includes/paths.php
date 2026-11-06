<?php

function hm_site_root(): string {
  return dirname(__DIR__);
}

function hm_norm_path(string $path): string {
  $path = str_replace("\\", "/", $path);
  return rtrim(strtolower($path), "/");
}

function hm_is_inside_root(string $candidate, string $root): bool {
  $candidateNorm = hm_norm_path($candidate);
  $rootNorm = hm_norm_path($root);
  return $candidateNorm === $rootNorm || str_starts_with($candidateNorm, $rootNorm . "/");
}

function hm_resolved_candidate(string $path): string {
  if (is_dir($path)) {
    $real = realpath($path);
    return $real === false ? $path : $real;
  }
  $parent = realpath(dirname($path));
  if ($parent === false) return $path;
  return $parent . DIRECTORY_SEPARATOR . basename($path);
}

function hm_path_is_public(string $path): bool {
  $candidate = hm_resolved_candidate($path);
  $site = realpath(hm_site_root());
  if ($site === false || hm_is_inside_root($candidate, $site)) return true;
  $documentRoot = (string) ($_SERVER["DOCUMENT_ROOT"] ?? "");
  if ($documentRoot !== "") {
    $documentReal = realpath($documentRoot);
    if ($documentReal !== false && hm_is_inside_root($candidate, $documentReal)) return true;
  }
  $parent = realpath(dirname($path));
  if (!is_dir($path) && $parent === false) return true;
  return false;
}

function hm_private_candidate(): string {
  $configured = getenv("HM_PRIVATE_DIR");
  if (is_string($configured) && $configured !== "") return $configured;
  return dirname(hm_site_root()) . DIRECTORY_SEPARATOR . "hm-private";
}

function hm_assert_private_dir(string $dir, bool $create): string {
  if (hm_path_is_public($dir)) {
    throw new RuntimeException("private_public");
  }
  if (!is_dir($dir)) {
    if (!$create) return $dir;
    if (!mkdir($dir, 0700, true) && !is_dir($dir)) {
      throw new RuntimeException("private_create");
    }
  }
  if (hm_path_is_public($dir)) {
    throw new RuntimeException("private_public");
  }
  if (!hm_secure_directory($dir)) {
    throw new RuntimeException("private_perms");
  }
  $real = realpath($dir);
  if ($real === false || hm_path_is_public($real)) {
    throw new RuntimeException("private_public");
  }
  return $real;
}

function hm_secure_directory(string $dir): bool {
  if (PHP_OS_FAMILY === "Windows") return is_dir($dir);
  if (!@chmod($dir, 0700)) return false;
  clearstatcache(true, $dir);
  return (fileperms($dir) & 0077) === 0;
}

function hm_secure_file(string $path): bool {
  if (!is_file($path)) return false;
  if (PHP_OS_FAMILY === "Windows") return true;
  if (!@chmod($path, 0600)) return false;
  clearstatcache(true, $path);
  return (fileperms($path) & 0077) === 0;
}

function hm_private_dir(bool $create): string {
  return hm_assert_private_dir(hm_private_candidate(), $create);
}

function hm_data_dir(): string {
  $configured = getenv("HM_DATA_DIR");
  if (is_string($configured) && $configured !== "") return $configured;
  return hm_site_root() . DIRECTORY_SEPARATOR . "data";
}

function hm_legacy_auth_path(): string {
  $configured = getenv("HM_LEGACY_AUTH_PATH");
  if (is_string($configured) && $configured !== "") return $configured;
  return hm_data_dir() . DIRECTORY_SEPARATOR . "auth.json";
}

function hm_atomic_write(string $path, string $contents): bool {
  $dir = dirname($path);
  if (!is_dir($dir) || hm_path_is_public($dir)) return false;
  $tmp = $dir . DIRECTORY_SEPARATOR . "." . bin2hex(random_bytes(8)) . ".tmp";
  $written = file_put_contents($tmp, $contents, LOCK_EX);
  if ($written !== strlen($contents)) {
    if (is_file($tmp)) @unlink($tmp);
    return false;
  }
  if (!hm_secure_file($tmp)) {
    @unlink($tmp);
    return false;
  }
  if (PHP_OS_FAMILY === "Windows" && is_file($path)) {
    $old = $path . "." . bin2hex(random_bytes(4)) . ".old";
    if (!@rename($path, $old)) {
      @unlink($tmp);
      return false;
    }
    if (!@rename($tmp, $path)) {
      @rename($old, $path);
      @unlink($tmp);
      return false;
    }
    @unlink($old);
  } elseif (!@rename($tmp, $path)) {
    @unlink($tmp);
    return false;
  }
  if (!hm_secure_file($path)) {
    @unlink($path);
    return false;
  }
  return true;
}
