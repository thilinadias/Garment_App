<?php
require_once __DIR__ . '/../config/auth.php'; ensure_login(); ensure_role(['admin','manager']);

$pid = (int)($_POST['photo_id'] ?? 0); $job_id = (int)($_POST['job_id'] ?? 0);
$table = preg_match('/^\w+$/', $_POST['table'] ?? '') ? $_POST['table'] : 'job_photos';
if($pid<=0 || $job_id<=0){ http_response_code(400); echo 'Bad request'; exit; }

$cols = []; try{ $cols = $pdo->query("SHOW COLUMNS FROM `{$table}`")->fetchAll(PDO::FETCH_COLUMN); } catch(Exception $e){ $cols=[]; }
$present = array_values(array_intersect(['filename','path','file_path','photo_path','image_path','url','photo','file','image','img','photo_url','file_url','uri'], $cols));
$coalesce = $present ? 'COALESCE(`'.implode('`,`',$present).'`)' : 'NULL';

$st=$pdo->prepare("SELECT {$coalesce} AS path FROM `{$table}` WHERE id=?"); $st->execute([$pid]); $p=$st->fetch(PDO::FETCH_ASSOC);
$pdo->prepare("DELETE FROM `{$table}` WHERE id=?")->execute([$pid]);

if($p && isset($p['path'])){
  $fs = str_replace('\\','/', $p['path']);
  if(!preg_match('~^[a-zA-Z]:/|^/~',$fs)) $fs = __DIR__.'/../'.ltrim($fs,'/');
  if(is_file($fs)) @unlink($fs);
}
log_event('job_photo_delete','job',$job_id,['photo_id'=>$pid,'table'=>$table]);
header('Location: '.url('jobs/edit.php').'?id='.$job_id);
