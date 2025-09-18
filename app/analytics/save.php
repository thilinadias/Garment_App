<?php
require_once __DIR__ . '/../config/auth.php'; ensure_login(); ensure_role(['admin','manager']);
$title=trim($_POST['title']??'Custom Chart'); $dim=$_POST['dim']??'status'; $metric=$_POST['metric']??'jobs'; $type=$_POST['type']??'bar'; $order=(int)($_POST['sort_order']??100);
try{
  $pdo->prepare("INSERT INTO chart_configs (title, dim, metric, type, sort_order, created_by) VALUES (?,?,?,?,?,?)")
      ->execute([$title,$dim,$metric,$type,$order,(current_user()['id']??null)]);
  log_event('chart_saved','chart', $pdo->lastInsertId(), ['dim'=>$dim,'metric'=>$metric,'type'=>$type,'order'=>$order]);
  header('Content-Type: application/json'); echo json_encode(['ok'=>true]);
}catch(Exception $e){
  header('Content-Type: application/json'); echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]);
}
