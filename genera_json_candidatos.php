<?php

// Configuración de cabeceras para CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');
header('Allow: GET, POST, OPTIONS, PUT, DELETE');
header('Content-Type: application/json; charset=utf-8');

// Configuración de zona horaria
date_default_timezone_set('America/Mexico_City');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(204);
    exit();
}

if (!isset($_POST['cargo_sirec'])) {
    http_response_code(500);
    echo json_encode(
        array(
            'ok' => false,
            'msg' => 'No se recibió el argumento del tipo de candidaturas a exportar.'
        )
    );
    exit();
} else {
    $cargo_sirec = $_POST['cargo_sirec'];

    // Configuración de conexión a la base de datos
    $serverName = "sqlrv-aplicaciones2023.public.3dd878c02081.database.windows.net, 3342";
    $connectionOptions = array(
        "Database" => "sirec2023_ModV",
        // "Port" => ,
        "Uid" => "sirec2023ModV_db",
        "PWD" => "nhFxcza7Pxgz"
    );

    // Conexión a la base de datos
    $conn = sqlsrv_connect($serverName, $connectionOptions);
    if ($conn === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    // Ejecución del query
    $query = "SELECT usr.ambito_territorial AS id_ambito, usr.id_coalicion, usr.id_partido_politico, cc.tipo_asociacion, dbo.ObtieneSiglasPartidoOCoalicion(usr.id_coalicion, usr.id_partido_politico) AS partido_csp, CONCAT(usr.nombres, ' ',usr.apellido_paterno, ' ', usr.apellido_materno) AS nombre_completo, dbo.ObtieneIdParticipanteDiputacionPREP(usr.id_coalicion, usr.id_partido_politico, cc.tipo_asociacion, dbo.ObtieneSiglasPartidoOCoalicion(usr.id_coalicion, usr.id_partido_politico), usr.cargo) AS id_participante FROM usuarios AS usr INNER JOIN cat_partidos_politicos AS catPP ON (usr.id_partido_politico = catPP.id_partido_politico) INNER JOIN cat_cargos AS catCargo ON (usr.cargo = catCargo.id_cargo) LEFT JOIN cat_estados_republica AS c_edos ON (usr.lugar_nacimiento = c_edos.id_estado) LEFT JOIN cat_coaliciones cc ON (cc.id_coalicion = usr.id_coalicion)  WHERE usr.estado = 7 AND usr.sustitucion = 0 AND usr.cargo = ?";
    $params = array($cargo_sirec);
    $stmt = sqlsrv_query($conn, $query, $params);

    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    ($cargo_sirec == 2 ? $type = 4 : $type = 2);
    $candidatos = array();
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $candidato = array(
            'id_ambito' => $row['id_ambito'],
            'id_coalicion' => $row['id_coalicion'],
            'id_partido_politico' => $row['id_partido_politico'],
            'tipo_asociacion' => utf8_encode($row['tipo_asociacion']),
            'partido_csp' => $row['partido_csp'],
            'nombre_completo' => utf8_encode($row['nombre_completo']),
            'id_participante' => $row['id_participante']
        );
        $candidatos[] = $candidato;
    }

    // echo json_encode($candidatos);


    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);

    // Leer el archivo JSON existente
    $jsonFilePath = 'db/candidatos.json'; // Reemplaza con la ruta a tu archivo JSON
    $jsonContent = file_get_contents($jsonFilePath);
    $jsonData = json_decode($jsonContent, true);

    // echo "<pre>";
    // echo json_encode($jsonData);
    // echo "</pre>";

    // exit;

    // Actualizar los elementos del cargo_sirec correspondiente
    $found = false;
    foreach ($jsonData as &$item) {
        // echo "entra!";
        // echo $item['candidatos']; 
        if ($item['cargo_sirec'] == $cargo_sirec) {
            // echo "entra!";
            $item['type'] = $type;
            $item['candidatos'] = $candidatos;
            $item['fecha_hora_actualizado'] = date('Y-m-d H:i:s');
            $found = true;
            break;
        }
    }
    // exit;

    // Si no se encontró el cargo_sirec, agregar un nuevo elemento
    if (!$found) {
        $jsonData[] = array(
            'cargo_sirec' => $cargo_sirec,
            'type' => '',
            'candidatos' => $candidatos,
            'fecha_hora_actualizado' => date('Y-m-d H:i:s')
        );
    }

    // Guardar el archivo JSON actualizado
    file_put_contents($jsonFilePath, json_encode($jsonData, JSON_PRETTY_PRINT));

    // Respuesta
    echo json_encode(
        array(
            'ok' => true,
            'msg' => 'Datos actualizados correctamente.'
        )
    );
}