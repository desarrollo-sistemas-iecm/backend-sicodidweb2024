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
include "helpers.php";
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


// Consigue todos nombres de PARTIDOS participantes(incluyendos los que componen
// las canidaturas comunes) de los campos a consultar en votación según la elección, estó 
// para armar el la sentencia SELECT que traerá toda la información.
// $where y $campoExtra1 los uso para traer todo por DTO / DEL. El campo $campoExtra2
// lo utilizo por si se filtra por SECCION
function getFieldNameParticipan($type, $where="", $campoExtra1="", $where2="" ){
		$db = new SQLite3('db/database.db3');
		// AQUI CONSIGO LOS CAMPOS A MOSTRAR POR ELECCION
		$qryParticipantes = "";  
		
		$campoCorte = $campoExtra1 =="" ? "id_distrito, ": $campoExtra1;
		
		switch ($type) {
			case 1:
				$qryParticipantes = 'select DISTINCT JG.id_participante, P.siglas, P.descripcion from scd_participantes_jgob JG
				left join scd_cat_participantes P
				on JG.id_participante = P.id_participante '.
				' order by JG.prelacion;';
				break;
			case 2:
				$qryParticipantes = 'select DISTINCT '.$campoExtra1.' MR.id_participante, 	P.siglas, P.descripcion from scd_participantes_mr MR
				left join scd_cat_participantes P
				on MR.id_participante = P.id_participante '.$where.
				' order by MR.prelacion, '.$campoExtra1.' MR.prelacion';
				break;
			case 3:  //RP
				$qryParticipantes = '';
				break;
			case 4:
					$qryParticipantes = 'select DISTINCT '.$campoExtra1.' JD.id_participante, P.siglas, P.descripcion from scd_participantes_jdel JD
					left join scd_cat_participantes P
					on JD.id_participante = P.id_participante '.$where.
					' order by '.$campoExtra1.' JD.prelacion;';
				break;
		}
		
		
			
////////	echo "!!!! ".$qryParticipantes; return;		
		// apertura de BD
		$reg_data=0;
		//$db = new SQLite3('db/database.db3');
		$res_catch = $db->query($qryParticipantes);
	
		if(!$res_catch) return null;
		
		$value_records = array();
		
		$db->enableExceptions(false);
		while ($row = $res_catch->fetchArray(SQLITE3_ASSOC))
		{
			//$var_catch = $row_catch['value']." - ".$row_catch['label']."<br>";
			array_push($value_records, $row);
			$reg_data++;
			// if($type==1) break;
		}
		
		$coma = "";
		$participanSQL="";
		// Recorro para armar SQL
		foreach ($value_records as $clave => $valor) {
				$participanSQL .= ($coma.' SUM(votos_part_'.$valor["id_participante"].') as votos_part_'.$valor["id_participante"]);
				$coma = ", ";
		}
		
		$participanSQL =  "Select ".$campoCorte.$participanSQL.", sum(votos_cand_no_reg) as votos_cand_no_reg, sum(votos_nulos) as votos_nulos, sum(votacion_total) as votacion_total from scd_votos ".$where.$where2;
	
	/*	
		$participanSQL =  "Select ".$campoCorte.$participanSQL.", votos_cand_no_reg as [Candidatos no registrados], votos_nulos as [Votos nulos], votacion_total as [Votación total] from scd_votos ".$where.$where2;
	*/	
		
		$db->close();
		unset($db);	
		return $participanSQL;
}


