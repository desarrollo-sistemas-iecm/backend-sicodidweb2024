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
		
		
		
		$qryParticipantes = "";
		switch ($type) {
			case 1:
				$name_item ="";
				$qryParticipantes = 'select JG.id_participante, P.descripcion, P.siglas, 
				JG.tipo_participante, 	JG.prelacion, JG.integrantes   from scd_participantes_jgob JG 
				left join scd_cat_participantes P
				on JG.id_participante = P.id_participante;';
				break;
			case 2:
				$name_item ="V.id_distrito";
				if($item=="") $item="1";
				$qryParticipantes = 'select MR.id_participante, P.descripcion, P.siglas, MR.id_distrito, 
				MR.tipo_participante, 	MR.prelacion, MR.integrantes   from scd_participantes_mr MR 
				left join scd_cat_participantes P
				on MR.id_participante = P.id_participante
				where id_distrito= '.$item.';';
				break;
			case 3:
				if($item=="") $item="1";
				$name_item ="V.id_distrito";
				$qryParticipantes = 'select RP.id_participante, P.descripcion, P.siglas, RP.id_distrito, 
				RP.tipo_participante, 	RP.prelacion, RP.integrantes   from scd_participantes_rp RP 
				left join scd_cat_participantes P
				on RP.id_participante = P.id_participante
				where id_distrito= '.$item.';';
				break;
			case 4:
				if($item=="") $item="2";
				$name_item ="V.id_delegacion";
				$qryParticipantes = 'select JD.id_participante, P.descripcion, P.siglas, JD.id_delegacion, 
				JD.tipo_participante, 	JD.prelacion, JD.integrantes   from scd_participantes_jdel JD 
				left join scd_cat_participantes P
				on JD.id_participante = P.id_participante
				where id_delegacion= '.$item.';';
				break;
		}
		
		//echo $qryParticipantes; die();
		
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
		}
		
		//---------------------------------------------------
		// Datos: Armamos solo los campos a traer
		//----------------------------------------------------
		$participan = "";
		$participanSUM = "";
		$llave=0;
		
		$qryData = 'SELECT V.id_delegacion, V.id_distrito, V.id_seccion, V.tipo_casilla '.$participan.', votos_cand_no_reg, votos_nulos, votacion_total, boletas_sob, ciudadanos_votaron, representantes_votaron, total_votaron, C.lista_nominal, votacion_total  
		FROM prep_votos V 
		left join scd_casillas C 
		on V.id_distrito = C.id_distrito and V.id_delegacion = C.id_delegacion 
		and V.id_seccion = C.id_seccion and V.tipo_casilla = C.tipo_casilla
		where V.id_tipo_eleccion= '.$type;
		
		if($type!=1){
			$qryData .= " and ".$name_item.'='.$item;
		}
		
		if($item_2!=""){
			$qryData .= " and V.id_seccion = ".$item_2;
		}
		if($item_3!=""){
			$qryData .= " and V.tipo_casilla = '".$item_3."';";
		}
		
	
		
		//echo $qryData; return;

		$itemRecords["resumen"]= array();
		// vars para resumen
		$acumulado=0; $no_reg=0; $nulo =0; $total =0;
		
		
		$res_catch = $db->query($qryData);
		while ($row = $res_catch->fetchArray(SQLITE3_ASSOC))
		{
			
			
			// SUMATORIAS PARA RESUMEN
			$acumulado += (intval($row["votacion_total"]) - intval($row["votos_cand_no_reg"]) - intval($row["votos_nulos"]));
			$no_reg += intval($row["votos_cand_no_reg"]); 
			$nulo +=  intval($row["votos_nulos"]); 
			$total += intval($row["votacion_total"]);
			

		}
		
		$votos_acumulados_por = "0%";
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
