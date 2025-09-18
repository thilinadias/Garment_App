<?php
require_once __DIR__ . '/../config/auth.php'; ensure_login(); ensure_role(['admin','manager']); ensure_uploads();

$job_id = (int)($_GET['job_id'] ?? 0);
if($job_id<=0){ http_response_code(400); echo 'Invalid job id'; exit; }

$cols = []; try{ $cols = $pdo->query("SHOW COLUMNS FROM job_photos")->fetchAll(PDO::FETCH_COLUMN); } catch(Exception $e){ $cols=[]; }
$pathCols = array_values(array_intersect(['filename','path','file_path','photo_path','image_path','url','photo'], $cols));

$upload_base = __DIR__.'/../uploads/jobs/job'.$job_id;
if(!is_dir($upload_base)){ @mkdir($upload_base, 0775, true); }

$ok=0; $errs=[]; $allowed=['image/jpeg','image/png','image/gif','image/webp'];
foreach(($_FILES['photos']['name'] ?? []) as $i=>$name){
  if(!is_uploaded_file($_FILES['photos']['tmp_name'][$i])) continue;
  $type = mime_content_type($_FILES['photos']['tmp_name'][$i]);
  if(!in_array($type,$allowed)){ $errs[] = $name.': unsupported type'; continue; }
  $ext = pathinfo($name, PATHINFO_EXTENSION) ?: 'jpg';
  $basename = 'job'.$job_id.'_'.uniqid().'.'.strtolower($ext);
  $dest_fs = $upload_base.'/'.$basename;

  if(move_uploaded_file($_FILES['photos']['tmp_name'][$i], $dest_fs)){
    $rel = 'uploads/jobs/job'.$job_id.'/'.$basename;
    // Build insert setting ALL present path-like columns with the same value
    $cols_ins = array_merge(['job_id'], $pathCols);
    $placeholders = rtrim(str_repeat('?,', count($cols_ins)),',');
    $sql = "INSERT INTO job_photos (".implode(',',$cols_ins).") VALUES ($placeholders)";
    $params = [$job_id]; foreach($pathCols as $_){ $params[] = $rel; }
    $pdo->prepare($sql)->execute($params);
    $ok++;
  } else {
    $errs[] = $name.': move failed';
  }
}
log_event('job_photo_upload','job',$job_id,['uploaded'=>$ok,'errors'=>count($errs)]);
header('Location: '.url('jobs/edit.php').'?id='.$job_id.($ok?('&msg=uploaded_'.$ok):''));
