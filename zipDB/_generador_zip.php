<?php
// Nombre del archivo CSV existente

//obtengo la fecha de corte
$db = new SQLite3('../db/database.db3');
$query1 = $db->query("SELECT * FROM corte");
$row1 = $query1->fetchArray();

$fechacorte='';
$fechacorte = $row1['anio'].$row1['mes'].$row1['dia'].'_'.$row1['hora'].$row1['minuto'];


//archivos por agregar
$alcaldias='';
$diputaciones='';
$jefatura='';

$alcaldias=$fechacorte.'_PREP_ALC_CDMX.zip';
$diputaciones=$fechacorte.'_PREP_DIP_LOC_CDMX.zip';
$jefatura=$fechacorte.'_PREP_GUB_CDMX.zip';


// Crear un archivo ZIP y agregar los archivos CSV


    // Comprimir el archivo ZIP en otro archivo ZIP
    $nestedZipFile='';
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

?>