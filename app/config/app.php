<?php
// app/config/app.php
// Common helpers + settings loader for the Garment App.

require_once __DIR__ . '/db.php'; // provides $pdo

if (!function_exists('h')) {
  function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}

if (!function_exists('url')) {
  function url(string $path = ''): string {
    // simple URL helper (works behind reverse proxies too)
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $base   = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
    if ($path !== '' && $path[0] !== '/') $path = '/'.$path;
    return $scheme.'://'.$host.$base.$path;
  }
}

/**
 * Read a setting from DB with in-memory cache.
 * Falls back to $default if row/table is missing.
 */
if (!function_exists('app_setting')) {
  function app_setting(string $key, $default = '') {
    static $cache = [];
    if (array_key_exists($key, $cache)) return $cache[$key];

    // ensure $pdo exists (db.php included above)
    global $pdo;
    try {
      $st = $pdo->prepare('SELECT v FROM settings WHERE k = ? LIMIT 1');
      $st->execute([$key]);
      $val = $st->fetchColumn();
      if ($val === false || $val === null || $val === '') $val = $default;
      return $cache[$key] = $val;
    } catch (Throwable $e) {
      // table may not exist yet -> return default
      return $cache[$key] = $default;
    }
  }
}
