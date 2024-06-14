<?php
// Cabecera para evitar CORS
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Allow: GET, POST, OPTIONS, PUT, DELETE");

include "funciones.php";

$error="";

$type = ""; $item ="";$item_2=""; $item_3="";

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

function urbanasNoUrbanasENTIDAD($type, $item, $item_2, $item_3, $db)
{
	$type_param ="";
	if($type!="")
	{
		$type_param =$type;
	}
	else
	{
		$type_param=1;
	}
	$urbanas = "";
	$no_urbanas = "";
	$sqlNO_URBANA = "";
	$sqlURBANA = "";
	
	$record = array();
//	echo "URBANA";
//	echo $sqlURBANA ;
	
	
	$name_item ="";
	$whare = "";
	$and = " and ";
	
	switch ($type_param) {
		case "1": // JG
			$name_item ="id_distrito";
			$whare = " and id_distrito = ".$item;
			if($item==""){
				$name_item ="";
				$whare = "";
				
			}
			break;
		case "2":	//DMR
			$name_item ="id_distrito";
			$whare = " and id_distrito = ".$item;
			if($item==""){
				$name_item ="";
				$whare = "";
				
			}
			break;
		case "3": //RP
			$name_item ="id_distrito";
			$whare = " and id_distrito = ".$item;
			if($item==""){
				$name_item ="";
				$whare = "";
				
			}
			break;
		case "4": //ALC
			$name_item ="id_delegacion";
			$whare = " and id_delegacion = ".$item;
			if($item==""){
				$name_item ="";
				$whare = "";
				
			}
			break;
	}
		
	$sqlNO_URBANA = "SELECT count(id_distrito) as [NO_URBANAS] FROM scd_votos where clave_mdc in(SELECT clave_mdc FROM nourbanas) and id_tipo_eleccion='".$type_param."' and contabilizar='T' ".$whare;

	$sqlURBANA = "SELECT count(id_distrito) as [URBANAS] FROM scd_votos where clave_mdc not in(SELECT clave_mdc FROM nourbanas) and id_tipo_eleccion='".$type_param."' and contabilizar='T' ".$whare;
	
	
	
	if($item_2!=""){
		$sqlNO_URBANA .= " and id_seccion = ".$item_2;
		$sqlURBANA .= " and id_seccion = ".$item_2;
	}
	if($item_3!=""){
		$sqlNO_URBANA .= " and tipo_casilla = '".$item_3."';";
		$sqlURBANA .= " and tipo_casilla = '".$item_3."';";
	}
		
	
///	echo $sqlNO_URBANA; die();
	
	
	$res_catch = $db->query($sqlNO_URBANA);
	if(!$res_catch) return null;
	$db->enableExceptions(false);
	
	while ($row = $res_catch->fetchArray(SQLITE3_ASSOC))
	{
		//$var_catch = $row_catch['value']." - ".$row_catch['label']."<br>";
		$no_urbanas = "".$row["NO_URBANAS"]."";
		// if($type_param==1) break;
	}

	$res_catch = $db->query($sqlURBANA);
	if(!$res_catch) return null;
	$db->enableExceptions(false);
	
	while ($row = $res_catch->fetchArray(SQLITE3_ASSOC))
	{
		//$var_catch = $row_catch['value']." - ".$row_catch['label']."<br>";
		$urbanas = "".$row["URBANAS"]."";
		// if($type_param==1) break;
	}
		
	$record = array(
			"urbanas" => $urbanas,
			"nourbanas" => $no_urbanas,
	);
	return $record;
}

