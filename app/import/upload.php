<?php
require_once __DIR__ . '/../config/auth.php'; ensure_login(); ensure_role(['admin','manager']); ensure_uploads();

$ok=false; $msg=''; $imported=0; $errors=[];
function norm($s){ return strtoupper(trim($s)); }

function date_from_value($v){
  if($v === '' || $v === null) return null;
  $v = trim((string)$v);

  // Excel serial number
  if(preg_match('/^\d+(\.\d+)?$/', $v)){
    return excel_serial_to_date($v);
  }

  // Normalize separators to '-'
  $norm = str_replace(['/', '.', '\\'], '-', $v);

  // Try Y-m-d (with auto-correct for Y-d-m like 2025-13-02)
  if(preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $norm, $m)){
    $y = (int)$m[1]; $mth = (int)$m[2]; $day = (int)$m[3];
    if($mth > 12 && $day >= 1 && $day <= 12){ $tmp=$mth; $mth=$day; $day=$tmp; } // assume Y-d-m
    if($mth>=1 && $mth<=12 && $day>=1 && $day<=31){ return sprintf('%04d-%02d-%02d', $y, $mth, $day); }
    return null;
  }

  // Try d-m-Y
  if(preg_match('/^(\d{1,2})-(\d{1,2})-(\d{4})$/', $norm, $m)){
    $day=(int)$m[1]; $mth=(int)$m[2]; $y=(int)$m[3];
    if($mth>=1 && $mth<=12 && $day>=1 && $day<=31){ return sprintf('%04d-%02d-%02d', $y, $mth, $day); }
    return null;
  }

  // Try m-d-Y
  if(preg_match('/^(\d{1,2})-(\d{1,2})-(\d{4})$/', $norm, $m)){
    $mth=(int)$m[1]; $day=(int)$m[2]; $y=(int)$m[3];
    if($mth>=1 && $mth<=12 && $day>=1 && $day<=31){ return sprintf('%04d-%02d-%02d', $y, $mth, $day); }
    return null;
  }

  return null;
}

try{
  if(!isset($_FILES['file'])||!is_uploaded_file($_FILES['file']['tmp_name'])) throw new Exception('No file uploaded.');
  $ext=strtolower(pathinfo($_FILES['file']['name'],PATHINFO_EXTENSION)); $rows=[];
  if($ext==='csv'){
    if(($f=fopen($_FILES['file']['tmp_name'],'r'))===false) throw new Exception('Cannot open CSV.');
    while(($r=fgetcsv($f))!==false){ $rows[]=$r; } fclose($f);
  } elseif($ext==='xlsx'){
    require_once __DIR__.'/XlsxReader.php'; $rows=XlsxReader::readFirstSheet($_FILES['file']['tmp_name']);
  } else {
    throw new Exception('Unsupported file type. Use CSV or XLSX.');
  }
  if(!$rows) throw new Exception('File is empty.');

  $header=array_map('norm',$rows[0]); $need=['JOB NUMBER','STYLE','CUT DATE','CUT QTY','FACTORY','OUT DATE','OUT QTY','STATUS','NOTES']; $map=[];
  foreach($need as $c){ $i=array_search($c,$header); if($i===false) throw new Exception('Missing column: '.$c); $map[$c]=$i; }

  $pdo->beginTransaction();
  for($i=1;$i<count($rows);$i++){
    $r=$rows[$i]; if(!is_array($r) || (count($r)==1 && trim((string)$r[0])==='')) continue;

    $job_number=trim($r[$map['JOB NUMBER']] ?? ''); if($job_number===''){ $errors[]='Row '.($i+1).': JOB NUMBER is required'; continue; }

    $style_name=trim($r[$map['STYLE']] ?? ''); $style_id=null;
    if($style_name!==''){
      $st=$pdo->prepare('SELECT id FROM styles WHERE name=?'); $st->execute([$style_name]); $style_id=$st->fetchColumn();
      if(!$style_id){ $pdo->prepare('INSERT INTO styles (name) VALUES (?)')->execute([$style_name]); $style_id=$pdo->lastInsertId(); }
    }

    $cut_date=date_from_value($r[$map['CUT DATE']] ?? ''); if(($r[$map['CUT DATE']] ?? '')!=='' && !$cut_date){ $errors[]='Row '.($i+1).': invalid CUT DATE "'.($r[$map['CUT DATE']] ?? '').'"'; }
    $cut_qty=(int)($r[$map['CUT QTY']] ?? 0);

    $factory_name=trim($r[$map['FACTORY']] ?? ''); $fid=null;
    if($factory_name!==''){
      $st=$pdo->prepare('SELECT id FROM factories WHERE name=?'); $st->execute([$factory_name]); $fid=$st->fetchColumn();
      if(!$fid){ $pdo->prepare('INSERT INTO factories (name) VALUES (?)')->execute([$factory_name]); $fid=$pdo->lastInsertId(); }
    }

    $out_date=date_from_value($r[$map['OUT DATE']] ?? ''); if(($r[$map['OUT DATE']] ?? '')!=='' && !$out_date){ $errors[]='Row '.($i+1).': invalid OUT DATE "'.($r[$map['OUT DATE']] ?? '').'"'; }
    $out_qty=(int)($r[$map['OUT QTY']] ?? 0);

    $status=trim($r[$map['STATUS']] ?? 'New'); if($status==='') $status='New';
    $notes=trim($r[$map['NOTES']] ?? '');

    $date_count=null;
    if($cut_date && $out_date){
      $d1=DateTime::createFromFormat('Y-m-d',$cut_date);
      $d2=DateTime::createFromFormat('Y-m-d',$out_date);
      if($d1 && $d2){ $date_count=$d1->diff($d2)->days; }
    }

    $pdo->prepare('INSERT INTO jobs (job_number,style_id,cut_date,cut_qty,factory_id,out_date,out_qty,date_count,status,notes) VALUES (?,?,?,?,?,?,?,?,?,?)')
        ->execute([$job_number,$style_id?:null,$cut_date?:null,$cut_qty?:null,$fid?:null,$out_date?:null,$out_qty?:null,$date_count,$status,$notes]);

    $imported++;
  }
  $pdo->commit();
  log_event('import_jobs','import',null,['imported'=>$imported,'errors'=>count($errors)]);
  $ok=true; $msg="Imported $imported rows. ".(count($errors)?("Errors: ".count($errors)):"");
} catch(Exception $e) {
  $msg='Import failed: '.$e->getMessage();
}

include __DIR__ . '/../includes/header.php'; ?>
<div class="card p-3"><h4 class="mb-3">Bulk Upload Result</h4>
  <div class="alert <?php echo $ok?'alert-success':'alert-danger'; ?>"><?php echo h($msg); ?></div>
  <?php if(!empty($errors)):?>
    <div class="alert alert-warning">
      <div><strong>Row-level warnings:</strong></div>
      <ul class="mb-0">
        <?php foreach($errors as $e):?><li><?php echo h($e);?></li><?php endforeach;?>
      </ul>
    </div>
  <?php endif;?>
  <a class="btn btn-primary" href="<?php echo url('import/index.php'); ?>">Back to Import</a>
  <a class="btn btn-outline-primary" href="<?php echo url('jobs/index.php'); ?>">Go to Jobs</a>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
