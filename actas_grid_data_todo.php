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

function LeeVotosPrep(){
		
		$type = trim(htmlentities($_GET["type"]));
		$item = trim(htmlentities($_GET["item"]));
		$item_2=""; $item_3="";
		if(isset($_GET["item_2"])) $item_2 = trim(htmlentities($_GET["item_2"]));
		if(isset($_GET["item_3"])) $item_3 = trim(htmlentities($_GET["item_3"]));
		
		$name_item ="";
		// Registro general de BD
		$itemRecords = array();
		$itemNameRecords = array();		
		
		//votacion_total*100/lista_nominal
		$itemParticipacion = array();	
		
		//$tmp = ['id_delegacion', 'id_distrito', 'id_seccion', 'tipo_casilla' ];
		
		$tmp = [ 'id_distrito', ];
		
		foreach ($tmp as $nombre) {
			$nombre_tit = $nombre;
			if($nombre=="tipo_casilla"){
				$nombre_tit = "Tipo Casilla";
			}
			if($nombre=='id_distrito') $nombre_tit ="";
			$columna = array(
						"dataIndex" => $nombre,
						"title" => $nombre_tit,
						"key"=> $nombre,
						"resizable" => 'resizable',
						"width"=> 100,
						"minWidth"=> 100,
						"maxWidth"=> 200
				);
			$itemNameRecords[]= $columna;	
		}
		
		
		
		$qryParticipantes = "";
		switch ($type) {
			case 1:
				$name_item ="V.id_distrito";
				$qryParticipantes = 'select DISTINCT JG.id_participante, P.descripcion, P.siglas, 
				JG.tipo_participante, 	JG.prelacion, JG.integrantes   from scd_participantes_jgob JG 
				left join scd_cat_participantes P
				on JG.id_participante = P.id_participante;';
				break;
			case 2:
				$name_item ="V.id_distrito";
				$qryParticipantes = 'select DISTINCT MR.id_participante, P.descripcion, P.siglas,
				MR.tipo_participante  from scd_participantes_mr MR 
				left join scd_cat_participantes P
				on MR.id_participante = P.id_participante where MR.id_participante in(1,2,3,4,5,6,7,8,10,14);';
				break;
			case 3:
				$name_item ="V.id_distrito";
				$qryParticipantes = 'select DISTINCT RP.id_participante, P.descripcion, P.siglas, 
				RP.tipo_participante   from scd_participantes_rp RP 
				left join scd_cat_participantes P
				on RP.id_participante = P.id_participante
				';
				break;
			case 4:
				$name_item ="V.id_delegacion";
				$qryParticipantes = 'select DISTINCT JD.id_participante, P.descripcion, P.siglas,
				JD.tipo_participante  from scd_participantes_jdel JD 
				left join scd_cat_participantes P
				on JD.id_participante = P.id_participante where JD.id_participante in(4,5,6,7,9,10,14);';
				break;
		}
				

		// apertura de BD
		$reg_data=0;
		
//echo $qryParticipantes;return;
		
		$db = new SQLite3('db/database.db3');
		$res_catch = $db->query($qryParticipantes);
	
		
		$itemRecords["value_fields"] = array();
		$db->enableExceptions(false);
		while ($row = $res_catch->fetchArray(SQLITE3_ASSOC))
		{
			//$var_catch = $row_catch['value']." - ".$row_catch['label']."<br>";
			array_push($itemRecords["value_fields"], $row);
			$reg_data++;
			// if($type==1) break;
		}
		
		//---------------------------------------------------
		// Datos: Armamos solo los campos a traer
		//----------------------------------------------------
		$participan = "";
		$participanSUM = "";
		$llave=0;
		
		$primecampo ="";
		foreach ($itemRecords["value_fields"] as $clave => $valor) {
				
				if($primecampo == "") {
					$primecampo = "votos_part_".$valor["id_participante"];
				}
				/*
				else{
					//if($primecampo == "votos_part_".$valor["id_participante"] && $valor["id_participante"]>10) continue;
					if($primecampo == "votos_part_".$valor["id_participante"] || $valor["id_participante"]>10) continue;
				}
				*/
				

				//echo "<br><br>".$clave." - ".$valor["id_participante"]." - ".$valor["descripcion"];
				if($valor["id_participante"]<=10) {
						if($valor["id_participante"]<=9){
								$participan .= (', votos_part_'.$valor["id_participante"]);
								$participanSUM .= (', SUM(votos_part_'.$valor["id_participante"].') as votos_part_'.$valor["id_participante"]);
						}
						
						if($valor["id_participante"]==10){
							
							if($type=="2"){
								$participan .= (', votos_part_10');
								$participanSUM .= (', sum (ifnull(votos_part_10,0)+ifnull(votos_part_11,0)+ifnull(votos_part_12,0)+ifnull(votos_part_13,0)) as votos_part_10');
						
							}
							else{
								$participan .= (', votos_part_10');
								$participanSUM .= (', sum (ifnull(votos_part_1,0)+ifnull(votos_part_2,0)+ifnull(votos_part_3,0)+ifnull(votos_part_10,0)+ifnull(votos_part_11,0)+ifnull(votos_part_12,0)+ifnull(votos_part_13,0)) as votos_part_10');
							
							}
							
							if($type=="4"){
								$participan .= (', votos_part_14');
								$participanSUM .= (', sum (ifnull(total_votos_cc5,0)) as votos_part_14');
						
							}
							else{
								$participan .= (', votos_part_14');
								$participanSUM .= (', sum (ifnull(votos_part_4,0)+ifnull(votos_part_5,0)+ifnull(votos_part_7,0)+ifnull(votos_part_14, 0) +ifnull(votos_part_15,0)+ifnull(votos_part_16,0)+ifnull(votos_part_17,0)) as votos_part_14');
						
							}
						}
						// Armo nombre y tipo de las columnas
						//$llave++;
						$llave = $valor["id_participante"];
						$realName = 'votos_part_'.$valor["id_participante"];
			
						$columna = array(
								"dataIndex" => $realName,
								"key"=>$llave,
								"title" => $realName,
								"resizable" => 'resizable',
								"width"=> 100,
								"minWidth"=> 100,
								"maxWidth"=> 200
						);
						$itemNameRecords[]= $columna;
						
						if($valor["id_participante"]==10){
							$columna = array(
									"dataIndex" => "votos_part_14",
									"key"=>14,
									"title" => "votos_part_14",
									"resizable" => 'resizable',
									"width"=> 100,
									"minWidth"=> 100,
									"maxWidth"=> 200
							);
							$itemNameRecords[]= $columna;
							
						}					
				}
				
			
			
		}
		
		
	//echo "<br>".$participan; return;
		
		//echo $participanSUM; return ;
		// Cargo últimos nombre de columnas
		
		//$tmp = ['tipo_casilla','votos_cand_no_reg', 'votos_nulos', 'votacion_total'];
		
		$tmp = ['votos_cand_no_reg', 'votos_nulos'];
		
		foreach ($tmp as $nombre) {
			$realName = $nombre;
			if($nombre == "votos_cand_no_reg"){
				$realName = "No registrado";
			}
			if($nombre == "votos_nulos"){
				$realName = "Nulos";
			}
			if($nombre == "tipo_casilla"){
				$realName = "Casilla";
			}
			$columna = array(
						"title" => $realName,
						"dataIndex" => $nombre,
						"key"=> $nombre,
						"resizable" => 'resizable',
						"width"=> 100,
						"minWidth"=> 100,
						"maxWidth"=> 200
				);
			$itemNameRecords[]= $columna;	
		}
		
		/*
		$qryData = 'SELECT V.id_delegacion, V.id_seccion, V.tipo_casilla, V.id_distrito '.$participanSUM.', votos_cand_no_reg, votos_nulos, votacion_total, boletas_sob, ciudadanos_votaron, representantes_votaron, total_votaron, C.lista_nominal, votacion_total  
		FROM scd_votos V 
		left join scd_casillas C 
		on V.id_distrito = C.id_distrito and V.id_delegacion = C.id_delegacion 
		and V.id_seccion = C.id_seccion and V.tipo_casilla = C.tipo_casilla
		where V.id_tipo_eleccion= '.$type.' limit 4;';
		*/
		$qryData = 'SELECT V.id_delegacion, V.id_seccion, V.tipo_casilla, V.id_distrito '.$participanSUM.', sum(votos_cand_no_reg) as votos_cand_no_reg, sum(votos_nulos) as votos_nulos, sum(votacion_total) as votacion_total, sum(boletas_sob) as boletas_sob, sum(ciudadanos_votaron) as ciudadanos_votaron, sum(representantes_votaron) as representantes_votaron, sum(total_votaron) as total_votaron, sum(C.lista_nominal) as lista_nominal  
		FROM scd_votos V 
		left join scd_casillas C 
		on V.id_distrito = C.id_distrito and V.id_delegacion = C.id_delegacion 
		and V.id_seccion = C.id_seccion and V.tipo_casilla = C.tipo_casilla
		where validado	="T" and contabilizar="T"  and V.id_tipo_eleccion= '.$type;
		
		if($type>=1){
			// $qryData .= " and ".$name_item.'='.$item;
		}
		
		
		if($item_2!=""){
			$qryData .= " and V.id_seccion = ".$item_2;
		}
		if($item_3!=""){
			$qryData .= " and V.tipo_casilla = '".$item_3."';";
		}
		
		if($item!=""){
			$qryData .= " and ".$name_item." = ".$item;
		}
		
		$qryData .= "  limit 4;";
					
		$itemRecords["columns"] = $itemNameRecords;
		
		
//echo "<br>".$qryData; return;
		
		$itemRecords["data"] = array();
		$itemRecords["participacion"]= array();
		$itemRecords["resumen"]= array();
		// vars para resumen
		$acumulado=0; $no_reg=0; $nulo =0; $total =0;
		
		///echo $qryData; return;
		
		
		$res_catch = $db->query($qryData);
		
		$votacion_total =0;
		while ($row = $res_catch->fetchArray(SQLITE3_ASSOC))
		{
			$votacion_total = $row["votacion_total"]; // Nuevo 04/Abril/2024
			
			$registro = array();
			$registro = $row;
			foreach ($registro as $clave => $valor) {
				
				$valorCalculo = $valor;
				
				if (str_contains2($clave, 'votos_part_') || str_contains2($clave, 'votos_nulos') || str_contains2($clave, 'votos_cand_no_reg') ) {
					$porcentCalc = 0;
					if($valorCalculo>0 && $votacion_total>0){
						//echo "<br>222ENTRE!!!!".$valor."<br>";
						$porcentCalc = ($valorCalculo*100) / $votacion_total;
					}
					$registro[$clave] = sprintf("%01.4f", $porcentCalc)." %";
				}
				// Para buscar las Siglas con el campo llave de "campo_votos"
				/*$mapeo_name[$valor["campo_votos"]]["siglas"] = $valor["campo_votos"];//$valor["siglas"];
				$mapeo_name[$valor["campo_votos"]]["descripcion"] = $valor["descripcion"];
				$mapeo_name[$valor["campo_votos"]]["id_participante"] = $valor["id_participante"];*/
					
				//	echo "<br>clave: ".$clave.", valor: ".$valor."<br>";
			}
			
			
			//$registro["porcentaje"] = "1000000%"; 
			
			//array_push($itemRecords["data"], $row);
			array_push($itemRecords["data"], $row);
			array_push($itemRecords["data"], $registro);
			
			//$participacion =  intval($row["votacion_total"])*100 / intval($row["lista_nominal"]);
			if(intval($row["lista_nominal"]>0)){
				$participacion =  number_format(intval($row["votacion_total"])*100 / intval($row["lista_nominal"]), 4, '.', ',');
			}
			else{
				$participacion = 0;
			}
			
			// Obtener el primer caracter
			$primerCaracter = substr($row["tipo_casilla"], 0, 1);
			if($primerCaracter=='B') $primerCaracter='Básica';
			if($primerCaracter=='C') $primerCaracter='Contigua';
			if($primerCaracter=='A') $primerCaracter='Voto en el extranjero';
			
			// Obtener el resto de la cadena
			$restoCadena = substr($row["tipo_casilla"], 1);
			
			$participa = array(
						"id_delegacion" => $row["id_delegacion"],
						"id_distrito" => $row["id_seccion"],
						"id_seccion" => $row["id_distrito"],
						"tipo_casilla" => $row["tipo_casilla"],
						"num_casilla"=> str_pad($restoCadena , 4, "0", STR_PAD_LEFT),
						"caracter_cas" => $primerCaracter,
						"total_votaron"=> $row["total_votaron"],
						"lista_nominal" => $row["lista_nominal"],
						"participacion" => $participacion,
						"votacion_total" => $row["votacion_total"]
			);
			
			// SUMATORIAS PARA RESUMEN
			$acumulado += (intVal($row["votacion_total"]) - intVal($row["votos_cand_no_reg"]) - intVal($row["votos_nulos"]));
			$no_reg += intVal($row["votos_cand_no_reg"]); 
			$nulo +=  intVal($row["votos_nulos"]); 
			$total += intVal($row["votacion_total"]);
			
			$itemParticipacion[] = $participa;
		}
		
		$votos_acumulados_por ="0%";
		$candidatos_no_reg_por = "0%";
		$nulos_por = "0%";
		$total_por = "0%";
		
		// registro de resumen
		$itemResumen = array(
			"votos_acumulados" => $acumulado,
			"candidatos_no_reg" => $no_reg,
			"nulos" => $nulo,
			"total" => $total,
			"votos_acumulados_por" => $votos_acumulados_por,
			"candidatos_no_reg_por" => $candidatos_no_reg_por,
			"nulos_por" => $nulos_por,
			"total_por" => "100.0000%",
		);
		
		$itemRecords["resumen"] = $itemResumen;
		$itemRecords["participacion"] = $itemParticipacion;
		
		echo json_encode($itemRecords);
			
		return;
	
}


try {
	if($_SERVER['REQUEST_METHOD']=="GET"){
			LeeVotosPrep();
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
