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

try {
	
	function consigueCat($db, $id_campo, $type){
		
		$tabla_cat = "";
		$name_item_cat = "";
		$where_temp = "";
		switch ($type) {
			case 1:
				$tabla_cat = "scd_participantes_jgob";
				$name_item_cat ="id_distrito";
				$where_temp = "";
				break;
			case 2:
				$tabla_cat = "scd_participantes_mr";
				$name_item_cat ="id_distrito";
				$where_temp = " ".$name_item_cat." = ".$id_campo." AND ";
				break;
			case 3:
				$tabla_cat = "scd_participantes_rp";
				$name_item_cat ="id_distrito";
				$where_temp = " ".$name_item_cat." = ".$id_campo." AND ";
				break;
			case 4:
				$tabla_cat = "scd_participantes_jdel";
				$name_item_cat ="id_delegacion";
				$where_temp = " ".$name_item_cat." = ".$id_campo." AND ";
				break;
		}
			// Lamamos catálogo de porcentajes
			$sqlCatPor = "WITH RECURSIVE neat(
    id, tag1, etc1, tag2, etc2 
			) AS(
				SELECT
					id_participante
					, ''
					, integrantesPartidosCC || '&'
					, '' 
					, porcentaje || '&' 
				FROM ".$tabla_cat." 
				WHERE ".$where_temp." porcentaje != ''
				UNION ALL
				SELECT 
					id
					, SUBSTR(etc1, 0, INSTR(etc1, '&'))
					, SUBSTR(etc1, INSTR(etc1, '&')+1)
					, SUBSTR(etc2, 0, INSTR(etc2, '&')) 
					, SUBSTR(etc2, INSTR(etc2, '&')+1)
				FROM neat
				WHERE etc1 <> '' OR etc2 <> '' 
			)
			SELECT 
				id AS id_participante,
				('votos_part_' || tag1) AS nombre_campo1, 
				CAST(tag1 AS INT) AS id,
				CAST(tag2 AS NUMBER) AS porcent
			FROM neat
			WHERE tag1 <> '' OR tag2 <> '' 
			ORDER BY 
				id ASC,
				tag1 ASC";
				

		$res_catchTMP = $db->query($sqlCatPor);
		
		$itemRecordsTMP = array();
		$db->enableExceptions(false);
		while ($rowTMP = $res_catchTMP->fetchArray(SQLITE3_ASSOC))
		{
			array_push($itemRecordsTMP, $rowTMP);
		}
		//	echo "<br><br>CAT : ".$sqlCatPor."<br><br>";
		return $itemRecordsTMP;
					
	}
	
