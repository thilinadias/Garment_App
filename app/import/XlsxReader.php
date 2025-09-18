<?php
class XlsxReader{
  public static function readFirstSheet($file){
    if(!class_exists('ZipArchive')) throw new Exception('ZipArchive not available');
    $z=new ZipArchive();
    if($z->open($file)!==true) throw new Exception('Cannot open XLSX');
    $shared=[];
    $sxml=$z->getFromName('xl/sharedStrings.xml');
    if($sxml){ $sx=simplexml_load_string($sxml); foreach($sx->si as $si){ if(isset($si->t)) $shared[]=(string)$si->t; else{ $t=''; foreach($si->r as $run){ $t.=(string)$run->t; } $shared[]=$t; } } }
    $wb=simplexml_load_string($z->getFromName('xl/workbook.xml'));
    $rels=simplexml_load_string($z->getFromName('xl/_rels/workbook.xml.rels'));
    $firstId=(string)$wb->sheets->sheet[0]['r:id']; $target=null;
    foreach($rels->Relationship as $rel){ if((string)$rel['Id']===$firstId){ $target=(string)$rel['Target']; break; } }
    if(!$target) $target='worksheets/sheet1.xml';
    if(strpos($target,'worksheets/')!==0) $target='worksheets/'.$target;
    $sheetXml=$z->getFromName('xl/'.$target); if(!$sheetXml) throw new Exception('Cannot read sheet');
    $sx=simplexml_load_string($sheetXml);
    $rows=[];
    foreach($sx->sheetData->row as $row){
      $r=[];
      foreach($row->c as $c){
        $v=(string)$c->v;
        if(isset($c['t']) && (string)$c['t']==='s'){ $idx=intval($v); $r[]=$shared[$idx] ?? ''; }
        else { $r[]=$v; }
      }
      $rows[]=$r;
    }
    return $rows;
  }
}