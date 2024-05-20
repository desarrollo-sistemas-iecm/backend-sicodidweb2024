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
include "funciones_nourbanas.php";

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
		$item ="";
		$type = trim(htmlentities($_GET["type"]));
		$item = trim(htmlentities($_GET["item"]));
		$item_2=""; $item_3=""; 
		
		
		if(isset($_GET["item_2"])) $item_2 = trim(htmlentities($_GET["item_2"]));
		if(isset($_GET["item_3"])) $item_3 = trim(htmlentities($_GET["item_3"]));	
		
		$db = new SQLite3('db/database.db3');
		$db->enableExceptions(false);
		
		$record = urbanasNoUrbanas($type, $item, $item_2, $item_3, $db);


		echo json_encode($record);	
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