// Fin funcion
	if($_SERVER['REQUEST_METHOD']=="GET"){
		
		$type = trim(htmlentities($_GET["type"]));
		$item = trim(htmlentities($_GET["item"]));
		$item_2=""; $item_3="";
		if(isset($_GET["item_2"])) $item_2 = trim(htmlentities($_GET["item_2"]));
		if(isset($_GET["item_3"])) $item_3 = trim(htmlentities($_GET["item_3"]));
		
		$name_item ="";
		// Registro general de BD
		$itemRecords = array();
		$itemNameRecords = array();		
		
		
		$tmp = ['id_delegacion', 'id_distrito', 'id_seccion', 'tipo_casilla' ];
		
		foreach ($tmp as $nombre) {
			$columna = array(
						"dataIndex" => $nombre,
						"title" => $nombre,
						"key"=> $nombre,
						"resizable" => 'resizable',
						"width"=> 100,
						"minWidth"=> 100,
						"maxWidth"=> 200
				);
			$itemNameRecords[]= $columna;	
		}
		
		$name_item ="";
		$name_total_cc="";
		
		$qryParticipantes = "";
		switch ($type) {
			case 1:
				$name_item ="id_distrito";
				break;
			case 2:
				$name_item ="id_distrito";
				break;
			case 3:
				$name_item ="id_distrito";
				break;
			case 4:
				$name_item ="id_delegacion";
				break;
		}
		
		$qryData = "SELECT id_distrito, id_delegacion, id_seccion, id_tipo_eleccion, sum(votos_part_1) as votos_part_1, sum(votos_part_2) as votos_part_2, sum(votos_part_3) as votos_part_3, sum(votos_part_4) as votos_part_4, sum(votos_part_5) as votos_part_5, sum(votos_part_6) as votos_part_6, sum(votos_part_7) as votos_part_7, 
				sum(votos_part_8) as votos_part_8, sum(votos_part_9) as votos_part_9, sum(total_votos_cc1) as total_votos_cc1, sum(total_votos_cc2) as total_votos_cc2, sum(total_votos_cc3) as total_votos_cc3, sum(total_votos_cc4) as total_votos_cc4, sum(total_votos_cc5) as total_votos_cc5, sum(total_votos_cc6) as total_votos_cc6, sum(total_votos_cc7) as total_votos_cc7, sum(total_votos_cc8) as total_votos_cc8, sum(total_votos_cc9) as total_votos_cc9, sum(votos_cand_no_reg) as votos_cand_no_reg, sum(votos_nulos) as votos_nulos, 
				sum(votacion_total) as votacion_total, sum(boletas_sob) as boletas_sob, 
				sum(ciudadanos_votaron) as ciudadanos_votaron, sum(representantes_votaron) as representantes_votaron, 
				sum(total_votaron) as total_votaron, sum(boletas_extraidas) as boletas_extraidas, 
				sum(total_sobres) as total_sobres 
					FROM scd_votos where id_tipo_eleccion= ".$type;
		
		if($item!=""){
			$qryData .= " and ".$name_item.'='.$item;
		}
		if($item_2!=""){
			$qryData .= " and id_seccion = ".$item_2;
		}
		if($item_3!=""){
			$qryData .= " and tipo_casilla = '".$item_3."';";
		}
		
		//$qryData .= " GROUP BY ".$name_item;
		
		
		//echo $qryData; return;
		// apertura de BD
		$reg_data=0;
		$db = new SQLite3('db/database.db3');
		$res_catch = $db->query($qryData);
		
		$itemRecords["value_fields"] = array();
		$db->enableExceptions(false);
		
		$registro = array();
		while ($row = $res_catch->fetchArray(SQLITE3_ASSOC))
		{
			
			$registro = $row;
			
			// total_votos_cc1 == PRI/PAN/PRD
			// total_votos_cc5 == VERDE/PT/MORENA
			$itemCat = array();	
			$itemCat = consigueCat($db, $registro[$name_item] , $type );
			
			//echo "<br>total_votos_cc5: ".$registro["total_votos_cc5"]."<br>";
			
			$cuantos_par =0;
			$mod = 0;
			if(count($itemCat)>0){ 
				// Entró? existe datos a distribuir
				$id = 0;
				foreach ($itemCat as $rowTmp) {
					
						$id = $rowTmp["id_participante"];
						
						$votos_cc = 0;
						if($id ==10) {
							//PAN/PRI/PRD
							$votos_cc = $row["total_votos_cc1"];
							$cuantos_par = 3;
						}
						if($id ==14) {
							//PVEM/PT/MORENA
							$votos_cc = $row["total_votos_cc5"];
							$cuantos_par = 3;
						}
						
						
						$nombre = $rowTmp["nombre_campo1"];
						$porcent = $rowTmp["porcent"];
						$votos_a_repartir = ($porcent * $votos_cc)/100;
						
						$parte_entera = intval($votos_a_repartir);
						
						$anterior = $registro[$nombre];
						$registro[$nombre] = $anterior + $parte_entera;
						/*
						echo "<br><br>Original ".$anterior." , con reparto ".$registro[$nombre]." Campo ".$nombre.", % ".$porcent.", aplicado% ".$parte_entera." mod ".$mod."<br><br>";
						*/
				}
			}
			
			array_push($itemRecords["value_fields"], $registro);
			//echo "<br><br>CAT: <br>".var_dump($row)."<br><br>";
			
			$reg_data++;
		}
		

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
	$html = 'actualizando_bd.html';
	$url = "http://$host$ruta/$html";
	header("Location: $url");

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
