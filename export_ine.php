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
	
	function consigueFranja2JGOB($db){
		
			// Lamamos catálogo de porcentajes
			$sqlCatPor = "
				SELECT '09' || printf('%04d', P.id_seccion) || substr(P.tipo_casilla, 1, 3) || '00' as clave_casilla,
				 '09' || printf('%04d', P.id_seccion) || substr(P.tipo_casilla, 1, 3) || '00' || P.tipo_casilla as clave_acta,
				 '09' as id_entidad, 'CIUDAD DE MÉXICO' as entidad, printf('%03d', P.id_distrito) as distrito_local,
				 printf('%04d', P.id_seccion) as seccion, substr(P.tipo_casilla, 2, 2) as id_casilla, 
				 substr(P.tipo_casilla, 1, 1) as tipo_casilla, '00' as ext_contigua, '01' as ubicacion_casilla,
				 CASE WHEN substr(P.tipo_casilla, 1, 1) = 'S' THEN 'A02E'
					  WHEN substr(P.tipo_casilla, 1, 1) = 'A' THEN 'A01VA'
					  WHEN substr(P.tipo_casilla, 1, 1) = 'P' THEN 'A01PPP'
					  WHEN substr(P.tipo_casilla, 1, 1) = 'M' THEN 'A01'
					  WHEN substr(P.tipo_casilla, 1, 1) = 'U' THEN 'A04UE'
					  ELSE 'A02'
				 END as tipo_acta, P.boletas_sob as total_boletas_sobrantes, P.ciudadanos_votaron as personas_votaron,
				 P.representantes_votaron as total_rep_partido_ci_votaron, P.votacion_total as total_votos_sacados,
				 P.votos_part_1 as 'PAN', P.votos_part_2 as 'PRI', P.votos_part_3 as 'PRD', P.votos_part_4 as 'PVEM',
				 P.votos_part_5 as 'PT', P.votos_part_6 as 'MC', P.votos_part_7 as 'MORENA',
				 P.votos_part_10 as 'PAN_PRI_PRD', P.votos_part_11 as 'PAN_PRI', P.votos_part_12 as 'PAN_PRD',
				 P.votos_part_13 as 'PRI_PRD', P.votos_part_14 as 'PVEM_PT_MORENA', P.votos_part_15 as 'PVEM_PT',
				 P.votos_part_16 as 'PVEM_MORENA', P.votos_part_17 as 'PT_MORENA', P.votos_cand_no_reg as 'no_registradas',
				 P.votos_nulos as 'nulos', P.votacion_total as 'total_votos_asentado',P.votacion_total as 'total_votos_calculado',
				 C.lista_nominal as 'lista_nominal', '??' as 'representantes_pp_ci', '??' as 'observaciones',
				 CASE WHEN P.contabilizar = 'T' THEN '1'
					  ELSE '0'
				 END as 'contabilizada', '?' as 'mecanismo_traslado', 'FFFFFFxxxxxx6969696' as 'codigo_integridad',
				 'dd/mm/yyyy hh_mm:ss (UTC-6)' as 'fecha_hora_acopio', P.fecha_alta as 'fecha_hora_captura', 
				 'dd/mm/yyyy hh_mm:ss (UTC-6)' as 'fecha_hora_verificacion', 'CASILLA' as origen, 'ESCANER' as 'digitalizacion',
				 'ACTA PREP' as 'tipo_documento'
				 from scd_casillas as C
				left join scd_votos P 
				on P.id_distrito = C.id_distrito 
				and P.id_delegacion = C.id_delegacion 
				and P.id_seccion = C.id_seccion 
				and P.tipo_casilla = C.tipo_casilla
				left join sedimde_enc_seguimiento as S
				on P.id_distrito = S.id_distrito 
				and P.id_delegacion = S.id_delegacion 
				and P.id_seccion = S.id_seccion 
				and P.tipo_casilla = S.tipo_casilla || substr('00' || S.id_casilla1, -2)
				where id_tipo_eleccion =1;";
		
		$itemRecordsTMP = array();
		$db->enableExceptions(false);
		$res_catchTMP = $db->query($sqlCatPor);
		

		while ($row = $res_catchTMP->fetchArray(SQLITE3_ASSOC))
		{
			array_push($itemRecordsTMP, $row);
		}
		
		//	echo "<br><br>CAT : ".$sqlCatPor."<br><br>";
		return $itemRecordsTMP;
					
	}
	
// Fin funcion
	if($_SERVER['REQUEST_METHOD']=="GET"){
		
		$type = trim(htmlentities($_GET["type"]));
		
		// Registro general de BD
		$itemRecords = array();
		//echo $qryData; return;
		// apertura de BD

		$db = new SQLite3('db/database.db3');
		$itemRecords = consigueFranja2JGOB($db);
		
		// 13/ABRIL/2024
		// PARA OBTENER DATOS DE TITULOS de datos FRANJA 2
		$titulos = array();
		foreach ($itemRecords as $row) {
			if(count($titulos)<=0){
				foreach ($row as $clave => $valor) {
					array_push($titulos, $clave);
				}
			}	
			break;
		}
		
		
		$delimiter = ",";
		
		 //create a file pointer
		$f = fopen('bd-jgob.csv', 'w');
		
		// AGREGAMOS LOS TITULOS
		// 13/Abril/2024
		fputcsv($f, $titulos, $delimiter);
		foreach ($itemRecords as $row) {
			//echo var_dump($row)."<br>";
			fputcsv($f, $row, $delimiter);
		}
		
		fclose($f);
		$db->close();
		
		$zip = new ZipArchive();
		$date = "10Abril2024";
		$filename = $date."_PREP_ALC_CDMX_TEST.zip";

		if ($zip->open($filename, ZipArchive::CREATE)!==TRUE) {
			exit("cannot open <$filename>\n");
		}

		$zip->addFile("bd-jgob.csv","CDMX_ALC_2021.csv");
		//$zip->addFile("alcaldias_candidatos_2021.csv","alcaldias_candidato.csv");
		//$zip->addFile("LEEME_A.txt","leeme.txt");

		$zip->close();
		//fputcsv($f, $lineData, $delimiter);
		//echo json_encode($itemRecords);



		unlink('bd-jgob.csv');
		unlink('H_PREP_ALC_CDMX.zip');

		//unlink('bd-diputados.csv');
		//unlink(''.$date.'_PREP_DIP_LOC_CDMX.zip');

		//unlink('bd-gobierno.csv');
		//unlink(''.$date.'_PREP_GUB_CDMX.zip');
		header("Content-disposition: attachment; filename=$filename");
		readfile($filename);
		unlink($filename);
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
