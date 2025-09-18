<?php
require_once __DIR__ . '/../config/auth.php'; ensure_login();

$from=$_GET['from']??''; $to=$_GET['to']??''; $factory_id=$_GET['factory_id']??''; $style_id=$_GET['style_id']??''; $date_field=$_GET['date_field']??'cut_date';
$allowed=['cut_date'=>'j.cut_date','out_date'=>'j.out_date','created_at'=>'j.created_at'];
$labels=['cut_date'=>'Cut Date','out_date'=>'Out Date','created_at'=>'Created At'];
if(!isset($allowed[$date_field])) $date_field='cut_date';
$col=$allowed[$date_field]; $date_label=$labels[$date_field];

$params=[]; $where=[];
if($from){ $where[]="$col >= ?"; $params[]=$from; }
if($to){ $where[]="$col <= ?"; $params[]=$to; }
if($factory_id){ $where[]="j.factory_id = ?"; $params[]=(int)$factory_id; }
if($style_id){ $where[]="j.style_id = ?"; $params[]=(int)$style_id; }
$whereSql=$where?('WHERE '.implode(' AND ',$where)):'';

$sql="SELECT j.id,j.job_number,s.name AS style,j.cut_date,j.cut_qty,f.name AS factory,j.out_date,j.out_qty,j.date_count,j.status,j.created_at
      FROM jobs j
      LEFT JOIN factories f ON j.factory_id=f.id
      LEFT JOIN styles s ON j.style_id=s.id
      $whereSql
      ORDER BY $col DESC";
$st=$pdo->prepare($sql); $st->execute($params); $rows=$st->fetchAll();

// Compute totals
$total_out=0; foreach($rows as $r){ $total_out+=(int)$r['out_qty']; }

// Resolve filter labels
$factory_label='All'; if($factory_id){ $s=$pdo->prepare('SELECT name FROM factories WHERE id=?'); $s->execute([(int)$factory_id]); $factory_label=$s->fetchColumn() ?: ('#'.(int)$factory_id); }
$style_label='All'; if($style_id){ $s=$pdo->prepare('SELECT name FROM styles WHERE id=?'); $s->execute([(int)$style_id]); $style_label=$s->fetchColumn() ?: ('#'.(int)$style_id); }

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="jobs_report.csv"');
$out=fopen('php://output','w');

// Filter summary block
fputcsv($out, ['Report','Jobs Report']);
fputcsv($out, ['Generated', date('Y-m-d H:i:s')]);
fputcsv($out, ['Date Field', $date_label]);
fputcsv($out, ['From', $from ?: 'All']);
fputcsv($out, ['To', $to ?: 'All']);
fputcsv($out, ['Factory', $factory_label]);
fputcsv($out, ['Style', $style_label]);
fputcsv($out, ['Total Out Qty', $total_out]);
fputcsv($out, []); // blank line

// Data header
fputcsv($out, ['ID','Job #','Style','Cut Date','Cut Qty','Factory','Out Date','Out Qty','Days','Status','Created']);
// Data rows
foreach($rows as $r){
  fputcsv($out, [$r['id'],$r['job_number'],$r['style'],$r['cut_date'],$r['cut_qty'],$r['factory'],$r['out_date'],$r['out_qty'],$r['date_count'],$r['status'],$r['created_at']]);
}
fclose($out);

// Log with totals & filter details
log_event('report_csv_export','report',null,[
  'filters'=>$_GET,
  'date_field'=>$date_field,
  'rows'=>count($rows),
  'total_out'=>$total_out
]);

exit;