function getRecordsetCarrousel($tmpSQL =""){	
		// apertura de BD
		
		//echo $tmpSQL; die();
		
		$db = new SQLite3('db/database.db3');
		
		$res_catch = $db->query($tmpSQL);
	
		if(!$res_catch) return null;
		
		$value_recordset = array();
		
		$db->enableExceptions(false);
		while ($row = $res_catch->fetchArray(SQLITE3_ASSOC))
		{
			$total_votos = $row["votacion_total"];

			$rowTMP = [];
			
			foreach ($row as $clave => $valor) {
				
				$partido = '';
				$id = '';
				$porcenToken = 0;
				if (str_contains2($clave, 'vot') && $clave!="votacion_total") {
					$token = explode("_", $clave);
					$countToken = count($token);
					if($countToken>1){
						// traemos el nombre de campo, si es número es un partido
						if(is_numeric($token[$countToken-1])){
							$partido = $token[$countToken-1].".jpg";
							$id = $token[$countToken-1];
							// calculamos porcentaje
							if($total_votos<=0) {
								$porcenToken=0;
							}
							else
							{
								$porcenToken = ($valor*100) / $total_votos;
							}
						}
						// Si no es númerico retornamos el nombre completo del campo
						else
						{
							if($clave=='votos_nulos') {
								$partido = "nulos.jpg";
							}
							else{
								$partido = "no_reg.jpg";
							}
							//$partido = $clave;
							
							// No es partido, pero si nulos o sin partido
							// calculamos porcentaje
							if($total_votos<=0){
								$porcenToken=0;
							}
							else{
								$porcenToken = ($valor*100) / $total_votos;
							}
							
						}
					}
					
				}
				else{
					$partido = '';
					if($clave == "votacion_total")  $porcenToken =100;
				}
				
				$porTmp = '';
				if($porcenToken>0){
					$porTmp = sprintf("%01.4f", $porcenToken)."%";
				}
				$rowTMP["id"]=$id;
				$rowTMP["campo"]=$clave;
				$rowTMP["valor"]=$valor;
				$rowTMP["partido"]=$partido;
				$rowTMP["porcentaje"]=$porTmp;
				
				array_push($value_recordset, $rowTMP);
			}
			
			
			//array_push($value_recordset, $row);
			//$reg_data++;
			// if($type==1) break;
		}		
		
		$db->close();
		unset($db);	
		return $value_recordset;
}

try {
	if($_SERVER['REQUEST_METHOD']=="GET"){
		$item ="";
		$type = trim(htmlentities($_GET["type"]));
		$item = trim(htmlentities($_GET["item"]));
		$item_2=""; $item_3=""; 
		
		// 13/Abril/2024
		$es_A_M_P = trim(htmlentities($_GET["es_A_M_P"]));
		
		
		if(isset($_GET["item_2"])) $item_2 = trim(htmlentities($_GET["item_2"]));
		if(isset($_GET["item_3"])) $item_3 = trim(htmlentities($_GET["item_3"]));
		
		$name_item ="";
		$whare = "";
		
		switch ($type) {
			case "1": // JG
				$name_item ="id_distrito";
				$whare = "  where id_distrito = ".$item;
				break;
			case "2":	//DMR
				$name_item ="id_distrito";
				$whare = "  where id_distrito = ".$item;
				break;
			case "3": //RP
				$name_item ="id_distrito";
				$whare = "  where id_distrito = ".$item;
				break;
			case "4": //ALC
				$name_item ="id_delegacion";
				$whare = "  where id_delegacion = ".$item;
				break;
		}
		
		
		if(isset($_GET["item_2"])){
			$es_A_M_P = trim(htmlentities($_GET["es_A_M_P"]));
			$where2 = " and substr(tipo_casilla,1,1) ='".$es_A_M_P."'";
		}
		else{
			$es_A_M_P = "";
			$where2 = "";
		}
		
		
			
		// --- ABro bd
		//$db = new SQLite3('db/database.db3');
		//getFieldNameParticipan("4", " where id_delegacion = 2 ", "id_delegacion, ", " and id_seccion=22");
		$qryParticipantes ="";
		
		if($item==""){
			$qryParticipantes =  getFieldNameParticipan($type, " where 1 = 1", $name_item.", ");
		}
		else{	
			$qryParticipantes =  getFieldNameParticipan($type, " where ".$name_item." = ".$item." ", $name_item.", ");
		}
		
		
		//echo "1!!!!!!--- ".$qryParticipantes; die();
		
		
		
		
		
		
		
		
		$qryParticipantes = trim($qryParticipantes);
		
		$and = " and ";
		if(substr(trim($qryParticipantes), -13)=='id_distrito ='){
			$qryParticipantes = substr(trim($qryParticipantes), 0, -13);
			$and = "";
		}
		
		
//echo "-----".$qryParticipantes; die();
//return;		
				
		if($item_2!="" && $item!=""){
			$qryParticipantes .= " ".$and." id_seccion = ".$item_2;
			if($item_3!=""){
				$qryParticipantes .= " ".$and." tipo_casilla = '".$item_3."'";
			}
		}
		$qryParticipantes .= " ".$and." id_tipo_eleccion=".$type.$where2;
		
		//" group by ".$name_item;
		
//		echo $qryParticipantes; 
//		return;	

		$records = getRecordsetCarrousel($qryParticipantes);
		
		//$qryParticipantes = "";  = getFieldNameParticipan("4", " where id_delegacion = 2 ", "id_delegacion, ", " and id_seccion=22");
		//echo $qryParticipantes;  return;
		// //echo "<br><br>".var_dump($qryParticipantes = ""; )."<br><br>"; return;


		echo json_encode($records);	
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