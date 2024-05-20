<?php
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Allow: GET, POST, OPTIONS, PUT, DELETE");

	function participantes($db)
	{
			
		$sqlData = "SELECT 'votos_part_' || trim(cast(id_participante as text)) as id_llave, id_participante, descripcion, siglas, trim(cast(id_participante as text)) || '.jpg' as imagen	FROM scd_cat_participantes";
		
		$record = array();
		
		$res_catch = $db->query($sqlData);
		if(!$res_catch) return null;
		$db->enableExceptions(false);
		
		while ($row = $res_catch->fetchArray(SQLITE3_ASSOC))
		{
			array_push($record, $row);
		}


		return $record;
	}


	$db = new SQLite3('db/database.db3');
	$db->enableExceptions(false);
	

	$registros = participantes($db);
	
	echo json_encode($registros);
	

?>


