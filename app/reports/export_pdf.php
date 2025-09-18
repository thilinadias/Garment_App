<?php
require_once __DIR__ . '/../config/auth.php'; ensure_login();
$from=$_GET['from']??''; $to=$_GET['to']??''; $factory_id=$_GET['factory_id']??''; $style_id=$_GET['style_id']??''; $date_field=$_GET['date_field']??'cut_date';
$allowed=['cut_date'=>'j.cut_date','out_date'=>'j.out_date','created_at'=>'j.created_at'];
if(!isset($allowed[$date_field])) $date_field='cut_date';
$col=$allowed[$date_field];

$params=[]; $where=[];
if($from){ $where[]="$col >= ?"; $params[]=$from; }
if($to){ $where[]="$col <= ?"; $params[]=$to; }
if($factory_id){ $where[]="j.factory_id = ?"; $params[]=(int)$factory_id; }
if($style_id){ $where[]="j.style_id = ?"; $params[]=(int)$style_id; }
$whereSql=$where?('WHERE '.implode(' AND ',$where)):'';

$st=$pdo->prepare("SELECT j.id,j.job_number,s.name AS style,j.cut_date,j.cut_qty,f.name AS factory,j.out_date,j.out_qty,j.date_count,j.status,j.created_at FROM jobs j LEFT JOIN factories f ON j.factory_id=f.id LEFT JOIN styles s ON j.style_id=s.id $whereSql ORDER BY $col DESC");
$st->execute($params); $rows=$st->fetchAll();

$total_out=0; foreach($rows as $r){ $total_out+=(int)$r['out_qty']; }
$company = app_setting('company_name') ?: 'Garment App';

function pdf_esc($s){
  $s = (string)$s;
  $s = str_replace("\\", "\\\\", $s);
  $s = str_replace("(", "\\(", $s);
  $s = str_replace(")", "\\)", $s);
  $s = str_replace("\r", "", $s);
  $s = str_replace("\n", "\\n", $s);
  return $s;
}

$headers=['ID','Job #','Style','Cut Date','Cut Qty','Factory','Out Date','Out Qty','Days','Status','Created'];
$w=[25,50,70,60,50,90,60,50,40,60,80]; $x=[]; $p=30; for($i=0;$i<count($w);$i++){ $x[$i]=$p; $p+=$w[$i]; }

$content=[];
$content[]='BT';
$content[]='/F1 14 Tf';
$content[]='70 560 Td';
$content[]='('.pdf_esc($company.' - Jobs Report').') Tj';
$content[]='0 -18 Td';
$content[]='/F1 10 Tf';
$content[]='(Generated: '.date('Y-m-d H:i:s').') Tj';

$y=520;
$content[]='ET';
$content[]='BT';
$content[]='/F1 9 Tf';

for($i=0;$i<count($headers);$i++){
  $content[] = '1 0 0 1 '.(30+$x[$i]).' '.$y.' Tm ('.pdf_esc($headers[$i]).') Tj';
}
$y-=14;

foreach($rows as $r){
  if($y<50){
    $y=520;
    for($i=0;$i<count($headers);$i++){
      $content[] = '1 0 0 1 '.(30+$x[$i]).' '.$y.' Tm ('.pdf_esc($headers[$i]).') Tj';
    }
    $y-=14;
  }
  $vals=[$r['id'],$r['job_number'],$r['style'],$r['cut_date'],$r['cut_qty'],$r['factory'],$r['out_date'],$r['out_qty'],$r['date_count'],$r['status'],$r['created_at']];
  for($i=0;$i<count($vals);$i++){
    $content[] = '1 0 0 1 '.(30+$x[$i]).' '.$y.' Tm ('.pdf_esc((string)$vals[$i]).') Tj';
  }
  $y-=12;
}
$content[] = '1 0 0 1 30 '.($y-10).' Tm (Total Out Qty: '.(int)$total_out.') Tj';
$content[]='ET';

$stream = implode("\n", $content);

$objs=[];
$objs[]='<< /Type /Catalog /Pages 2 0 R >>';
$objs[]='<< /Type /Pages /Kids [3 0 R] /Count 1 >>';
$objs[]='<< /Type /Page /Parent 2 0 R /MediaBox [0 0 842 595] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>';
$objs[]='<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
$objs[]='<< /Length '.strlen($stream).' >>\nstream\n'.$stream.'\nendstream';

$out = "%PDF-1.4\n";
$offs=[];
for($i=0;$i<count($objs);$i++){
  $offs[$i+1] = strlen($out);
  $out .= ($i+1)." 0 obj\n".$objs[$i]."\nendobj\n";
}
$xref = strlen($out);
$out .= "xref\n0 ".(count($objs)+1)."\n";
$out .= "0000000000 65535 f \n";
for($i=1;$i<=count($objs);$i++){
  $out .= sprintf("%010d 00000 n \n", $offs[$i]);
}
$out .= "trailer << /Size ".(count($objs)+1)." /Root 1 0 R >>\nstartxref\n".$xref."\n%%EOF";

log_event('report_pdf_export','report',null,['filters'=>$_GET,'rows'=>count($rows),'total_out'=>$total_out]);

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="jobs_report.pdf"');
echo $out; exit;
