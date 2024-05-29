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

// Función para obtener la información del servidor
function getServerInfo()
{
    return array(
        'nombre_server' => $_SERVER['SERVER_NAME'],
        'ip' => $_SERVER['SERVER_ADDR'],
        'hostname' => gethostname(),
        'getenv' => getenv('HOSTNAME')
    );
}

// Función para manejar errores y excepciones
function handleException($exception)
{
    error_log($exception->getMessage()); // Registrar el error en el log del servidor
    http_response_code(500);
    echo json_encode(
        array(
            'ok' => false,
            'msg' => 'Ocurrió un error en el servidor.'
        )
    );
    exit();
}

set_exception_handler('handleException');

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $db = new SQLite3('db/database.db3');
        $db->enableExceptions(true);
        $result = $db->query('SELECT corte_fecha, corte_hora FROM corte');
        $row = $result->fetchArray(SQLITE3_ASSOC);

        if (!$row) {
            $response = array(
                'ok' => false,
                'msg' => '¡Sin información de corte!'
            );
        } else {
            // Obtener la hora actual y restarle una hora
            $horarioConsulta = new DateTime();
            $horarioConsulta->sub(new DateInterval('PT1H')); // Restar una hora
            $formattedHorarioConsulta = $horarioConsulta->format('d-m-Y H:i:s');

            $response = array(
                'ok' => true,
                'msg' => '¡La información está lista!',
                'data' => array(
                    'corte' => $row,
                    'horario_consulta' => $formattedHorarioConsulta
                )
            );
        }
        echo json_encode($response);
    } else {
        http_response_code(405);
        echo json_encode(
            array(
                'ok' => false,
                'msg' => 'Método no permitido.'
            )
        );
    }
} catch (Exception $e) {
    handleException($e);
}