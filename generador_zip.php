<?php
//error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);
include('export_ine_alc.php');
include('export_ine_dip.php');
include('export_ine_gob.php');// nuevo por generar 


$db = new SQLite3('../db/database.db3');

$db->enableExceptions(false);
$query1 = $db->query("SELECT * FROM corte");
$row1 = $query1->fetchArray();

$date = $row1['anio'].$row1['mes'].$row1['dia'].'_'.$row1['hora'].$row1['minuto'];


$zip = new ZipArchive();
//$date = date('Ymd_Hi');
$filename = $date."_PREP_CDMX.zip";

if ($zip->open($filename, ZipArchive::CREATE)!==TRUE) {
    exit("cannot open <$filename>\n");
}

$zip->addFile($date."_PREP_ALC_CDMX.zip",$date."_PREP_ALC_CDMX.zip");
$zip->addFile($date."_PREP_DIP_LOC_CDMX.zip",$date."_PREP_DIP_LOC_CDMX.zip");
$zip->addFile($date."_PREP_GUB_CDMX.zip",$date."_PREP_GUB_CDMX.zip");


$zip->close();
$db->close();
unset($db);
header("Content-type: application/octet-stream;");
header("Content-disposition: attachment; filename=$filename");

// unlink('bd-alcaldia.csv');
//unlink(''.$date.'_PREP_ALC_CDMX.zip');

//unlink('bd-diputados.csv');
//unlink(''.$date.'_PREP_DIP_LOC_CDMX.zip');

//unlink('bd-gobierno.csv');
//unlink(''.$date.'_PREP_GUB_CDMX.zip');

readfile($filename);
unlink($filename);
?>