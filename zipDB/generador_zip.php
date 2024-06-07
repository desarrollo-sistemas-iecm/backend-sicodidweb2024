<?php

function execute_script($relative_path) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $url = $protocol . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/' . $relative_path;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Desactiva la verificación del certificado SSL (no recomendado en producción)
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // Desactiva la verificación del nombre del host (no recomendado en producción)
    curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Establece un tiempo de espera de 30 segundos
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15); // Establece un tiempo de espera de conexión de 15 segundos
    $output = curl_exec($ch);
    if (curl_errno($ch)) {
        echo "cURL error: " . curl_error($ch) . "\n";
        exit;
    }
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code != 200) {
        echo "Error al ejecutar $url: HTTP $http_code\n";
        exit;
    }
    // print_r($output);
    return $output;
}

function execute_script_on_servers($relative_path, $servers) {
    $errors = [];
    foreach ($servers as $server) {
        $protocol = "https://";
        $url = $protocol . $server . dirname($_SERVER['PHP_SELF']) . '/' . $relative_path;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Desactiva la verificación del certificado SSL (no recomendado en producción)
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // Desactiva la verificación del nombre del host (no recomendado en producción)
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Establece un tiempo de espera de 30 segundos
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15); // Establece un tiempo de espera de conexión de 15 segundos
        $output = curl_exec($ch);
        if (curl_errno($ch)) {
            $errors[] = "cURL error on $server: " . curl_error($ch);
        }
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code != 200) {
            $errors[] = "Error al ejecutar $url: HTTP $http_code";
        } else {
            // Si la ejecución fue exitosa, no necesitamos probar en otros servidores
            return $output;
        }
    }

    // Si llegamos aquí, significa que falló en todos los servidores
    foreach ($errors as $error) {
        echo $error . "\n";
    }
    exit;
}

// Verificar si estamos detrás de un balanceador de carga
$behind_load_balancer = !empty($_SERVER['HTTP_X_FORWARDED_FOR']) || isset($_SERVER['HTTP_X_FORWARDED_PROTO']);

// Definir los servidores si estamos detrás de un balanceador de carga
$servers = [
    '192.168.7.8',
    '192.168.7.9',
    '192.168.7.10'
];

// Obtener la fecha de corte
$db = new SQLite3('../db/database.db3');
$query1 = $db->query("SELECT * FROM corte");
$row1 = $query1->fetchArray();

$fechacorte = '';
$fechacorte = $row1['anio'].$row1['mes'].$row1['dia'].'_'.$row1['hora'].$row1['minuto'];

// Archivos por agregar
$alcaldias = $fechacorte.'_SICODID_ALC_CDMX.zip';
$diputaciones = $fechacorte.'_SICODID_DIP_LOC_CDMX.zip';
$jefatura = $fechacorte.'_SICODID_GUB_CDMX.zip';

// Ejecutar otros scripts PHP para generar los archivos ZIP
if ($behind_load_balancer) {
    // Si estamos detrás de un balanceador de carga, ejecutamos los scripts en todos los servidores
    execute_script_on_servers('export_ine_dip.php', $servers);
    execute_script_on_servers('export_ine_gob.php', $servers);
    execute_script_on_servers('export_ine_alc.php', $servers);
} else {
    // Si no estamos detrás de un balanceador de carga, ejecutamos los scripts localmente
    execute_script('export_ine_dip.php');
    execute_script('export_ine_gob.php');
    execute_script('export_ine_alc.php');
}

// Verificar si los archivos ZIP existen
if (file_exists($alcaldias) && file_exists($diputaciones) && file_exists($jefatura)) {
    // Crear un archivo ZIP y agregar los archivos ZIP
    $nestedZipFile = $fechacorte.'_SICODID_CDMX.zip';
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