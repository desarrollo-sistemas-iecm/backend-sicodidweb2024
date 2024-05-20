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
		$type ="";
		
		if(isset($_GET["type"])){
			$type = trim(htmlentities($_GET["type"]));
		}
		else{
			die();
		}
		$error = 0;
		
		$categories = '{
				"categories": []
		}';
		
		if($type=="1" || $type=="2"){
			// Es distrito
			$categories = '{
			"categories": [';
			
			for ($i = 1; $i <= 33; $i++) {
				$categories .= '{
					"id": "'.$i.'",
					"name": "Distrito '.$i.'"
				}';
				
				if($i <= 32){
					$categories .= ",";
				}
			}
			
			$categories .= ']}';
		}
		else{
			// Es alcaldia
			$n_dele[2] = "AZCAPOTZALCO";
			$n_dele[3] = "COYOACÁN";
			$n_dele[4] = "CUAJIMALPA";
			$n_dele[5] = "GUSTAVO A. MADERO";
			$n_dele[6] = "IZTACALCO";
			$n_dele[7] = "IZTAPALAPA";
			$n_dele[8] = "LA MAGDALENA CONTRERAS";
			$n_dele[9] = "MILPA ALTA";
			$n_dele[10] = "ÁLVARO OBREGÓN";
			$n_dele[11] = "TLÁHUAC";
			$n_dele[12] = "TLALPAN";
			$n_dele[13] = "XOCHIMILCO";
			$n_dele[14] = "BENITO JUÁREZ";
			$n_dele[15] = "CUAUHTÉMOC";
			$n_dele[16] = "MIGUEL HIDALGO";
			$n_dele[17] = "VENUSTIANO CARRANZA";
			
			$categories = '{
				"categories": [';
				
				for ($i = 2; $i <= 17; $i++) {
					$categories .= '{
						"id": "'.$i.'",
						"name": "'.$n_dele[$i].'"
					}';
					
					if($i <= 16){
						$categories .= ",";
					}
			}
			
			$categories .= ']}';
		}
		
		
		echo $categories;
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
