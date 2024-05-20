<?php
function contarItemsPorParticipante($datos) {
    $conteo = array();

    foreach ($datos as $dato) {
        $id_participante = $dato['id_participante'];

        if (isset($conteo[$id_participante])) {
            $conteo[$id_participante]++;
        } else {
            $conteo[$id_participante] = 1;
        }
    }

    return $conteo;
}

// Ejemplo de uso con tus datos
$datos = array(
    array("item" => 2, "number" => 86661, "name" => "total_votos_cc1", "rank" => 1, "id_participante" => 28),
    array("item" => 3, "number" => 168872, "name" => "total_votos_cc1", "rank" => 1, "id_participante" => 28),
    array("item" => 4, "number" => 72304, "name" => "total_votos_cc1", "rank" => 1, "id_participante" => 28),
    array("item" => 5, "number" => 213088, "name" => "total_votos_cc1", "rank" => 1, "id_participante" => 28),
    array("item" => 6, "number" => 4587, "name" => "votos_part_6", "rank" => 1, "id_participante" => 6),
    array("item" => 7, "number" => 199454, "name" => "total_votos_cc1", "rank" => 1, "id_participante" => 28),
    array("item" => 8, "number" => 56015, "name" => "total_votos_cc1", "rank" => 1, "id_participante" => 28),
    array("item" => 9, "number" => 21424, "name" => "total_votos_cc1", "rank" => 1, "id_participante" => 28),
    array("item" => 10, "number" => 182594, "name" => "total_votos_cc1", "rank" => 1, "id_participante" => 28),
    array("item" => 11, "number" => 3457, "name" => "votos_part_9", "rank" => 1, "id_participante" => 9),
    array("item" => 12, "number" => 120020, "name" => "total_votos_cc1", "rank" => 1, "id_participante" => 28),
    array("item" => 13, "number" => 60600, "name" => "total_votos_cc1", "rank" => 1, "id_participante" => 28),
    array("item" => 14, "number" => 3754, "name" => "votos_part_6", "rank" => 1, "id_participante" => 6),
    array("item" => 15, "number" => 119443, "name" => "total_votos_cc1", "rank" => 1, "id_participante" => 28),
    array("item" => 16, "number" => 104163, "name" => "total_votos_cc1", "rank" => 1, "id_participante" => 28),
    array("item" => 17, "number" => 83479, "name" => "total_votos_cc1", "rank" => 1, "id_participante" => 28)
);	


$resultado = contarItemsPorParticipante($datos);

// Almacenar el resultado en un arreglo
$arreglo_resultado = array();
foreach ($resultado as $participante => $cantidad) {
    $arreglo_resultado[] = array("id_participante" => $participante, "cantidad_items" => $cantidad);
}

// Imprimir el resultado en pantalla (opcional)
print_r($arreglo_resultado);
?>
