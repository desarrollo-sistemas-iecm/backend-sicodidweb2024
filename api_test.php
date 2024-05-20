<?php
// Cabecera para evitar CORS
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
//header("Allow: GET, POST, OPTIONS, PUT, DELETE");
header("Allow: GET, POST");

$method = $_SERVER['REQUEST_METHOD'];
if($method == "OPTIONS") {
    die();
}

// Solo aceptamos POSTS
if($method!='POST'){
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
	if($_SERVER['REQUEST_METHOD']=="POST"){
		
			// CHECAMPS BODY:
			// Procesamos los datos
			$datos = json_decode(file_get_contents('php://input'), true);
			
			// error
			$error =666;
			
			// apertura de BD
			$db = new SQLite3('db/database.db3');
			// Contadores:
			$reg_data = 0;
			$reg_cat =0;
			
			
			// Registro general de BD
			$itemRecords = array();	
			
			//----------------------------------
			// Catalogo de Delegaciones
			$res_catch = $db->query('SELECT id_delegacion as [value], nombre_delegacion as [label]  FROM cain_cat_delegacion order by id_delegacion;');
			
			$itemRecords["delegaciones"] = array();
			$db->enableExceptions(false);
			while ($row_catch = $res_catch->fetchArray(SQLITE3_ASSOC))
			{
				//$var_catch = $row_catch['value']." - ".$row_catch['label']."<br>";
				array_push($itemRecords["delegaciones"], $row_catch);
				$reg_cat++;
			}
			//----------------------------------
			
			
			//--------------------------------------------------
			// Catálogo de Alcaldes
			$res_catch = $db->query('select JD.id_participante, P.descripcion, P.siglas, JD.id_delegacion, 
				JD.tipo_participante, 	JD.prelacion, JD.integrantes   from scd_participantes_jdel JD 
				left join scd_cat_participantes P
				on JD.id_participante = P.id_participante
				where id_delegacion='.$datos["id_delegacion"].';');
				
			$itemRecords["alcaldes"] = array();
			$db->enableExceptions(false);
			while ($row = $res_catch->fetchArray(SQLITE3_ASSOC))
			{
				//$var_catch = $row_catch['value']." - ".$row_catch['label']."<br>";
				array_push($itemRecords["alcaldes"], $row);
				$reg_data++;
			}
			//---------------------------------------------------
			// Datos: Armamos solo los campos a traer
			//----------------------------------------------------
			$participan = "";
			foreach ($itemRecords["alcaldes"] as $clave => $valor) {
				//echo "<br><br>".$clave." - ".$valor["id_participante"]." - ".$valor["descripcion"];
				$participan .= (', sum("votos_part_'.$valor["id_participante"].'") as votos_part_'.$valor["id_participante"]);
			}
			
			//echo "<br><br>PARTICIPAN: ".$participan."<br><br>";
			/*
			echo "<br><br>CUANTOS: ".count($itemRecords["alcaldes"]);
			echo "<br><br>DATO: ".$itemRecords["alcaldes"][0]["id_participante"]." - ".$itemRecords["alcaldes"][0]["descripcion"];

			foreach ($itemRecords["alcaldes"] as $clave => $valor) {
				echo "<br><br>".$clave." - ".$valor["id_participante"]." - ".$valor["descripcion"];
			}
			
			$res_catch = $db->query('SELECT "id_delegacion", sum("votos_part_1") as votos_part_1, sum("votos_part_2") as votos_part_2, sum("votos_part_3")  as votos_part_3, sum("votos_part_4")  as votos_part_4, sum("votos_part_5") as votos_part_5, sum("votos_part_6") as votos_part_6, sum("votos_part_7") as votos_part_7, sum("votos_part_8")  as votos_part_8, sum("votos_part_9") as votos_part_9, sum("votos_part_10") as votos_part_10, sum("votos_part_11")  as votos_part_11, sum("votos_part_12")  as votos_part_12, sum("votos_part_13") as votos_part_13, sum("votos_part_14") as votos_part_14, sum("votos_part_15") as votos_part_15, sum("votos_part_16") as votos_part_16, sum("votos_part_17") as votos_part_17, sum("votos_part_18") as votos_part_18, sum("votos_part_19") votos_part_19, sum("votos_part_20") as votos_part_20, 
			 sum("votos_part_21") as votos_part_21, sum("votos_part_22") votos_part_22, sum("votos_part_23") as votos_part_23, sum("votos_part_24") as votos_part_24, sum("votos_part_25") as votos_part_25, sum("votos_part_26") as votos_part_26, sum("votos_part_27") as votos_part_27, sum("votos_part_28") as votos_part_28, sum("votos_part_29") as votos_part_29, sum("votos_part_30") as votos_part_30, sum("votos_part_31") as votos_part_31, sum("votos_part_32") as votos_part_32,  sum("votos_part_33") as votos_part_33, sum("votos_part_34") as votos_part_34, sum("votos_part_35") as votos_part_35, 
			 sum("total_votos_cc1") as total_votos_cc1, sum("total_votos_cc2") as total_votos_cc2, sum("total_votos_cc3") as total_votos_cc2, sum("total_votos_cc4") as total_votos_cc4, sum("total_votos_cc5") as total_votos_cc5, sum("total_votos_cc6") as total_votos_cc6, sum("total_votos_cc7") as total_votos_cc7, sum("total_votos_cc8") as total_votos_cc8, sum("total_votos_cc9") as total_votos_cc9, sum("votos_cand_no_reg") as votos_cand_no_reg, sum("votos_nulos") as votos_nulos, sum("votacion_total") as votacion_total, sum("boletas_sob") as boletas_sob, sum("ciudadanos_votaron") as ciudadanos_votaron, sum("representantes_votaron") as representantes_votaron, sum("total_votaron") as total_votaron, sum("boletas_extraidas") as boletas_extraidas 
				FROM "prep_votos" 
			 group by id_delegacion');
			*/
			$res_catch = $db->query('SELECT "id_delegacion" '.$participan.',
			 sum("total_votos_cc1") as total_votos_cc1, sum("total_votos_cc2") as total_votos_cc2, sum("total_votos_cc3") as total_votos_cc2, sum("total_votos_cc4") as total_votos_cc4, sum("total_votos_cc5") as total_votos_cc5, sum("total_votos_cc6") as total_votos_cc6, sum("total_votos_cc7") as total_votos_cc7, sum("total_votos_cc8") as total_votos_cc8, sum("total_votos_cc9") as total_votos_cc9, sum("votos_cand_no_reg") as votos_cand_no_reg, sum("votos_nulos") as votos_nulos, sum("votacion_total") as votacion_total, sum("boletas_sob") as boletas_sob, sum("ciudadanos_votaron") as ciudadanos_votaron, sum("representantes_votaron") as representantes_votaron, sum("total_votaron") as total_votaron, sum("boletas_extraidas") as boletas_extraidas 
				FROM "prep_votos" where id_delegacion='.$datos["id_delegacion"].'
			 group by id_delegacion');

			$itemRecords["datos"] = array();
			$db->enableExceptions(false);
			while ($row_catch = $res_catch->fetchArray(SQLITE3_ASSOC))
			{
				//$var_catch = $row_catch['value']." - ".$row_catch['label']."<br>";
				array_push($itemRecords["datos"], $row_catch);
				$reg_cat++;
			}
			

			// Cerramos base de datos
			$db->close();
			unset($db);
			$error = 0;
			
			// Registro de estado
			$itemRecords["estado"] = array();
			
			$estado = array(
				"reg_data" => $reg_data,
				"reg_cat" => $reg_cat,
				"error" => $error
			);
			
			array_push($itemRecords["estado"], $estado);

			
			echo json_encode($itemRecords);


		//-------------------------------
		/*
			echo "<br><br>CUANTOS: ".count($itemRecords["alcaldes"]);
			echo "<br><br>DATO: ".$itemRecords["alcaldes"][0]["id_participante"]." - ".$itemRecords["alcaldes"][0]["descripcion"];

			foreach ($itemRecords["alcaldes"] as $clave => $valor) {
				echo "<br><br>".$clave." - ".$valor["id_participante"]." - ".$valor["descripcion"];
			}
		*/
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
