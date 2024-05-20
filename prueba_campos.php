<?php
// Cabecera para evitar CORS
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Allow: GET, POST, OPTIONS, PUT, DELETE");

include "funciones.php";


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
	if($_SERVER['REQUEST_METHOD']=="GET"){
		
		$type = trim(htmlentities($_GET["type"]));
		
		$itemRecords = array();
		
		$db = new SQLite3('db/database.db3');
		
		// Data general
		$itemRecords = getAvanceContabilizadaResumen($db);
		
		// consigo Rankig ganadores 
		$items_winner_JG = getRecordSetMap(1, $db);
		$items_winner_MR = getRecordSetMap(2, $db);
		$items_winner_RP = getRecordSetMap(3, $db);
		$items_winner_ALC = getRecordSetMap(4, $db);
		
////var_dump($items_winner_ALC);  
////return;
		// Cargo datos para JSON:
		// Rank JG
		$itemRecords["rank_dto_jg"] = $items_winner_JG;
		// Rank DMR
		$itemRecords["rank_dto_dmr"] = $items_winner_MR;
		// Rank RP
		$itemRecords["rank_dto_rp"] = $items_winner_RP;
		// Rank ALC
		$itemRecords["rank_dto_alc"] = $items_winner_ALC;
		
		
		
		// DTOS y ALCALDIAS GANADAS (dmr/alcaldes)
		
		
		$winDTO_MR = contarItemsPorParticipante($items_winner_MR);
		$winALC_ALC = contarItemsPorParticipante($items_winner_ALC);
		// Dtos ganados (DMR)
		//$itemRecords["win_dto_dmr"] = $winDTO_MR;
		// Dtos ganados (DMR)
		//$itemRecords["win_alc_alc"] = $winALC_ALC;
		
		// Data de las elecciones (CDMX)
		$items_Data_JG = getRecordSetData(1, $db);
		$items_Data_MR = getRecordSetData(2, $db);
		$items_Data_RP = getRecordSetData(3, $db);
		$items_Data_ALC = getRecordSetData(4, $db);
		// DATOS JG
		$itemRecords["votos_del_jg"] = $items_Data_JG;
		// DATOS MR
		$itemRecords["votos_dto_dmr"] = $items_Data_MR;
		// DATOS RP
		$itemRecords["votos_dto_rpe"] = $items_Data_RP;
		// DATOS ALC
		$itemRecords["votos_del_alc"] = $items_Data_ALC;
		
		// RESULTADO DEL ESTADO DE LAS TRANSACCIONES 
		$estado = array(
			"reg_data" => 1,
			"reg_cat" => 0,
			"error" => 0
		);
		
		$itemRecords["estado"] = [$estado];
		
		echo json_encode($itemRecords); 
		
		unset ($itemRecords, $items_Data_JG, $items_Data_MR, $items_Data_ALC, $items_winner_JG, $items_winner_MR, $items_winner_ALC);
		
		$db->close();
		unset($db);	
		return;
		
		
		/*
		if(!$items_Data_ALC) {
			echo "sin datos";
			return;
		}
		
		echo "<br><br>GANADORES ITEMS: <br><br>";
		var_dump($items_Data_ALC);
		
		foreach ($items_Data_ALC as $item => $valores) {
			echo "<br>Valores para $item :<br><br>";
			var_dump($valores);
			echo "<br><br>";
		}
		echo "<br><br><br>";
		
		return;
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
?>
