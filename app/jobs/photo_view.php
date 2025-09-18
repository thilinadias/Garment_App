<?php
require_once __DIR__ . '/../config/auth.php'; ensure_login(); ensure_role(['admin','manager']);

$table = $_GET['table'] ?? 'job_photos';
$path_col = $_GET['path_col'] ?? '__coalesce'; // ignore and coalesce if special token
$id = (int)($_GET['id'] ?? 0);
$debug = isset($_GET['debug']);

if(!preg_match('/^\w+$/',$table) || $id<=0){
  http_response_code(400); echo 'Bad request'; exit;
}

function cols(PDO $pdo,$t){ try{ return $pdo->query("SHOW COLUMNS FROM `{$t}`")->fetchAll(PDO::FETCH_COLUMN); } catch(Exception $e){ return []; } }
function path_candidates(){ return ['filename','path','file_path','photo_path','image_path','url','photo','file','image','img','photo_url','file_url','uri']; }

$cols = cols($pdo,$table);
$hasJobId = in_array('job_id',$cols);

$selectExpr = '';
if($path_col==='__coalesce'){
  $present = [];
  foreach(path_candidates() as $c){ if(in_array($c,$cols)) $present[]="`$c`"; }
  $selectExpr = $present ? 'COALESCE('.implode(',',$present).')' : 'NULL';
} else {
  if(!preg_match('/^\w+$/',$path_col) || !in_array($path_col,$cols)){
    $selectExpr = 'NULL';
  } else {
    $selectExpr = "`$path_col`";
  }
}

$sql = "SELECT {$selectExpr} AS path".($hasJobId?", job_id":"")." FROM `{$table}` WHERE id=?";
$st  = $pdo->prepare($sql); $st->execute([$id]); $row = $st->fetch(PDO::FETCH_ASSOC);
if(!$row || !isset($row['path'])){ http_response_code(404); echo 'Not found'; exit; }

$raw = (string)$row['path'];
$p = str_replace('\\','/', trim($raw));

if($debug){
  header('Content-Type: text/plain; charset=utf-8');
  echo "table=$table\nid=$id\nraw=$raw\nnorm=$p\ncols=".implode(',',$cols)."\n";
}

if(preg_match('~^https?://~i',$p)){
  if($debug){ echo "-> redirect url $p\n"; exit; }
  header('Location: '.$p, true, 302); exit;
}

$root = realpath(__DIR__.'/..');
$cands = [];

// uploads inside string
if(preg_match('~uploads[/\\\\].+~i',$p,$m)){ $rel=str_replace('\\','/',$m[0]); $cands[]="$root/".ltrim($rel,'/'); }
// raw as abs/rel
$cands[] = preg_match('~^[a-zA-Z]:/|^/~',$p) ? $p : "$root/".ltrim($p,'/');
// guess by job_id
if($hasJobId && !empty($row['job_id'])){
  $base = basename($p); if($base){ $cands[] = "$root/uploads/jobs/".((int)$row['job_id'])."/$base"; }
}
// plain uploads root + basename
$base = basename($p); if($base){ $cands[] = "$root/uploads/$base"; }

$chosen = null;
foreach($cands as $fs){
  $real = realpath($fs);
  if($real && is_file($real)){ $chosen=$real; break; }
  if(is_file($fs)){ $chosen=$fs; break; }
}

if($debug){
  foreach($cands as $fs){ echo "try: $fs -> ".(is_file($fs)?'YES':'no')."\n"; }
  echo $chosen ? "RESULT=$chosen\n" : "RESULT=NOT FOUND\n";
  if(!$chosen) exit;
}

if(!$chosen){ http_response_code(404); echo 'File not found'; exit; }

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime  = finfo_file($finfo, $chosen) ?: 'application/octet-stream';
finfo_close($finfo);
header('Content-Type: '.$mime);
header('Content-Length: '.filesize($chosen));
readfile($chosen);
exit;
