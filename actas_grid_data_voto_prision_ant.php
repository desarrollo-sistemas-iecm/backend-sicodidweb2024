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
	if($_SERVER['REQUEST_METHOD']=="GET"){
		
		$type = trim(htmlentities($_GET["type"]));
		$item = "";
		$item_2=""; $item_3="";
		
		// 26/Marzo/2024
		// Tpo de acta:
		// P -> Prisión
		// M -> Voto en el extranjero
		// A -> Anticipado/Postración
		
		$tipo_acta = "S";
		
		if(isset($_GET["item"])) $item = trim(htmlentities($_GET["item"]));
		if(isset($_GET["item_2"])) $item_2 = trim(htmlentities($_GET["item_2"]));
		if(isset($_GET["item_3"])) $item_3 = trim(htmlentities($_GET["item_3"]));
		if(isset($_GET["tipo_acta"])) $tipo_acta = trim(htmlentities($_GET["tipo_acta"]));
		
		// 26/Marzo/2024
		$filtro_tipo_acta = " and substr(V.tipo_casilla,1,1) = '".$tipo_acta."' ";
		//and substr(tipo_casilla,1,1) = 'S'
		$name_item ="";
		// Registro general de BD
		$itemRecords = array();
		$itemNameRecords = array();		
		
		//votacion_total*100/lista_nominal
		$itemParticipacion = array();	
		
		//$tmp = ['id_delegacion', 'id_distrito', 'id_seccion', 'tipo_casilla' ];
		
		$tmp = [ 'tipo_casilla' ,'id_distrito'];
		
		foreach ($tmp as $nombre) {
			$nombre_tit = $nombre;

			if($nombre=='id_distrito') $nombre_tit ="";
			$columna = array(
						"dataIndex" => $nombre,
						"title" => $nombre_tit,
						"key"=> $nombre,
						"resizable" => 'resizable',
						"width"=> 100,
						"minWidth"=> 100,
						"maxWidth"=> 200,
						"fixed" => 'left'   // FIJAMOS COLUMNAS
						
				);
			$itemNameRecords[]= $columna;	
		}
		
		// 12/Abril/2024
		// Nombre de campo con el nombre/ md5 del archivo de acta
		$nombre_campo_acta ='';
		$md5_campo_acta ='';
		
		$qryParticipantes = "";
		switch ($type) {
			case 1:
				$nombre_campo_acta ='img_nombre_jgob as nombre_img';  // 12/Abril/2024
				$md5_campo_acta ='md5_img_jgob as md5_img';
				
				$name_item ="V.id_distrito";
				$qryParticipantes = 'select DISTINCT JG.id_participante, P.descripcion, P.siglas, 
				JG.tipo_participante, 	JG.prelacion, JG.integrantes   from scd_participantes_jgob JG 
				left join scd_cat_participantes P
				on JG.id_participante = P.id_participante;';
				break;
			case 2:
				$nombre_campo_acta ='img_nombre_dmr as nombre_img';  // 12/Abril/2024
				$md5_campo_acta ='md5_img_rp as md5_img';
				
				$name_item ="V.id_distrito";
				
				if($item=='' || $item<=0){
					$qryParticipantes = 'select MR.id_participante, P.descripcion, P.siglas,
					MR.tipo_participante, MR.prelacion, MR.integrantes from scd_participantes_jgob MR
					left join scd_cat_participantes P
					on MR.id_participante = P.id_participante';
				}
				else
				{
					$qryParticipantes = 'select MR.id_participante, P.descripcion, P.siglas, MR.id_distrito, 
					MR.tipo_participante, 	MR.prelacion, MR.integrantes   from scd_participantes_mr MR 
					left join scd_cat_participantes P
					on MR.id_participante = P.id_participante
					where id_distrito= '.$item.' order by prelacion;';
				}
				
				break;
			case 3:
				$nombre_campo_acta ='img_nombre_rp as nombre_img';  // 12/Abril/2024
				$md5_campo_acta ='md5_img_dmr as md5_img';
				
				$name_item ="V.id_distrito";
				
				$qryParticipantes = 'select DISTINCT RP.id_participante, P.descripcion, P.siglas, 
				RP.tipo_participante   from scd_participantes_rp RP 
				left join scd_cat_participantes P
				on RP.id_participante = P.id_participante;';
				break;
			case 4:
				$nombre_campo_acta ='img_nombre_alc as nombre_img';  // 12/Abril/2024
				$md5_campo_acta ='md5_img_alc as md5_img';
				
				$name_item ="V.id_delegacion";
				if($item=='' || $item<=0){
					$qryParticipantes = 'select JD.id_participante, P.descripcion, P.siglas, JD.id_delegacion, 
					JD.tipo_participante, 	JD.prelacion, JD.integrantes   from scd_participantes_jdel JD 
					left join scd_cat_participantes P
					on JD.id_participante = P.id_participante
					where id_delegacion>=0 order by prelacion;';
				}
				else{
					$qryParticipantes = 'select JD.id_participante, P.descripcion, P.siglas, JD.id_delegacion, 
					JD.tipo_participante, 	JD.prelacion, JD.integrantes   from scd_participantes_jdel JD 
					left join scd_cat_participantes P
					on JD.id_participante = P.id_participante
					where id_delegacion= '.$item.' order by prelacion;';
				}
				
				break;
			
				break;
		}
				
// echo $qryParticipantes; return;
		// apertura de BD
		$reg_data=0;
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
		foreach ($itemRecords["value_fields"] as $clave => $valor) {

// OJO!!! TEMPORAL:
	//if($valor["id_participante"]>11) continue;

				//echo "<br><br>".$clave." - ".$valor["id_participante"]." - ".$valor["descripcion"];
				$participan .= (', votos_part_'.$valor["id_participante"]);
				
				$participanSUM .= (', SUM(votos_part_'.$valor["id_participante"].') as votos_part_'.$valor["id_participante"]);
				
				// Armo nombre y tipo de las columnas
				$llave++;
				$columna = array(
						"dataIndex" => 'votos_part_'.$valor["id_participante"],
						"key"=>$llave,
						"title" => 'votos_part_'.$valor["id_participante"],
						"resizable" => 'resizable',
						"width"=> 100,
						"minWidth"=> 100,
						"maxWidth"=> 200
				);
				$itemNameRecords[]= $columna;
			
			
		}
	//	echo "<br>".$participan; return;
		
		//echo $participanSUM; return ;
		// Cargo últimos nombre de columnas
		$tmpReal = ['Candidato no registrado', 'Votos nulos', 'Votación total', 'Observaciones','ue_identificador_comprobante', 'ue_circunscripcion_federal', 'ue_id_casilla', 'ue_tipo_casilla', 'ue_id_ext_contigua', 'ue_tipo_documento', 'ue_personas_votaron', 'ue_identificacion', 'ue_votos_partidos', 'ue_version_software', 'ue_fecha_impresion', 'ue_codigo_integridad', 'ue_qr'];
		
		
		$tmp = ['votos_cand_no_reg', 'votos_nulos', 'votacion_total'];
		
		$tituloReal ="";
		
		foreach ($tmp as $nombre) {
			$tituloReal = $nombre;
			switch ($nombre) {

				case 'votos_cand_no_reg':
					$tituloReal = $tmpReal[0];
					break;
				case 'votos_nulos':
					$tituloReal = $tmpReal[1];
					break;
				case 'votacion_total':
					$tituloReal = $tmpReal[2];;
					break;
				case 'observaciones':
					$tituloReal = $tmpReal[3];
					break;
			}
			$columna = array(
						"title" => $tituloReal,
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
		$qryData = 'SELECT V.id_delegacion, V.id_seccion, V.tipo_casilla, V.id_distrito '.$participan.', votos_cand_no_reg, votos_nulos, votacion_total, boletas_sob, ciudadanos_votaron, representantes_votaron, total_votaron, C.lista_nominal, votacion_total, "" as observaciones 
		FROM scd_votos V 
		left join scd_casillas C 
		on V.id_distrito = C.id_distrito and V.id_delegacion = C.id_delegacion 
		and V.id_seccion = C.id_seccion and V.tipo_casilla = C.tipo_casilla
		where V.id_tipo_eleccion= '.$type.' and C.lista_nominal>0 limit 2;';
	*/
		$qryData = 'SELECT V.id_delegacion,
		V.id_seccion,
		V.tipo_casilla,
		V.id_distrito,
		V.clave_mdc '.$participan.',
		votos_cand_no_reg,
		votos_nulos,
		votacion_total,
		boletas_sob,
		ciudadanos_votaron,
		representantes_votaron,
		total_votaron,
		C.lista_nominal,
		votacion_total,
		"" AS observaciones,
		contabilizar,
		'.$nombre_campo_acta.',
		'.$md5_campo_acta.',
		"" as ue_identificador_comprobante,
		"" As ue_circunscripcion_federal,
		"" AS ue_id_casilla,
		"" AS ue_tipo_casilla,
		"" AS  ue_id_ext_contigua,
		"" AS  ue_tipo_documento,
		"" AS  ue_personas_votaron,
		"" AS  ue_identificacion,
		"" AS  ue_votos_partidos,
		"" AS  ue_version_software,
		"" AS  ue_fecha_impresion,
		"" AS  ue_codigo_integridad,
		"" AS  ue_qr
	FROM scd_votos V
		left join scd_casillas C on V.id_distrito = C.id_distrito
		and V.id_delegacion = C.id_delegacion
		and V.id_seccion = C.id_seccion
		and V.tipo_casilla = C.tipo_casilla
		left join dig_actas_prep D on V.id_distrito = D.id_distrito
		and V.clave_mdc = D.acta
	where V.id_tipo_eleccion = '.$type.$filtro_tipo_acta;
		
		if($item!=""){
			$qryData .= " and ".$name_item.'='.$item;
		}
		
		
		if($item_2!=""){
			$qryData .= " and V.id_seccion = ".$item_2;
		}
		if($item_3!=""){
			$qryData .= " and V.tipo_casilla = '".$item_3."'";
		}

		$qryData .= ' ORDER BY CAST(SUBSTR(V.tipo_casilla, 2) AS INTEGER) ASC;';
		
					
		$itemRecords["columns"] = $itemNameRecords;
		
// echo "<br>".$qryData; return;
		
		$itemRecords["data"] = array();
		$itemRecords["participacion"]= array();
		$itemRecords["resumen"]= array();
		// vars para resumen
		$acumulado=0; $no_reg=0; $nulo =0; $total =0;
		
		// echo $qryData; return;
		
		
		$res_catch = $db->query($qryData);
		while ($row = $res_catch->fetchArray(SQLITE3_ASSOC))
		{
			array_push($itemRecords["data"], $row);
			
			//$participacion =  intval($row["votacion_total"])*100 / intval($row["lista_nominal"]);
			if(intval($row["lista_nominal"]>0)){
				$participacion =  number_format(intval($row["votacion_total"])*100 / intval($row["lista_nominal"]), 4, '.', ',');
			}
			else{
				$participacion = 0;
			}
			
			// Obtener el primer caracter
			$primerCaracter = substr($row["tipo_casilla"], 0, 1);
			if($primerCaracter=='B') $primerCaracter='Voto en Prisión Preventiva '.substr($row["tipo_casilla"], 1);
			if($primerCaracter=='C') $primerCaracter='Voto en Prisión Preventiva '.substr($row["tipo_casilla"], 1);
			if($primerCaracter=='A') $primerCaracter='Voto en el extranjero';
			
			$primerCaracter2 = substr($row["tipo_casilla"], 0, 1);
			if($primerCaracter2=='B') $primerCaracter2='Voto Anticipado '.substr($row["tipo_casilla"], 1);
			if($primerCaracter2=='C') $primerCaracter2='Voto Anticipado '.substr($row["tipo_casilla"], 1);
			if($primerCaracter2=='A') $primerCaracter2='Voto en el extranjero';
			
			// Obtener el resto de la cadena
			$restoCadena = substr($row["tipo_casilla"], 1);
			
			$participa = array(
						"id_delegacion" => $row["id_delegacion"],
						"id_distrito" => $row["id_distrito"],
						"id_seccion" => $row["id_seccion"],
						"tipo_casilla" => $row["tipo_casilla"],
						"num_casilla"=> str_pad($restoCadena , 4, "0", STR_PAD_LEFT),
						"caracter_cas" => $primerCaracter,
						"total_votaron"=> $row["total_votaron"],
						"lista_nominal" => $row["lista_nominal"],
						"participacion" => $participacion,
						"votacion_total" => $row["votacion_total"],
						"caracter_cas2" => $primerCaracter2,
						
			);
			
			// SUMATORIAS PARA RESUMEN
			$acumulado += (intVal($row["votacion_total"]) - intVal($row["votos_cand_no_reg"]) - intVal($row["votos_nulos"]));
			$no_reg += intVal($row["votos_cand_no_reg"]); 
			$nulo +=  intVal($row["votos_nulos"]); 
			$total += intVal($row["votacion_total"]);
			
			$itemParticipacion[] = $participa;
		}
		
		$votos_acumulados_por =0;
		$candidatos_no_reg_por = 0;
		$nulos_por = 0;
		$total_por = 0;
		if($total>0){
				if($acumulado>0){
					$votos_acumulados_por = ($acumulado *100) /$total;
				}
				
				if($no_reg>0){
					$candidatos_no_reg_por = ($no_reg *100) /$total;
				}
				
				if($nulo>0){
					$nulos_por = ($nulo *100) /$total;
				}
		}
		// registro de resumen
		$itemResumen = array(
			"votos_acumulados" => $acumulado,
			"candidatos_no_reg" => $no_reg,
			"nulos" => $nulo,
			"total" => $total,
			"votos_acumulados_por" => sprintf("%01.4f", $votos_acumulados_por)."%",
			"candidatos_no_reg_por" => sprintf("%01.4f", $candidatos_no_reg_por)."%",
			"nulos_por" => sprintf("%01.4f", $nulos_por)."%",
			"total_por" => "100.0000%",
		);
		
		$itemRecords["resumen"] = $itemResumen;
		$itemRecords["participacion"] = $itemParticipacion;
		
		echo json_encode($itemRecords);
			
		return;
		
		/// --   SUPLE:
		/*
		$error = 0;
		echo '{
				"data" : [
						{
						  "key": "1",
						  "name": "John Brown",
						  "age": 32,
						  "address": "New York No. 1 Lake Park",
						  "tags": ["nice", "developer"]
						}, 
						{
						  "key": "2",
						  "name": "Jim Green",
						  "age": 42,
						  "address": "London No. 1 Lake Park",
						  "tags": ["loser"]
						}, 
						{
						  "key": "3",
						  "name": "Joe Black",
						  "age": 32,
						  "address": "Sidney No. 1 Lake Park",
						  "tags": ["cool", "teacher"]
						}
					],
					
				"columns" : [
					{
					  "dataIndex": "name",
					  "key": "name",
					  "resizable": true,
					  "width": 150
					}, 
					{
					  "title": "Age",
					  "dataIndex": "age",
					  "key": "age",
					  "resizable": true,
					  "width": 100,
					  "minWidth": 100,
					  "maxWidth": 200
					}, 
					{
					  "title": "Address",
					  "dataIndex": "address",
					  "key": "address"
					}, 
					{
					  "title": "Tags",
					  "key": "tags",
					  "dataIndex": "tags"
					},
					{
					  "title": "Action",
					  "key": "action"
					}
				]
							

			}
		';
		
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
