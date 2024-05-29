<?php

function franja2_esperadas($type, $db)
{
		
	$sqlData = " select * from (
 select count(*) as cuantos, sum(lista_nominal) as ln from scd_casillas
) as esperadas";
	
	$record = array();
	
	$res_catch = $db->query($sqlData);
	if(!$res_catch) return null;
	$db->enableExceptions(false);
	
	while ($row = $res_catch->fetchArray(SQLITE3_ASSOC))
	{
		array_push($record, $row);
	}


	return $record;
}

function franja2($type, $db)
{
	
	
		
	$sqlData = "select *, 'capturadas' as tipo,
 CASE WHEN ln = 0 THEN NULL ELSE ROUND(votacion_total * 100.0 / ln, 4) END as participacion
from (
    SELECT 
        id_tipo_eleccion, 
        COUNT(C.id_distrito) AS cuantos, 
        SUM(C.lista_nominal) AS ln, 
        SUM(votos_cand_no_reg) AS votos_cand_no_reg, 
        SUM(votos_nulos) AS votos_nulos,
        SUM(votacion_total) AS votacion_total
    FROM scd_votos P
        LEFT JOIN scd_casillas C 
        ON P.id_distrito = C.id_distrito 
        AND P.id_delegacion = C.id_delegacion 
        AND P.id_seccion = C.id_seccion 
        AND P.tipo_casilla = C.tipo_casilla
	where id_tipo_eleccion=".$type."
    GROUP BY 
        id_tipo_eleccion
 ) as capturadas";
	
	$sqlData .= "
	UNION ALL
	 select *, 'capturadas' as tipo, CASE WHEN ln = 0 THEN NULL ELSE ROUND(votacion_total * 100.0 / ln, 4) END as participacion
	from (
		SELECT 
			id_tipo_eleccion, 
			COUNT(C.id_distrito) AS cuantos, 
			SUM(C.lista_nominal) AS ln, 
			SUM(votos_cand_no_reg) AS votos_cand_no_reg, 
			SUM(votos_nulos) AS votos_nulos,
			SUM(votacion_total) AS votacion_total
		FROM scd_votos P
			LEFT JOIN scd_casillas C 
			ON P.id_distrito = C.id_distrito 
			AND P.id_delegacion = C.id_delegacion 
			AND P.id_seccion = C.id_seccion 
			AND P.tipo_casilla = C.tipo_casilla
		where P.contabilizar='T'
		GROUP BY 
			id_tipo_eleccion
	 ) as contabilizadas
	";
	
	$sqlData .= "
		UNION ALL
		select *, 'nocontabilizada' as tipo,
		 CASE WHEN ln = 0 THEN NULL ELSE ROUND(votacion_total * 100.0 / ln, 4) END as participacion
		from (
			SELECT 
				id_tipo_eleccion, 
				COUNT(C.id_distrito) AS cuantos, 
				SUM(C.lista_nominal) AS ln, 
				SUM(votos_cand_no_reg) AS votos_cand_no_reg, 
				SUM(votos_nulos) AS votos_nulos,
				SUM(votacion_total) AS votacion_total
			FROM scd_votos P
				LEFT JOIN scd_casillas C 
				ON P.id_distrito = C.id_distrito 
				AND P.id_delegacion = C.id_delegacion 
				AND P.id_seccion = C.id_seccion 
				AND P.tipo_casilla = C.tipo_casilla
			where P.contabilizar='F'
			GROUP BY 
				id_tipo_eleccion
		 ) as sin_contabilizadas
		 ";
	$record = array();
	
	$res_catch = $db->query($sqlData);
	if(!$res_catch) return null;
	$db->enableExceptions(false);
	
	while ($row = $res_catch->fetchArray(SQLITE3_ASSOC))
	{
		array_push($record, $row);
	}


	return $record;
}

	$db = new SQLite3('db/database.db3');
	$db->enableExceptions(false);
	
	$registro = franja2(1, $db);
	$registroEsperadas = franja2_esperadas(1, $db);
	
	echo json_encode($registro);
	
	echo json_encode($registroEsperadas);

?>