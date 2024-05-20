<?php
// Cabecera para evitar CORS
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Allow: GET, POST, OPTIONS, PUT, DELETE");

$method = $_SERVER['REQUEST_METHOD'];
if($method == "OPTIONS") {
    die();
}
//$type = "";
$seccion = "";
$delegacion = "";
$distrito = "";
$cuantos = 0;

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
		
		//$type = trim(htmlentities($_GET["type"]));
		$seccion = trim(htmlentities($_GET["seccion"]));
		
		// apertura de BD
		$db = new SQLite3('db/database.db3');
		$sqlTMP = "SELECT id_distrito, id_delegacion, id_seccion, count(id_seccion) as cuantos FROM scd_casillas where id_seccion = ".$seccion." and estatus='T'";	
		$resTMP = $db->query($sqlTMP);
		$rowTMP = $resTMP->fetchArray();
		$cuantos = $rowTMP["cuantos"];
		if($cuantos>0){
			$error = 0;
			$seccion = $rowTMP["id_seccion"];
			$delegacion = $rowTMP["id_delegacion"];
			$distrito = $rowTMP["id_distrito"];
			echo '
				{
					"encontrada":
					  {
						"id_seccion": "'.$seccion.'",
						"id_delegacion": "'.$delegacion.'",
						"id_distrito": "'.$distrito.'",
						"cuantos": '.$cuantos.'
					  }
		
				}
				  
			';
		}
		else{
			$error = 0;
			echo '
				{
					"encontrada":
					  {
						"id_seccion": "'.$seccion.'",
						"id_delegacion": "0",
						"id_distrito": "0",
						"cuantos": "0"
					  }
		
				}
				  
			';
		}
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
		return;
	}
} catch (Exception $e) {

	$host = $_SERVER['HTTP_HOST'];
	$ruta = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
	//$html = 'actualizando_bd.html';
	//$url = "http://$host$ruta/$html";
	//header("Location: $url");
	$estado = array(
				"reg_data" => 0,
				"reg_cat" => 0,
				"error" => $e
			);
	echo json_encode($estado);

} finally {
	if($error==666)
	{
		//$host = $_SERVER['HTTP_HOST'];
		//$ruta = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
		//$html = 'actualizando_bd.html';
		//$url = "http://$host$ruta/$html";
		//header("Location: $html");
	}
}
