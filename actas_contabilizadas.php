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
		
		$error = 0;
		echo '
			{
				"categories": [
				  {
					"id": "1",
					"name": "Nombre(s) Apellido(s) Candidato 1",
					"color": "orange",
					"valor": "222",
					"porcentaje": 30,
					"icono": "1",
					"ganadas": 1
				  },
				  {
					"id": "2",
					"name": "Nombre(s) Apellido(s) Candidato 2",
					"color": "blue",
					"valor": "120",
					"porcentaje": 20,
					"icono": "2",
					"ganadas": 3
				  },
				  {
					"id": "3",
					"name": "Nombre(s) Apellido(s) Candidato 3",
					"color": "purple",
					"valor": "350",
					"porcentaje": 30,
					"icono": "3",
					"ganadas": 0
				  },
				  {
					"id": "4",
					"name": "Nombre(s) Apellido(s) Candidato 4",
					"color": "yellow",
					"valor": "50",
					"porcentaje": 8,
					"icono": "4",
					"ganadas": 1
				  },
				  {
					"id": "5",
					"name": "Nombre(s) Apellido(s) Candidato 5",
					"color": "green",
					"valor": "450",
					"porcentaje": 50,
					"icono": "5",
					"ganadas": 0
				  },
				  {
					"id": "6",
					"name": "Nombre(s) Apellido(s) Candidato 6",
					"color": "red",
					"valor": "350",
					"porcentaje": 30,
					"icono": "6",
					"ganadas": 0
				  },
				  {
					"id": "7",
					"name": "Nombre(s) Apellido(s) Candidato 7",
					"color": "#E468A2",
					"valor": "650",
					"porcentaje": 70,
					"icono": "7",
					"ganadas": 1
				  },
				  {
					"id": "8",
					"name": "Nombre(s) Apellido(s) Candidato 8",
					"color": "black",
					"valor": "250",
					"porcentaje": 20,
					"icono": "8",
					"ganadas": 0
				  },
				  {
					"id": "9",
					"name": "Nombre(s) Apellido(s) Candidato 9",
					"color": "black",
					"valor": "250",
					"porcentaje": 20,
					"icono": "9",
					"ganadas": 0
				  }
				]
			  }
			  
		';
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
