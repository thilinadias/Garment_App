<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
date_default_timezone_set('Asia/Colombo');

define('BASE_URL', getenv('BASE_URL') ?: '/garment_app/');
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'garment_app');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo "Database connection failed. " . htmlspecialchars($e->getMessage());
    exit;
}

function h($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
function url($path) { $base = rtrim(BASE_URL, '/') . '/'; return $base . ltrim($path, '/'); }
function ensure_uploads() { $p = __DIR__ . '/../uploads'; if (!is_dir($p)) { @mkdir($p, 0777, true); @mkdir($p.'/jobs',0777,true);} return $p; }
function client_ip() {
  foreach (['HTTP_CF_CONNECTING_IP','HTTP_X_FORWARDED_FOR','HTTP_X_REAL_IP','REMOTE_ADDR'] as $k) {
    if (!empty($_SERVER[$k])) { $ip = $_SERVER[$k]; if (strpos($ip, ',')!==false) $ip = explode(',',$ip)[0]; return trim($ip); }
  } return null;
}
function log_event($action,$entity_type=null,$entity_id=null,$details=null){
  try{ global $pdo; $user_id=$_SESSION['user_id']??null; $ip=client_ip();
    $pdo->prepare("INSERT INTO app_logs (user_id,action,entity_type,entity_id,details,ip) VALUES (?,?,?,?,?,?)")
        ->execute([$user_id,$action,$entity_type,$entity_id,is_array($details)?json_encode($details):$details,$ip]);
  }catch(Exception $e){}
}
function app_setting($key, $default=null) {
  try {
    global $pdo;
    static $cache = [];
    if (array_key_exists($key, $cache)) return $cache[$key] ?? $default;
    $stmt = $pdo->prepare("SELECT `value` FROM settings WHERE `key` = ? LIMIT 1");
    $stmt->execute([$key]);
    $val = $stmt->fetchColumn();
    $cache[$key] = ($val === false) ? null : $val;
    return ($val === false) ? $default : $val;
  } catch (Exception $e) {
    return $default;
  }
}
function excel_serial_to_date($serial){ if($serial===''||$serial===null) return null; $serial=floatval($serial); if($serial<=0) return null; $base=new DateTime('1899-12-30'); $base->add(new DateInterval('P'.intval(round($serial)).'D')); return $base->format('Y-m-d'); }
?>