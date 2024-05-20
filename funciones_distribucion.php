<?php

// Cabecera para evitar CORS
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Allow: GET, POST, OPTIONS, PUT, DELETE");
$error="";
$method = $_SERVER['REQUEST_METHOD'];
if($method == "OPTIONS") {
    die();
}

// Solo aceptamos POSTS
if($method!='GET'){
	$error=666;
	$estado = array(
			"reg_data" => 0,
			"reg_cat" => 0,
			"error" => "Acción o método no implementado(".$method.")"
		);
	echo json_encode($estado);	
	return;
}
include "helpers.php";

try {
	if($_SERVER['REQUEST_METHOD']=="GET"){
		$item ="";
		$type = trim(htmlentities($_GET["type"]));
		$item = trim(htmlentities($_GET["item"]));
		$item_2=""; $item_3="";
		if(isset($_GET["item_2"])) $item_2 = trim(htmlentities($_GET["item_2"]));
		if(isset($_GET["item_3"])) $item_3 = trim(htmlentities($_GET["item_3"]));
		
		$name_item ="";
		$whare = "";
		$catalogo = "";
		
		switch ($type) {
			case "1": // JG
				$catalogo = "scd_candidatos_jgob";
				$name_item ="id_distrito";
				//$whare = "  where id_distrito = ".$item;
				$whare ="";
				break;
			case "2":	//DMR
				$catalogo = "scd_candidatos_mr";
				$name_item ="id_distrito";
				$whare = "  where id_distrito = ".$item;
				break;
			case "3": //RP
				$catalogo = "";
				$name_item ="id_distrito";
				$whare = "  where id_distrito = ".$item;
				break;
			case "4": //ALC
				$catalogo = "scd_candidatos_jdel";
				$name_item ="id_delegacion";
				$whare = "  where id_delegacion = ".$item;
				break;
		}		
		
		$itemRecords = array();
		$itemRecords["catalogo"] = array();
		$itemRecords["split_integrantes"] = array();
		$itemRecords["data"] = array();

		// Abro BD para consulta
		$db = new SQLite3('db/database.db3');
		
		// Consigo catalogo de CANDIDATOS
		$qry = "SELECT * FROM ".$catalogo.$whare;
		$res_catch = $db->query($qry);
		while ($row = $res_catch->fetchArray(SQLITE3_ASSOC))
		{
			array_push($itemRecords["catalogo"], $row);
		}
		
		// Hago split a campo de integrantes
		
		
		$qry = "WITH RECURSIVE neat(
					id, tag, etc
				) AS(
					SELECT
						id_participante
						, ''
						, integrantes || '&'
					FROM scd_candidatos_jgob
					WHERE id_participante
					UNION ALL
					SELECT 
						id
						, SUBSTR(etc, 0, INSTR(etc, '&'))
						, SUBSTR(etc, INSTR(etc, '&')+1)
					FROM neat
					WHERE etc <> ''
				)
				SELECT 
					id as id_participante, ('votos_part_' || tag) as nombre_campo ,CAST(tag AS INT) as participante
				FROM neat
				WHERE tag <> ''
				ORDER BY 
					participante ASC, tag ASC
				; ";
		$res_catch = $db->query($qry);
		while ($row = $res_catch->fetchArray(SQLITE3_ASSOC))
		{
			array_push($itemRecords["split_integrantes"], $row);
		}
		
		
		// --- ABro bd
		//$db = new SQLite3('db/database.db3');
		//getFieldNameParticipan("4", " where id_delegacion = 2 ", "id_delegacion, ", " and id_seccion=22");
		//$qryParticipantes =  getFieldNameParticipan($type, " where ".$name_item." = ".$item." ", $name_item.", ");
		
		//$whare .= " and id_tipo_eleccion=".$type;
		$qryParticipantes = "SELECT id_tipo_eleccion, sum(votos_part_1) as votos_part_1, sum(votos_part_2) as votos_part_2, sum(votos_part_3) as votos_part_3, sum(votos_part_4) as votos_part_4, sum(votos_part_5) as votos_part_5, sum(votos_part_6) as votos_part_6, sum(votos_part_7) as votos_part_7, sum(votos_part_8) as votos_part_8, sum(votos_part_9) as votos_part_9, sum(votos_part_10) as votos_part_10, sum(votos_part_11) as votos_part_11, sum(votos_part_12) as votos_part_12, sum(votos_part_13) as votos_part_13, sum(votos_part_14) as votos_part_14, sum(votos_part_15) as votos_part_15, sum(votos_part_16) as votos_part_16, sum(votos_part_17) as votos_part_17, sum(votos_part_18) as votos_part_18, sum(votos_part_19) as votos_part_19, sum(votos_part_20) as votos_part_20, sum(votos_part_21) as votos_part_21, sum(votos_part_22) as votos_part_22, sum(votos_part_23) as votos_part_23, sum(votos_part_24) as votos_part_24, sum(votos_part_25) as votos_part_25, sum(votos_part_26) as votos_part_26, sum(votos_part_27) as votos_part_27, sum(votos_part_28) as votos_part_28, sum(votos_part_29) as votos_part_29, sum(votos_part_30) as votos_part_30, sum(votos_part_31) as votos_part_31, sum(votos_part_32) as votos_part_32, sum(votos_part_33) as votos_part_33, sum(votos_part_34) as votos_part_34, sum(votos_part_35) as votos_part_35, sum(total_votos_cc1) as total_votos_cc1, sum(total_votos_cc2) as total_votos_cc2, sum(total_votos_cc3) as total_votos_cc3, sum(total_votos_cc4) as total_votos_cc4, sum(total_votos_cc5) as total_votos_cc5, sum(total_votos_cc6) as total_votos_cc6, sum(total_votos_cc7) as total_votos_cc7, sum(total_votos_cc8) as total_votos_cc8, sum(total_votos_cc9) as total_votos_cc9, sum(votos_cand_no_reg) as votos_cand_no_reg, sum(votos_nulos) as votos_nulos, sum(votacion_total) as votacion_total, sum(boletas_sob) as boletas_sob, sum(ciudadanos_votaron) as ciudadanos_votaron, sum(representantes_votaron) as representantes_votaron, sum(total_votaron) as total_votaron, sum(boletas_extraidas) as boletas_extraidas, sum(total_sobres) as total_sobres  FROM prep_votos ";
		
		if ($type == 1){
			$qryParticipantes .= $whare." where contabilizar = 'T' AND id_tipo_eleccion=".$type;
		} else {
			$qryParticipantes .= $whare." and contabilizar = 'T' AND id_tipo_eleccion=".$type;
		}

		if($item!="" && $type == 2 || $item!="" && $type == 1){
			$qryParticipantes .= " and id_distrito = ".$item." ";
		}else if($item!="" && $type ==4) {
			$qryParticipantes .= " and id_delegacion = ".$item." ";
		}

		if($item_2!=""){
			$qryParticipantes .= " and id_seccion = ".$item_2;
			if($item_3!=""){
				$qryParticipantes .= " and tipo_casilla = '".$item_3."'";
			}
		}
		$qryParticipantes .= " and contabilizar='T'";
		//contabilizar
		//echo $qryParticipantes; return;
		$res_catch = $db->query($qryParticipantes);
		while ($row = $res_catch->fetchArray(SQLITE3_ASSOC))
		{
			array_push($itemRecords["data"], $row);
		}
	/*	
		
		$qryParticipantes = "";
		
		$qryParticipantes .= " where id_tipo_eleccion=".$type."";
		
		if($item_2!=""){
			$qryParticipantes .= " and id_seccion = ".$item_2;
			if($item_3!=""){
				$qryParticipantes .= " and tipo_casilla = '".$item_3."'";
			}
		}
		echo $row["query"]; return;
		
	*/
		// USO:   http://localhost/prep2024/funciones_distribucion.php?type=4&item=3&item_2=&item_3=
		
		echo json_encode($itemRecords);	
		return;
	}
	else{
		$error=666;
		$estado = array(
				
				"reg_data" => 0,
				"reg_cat" => 0,
				"error" => "Acción no implementada."
			);
		echo json_encode($estado);
	}
} catch (Exception $e) {

	$host = $_SERVER['HTTP_HOST'];
	$ruta = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
	//$html = 'actualizando_bd.html';
	//$url = "http://$host$ruta/$html";
	//header("Location: $url");
	echo $e;
} finally {
	if($error==666)
	{
		//$host = $_SERVER['HTTP_HOST'];
		//$ruta = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
		$html = 'actualizando_bd.html';
		//$url = "http://$host$ruta/$html";
		header("Location: $html");
	}
}


?>