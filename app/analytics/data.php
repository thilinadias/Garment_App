<?php require_once __DIR__ . '/../config/auth.php'; ensure_login();
$dim=$_GET['dim']??'status'; $metric=$_GET['metric']??'jobs'; switch($dim){
  case 'factory': $sql="SELECT COALESCE(f.name,'Unassigned') label, COUNT(*) jobs, SUM(j.cut_qty) cut_qty, SUM(j.out_qty) out_qty FROM jobs j LEFT JOIN factories f ON j.factory_id=f.id GROUP BY label ORDER BY jobs DESC"; break;
  case 'style': $sql="SELECT COALESCE(s.name,'Unassigned') label, COUNT(*) jobs, SUM(j.cut_qty) cut_qty, SUM(j.out_qty) out_qty FROM jobs j LEFT JOIN styles s ON j.style_id=s.id GROUP BY label ORDER BY jobs DESC"; break;
  case 'month': $sql="SELECT DATE_FORMAT(j.cut_date,'%Y-%m') label, COUNT(*) jobs, SUM(j.cut_qty) cut_qty, SUM(j.out_qty) out_qty FROM jobs j GROUP BY label ORDER BY label"; break;
  default: $sql="SELECT j.status label, COUNT(*) jobs, SUM(j.cut_qty) cut_qty, SUM(j.out_qty) out_qty FROM jobs j GROUP BY j.status ORDER BY jobs DESC";
}
$res=$pdo->query($sql)->fetchAll(); $labels=[]; $values=[]; foreach($res as $r){ $labels[]=$r['label']; $values[]=(int)$r[$metric]; }
header('Content-Type: application/json'); echo json_encode(['labels'=>$labels,'values'=>$values]);