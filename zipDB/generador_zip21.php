<?php

function execute_script($relative_path) {
    $url = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/' . $relative_path;
    $response = file_get_contents($url);
    if ($response === FALSE) {
        echo "Error al ejecutar $url\n";
        exit;
    }
    return $response;
}

// Obtener la fecha de corte
$db = new SQLite3('../db/database.db3');
$query1 = $db->query("SELECT * FROM corte");
$row1 = $query1->fetchArray();

$fechacorte = '';
$fechacorte = $row1['anio'].$row1['mes'].$row1['dia'].'_'.$row1['hora'].$row1['minuto'];

// Archivos por agregar
$alcaldias = $fechacorte.'_PREP_ALC_CDMX.zip';
$diputaciones = $fechacorte.'_PREP_DIP_LOC_CDMX.zip';
$jefatura = $fechacorte.'_PREP_GUB_CDMX.zip';

// Ejecutar otros scripts PHP para generar los archivos ZIP
execute_script('export_ine_dip.php');
execute_script('export_ine_gob.php');
execute_script('export_ine_alc.php');

// Verificar si los archivos ZIP existen
if (file_exists($alcaldias) && file_exists($diputaciones) && file_exists($jefatura)) {

    // Crear un archivo ZIP y agregar los archivos ZIP
    $nestedZipFile = $fechacorte.'_PREP_CDMX.zip';
    $nestedZip = new ZipArchive();
    
    if ($nestedZip->open($nestedZipFile, ZipArchive::CREATE) === TRUE) {

        $nestedZip->addFile($alcaldias, basename($alcaldias));
        $nestedZip->addFile($diputaciones, basename($diputaciones));
        $nestedZip->addFile($jefatura, basename($jefatura));
        $nestedZip->close();

        // Descargar el archivo ZIP anidado
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . basename($nestedZipFile) . '"');
        readfile($nestedZipFile);

        // Eliminar los archivos temporales
        unlink($nestedZipFile);
    } else {
        echo 'Error al crear el archivo ZIP anidado';
    }
} else {
    echo 'Error: Uno o más archivos ZIP no existen';
}

?>