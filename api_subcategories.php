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
		$type = trim(htmlentities($_GET["type"]));
		$seleccion = trim(htmlentities($_GET["seleccion"]));
		
		// apertura de BD
		$db = new SQLite3('db/database.db3');
		// Contadores:
		$reg_data = 0;
		$reg_cat =0;
		
		
		// Registro general de BD
		$itemRecords = array();	
		
		// FILTRO
		$filtro="";
		//Distrito
		if($type=="1" || $type==2){
			$filtro="id_distrito";
		}
		else
		{
		// Delegación
			$filtro="id_delegacion";
		}
		//----------------------------------
		// Catalogo de Delegaciones
		$res_catch = $db->query('SELECT DISTINCT id_seccion as [id], id_seccion as [name]  FROM scd_casillas where '.$filtro.' = '.$seleccion.' order by id_seccion;');
		
		$itemRecords["subcategories"] = array();
		$db->enableExceptions(false);
		while ($row_catch = $res_catch->fetchArray(SQLITE3_ASSOC))
		{
			//$var_catch = $row_catch['value']." - ".$row_catch['label']."<br>";
			array_push($itemRecords["subcategories"], $row_catch);
			$reg_cat++;
		}
		
		// Cerramos base de datos
		$db->close();
		unset($db);
		$error = 0;
		
		// Registro de estado
		$itemRecords["estado"] = array();
		
		$estado = array(
			"reg_data" => $reg_data,
			"reg_cat" => $reg_cat,
			"error" => $error
		);
		
		array_push($itemRecords["estado"], $estado);

		
		echo json_encode($itemRecords);

		//----------------------------------
		
		/*
		echo '
		{
			"subcategories": [
			  {
				"id": "1_1",
				"name": "Subcategoría 1.1"
			  },
			  {
				"id": "1_2",
				"name": "Subcategoría 1.2"
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
