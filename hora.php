<?php
	$fecha_original = "2024-11-20 17:00hr";

	// Convertir la cadena de fecha a un objeto DateTime
	$fecha_objeto = DateTime::createFromFormat('Y-m-d H:i\h\r', $fecha_original);

	// Formatear la fecha según el formato deseado
	$fecha_formateada = $fecha_objeto->format('d/m/Y H:i \h\r');

	echo $fecha_formateada;
?>