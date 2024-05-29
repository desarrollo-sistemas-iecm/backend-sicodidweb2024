<?php
// Cabecera para evitar CORS
error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);
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
	
	function consigueFranjaJGOB($db){
		
			// Lamamos catálogo de porcentajes
			$sqlCatPor = "
				SELECT '09' || printf('%04d', P.id_seccion) || substr(P.tipo_casilla, 1, 3) || '00' as CLAVE_CASILLA,
				 '09' || printf('%04d', P.id_seccion) || substr(P.tipo_casilla, 1, 3) || '00' || P.tipo_casilla as CLAVE_ACTA,
				 '09' as ID_ENTIDAD, 'CIUDAD DE MÉXICO' as ENTIDAD, printf('%03d', P.id_distrito) as DISTRITO_LOCAL,
				 printf('%04d', P.id_seccion) as SECCION, substr(P.tipo_casilla, 2, 2) as ID_CASILLA, 
				 substr(P.tipo_casilla, 1, 1) as TIPO_CASILLA, '00' as EXT_CONTIGUA, '01' as UBICACION_CASILLA,
				 CASE WHEN substr(P.tipo_casilla, 1, 1) = 'S' THEN 'A02E'
					  WHEN substr(P.tipo_casilla, 1, 1) = 'A' THEN 'A01VA'
					  WHEN substr(P.tipo_casilla, 1, 1) = 'P' THEN 'A01PPP'
					  WHEN substr(P.tipo_casilla, 1, 1) = 'M' THEN 'A01'
					  WHEN substr(P.tipo_casilla, 1, 1) = 'U' THEN 'A04UE'
					  ELSE 'A02'
				 END as TIPO_ACTA, P.boletas_sob as TOTAL_BOLETAS_SOBRANTES, P.ciudadanos_votaron as PERSONAS_VOTARON,
				 P.representantes_votaron as TOTAL_REP_PARTIDO_CI_VOTARON, P.votacion_total as TOTAL_VOTOS_SACADOS,
				 P.votos_part_1 as 'PAN', P.votos_part_2 as 'PRI', P.votos_part_3 as 'PRD', P.votos_part_4 as 'PVEM',
				 P.votos_part_5 as 'PT', P.votos_part_6 as 'MC', P.votos_part_7 as 'MORENA',
				 P.votos_part_10 as 'PAN_PRI_PRD', P.votos_part_11 as 'PAN_PRI', P.votos_part_12 as 'PAN_PRD',
				 P.votos_part_13 as 'PRI_PRD', P.votos_part_14 as 'PVEM_PT_MORENA', P.votos_part_15 as 'PVEM_PT',
				 P.votos_part_16 as 'PVEM_MORENA', P.votos_part_17 as 'PT_MORENA', P.votos_cand_no_reg as 'NO_REGISTRADAS',
				 P.votos_nulos as 'NULOS', P.votacion_total as 'TOTAL_VOTOS_ASENTADO',P.votacion_total as 'TOTAL_VOTOS_CALCULADO',
				 C.lista_nominal as 'LISTA_NOMINAL', P.representantes_votaron as 'REPRESENTANTES_PP_CI', 
				 CASE WHEN P.inconsistencia =1 THEN 'ILEGIBLE' 
WHEN P.inconsistencia =2 THEN 'SIN DATO'
WHEN P.inconsistencia =3 THEN 'EXCEDE LISTA NOMINAL'
WHEN P.inconsistencia =4 THEN 'SIN ACTA'
WHEN P.inconsistencia =5 THEN 'NO IDENTFICADA(FUERA DE CATALOGO)'
WHEN P.inconsistencia =6 THEN 'TODO ILEGIBLE'
WHEN P.inconsistencia =7 THEN 'TODOS VACIOS'
WHEN P.inconsistencia =11 THEN 'NP: SIN ACTA POR PAQUETE NO ENTREGADO'
WHEN P.inconsistencia =12 THEN 'NC: SIN ACTA POR CASILLA NO INSTALADA'
WHEN P.inconsistencia =13 THEN 'NB: SIN ACTA POR PAQUETE ENTREGADO SIN BOLSA'
WHEN P.inconsistencia =14 THEN 'PC-UE:SIN ACTA POR CONTINGENCIA EN URNA ELECRONICA' ELSE 'N/A' END as 'OBSERVACIONES',
			 CASE WHEN P.contabilizar = 'T' THEN '1'
					  ELSE '0'
			 END as 'CONTABILIZADA', 'F' as 'MECANISMO_TRASLADO',  A.md5_img_jgob as 'CODIGO_INTEGRIDAD',
			A.fechaRecepcion as 'FECHA_HORA_ACOPIO', P.fecha_alta as 'FECHA_HORA_CAPTURA', 
		 P.fecha_modif as 'FECHA_HORA_VERIFICACION',  CASE WHEN P.capturado_por=1 THEN 'CATD' 
				 WHEN P.capturado_por=2 THEN 'CATD'
				 WHEN P.capturado_por=3 THEN 'CASILLA'
				 WHEN P.capturado_por=4 THEN 'CASILLA'
				 ELSE 'N/A'	END as ORIGEN, CASE WHEN P.capturado_por=1 THEN 'MOVIL' 
				 WHEN P.capturado_por=2 THEN 'MOVIL'
				 WHEN P.capturado_por=3 THEN 'ESCANER'
				 WHEN P.capturado_por=4 THEN 'ESCANER' ELSE 'N/A'
				 END  as DIGITALIZACION,
				 'ACTA PREP' as 'TIPO_DOCUMENTO'
				 FROM scd_casillas as C
                 left join scd_votos P 
                 on P.id_distrito = C.id_distrito 
                 and P.id_delegacion = C.id_delegacion 
                 and P.id_seccion = C.id_seccion 
                 and P.tipo_casilla = C.tipo_casilla
                 LEFT JOIN dig_actas_prep as A
                 on P.clave_mdc = A.acta
                 where id_tipo_eleccion =1";
	
		
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
		
	//	$type = trim(htmlentities($_GET["type"]));
		
		// Registro general de BD
		$itemRecords = array();
		//echo $qryData; return;
		// apertura de BD

		$db = new SQLite3('../db/database.db3');
		$itemRecords = consigueFranjaJGOB($db);
		
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
	
	/////////////////////////////////// fecha de corte
$query3 ="SELECT * from corte";
$rows = $db->query($query3);

if($r3 = $rows->fetcharray()){

  $v_corte=$r3['hora'].":".$r3['minuto'];
  $v_fecha_corte=$r3['dia']."/".$r3['mes']."/".$r3['anio'];

}

		 //create a file pointer
	 $f = fopen('bd-gobierno.csv', 'w');

//////////////////////////////////

$delimiter = ",";
$tipo_eleccion="JEFATURA DE GOBIERNO";

	fputcsv($f, array(utf8_decode($tipo_eleccion)));
	fputcsv($f, array(utf8_decode($v_fecha_corte.' '.$v_corte.'(UTC-6)')));	
	
/////////// CAPTURADADS ////////
$q="
SELECT  id_tipo_eleccion, COUNT(C.id_distrito) AS cuantos, SUM(C.lista_nominal) AS ln, 
        SUM(votos_cand_no_reg) AS votos_cand_no_reg,  SUM(votos_nulos) AS votos_nulos, SUM(votacion_total) AS votacion_total
    FROM scd_votos P
        LEFT JOIN scd_casillas C 
        ON P.id_distrito = C.id_distrito 
        AND P.id_delegacion = C.id_delegacion 
        AND P.id_seccion = C.id_seccion 
        AND P.tipo_casilla = C.tipo_casilla WHERE id_tipo_eleccion=1
    GROUP BY 
        id_tipo_eleccion";

 $rows = $db->query($q);

 if($r = $rows->fetcharray()){
	
	$a_registradas=$r['cuantos'];
	$lista_nominal=$r['ln'];
	$cand_no_registradas=$r['votos_cand_no_reg'];
	$votos_nulos=$r['votos_nulos'];
	$v_votacion_total=$r['votacion_total'];
	
  }


///////////////////////////
$q ="
SELECT count(inconsistencia) as actas_fuera_catalogo FROM scd_votos where id_tipo_eleccion=1 and inconsistencia=5";

        $rows = $db->query($q);
        if($r = $rows->fetchArray())
           {
          $v_fuera_catalogo=$r['actas_fuera_catalogo'];  //actas fuera de catalogo
        }



//////////////// con inconsistencia de tipo 3 6y 7 para el porcentaje de actas con inconsistecia //////////////

$q ="
SELECT count(inconsistencia) as inconsistencia FROM scd_votos where id_tipo_eleccion=1 and inconsistencia in (1,2,3,4,6,7)";

        $rows = $db->query($q);
        if($r = $rows->fetchArray())
           {
          $v_inconsistencia=$r['inconsistencia'];  //actas fuera de catalogo
        }
/////////////////esperadas
 $q="select * from (select count(*) as esperadas, sum(lista_nominal) as ln from scd_casillas ) as esperadas";

 $rows = $db->query($q);
 if($r = $rows->fetchArray())
	{
   $esperadas=$r['esperadas'];  //actas fuera de catalogo
 }
//////////////// estas  son las contabilizados

$q="select *, CASE WHEN ln = 0 THEN NULL ELSE ROUND(votacion_total * 100.0 / ln, 4) END as participacion
from ( SELECT  id_tipo_eleccion,   COUNT(C.id_distrito) AS cuantos, 
        SUM(C.lista_nominal) AS ln, 
        SUM(votos_cand_no_reg) AS votos_cand_no_reg, 
        SUM(votos_nulos) AS votos_nulos,
        SUM(votacion_total) AS votacion_total
    FROM scd_votos P
        LEFT JOIN scd_casillas C 
        ON P.id_distrito = C.id_distrito 
        AND P.id_delegacion = C.id_delegacion 
        AND P.id_seccion = C.id_seccion 
        AND P.tipo_casilla = C.tipo_casilla
    where P.contabilizar='T' and  id_tipo_eleccion=1
    GROUP BY 
        id_tipo_eleccion
 ) as contabilizadas";
 
 $rows = $db->query($q);
        if($r = $rows->fetchArray()){

    $v_contabilizadas=$r['cuantos']; /// actas contabilizadas
    $v_votacion_total=$r['votacion_total'];
	$listaContabilizada=$r['ln'];
	$v_participacion=$r['participacion'];

       }


/////////////////////////// no contabilizadas
$q ="
SELECT sum(votacion_total) as votacion_total, sum(
	case tipo_casilla
		when 'S01' then votacion_total
		when 'S02' then votacion_total
		when 'S03' then votacion_total
		else 0
	end) as votacion_sin_especiales FROM scd_votos where id_tipo_eleccion=1 and contabilizar ='F'";

$rows = $db->query($q);
if($r = $rows->fetchArray()){
$v_nocontabilizadas=$r['votacion_total'];
$v_votacion_sin_especiales=$r['votacion_total']-$r['votacion_sin_especiales'];
}


  fputcsv($f,array('ACTAS_ESPERADAS','ACTAS_REGISTRADAS','ACTAS_FUERA_CATALOGO','ACTAS_CAPTURADAS','PORCENTAJE_ACTAS_CAPTURADAS', 
  'ACTAS_CONTABILIZADAS', 'PORCENTAJE_ACTAS_CONTABILIZADAS', 'PORCENTAJE_ACTAS_INCONSISTENCIAS', 'ACTAS_NO_CONTABILIZADAS',
   'LISTA_NOMINAL_ACTAS_CONTABILIZADAS', 'TOTAL_VOTOS_C_CS', 'TOTAL_VOTOS_S_CS', 'PORCENTAJE_PARTICIPACION_CIUDADANA'));

  fputcsv($f,array($esperadas, $a_registradas, $v_fuera_catalogo, $a_registradas,
  number_format((($a_registradas*100)/$esperadas), 4, '.', ','), $v_contabilizadas,
  number_format((($v_contabilizadas*100)/$esperadas), 4, '.', ','), number_format((($v_inconsistencia*100)/$esperadas), 4, '.', ','),
  $v_nocontabilizadas,$listaContabilizada,$v_votacion_total,$v_votacion_sin_especiales,$v_participacion));

		
		// AGREGAMOS LOS TITULOS
		fputcsv($f, $titulos, $delimiter);
		
		foreach ($itemRecords as $row) {
			//echo var_dump($row)."<br>";
			fputcsv($f, $row, $delimiter);
		}
		fclose($f);
		$db->close();
		
		$zip = new ZipArchive();
		$date = $r3['anio'].$r3['mes'].$r3['dia'].'_'.$r3['hora'].$r3['minuto'];
		$filename = $date."_PREP_GUB_CDMX.zip";

		if ($zip->open($filename, ZipArchive::CREATE)!==TRUE) {
			exit("cannot open <$filename>\n");
		}

		$zip->addFile("bd-gobierno.csv","CDMX_GUB_2024.csv");
		$zip->addFile("CDMX_GUB_CANDIDATURAS.csv","CDMX_GUB_CANDIDATURAS_2024.csv");
		$zip->addFile("LEEME_G.txt","leeme.txt");

		$zip->close();
		//fputcsv($f, $lineData, $delimiter);
		//echo json_encode($itemRecords);

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
	echo $e;

	// $host = $_SERVER['HTTP_HOST'];
	// $ruta = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
	// $html = 'actualizando_bd.html';
	// $url = "http://$host$ruta/$html";
	// header("Location: $url");

} finally {
	// if($error==666)
	// {
	// 	//$host = $_SERVER['HTTP_HOST'];
	// 	//$ruta = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
	// 	$html = 'actualizando_bd.html';
	// 	//$url = "http://$host$ruta/$html";
	// 	header("Location: $html");
	// }
}