try {
	if($_SERVER['REQUEST_METHOD']=="GET"){
		$type = "1"; $item ="";$item_2=""; $item_3="";
		if(isset($_GET["type"])) $type = trim(htmlentities($_GET["type"]));
		//$item = trim(htmlentities($_GET["item"]));
		if(isset($_GET["item"])) $item = trim(htmlentities($_GET["item"]));
		if(isset($_GET["item_2"])) $item_2 = trim(htmlentities($_GET["item_2"]));
		if(isset($_GET["item_3"])) $item_3 = trim(htmlentities($_GET["item_3"]));
		
		
		$itemRecords = array();
		
		$db = new SQLite3('db/database.db3');
		
		
		// Data general
		$itemRecords = getAvanceContabilizadaResumen($db, $type);
		
		// 16/ABRIL/2024
		//echo $itemRecords["avance_dmr"][0]["actas_capturadas"]; die();
		
		if(isset($itemRecords["avance_dmr"][0])){
			$itemRecords["avance_dmr"][0]["actas_capturadas"] += isset($itemRecords["avance_rp"][0]["actas_capturadas"])?$itemRecords["avance_rp"][0]["actas_capturadas"]:0;
			$itemRecords["avance_dmr"][0]["actas_capturadas_de"] += 44;
			$itemRecords["avance_dmr"][0]["ln_capturadas"] += isset($itemRecords["avance_rp"][0]["ln_capturadas"])?$itemRecords["avance_rp"][0]["ln_capturadas"]:0;
			
			
		}
		/*
		if(isset($itemRecords["resumen_dmr"][0])){

			$itemRecords["resumen_dmr"][0]["votos_acumulados"] += isset($itemRecords["resumen_rp"][0]["votos_acumulados"])?$itemRecords["resumen_rp"][0]["votos_acumulados"]:0;
			$itemRecords["resumen_dmr"][0]["candidatos_no_reg"] += isset($itemRecords["resumen_rp"][0]["candidatos_no_reg"])?$itemRecords["resumen_rp"][0]["candidatos_no_reg"]:0;
			$itemRecords["resumen_dmr"][0]["nulos"] += isset($itemRecords["resumen_rp"][0]["nulos"])?$itemRecords["resumen_rp"][0]["nulos"]:0;
			$itemRecords["resumen_dmr"][0]["total"] += isset($itemRecords["resumen_rp"][0]["total"])?$itemRecords["resumen_rp"][0]["total"]:0;
			
			$votos_sin_especiales1 = str_replace(',', '', $itemRecords["resumen_rp"][0]["votos_sin_especiales"]);
			$votos_sin_especiales2 = str_replace(',', '', $itemRecords["resumen_dmr"][0]["votos_sin_especiales"]);
			$itemRecords["resumen_dmr"][0]["votos_sin_especiales"] = number_format($votos_sin_especiales1 + $votos_sin_especiales2, 2, '.', ',');
			
			$votos_especiales1 = str_replace(',', '', $itemRecords["resumen_rp"][0]["votos_especiales"]);
			$votos_especiales2 = str_replace(',', '', $itemRecords["resumen_dmr"][0]["votos_especiales"]);
			$itemRecords["resumen_dmr"][0]["votos_especiales"] = number_format($votos_especiales1 + $votos_especiales2, 2, '.', ',');
			
			//$itemRecords["resumen_dmr"][0]["votos_especiales"] += isset($itemRecords["resumen_rp"][0]["votos_especiales"])?$itemRecords["resumen_rp"][0]["votos_especiales"]:0;
			$itemRecords["resumen_dmr"][0]["suma_esp_s_esp"] += isset($itemRecords["resumen_rp"][0]["suma_esp_s_esp"])?$itemRecords["resumen_rp"][0]["suma_esp_s_esp"]:0;
			
			$acumulado = $itemRecords["resumen_dmr"][0]["votos_acumulados"];
			$total = $itemRecords["resumen_dmr"][0]["total"] ;
			$votos_acumulados_por = ($acumulado *100) /$total;
			$itemRecords["resumen_dmr"][0]["votos_acumulados_por"] = sprintf("%01.4f", $votos_acumulados_por)."%";
			
			$no_reg = $itemRecords["resumen_dmr"][0]["candidatos_no_reg"];
			$candidatos_no_reg_por = ($no_reg *100) /$total;
			 $itemRecords["resumen_dmr"][0]["candidatos_no_reg_por"] = sprintf("%01.4f", $candidatos_no_reg_por)."%";
			 
			 $nulo = $itemRecords["resumen_dmr"][0]["nulos"];
			 $nulos_por = ($nulo *100) /$total;
			 $itemRecords["resumen_dmr"][0]["nulos_por"] = sprintf("%01.4f", $nulos_por)."%";
			 
		}
		*/
		if(isset($itemRecords["capturadas_dmr"][0])){
			$itemRecords["capturadas_dmr"][0]["actas_capturadas"] += isset($itemRecords["capturadas_rp"][0]["actas_capturadas"])?$itemRecords["capturadas_rp"][0]["actas_capturadas"]:0;
			
			////// OJO!!!!!!
			$itemRecords["capturadas_dmr"][0]["actas_capturadas_de"] += 44;
			
			$itemRecords["capturadas_dmr"][0]["ln"] += isset($itemRecords["capturadas_rp"][0]["ln"])?$itemRecords["capturadas_rp"][0]["ln"]:0;	
		}
		
		if(isset($itemRecords["avance_alc"][0])){
			
			///////////////////////////////////////////////////////////////////////
			//////////////////////////////OJOOOOOOOOO!!!!/////////////////////////////////////////
			/// SI NO QUITAN ACTAS DE EXTRANJERO PONER!!!!!
			$itemRecords["avance_alc"][0]["actas_capturadas_de"] -= 12;
			
			if($itemRecords["avance_alc"][0]["actas_capturadas_de"]>0){
				$itemRecords["avance_alc"][0]["actas_cap_porcen"] = number_format($itemRecords["avance_alc"][0]["actas_capturadas"]*100/$itemRecords["avance_alc"][0]["actas_capturadas_de"], 4);
				if($itemRecords["avance_alc"][0]["actas_capturadas"]==$itemRecords["avance_alc"][0]["actas_capturadas_de"]){
					$itemRecords["avance_alc"][0]["actas_cap_porcen"]="100.0000";
							
				}
			}
		
		}
		
		
		// CONSEGUIMOS CORTE HORARIO
		$corte = getCorte($db);		
		$itemRecords["corte"] = $corte;
		
		// consigo Rankig ganadores 
		$items_winner_JG = getRecordSetMap(1, $db);
		$items_winner_MR = getRecordSetMap(2, $db);
		
			//echo var_dump($items_winner_MR); die();
		$items_winner_RP = getRecordSetMap(3, $db);
		$items_winner_ALC = getRecordSetMap(4, $db);
		
		if(count($items_winner_MR)>0){
				if($items_winner_MR[0]["name"]=='NA'){
					$items_winner_MR=[];
				}
				
		}
		
		if(count($items_winner_ALC)>0){
			if($items_winner_ALC[0]["name"]=='NA'){
				$items_winner_ALC=[];
			}
		}

//echo var_dump($items_winner_MR); echo "<br>".var_dump($items_winner_MR[0]["name"]); die();

		//Consigue campos a consultar para armar consultas generales
		$fields_Vote_JG = getFieldNameElection(1, $db, "", "");
		$fields_Vote_MR = getFieldNameElection(2, $db, "", "id_distrito, ");
		$fields_Vote_RP = getFieldNameElection(3, $db, "", "id_distrito, ");
		$fields_Vote_ALC = getFieldNameElection(4, $db, "", "id_delegacion, ");
	
		$itemRecords["fields_vote_jg"] = $fields_Vote_JG["value_fields"];
		$itemRecords["fields_vote_mr"] = $fields_Vote_MR["value_fields"];
		//$itemRecords["fields_vote_rp"] = $fields_Vote_RP["value_fields"];
		$itemRecords["fields_vote_alc"] = $fields_Vote_ALC["value_fields"];


		// Cargo datos para JSON:
		// Rank JG
		$itemRecords["rank_dto_jg"] = $items_winner_JG;
		// Rank DMR
		$itemRecords["rank_dto_dmr"] = $items_winner_MR;
		// Rank RP
		$itemRecords["rank_dto_rp"] = $items_winner_RP;
		// Rank ALC
		$itemRecords["rank_del_alc"] = $items_winner_ALC;
		
		
		
		// DTOS y ALCALDIAS GANADAS (dmr/alcaldes)
		
		
		$winDTO_MR = contarItemsPorParticipante($items_winner_MR, 2, $db);
		$winALC_ALC = contarItemsPorParticipante($items_winner_ALC, 4, $db);
		// Dtos ganados (DMR)
		$itemRecords["win_dto_dmr"] = $winDTO_MR;
		// Dtos ganados (DMR)
		$itemRecords["win_alc_alc"] = $winALC_ALC;
		
		// Data de las elecciones (CDMX)
		//$items_Data_JG = getRecordSetDataGroupByTypeCasillaDTO(1, $db);
		$items_Data_JG = getRecordSetData(1, $db);
		$items_Data_MR = getRecordSetData(2, $db);
		$items_Data_RP = getRecordSetData(3, $db);
		$items_Data_ALC = getRecordSetData(4, $db);
		
		
		// 22/Marzo/2024
		// Consigo la votación en el extranjero
		$items_Extranjero_JG = getRecordSetDataGroupByTypeCasillaDTO(1, $db);
		$items_Extranjero_MR = getRecordSetDataGroupByTypeCasillaDTO(2, $db);
		$items_Extranjero_RP = getRecordSetDataGroupByTypeCasillaDTO(3, $db);
		$items_Extranjero_ALC = getRecordSetDataGroupByTypeCasillaDTO(4, $db);
		
		// DATOS JG
		$itemRecords["votos_del_jg"] = $items_Data_JG;
		
		
		
		/*
		echo "<br><br>";
		echo "SUMA<br><br>";
		echo var_dump($itemRecords["votos_del_jg"]); return;
		// Hasta aqui hay esto: votos_cand_no_reg, votos_nulos
		echo "<br><br>"; 
		*/
		
		$itemRecords["votos_del_jg_total"] = sumArrayTotal($items_Data_JG, $db, 1);
		
		/*
		echo "<br><br>";
		echo "SUMA<br><br>";
		echo var_dump($itemRecords["votos_del_jg_total"]); return;
		// Hasta aqui hay esto: votos_cand_no_reg, votos_nulos
		echo "<br><br>"; 
		*/
		
		
		$itemRecords["votos_extranjero_jg"] = $items_Extranjero_JG;
		$itemRecords["votos_extranjero_jg_total"] = sumArrayTotal($items_Extranjero_JG, $db, 1);
		// DATOS MR
		$itemRecords["votos_dto_dmr"] = $items_Data_MR;
		$itemRecords["votos_dto_dmr_total"] = sumArrayTotal($items_Data_MR, $db, 2);
		$itemRecords["votos_extranjero_dmr"] = $items_Extranjero_MR;
		$itemRecords["votos_extranjero_dmr_total"] = sumArrayTotal($items_Extranjero_MR, $db, 1);
		
		// DATOS RP
		$itemRecords["votos_dto_rpe"] = $items_Data_RP;
		$itemRecords["votos_dto_rpe_total"] = sumArrayTotal($items_Data_RP, $db, 3);
		$itemRecords["votos_extranjero_rpe"] = $items_Extranjero_RP;
		$itemRecords["votos_extranjero_rpe_total"] = sumArrayTotal($items_Extranjero_RP, $db, 1);

		// DATOS ALC
		$itemRecords["votos_del_alc"] = $items_Data_ALC;
		$itemRecords["votos_del_alc_total"] = sumArrayTotal($items_Data_ALC, $db, 4);
		$itemRecords["votos_extranjero_alc"] = $items_Extranjero_ALC;
		$itemRecords["votos_extranjero_alc_total"] = sumArrayTotal($items_Extranjero_ALC, $db, 1);
		
		//  -----------------------------12/MAR/2024
		// OBTENEMOS CANDIDATOS DE JGOB DE CATALOGOS
		$candidatosJG = getCandidatos(1, $db);
		$itemRecords["candidatos_jg"] = $candidatosJG;
		// OBTENEMOS CANDIDATOS DE MR DE CATALOGOS
		$candidatosMR = getCandidatos(2, $db);
		$itemRecords["candidatos_mr"] = $candidatosMR;
		// OBTENEMOS CANDIDATOS DE RP DE CATALOGOS
		$candidatosRP = getCandidatos(3, $db);
		$itemRecords["candidatos_rp"] = $candidatosRP;
		// OBTENEMOS CANDIDATOS DE ALC DE CATALOGOS
		$candidatosALC = getCandidatos(4, $db);
		$itemRecords["candidatos_alc"] = $candidatosALC;
		//  -----------------------------12/MAR/2024

		//15/ABRIL/2024
		// Obtenemos URBANAS Y NO URBANAS
		
		$recordUrbanas = urbanasNoUrbanasENTIDAD($type, $item, $item_2, $item_3, $db);
		$itemRecords["urbanas_nourbanas"] = $recordUrbanas;
		
		//-------------------------------12/ABRIL/2024
		$hosts = getHostsImages($db);
		$itemRecords["hosts"] = $hosts;
		
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
