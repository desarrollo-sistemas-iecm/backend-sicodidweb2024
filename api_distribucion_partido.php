<?php
// Cabecera para evitar CORS
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
//header("Allow: GET, POST, OPTIONS, PUT, DELETE");
header("Allow: GET, POST");

include "funciones.php";

/*
 * Uso. 'http://localhost/prep2024/api_distribucion_partido.php?type='+eleccion.cmb1+'&eleccion='+eleccion.eleccion
 * type: distrito a alcaldía dependiendo el caso (1, ..., 33) o (2, ..., 16)
 * eleccion: El tipo de elección (1, 2, 4)
*/

$method = $_SERVER['REQUEST_METHOD'];
if($method == "OPTIONS") {
    die();
}

// Solo aceptamos POSTS
if($method!='GET'){
	$error=666;
	$estado = array(
			"datosMSSQL" => '',
			"error" => "Acción o método no implementado(".$method.")",
			"msg" => "Datos encontrados en MSSQL"
		);
	echo json_encode($estado);	
	return;
}

try {
	if($_SERVER['REQUEST_METHOD']=="GET"){
		$type ="";
		$eleccion ="";
		
		if(isset($_GET["type"])){
			$type = intval(trim(htmlentities($_GET["type"])));
		}
		if(isset($_GET["eleccion"])){
			$eleccion = intval(trim(htmlentities($_GET["eleccion"])));
		}
		else{
			die();
		}
		$error = 0;
	
		
		if($type>=1 && $type<=33){
			$db = new SQLite3('db/database.db3');
			$itemRecords = getObtieneDistribucionPartidos($db, $type, $eleccion);
			$estado = array(
					"datosMSSQL" => $itemRecords,
					"error" => "Valores no implementados"
			);
			echo json_encode($estado);
		}
		else{
			$error=666;
			$estado = array(
					"datosMSSQL" => '',
					"error" => "Valores no implementados"
			);
			echo json_encode($estado);
			return;
		}
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
