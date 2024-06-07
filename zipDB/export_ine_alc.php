 <?php
// Cabecera para evitar CORS  version ultimo ine 22-mayo-24
//boletas extraidas - total asentados 24mayo24
// solo  me traigo valdidas - 26 de mayo 24
//error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);
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
	
	function consigueFranjaALC($db){
		
			// Lamamos catálogo de porcentajes
			$sqlCatPor = "SELECT 
      printf('%03d', P.id_delegacion) as ID_ALCALDIA,
      CASE
          WHEN P.id_delegacion = 2 THEN 'AZCAPOTZALCO'
          WHEN P.id_delegacion = 3 THEN 'COYOACAN'
          WHEN P.id_delegacion = 4 THEN 'CUAJIMALPA DE MORELOS'
          WHEN P.id_delegacion = 5 THEN 'GUSTAVO A MADERO'
          WHEN P.id_delegacion = 6 THEN 'IZTACALCO'
          WHEN P.id_delegacion = 7 THEN 'IZTAPALAPA'
          WHEN P.id_delegacion = 8 THEN 'LA MAGDALENA CONTRERAS'
          WHEN P.id_delegacion = 9 THEN 'MILPA ALTA'
          WHEN P.id_delegacion = 10 THEN 'ALVARO OBREGON'
          WHEN P.id_delegacion = 11 THEN 'TLAHUAC'
          WHEN P.id_delegacion = 12 THEN 'TLALPAN'
          WHEN P.id_delegacion = 13 THEN 'XOCHIMILCO'
          WHEN P.id_delegacion = 14 THEN 'BENITO JUAREZ'
          WHEN P.id_delegacion = 15 THEN 'CUAUHTEMOC'
          WHEN P.id_delegacion = 16 THEN 'MIGUEL HIDALGO'
          WHEN P.id_delegacion = 17 THEN 'VENUSTIANO CARRANZA'
          ELSE 'N/A'
      END as ALCALDIA,
      CASE
          WHEN P.tipo_casilla = 'A1'
          or P.tipo_casilla = 'A2'
          or P.tipo_casilla = 'A3' THEN 'N/A'
          ELSE printf('%04d', P.id_seccion)
      END AS SECCION,
      CASE
          WHEN P.tipo_casilla = 'A1'
          or P.tipo_casilla = 'A2'
          or P.tipo_casilla = 'A3' THEN 'N/A'
          ELSE substr(P.tipo_casilla, 2, 2)
      END as ID_CASILLA,
      substr(P.tipo_casilla, 1, 1) as TIPO_CASILLA,
      '00' as EXT_CONTIGUA,
      '01' as UBICACION_CASILLA,
      CASE
          WHEN substr(P.tipo_casilla, 1, 1) = 'S' THEN 'A04E'
          WHEN substr(P.tipo_casilla, 1, 1) = 'A' THEN 'A03VA'
          WHEN substr(P.tipo_casilla, 1, 1) = 'P' THEN 'A03PPP'
          WHEN substr(P.tipo_casilla, 1, 1) = 'U' THEN 'A06UE'
          ELSE 'A04'
      END as TIPO_ACTA,
      (
          CASE
              WHEN P.boletas_sob is null
               THEN 'SIN DATO'
              WHEN P.boletas_sob is null
               THEN 'SIN ACTA'
              WHEN P.boletas_sob is null
               THEN 'ILEGIBLE '
              ELSE P.boletas_sob
          end
      ) as 'TOTAL_BOLETAS_SOBRANTES',
      (
          CASE
              WHEN P.ciudadanos_votaron is null
               THEN 'SIN DATO'
              wHEN P.ciudadanos_votaron is null
               THEN 'SIN ACTA'
              WHEN P.ciudadanos_votaron is null
               THEN 'ILEGIBLE '
              ELSE P.ciudadanos_votaron
          end
      ) as 'PERSONAS_VOTARON',
      (
          CASE
              WHEN P.representantes_votaron is null
               THEN 'SIN DATO'
              wHEN P.representantes_votaron is null
               THEN 'SIN ACTA'
              WHEN P.representantes_votaron is null
               THEN 'ILEGIBLE '
              ELSE P.representantes_votaron
          end
      ) as 'TOTAL_REP_PARTIDO_CI_VOTARON',
      (
          CASE
              WHEN P.votacion_total is null
               THEN 'SIN DATO'
              wHEN P.votacion_total is null
               THEN 'SIN ACTA'
              WHEN P.votacion_total is null
               THEN 'ILEGIBLE '
              ELSE P.votacion_total
          end
      ) as 'TOTAL_VOTOS_SACADOS',
      (
          CASE
              WHEN P.votos_part_1 is null
               THEN 'SIN DATO'
              wHEN P.votos_part_1 is null
               THEN 'SIN ACTA'
              WHEN P.votos_part_1 is null
               THEN 'ILEGIBLE '
              ELSE P.votos_part_1
          end
      ) as 'PAN',
      (
          CASE
              WHEN P.votos_part_2 is null
               THEN 'SIN DATO'
              wHEN P.votos_part_2 is null
               THEN 'SIN ACTA'
              WHEN P.votos_part_2 is null
               THEN 'ILEGIBLE '
              ELSE P.votos_part_2
          end
      ) as 'PRI',
      (
          CASE
              WHEN P.votos_part_3 is null
               THEN 'SIN DATO'
              wHEN P.votos_part_3 is null
               THEN 'SIN ACTA'
              WHEN P.votos_part_3 is null
               THEN 'ILEGIBLE '
              ELSE P.votos_part_3
          end
      ) as 'PRD',
      (
          CASE
              WHEN P.votos_part_4 is null
               THEN 'SIN DATO'
              wHEN P.votos_part_4 is null
               THEN 'SIN ACTA'
              WHEN P.votos_part_4 is null
               THEN 'ILEGIBLE '
              ELSE P.votos_part_4
          end
      ) as 'PVEM',
      (
          CASE
              WHEN P.votos_part_5 is null
               THEN 'SIN DATO'
              wHEN P.votos_part_5 is null
               THEN 'SIN ACTA'
              WHEN P.votos_part_5 is null
               THEN 'ILEGIBLE '
              ELSE P.votos_part_5
          end
      ) as 'PT',
      (
          CASE
              WHEN P.votos_part_6 is null
               THEN 'SIN DATO'
              wHEN P.votos_part_6 is null
               THEN 'SIN ACTA'
              WHEN P.votos_part_6 is null
               THEN 'ILEGIBLE '
              ELSE P.votos_part_6
          end
      ) as 'MC',
      (
          CASE
              WHEN P.votos_part_7 is null
               THEN 'SIN DATO'
              wHEN P.votos_part_7 is null
               THEN 'SIN ACTA'
              WHEN P.votos_part_7 is null
               THEN 'ILEGIBLE '
              ELSE P.votos_part_7
          end
      ) as 'MORENA',
      (
          CASE
              WHEN P.votos_part_9 is null
               THEN 'SIN DATO'
              wHEN P.votos_part_9 is null
               THEN 'SIN ACTA'
              WHEN P.votos_part_9 is null
               THEN 'ILEGIBLE '
              ELSE P.votos_part_9
          end
      ) as 'CSP',
      (
          CASE
              WHEN P.votos_part_14 is null
               THEN 'SIN DATO'
              wHEN P.votos_part_14 is null
               THEN 'SIN ACTA'
              WHEN P.votos_part_14 is null
               THEN 'ILEGIBLE '
              ELSE P.votos_part_14
          end
      ) as 'MORENA_PT_PVEM',
      (
          CASE
              WHEN P.votos_part_10 is null
               THEN 'SIN DATO'
              wHEN P.votos_part_10 is null
               THEN 'SIN ACTA'
              WHEN P.votos_part_10 is null
               THEN 'ILEGIBLE '
              ELSE P.votos_part_10
          end
      ) as 'PAN_PRI_PRD',
      (
          CASE
              WHEN P.votos_part_11 is null
               THEN 'SIN DATO'
              wHEN P.votos_part_11 is null
               THEN 'SIN ACTA'
              WHEN P.votos_part_11 is null
               THEN 'ILEGIBLE '
              ELSE P.votos_part_11
          end
      ) as 'PAN_PRI',
      (
          CASE
              WHEN P.votos_part_12 is null
               THEN 'SIN DATO'
              wHEN P.votos_part_12 is null
               THEN 'SIN ACTA'
              WHEN P.votos_part_12 is null
               THEN 'ILEGIBLE '
              ELSE P.votos_part_12
          end
      ) as 'PAN_PRD',
      (
          CASE
              WHEN P.votos_part_13 is null
               THEN 'SIN DATO'
              wHEN P.votos_part_13 is null
               THEN 'SIN ACTA'
              WHEN P.votos_part_13 is null
               THEN 'ILEGIBLE '
              ELSE P.votos_part_13
          end
      ) as 'PRI_PRD',
      (
          CASE
              WHEN P.votos_cand_no_reg is null
               THEN 'SIN DATO'
              WHEN P.votos_cand_no_reg is null
               THEN 'SIN ACTA'
              WHEN P.votos_cand_no_reg is null
               THEN 'ILEGIBLE '
              ELSE P.votos_cand_no_reg
          end
      ) as 'NO_REGISTRADAS',
      (
          CASE
              WHEN P.votos_nulos is null
               THEN 'SIN DATO'
              WHEN P.votos_nulos is null
               THEN 'SIN ACTA'
              WHEN P.votos_nulos is null
               THEN 'ILEGIBLE'
              ELSE P.votos_nulos
          end
      ) as 'NULOS',
      P.boletas_extraidas as 'TOTAL_VOTOS_ASENTADOS',
      P.votacion_total as 'TOTAL_VOTOS_CALCULADOS',
      C.lista_nominal as 'LISTA_NOMINAL',
      P.representantes_votaron as 'REPRESENTANTES_PP_CI',
      strftime('%d/%m/%Y %H:%M:%S', datetime(P.fecha_alta)) || ' (UTC-6)' AS FECHA_HORA_CAPTURA
  FROM scd_casillas as C
      left join scd_votos P on P.id_distrito = C.id_distrito
      and P.id_delegacion = C.id_delegacion
      and P.id_seccion = C.id_seccion
      and P.tipo_casilla = C.tipo_casilla
      LEFT JOIN dig_actas_prep as A on P.clave_mdc = A.acta
  WHERE id_tipo_eleccion = 4";
		
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
		$itemRecords = consigueFranjaALC($db);
		
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

	 
///create a file pointer
	$f = fopen('bd-alcalde.csv', 'w');//////////////////////////

//////////////////////////////////

$delimiter = ",";
$tipo_eleccion="ALCALDIAS";
$blanks= array();

	fputcsv($f, array(utf8_decode($tipo_eleccion)));
	fputcsv($f, array(utf8_decode($v_fecha_corte.' '.$v_corte.'(UTC-6)')));


// INICIA RENGLO EN BLANCO GENERA UN SALTO DE LINEA 
array_push($blanks,array("\t","\t","\t","\t"));
foreach ($blanks as $fields) {
  fputcsv($f, $fields);
}

////////////////////////		
/////////// CAPTURADADS ////////
$q="SELECT  id_tipo_eleccion, COUNT(C.id_distrito) AS cuantos, SUM(C.lista_nominal) AS ln, 
        SUM(votos_cand_no_reg) AS votos_cand_no_reg,  SUM(votos_nulos) AS votos_nulos, SUM(votacion_total) AS votacion_total
    FROM scd_votos P
        LEFT JOIN scd_casillas C 
        ON P.id_distrito = C.id_distrito 
        AND P.id_delegacion = C.id_delegacion 
        AND P.id_seccion = C.id_seccion 
        AND P.tipo_casilla = C.tipo_casilla WHERE id_tipo_eleccion=4";

 $rows = $db->query($q);

 if($r = $rows->fetcharray()){
	
	$a_registradas=$r['cuantos'];
	$lista_nominal=$r['ln'];
	$cand_no_registradas=$r['votos_cand_no_reg'];
	$votos_nulos=$r['votos_nulos'];
	$v_votacion_total=$r['votacion_total'];
	
  }


///////////////////////////
/* $q ="SELECT count(inconsistencia) as actas_fuera_catalogo FROM scd_votos where id_tipo_eleccion=4 and inconsistencia=5";

        $rows = $db->query($q);
        if($r = $rows->fetchArray())
           {
          $v_fuera_catalogo=$r['actas_fuera_catalogo'];  //actas fuera de catalogo
        } */
        $v_fuera_catalogo=0;  //actas fuera de catalogo



//////////////// con inconsistencia de tipo 3 6y 7 para el porcentaje de actas con inconsistecia //////////////

/* $q ="SELECT count(inconsistencia) as inconsistencia FROM scd_votos where id_tipo_eleccion=4 and inconsistencia in (1,2,3,6,7)";

        $rows = $db->query($q);
        if($r = $rows->fetchArray())
           {
          $v_inconsistencia=$r['inconsistencia'];  //actas fuera de catalogo
        } */
        $v_inconsistencia=0;  //actas fuera de catalogo
/////////////////esperadas
 $q="select * from (select count(*) as esperadas, sum(lista_nominal) as ln from scd_casillas ) 
 as esperadas";

 $rows = $db->query($q);
 if($r = $rows->fetchArray())
	{
   $esperadas=$r['esperadas']-1;  //actas  menos extrangero
    }
//////////////// estas  son las contabilizados

$q="SELECT id_tipo_eleccion, COUNT(C.id_distrito) AS cuantos, SUM(C.lista_nominal) AS ln, 
        SUM(votos_cand_no_reg) AS votos_cand_no_reg, SUM(votos_nulos) AS votos_nulos,
        SUM(votacion_total) AS votacion_total, sum(ciudadanos_votaron) as ciudadanos_votaron FROM scd_votos P LEFT JOIN scd_casillas C ON P.id_distrito = C.id_distrito 
        AND P.id_delegacion = C.id_delegacion 
        AND P.id_seccion = C.id_seccion 
        AND P.tipo_casilla = C.tipo_casilla
    where P.contabilizar='T' and id_tipo_eleccion=4";
 
 $rows = $db->query($q);
        if($r = $rows->fetchArray()){

  $v_contabilizadas=$r['cuantos']; /// actas contabilizadas
  $v_votacion_total=$r['votacion_total'];
  $listaContabilizada=$r['ln'];
//	$v_participacion=$r['participacion'];

       }

/////////////////////////// no contabilizadas
$q ="SELECT id_tipo_eleccion, count(C.id_distrito) AS cuantos, SUM(C.lista_nominal) AS ln, 
SUM(votos_cand_no_reg) AS votos_cand_no_reg, 
SUM(votos_nulos) AS votos_nulos,
SUM(votacion_total) AS votacion_total
FROM scd_votos P
LEFT JOIN scd_casillas C 
ON P.id_distrito = C.id_distrito 
AND P.id_delegacion = C.id_delegacion 
AND P.id_seccion = C.id_seccion 
AND P.tipo_casilla = C.tipo_casilla
where P.contabilizar='F' and id_tipo_eleccion=4 ";

$rows = $db->query($q);
if($r = $rows->fetchArray()){
$v_nocontabilizadas=$r['cuantos'];


}

/////////// Me traigo las especiales por voto ///////
        
  $q="select count(*)as especial from scd_votos where tipo_casilla like '%S%'and
 id_tipo_eleccion= 4 and contabilizar ='T'";
 
 $rows = $db->query($q);
        if($r = $rows->fetchArray())
        {      
        
        
    $votos_sin_especiales=$r['especial'];   
        }
        
   $v_votacion_sin_especiales = $v_votacion_total-$votos_sin_especiales-$v_nocontabilizadas;

     fputcsv($f, array(
    //   'ACTAS_ESPERADAS',
			// 'ACTAS_REGISTRADAS',
			// 'ACTAS_FUERA_CATALOGO',
			// 'ACTAS_CAPTURADAS',
			// 'PORCENTAJE_ACTAS_CAPTURADAS',
			'ACTAS_COMPUTADAS',
			// 'PORCENTAJE_ACTAS_COMPUTADAS',
			// 'PORCENTAJE_ACTAS_INCONSISTENCIAS',
			// 'ACTAS_NO_COMPUTADAS',
			'LISTA_NOMINAL_ACTAS_CAPTURADAS',
			'TOTAL_VOTOS',
			// 'TOTAL_VOTOS_S_CS',
			'PORCENTAJE_PARTICIPACION_CIUDADANA'
     )
     );

     //fputcsv($f,array($esperadas, $a_registradas, $v_fuera_catalogo, $a_registradas,
     //number_format((($a_registradas*100)/$esperadas), 4, '.', ','), $v_contabilizadas,
     //number_format((($v_contabilizadas*100)/$esperadas), 4, '.', ','), number_format((($v_inconsistencia*100)/$esperadas), 4, '.', ','),
     //$v_nocontabilizadas,$listaContabilizada,$v_votacion_total,$v_votacion_sin_especiales,$v_participacion));
 
     fputcsv($f, array(
    //    $esperadas,
      //  $a_registradas,
       $v_fuera_catalogo,
    //    $a_registradas,
    //    number_format((($a_registradas / $esperadas) * 100), 4, '.', ','),
       $v_contabilizadas,
    //    number_format((($v_contabilizadas / $esperadas) * 100), 4, '.', ','),
      //  number_format((($v_inconsistencia / $esperadas) * 100), 4, '.', ','),
      //  $v_nocontabilizadas,
       $listaContabilizada,
       $v_votacion_total,
      //  $v_votacion_sin_especiales,
       number_format((($v_votacion_total / $listaContabilizada) * 100), 4, '.', ',')
     )
     );
     //  number_format((($v_votacion_total/$listaContabilizada)*100), 4, '.', ',')));


		// INICIA RENGLO EN BLANCO GENERA UN SALTO DE LINEA 
    $blanks = array();	
    array_push($blanks,array("\t","\t","\t","\t"));
  foreach ($blanks as $fields) {
  fputcsv($f, $fields);
  }
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
		$filename = $date."_SICODID_ALC_CDMX.zip";

		if ($zip->open($filename, ZipArchive::CREATE)!==TRUE) {
			exit("cannot open <$filename>\n");
		}

		$zip->addFile("bd-alcalde.csv","CDMX_ALC_2024.csv");
		$zip->addFile("CDMX_ALC_CANDIDATURAS.csv","CDMX_ALC_CANDIDATURAS_2024.csv");
		// $zip->addFile("LEEME_A.txt","leeme.txt");

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
