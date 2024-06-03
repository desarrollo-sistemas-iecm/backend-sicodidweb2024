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
include "helpers.php";



// Consigue los datos de partidos con distribución de votos
function consigueDatosJG($type, $item){
	$campoCorte = "";
	$qryParticipantesITEM = "";
	$inde1="";
	$inde2="";
	if(trim($item)!=""){
			switch ($type) {
				case 1:

					$campoCorte ="id_distrito";
					$where="";
					$inde1="";
					$inde2="";
					break;
					
				case 2:

					$campoCorte ="id_distrito";
					$inde1="sum(votos_part_8) as 'votos_part_8', ";
					$inde2="";
					$where="";
					break;
				case 3:  //RP
					$qryParticipantesITEM = '';
					$campoCorte ="";
					$inde1="";
					$inde2="";
					break;
				case 4:
						$campoCorte ="id_delegacion";
						$where="";
						$inde1="";
						$inde2="sum(votos_part_9) as 'votos_part_9', ";
					break;
			}
	
		
			$qryParticipantesITEM = "
				WITH distribucion AS (
				SELECT ".$campoCorte.", 
					 sum(V.votos_part_1) as 'votos_part_1', sum(V.votos_part_2) as 'votos_part_2', sum(V.votos_part_3) as 'votos_part_3', 
					 sum(V.votos_part_4) as 'votos_part_4', sum(V.votos_part_5) as 'votos_part_5',
					 sum(V.votos_part_6) as 'votos_part_6', sum(V.votos_part_7) as votos_part_7, sum(votos_cand_no_reg) as 'votos_cand_no_reg', 
					 sum(votos_nulos) as 'votos_nulos', sum(votacion_total) as 'votacion_total',
					 CAST(sum(V.votos_part_10)/3 as INT) as parte10, 
					 CAST(sum(V.votos_part_11)/2 as INT) as parte11,
					 CAST(sum(V.votos_part_12)/2 as INT) as parte12, 
					 CAST(sum(V.votos_part_13)/2 as INT) as parte13,
					 CAST(sum(V.votos_part_14)/3 as INT) as parte14,
					 CAST(sum(V.votos_part_15)/2 as INT) as parte15,
					 CAST(sum(V.votos_part_16)/2 as INT) as parte16,
					 CAST(sum(V.votos_part_17)/2 as INT) as parte17,
					 sum(V.votos_part_10)%3 as residuo10, 
					 sum(V.votos_part_11)%2 as residuo11, 
					 sum(V.votos_part_12)%2 as residuo12, 
					 sum(V.votos_part_13)%2 as residuo13, 
					 sum(V.votos_part_14)%3 as residuo14,
					 sum(V.votos_part_15)%2 as residuo15,
					 sum(V.votos_part_16)%2 as residuo16,
					 sum(V.votos_part_17)%2 as residuo17
					FROM scd_votos as V 
					where id_tipo_eleccion=".$type." and ".$campoCorte." = ".$item." and contabilizar='T' group by ".$campoCorte."
				),
				suma_igual AS (
					SELECT id_distrito, 
					 (votos_part_1 + parte10 + parte11 + parte12) as votos_part_1, 
					 (votos_part_2 + parte10 + parte11 + parte13) as votos_part_2,
					 (votos_part_3 + parte10 + parte12 + parte13) as votos_part_3,
					 (votos_part_4 + parte14 + parte15 + parte16) as votos_part_4,
					 (votos_part_5 + parte14 + parte15 + parte17) as votos_part_5,
					 votos_part_6, 
					 (votos_part_7 + parte14 + parte16 + parte17) as votos_part_7,
					 votos_cand_no_reg, votos_nulos, votacion_total,
					 parte10, parte11, parte12, parte13, parte14, parte15, parte16, parte17, residuo10, residuo11, residuo12, residuo13, residuo14,
					 residuo15, residuo16, residuo17
					 from distribucion
				),
				mayores AS (
					SELECT  *,
					CASE
						WHEN votos_part_1 >= votos_part_2 AND votos_part_1 >= votos_part_3 THEN 'votos_part_1'
						WHEN votos_part_2 >= votos_part_1 AND votos_part_2 >= votos_part_3 THEN 'votos_part_2'
						ELSE 'votos_part_3'
					END AS mayor10_1,
					CASE
							WHEN (votos_part_1 >= votos_part_2 AND votos_part_1 < votos_part_3) OR (votos_part_1 >= votos_part_3 AND votos_part_1 < votos_part_2) THEN 'votos_part_1'
							WHEN (votos_part_2 >= votos_part_1 AND votos_part_2 < votos_part_3) OR (votos_part_2 >= votos_part_3 AND votos_part_2 < votos_part_1) THEN 'votos_part_2'
						ELSE 'votos_part_3'
					END AS mayor10_2,
				
					CASE WHEN votos_part_1 >= votos_part_2 THEN 'votos_part_1'
					ELSE 'votos_part_2'
					END AS mayor11,

					CASE WHEN votos_part_1 >= votos_part_3 THEN 'votos_part_1'
					ELSE 'votos_part_3'
					END AS mayor12,

					CASE WHEN votos_part_2 >= votos_part_3 THEN 'votos_part_2'
					ELSE 'votos_part_3'
					END AS mayor13,
					
					CASE
						WHEN votos_part_4 >= votos_part_5 AND votos_part_4 >= votos_part_7 THEN 'votos_part_4'
						WHEN votos_part_5 >= votos_part_4 AND votos_part_5 >= votos_part_7 THEN 'votos_part_5'
						ELSE 'votos_part_7'
					END AS mayor14_1,
					CASE
							WHEN (votos_part_4 >= votos_part_5 AND votos_part_4 < votos_part_7) OR (votos_part_4 >= votos_part_7 AND votos_part_4 < votos_part_5) THEN 'votos_part_4'
							WHEN (votos_part_5 >= votos_part_4 AND votos_part_5 < votos_part_7) OR (votos_part_5 >= votos_part_7 AND votos_part_5 < votos_part_4) THEN 'votos_part_5'
						ELSE 'votos_part_7'
					END AS mayor14_2,

					CASE WHEN votos_part_4 >= votos_part_5 THEN 'votos_part_4'
					ELSE 'votos_part_5'
					END AS mayor15,

					CASE WHEN votos_part_4 >= votos_part_7 THEN 'votos_part_4'
					ELSE 'votos_part_7'
					END AS mayor16,

					CASE WHEN votos_part_5 >= votos_part_7 THEN 'votos_part_5'
					ELSE 'votos_part_7'
					END AS mayor17
					FROM
					suma_igual
				),
				distribucion_residuo AS (
					    SELECT
        *,
						votos_part_1 + 
							CASE WHEN residuo10=2 AND (mayor10_1 = 'votos_part_1' OR mayor10_2 = 'votos_part_1') THEN  1 ELSE 0 END +
							CASE WHEN residuo10=1 AND (mayor10_1 = 'votos_part_1' OR mayor10_2 = 'votos_part_1') THEN  1 ELSE 0 END +
							CASE WHEN mayor11 = 'votos_part_1' THEN residuo11 ELSE 0 END +
							CASE WHEN mayor12 = 'votos_part_1' THEN residuo12 ELSE 0 END AS votos_part_1_S,
						votos_part_2 + CASE WHEN residuo10=2 AND (mayor10_1 = 'votos_part_2' OR mayor10_2 = 'votos_part_2') THEN  1 ELSE 0 END +
							CASE WHEN mayor11 = 'votos_part_2' THEN residuo11 ELSE 0 END +
							CASE WHEN mayor13 = 'votos_part_2' THEN residuo13 ELSE 0 END AS votos_part_2_S,
						votos_part_3 + CASE WHEN residuo10=2 AND (mayor10_1 = 'votos_part_3' OR mayor10_2 = 'votos_part_3') THEN  1 ELSE 0 END +
							CASE WHEN mayor12 = 'votos_part_3' THEN  residuo12 ELSE 0 END +
							CASE WHEN mayor13 = 'votos_part_3' THEN residuo13 ELSE 0 END AS votos_part_3_S,
						votos_part_4 + 
							CASE WHEN residuo14 = 2 AND (mayor14_1 = 'votos_part_4' OR mayor14_2 = 'votos_part_4') THEN  1 ELSE 0 END +
							CASE WHEN residuo14 = 1 AND (mayor14_1 = 'votos_part_4' OR mayor14_2 = 'votos_part_4') THEN  1 ELSE 0 END +
							CASE WHEN mayor15 = 'votos_part_4' THEN residuo15 ELSE 0 END +
							CASE WHEN mayor16 = 'votos_part_4' THEN residuo16 ELSE 0 END AS votos_part_4_S,
						votos_part_5 + CASE WHEN residuo14=2 AND (mayor14_1 = 'votos_part_5' OR mayor14_2 = 'votos_part_5') THEN  1 ELSE 0 END +
							CASE WHEN mayor15 = 'votos_part_5' THEN residuo15 ELSE 0 END +
							CASE WHEN mayor17 = 'votos_part_5' THEN residuo17 ELSE 0 END AS votos_part_5_S,
						votos_part_7 + CASE WHEN residuo14=2 AND (mayor14_1 = 'votos_part_7' OR mayor14_2 = 'votos_part_7') THEN  1 ELSE 0 END +
							CASE WHEN mayor16 = 'votos_part_7' THEN  residuo16 ELSE 0 END +
							CASE WHEN mayor17 = 'votos_part_7' THEN residuo17 ELSE 0 END AS votos_part_7_S
					FROM
						mayores
				)
				select votos_part_1_S as votos_part_1, votos_part_2_S as votos_part_2, votos_part_3_S as votos_part_3, 
				votos_part_4_S as votos_part_4, votos_part_5_S as votos_part_5, votos_part_6, votos_part_7_S as votos_part_7,
				votos_cand_no_reg, votos_nulos, votacion_total from distribucion_residuo " ;
	
		}
		else{
			
			$qryParticipantesITEM = "WITH distribucion AS (
					SELECT id_distrito, 
					 sum(V.votos_part_1) as 'votos_part_1', sum(V.votos_part_2) as 'votos_part_2', sum(V.votos_part_3) as 'votos_part_3', 
					 sum(V.votos_part_4) as 'votos_part_4', sum(V.votos_part_5) as 'votos_part_5',
					 sum(V.votos_part_6) as 'votos_part_6', sum(V.votos_part_7) as votos_part_7,  
					 sum(V.votos_part_9) as 'votos_part_9', sum(votos_cand_no_reg) as 'votos_cand_no_reg', 
					 sum(votos_nulos) as 'votos_nulos', sum(votacion_total) as 'votacion_total',
					 CAST(sum(V.votos_part_10)/3 as INT) as parte10, 
					 CAST(sum(V.votos_part_11)/2 as INT) as parte11,
					 CAST(sum(V.votos_part_12)/2 as INT) as parte12, 
					 CAST(sum(V.votos_part_13)/2 as INT) as parte13,
					 CAST(sum(V.votos_part_14)/3 as INT) as parte14,
					 CAST(sum(V.votos_part_15)/2 as INT) as parte15,
					 CAST(sum(V.votos_part_16)/2 as INT) as parte16,
					 CAST(sum(V.votos_part_17)/2 as INT) as parte17,
					 sum(V.votos_part_10)%3 as residuo10, 
					 sum(V.votos_part_11)%2 as residuo11, 
					 sum(V.votos_part_12)%2 as residuo12, 
					 sum(V.votos_part_13)%2 as residuo13, 
					 sum(V.votos_part_14)%3 as residuo14,
					 sum(V.votos_part_15)%2 as residuo15,
					 sum(V.votos_part_16)%2 as residuo16,
					 sum(V.votos_part_17)%2 as residuo17
					FROM scd_votos as V 
					where id_tipo_eleccion=".$type." and contabilizar='T' group by id_distrito
				),
				suma_igual AS (
					SELECT id_distrito, 
					 (votos_part_1 + parte10 + parte11 + parte12) as votos_part_1, 
					 (votos_part_2 + parte10 + parte11 + parte13) as votos_part_2,
					 (votos_part_3 + parte10 + parte12 + parte13) as votos_part_3,
					 (votos_part_4 + parte14 + parte15 + parte16) as votos_part_4,
					 (votos_part_5 + parte14 + parte15 + parte17) as votos_part_5,
					 votos_part_6, 
					 (votos_part_7 + parte14 + parte16 + parte17) as votos_part_7,
					 votos_cand_no_reg, votos_nulos, votacion_total,
					 parte10, parte11, parte12, parte13, parte14, parte15, parte16, parte17, residuo10, residuo11, residuo12, residuo13, residuo14,
					 residuo15, residuo16, residuo17
					 from distribucion
				),
				mayores AS (
					SELECT  *,
	
						CASE
							WHEN votos_part_1 >= votos_part_2 AND votos_part_1 >= votos_part_3 THEN 'votos_part_1'
							WHEN votos_part_2 >= votos_part_1 AND votos_part_2 >= votos_part_3 THEN 'votos_part_2'
							ELSE 'votos_part_3'
						END AS mayor10_1,
						CASE
								WHEN (votos_part_1 >= votos_part_2 AND votos_part_1 < votos_part_3) OR (votos_part_1 >= votos_part_3 AND votos_part_1 < votos_part_2) THEN 'votos_part_1'
								WHEN (votos_part_2 >= votos_part_1 AND votos_part_2 < votos_part_3) OR (votos_part_2 >= votos_part_3 AND votos_part_2 < votos_part_1) THEN 'votos_part_2'
							ELSE 'votos_part_3'
						END AS mayor10_2,
				
						CASE WHEN votos_part_1 >= votos_part_2 THEN 'votos_part_1'
						ELSE 'votos_part_2'
						END AS mayor11,
			
						CASE WHEN votos_part_1 >= votos_part_3 THEN 'votos_part_1'
						ELSE 'votos_part_3'
						END AS mayor12,
			
						CASE WHEN votos_part_2 >= votos_part_3 THEN 'votos_part_2'
						ELSE 'votos_part_3'
						END AS mayor13,
						
						CASE
							WHEN votos_part_4 >= votos_part_5 AND votos_part_4 >= votos_part_7 THEN 'votos_part_4'
							WHEN votos_part_5 >= votos_part_4 AND votos_part_5 >= votos_part_7 THEN 'votos_part_5'
							ELSE 'votos_part_7'
						END AS mayor14_1,
						CASE
								WHEN (votos_part_4 >= votos_part_5 AND votos_part_4 < votos_part_7) OR (votos_part_4 >= votos_part_7 AND votos_part_4 < votos_part_5) THEN 'votos_part_4'
								WHEN (votos_part_5 >= votos_part_4 AND votos_part_5 < votos_part_7) OR (votos_part_5 >= votos_part_7 AND votos_part_5 < votos_part_4) THEN 'votos_part_5'
							ELSE 'votos_part_7'
						END AS mayor14_2,

						CASE WHEN votos_part_4 >= votos_part_5 THEN 'votos_part_4'
						ELSE 'votos_part_5'
						END AS mayor15,
	
						CASE WHEN votos_part_4 >= votos_part_7 THEN 'votos_part_4'
						ELSE 'votos_part_7'
						END AS mayor16,
		
						CASE WHEN votos_part_5 >= votos_part_7 THEN 'votos_part_5'
						ELSE 'votos_part_7'
						END AS mayor17
						FROM
						suma_igual
				),
				distribucion_residuo AS (
					SELECT
						*,
						votos_part_1 + 
							CASE WHEN residuo10=2 AND (mayor10_1 = 'votos_part_1' OR mayor10_2 = 'votos_part_1') THEN  1 ELSE 0 END +
							CASE WHEN residuo10=1 AND (mayor10_1 = 'votos_part_1' OR mayor10_2 = 'votos_part_1') THEN  1 ELSE 0 END +
							CASE WHEN mayor11 = 'votos_part_1' THEN residuo11 ELSE 0 END +
							CASE WHEN mayor12 = 'votos_part_1' THEN residuo12 ELSE 0 END AS votos_part_1_S,
						votos_part_2 + CASE WHEN residuo10=2 AND (mayor10_1 = 'votos_part_2' OR mayor10_2 = 'votos_part_2') THEN  1 ELSE 0 END +
							CASE WHEN mayor11 = 'votos_part_2' THEN residuo11 ELSE 0 END +
							CASE WHEN mayor13 = 'votos_part_2' THEN residuo13 ELSE 0 END AS votos_part_2_S,
						votos_part_3 + CASE WHEN residuo10=2 AND (mayor10_1 = 'votos_part_3' OR mayor10_2 = 'votos_part_3') THEN  1 ELSE 0 END +
							CASE WHEN mayor12 = 'votos_part_3' THEN  residuo12 ELSE 0 END +
							CASE WHEN mayor13 = 'votos_part_3' THEN residuo13 ELSE 0 END AS votos_part_3_S,
						votos_part_4 + 
							CASE WHEN residuo14 = 2 AND (mayor14_1 = 'votos_part_4' OR mayor14_2 = 'votos_part_4') THEN  1 ELSE 0 END +
							CASE WHEN residuo14 = 1 AND (mayor14_1 = 'votos_part_4' OR mayor14_2 = 'votos_part_4') THEN  1 ELSE 0 END +
							CASE WHEN mayor15 = 'votos_part_4' THEN residuo15 ELSE 0 END +
							CASE WHEN mayor16 = 'votos_part_4' THEN residuo16 ELSE 0 END AS votos_part_4_S,
						votos_part_5 + CASE WHEN residuo14=2 AND (mayor14_1 = 'votos_part_5' OR mayor14_2 = 'votos_part_5') THEN  1 ELSE 0 END +
							CASE WHEN mayor15 = 'votos_part_5' THEN residuo15 ELSE 0 END +
							CASE WHEN mayor17 = 'votos_part_5' THEN residuo17 ELSE 0 END AS votos_part_5_S,
						votos_part_7 + CASE WHEN residuo14=2 AND (mayor14_1 = 'votos_part_7' OR mayor14_2 = 'votos_part_7') THEN  1 ELSE 0 END +
							CASE WHEN mayor16 = 'votos_part_7' THEN  residuo16 ELSE 0 END +
							CASE WHEN mayor17 = 'votos_part_7' THEN residuo17 ELSE 0 END AS votos_part_7_S
					FROM
						mayores
				)
				select sum(votos_part_1_S) as sumvotos_part_1, sum(votos_part_2_S) as votos_part_2, sum(votos_part_3_S) as votos_part_3, 
				sum(votos_part_4_S) as votos_part_4, sum(votos_part_5_S) as votos_part_5, sum(votos_part_6) as votos_part_6, 
				sum(votos_part_7_S) as votos_part_7, 
				 sum(votos_cand_no_reg) as votos_cand_no_reg, sum(votos_nulos) as votos_nulos, sum(votacion_total) as votacion_total
				 from distribucion_residuo  " ;

	}
	
	

	return $qryParticipantesITEM;

};

// Consigue la sentencia los datos de partidos con distribución de votos
function consigueDatosJG_EXTRANJERO($type, $item){
	$campoCorte = "";
	$qryParticipantesITEM = "";
	$inde1="";
	$inde2="";
	if(trim($item)!=""){
			switch ($type) {
				case 1:

					$campoCorte ="id_distrito";
					$where="";
					$inde1="";
					$inde2="";
					break;
					
				case 2:

					$campoCorte ="id_distrito";
					$inde1="sum(votos_part_8) as 'votos_part_8', ";
					$inde2="";
					$where="";
					break;
				case 3:  //RP
					$qryParticipantesITEM = '';
					$campoCorte ="";
					$inde1="";
					$inde2="";
					break;
				case 4:
						$campoCorte ="id_delegacion";
						$where="";
						$inde1="";
						$inde2="sum(votos_part_9) as 'votos_part_9', ";
					break;
			}
	
		
			$qryParticipantesITEM = "
				WITH distribucion AS (
				SELECT ".$campoCorte.", 
					 sum(V.votos_part_1) as 'votos_part_1', sum(V.votos_part_2) as 'votos_part_2', sum(V.votos_part_3) as 'votos_part_3', 
					 sum(V.votos_part_4) as 'votos_part_4', sum(V.votos_part_5) as 'votos_part_5',
					 sum(V.votos_part_6) as 'votos_part_6', sum(V.votos_part_7) as votos_part_7, sum(V.votos_part_8) as votos_part_8, 
					 sum(V.votos_part_9) as 'votos_part_9', sum(votos_cand_no_reg) as 'votos_cand_no_reg', 
					 sum(votos_nulos) as 'votos_nulos', sum(votacion_total) as 'votacion_total',
					 CAST(sum(V.votos_part_10)/3 as INT) as parte10, 
					 CAST(sum(V.votos_part_11)/2 as INT) as parte11,
					 CAST(sum(V.votos_part_12)/2 as INT) as parte12, 
					 CAST(sum(V.votos_part_13)/2 as INT) as parte13,
					 CAST(sum(V.votos_part_14)/3 as INT) as parte14,
					 CAST(sum(V.votos_part_15)/2 as INT) as parte15,
					 CAST(sum(V.votos_part_16)/2 as INT) as parte16,
					 CAST(sum(V.votos_part_17)/2 as INT) as parte17,
					 sum(V.votos_part_10)%3 as residuo10, 
					 sum(V.votos_part_11)%2 as residuo11, 
					 sum(V.votos_part_12)%2 as residuo12, 
					 sum(V.votos_part_13)%2 as residuo13, 
					 sum(V.votos_part_14)%3 as residuo14,
					 sum(V.votos_part_15)%2 as residuo15,
					 sum(V.votos_part_16)%2 as residuo16,
					 sum(V.votos_part_17)%2 as residuo17
					FROM scd_votos as V 
					where id_tipo_eleccion=".$type." and ".$campoCorte." = ".$item.
					" and contabilizar='T' and substr(tipo_casilla,1,1) = 'M'  group by ".$campoCorte."
				),
				suma_igual AS (
					SELECT id_distrito, 
					 (votos_part_1 + parte10 + parte11 + parte12) as votos_part_1, 
					 (votos_part_2 + parte10 + parte11 + parte13) as votos_part_2,
					 (votos_part_3 + parte10 + parte12 + parte13) as votos_part_3,
					 (votos_part_4 + parte14 + parte15 + parte16) as votos_part_4,
					 (votos_part_5 + parte14 + parte15 + parte17) as votos_part_5,
					 votos_part_6, 
					 (votos_part_7 + parte14 + parte16 + parte17) as votos_part_7,
					 votos_part_8, votos_part_9, votos_cand_no_reg, votos_nulos, votacion_total,
					 parte10, parte11, parte12, parte13, parte14, parte15, parte16, parte17, residuo10, residuo11, residuo12, residuo13, residuo14,
					 residuo15, residuo16, residuo17
					 from distribucion
				),
				mayores AS (
					SELECT  *,
					CASE
						WHEN votos_part_1 >= votos_part_2 AND votos_part_1 >= votos_part_3 THEN 'votos_part_1'
						WHEN votos_part_2 >= votos_part_1 AND votos_part_2 >= votos_part_3 THEN 'votos_part_2'
						ELSE 'votos_part_3'
					END AS mayor10_1,
					CASE
							WHEN (votos_part_1 >= votos_part_2 AND votos_part_1 < votos_part_3) OR (votos_part_1 >= votos_part_3 AND votos_part_1 < votos_part_2) THEN 'votos_part_1'
							WHEN (votos_part_2 >= votos_part_1 AND votos_part_2 < votos_part_3) OR (votos_part_2 >= votos_part_3 AND votos_part_2 < votos_part_1) THEN 'votos_part_2'
						ELSE 'votos_part_3'
					END AS mayor10_2,
				
					CASE WHEN votos_part_1 >= votos_part_2 THEN 'votos_part_1'
					ELSE 'votos_part_2'
					END AS mayor11,

					CASE WHEN votos_part_1 >= votos_part_3 THEN 'votos_part_1'
					ELSE 'votos_part_3'
					END AS mayor12,

					CASE WHEN votos_part_2 >= votos_part_3 THEN 'votos_part_2'
					ELSE 'votos_part_3'
					END AS mayor13,
					
					CASE
						WHEN votos_part_4 >= votos_part_5 AND votos_part_4 >= votos_part_7 THEN 'votos_part_4'
						WHEN votos_part_5 >= votos_part_4 AND votos_part_5 >= votos_part_7 THEN 'votos_part_5'
						ELSE 'votos_part_7'
					END AS mayor14_1,
					CASE
							WHEN (votos_part_4 >= votos_part_5 AND votos_part_4 < votos_part_7) OR (votos_part_4 >= votos_part_7 AND votos_part_4 < votos_part_5) THEN 'votos_part_4'
							WHEN (votos_part_5 >= votos_part_4 AND votos_part_5 < votos_part_7) OR (votos_part_5 >= votos_part_7 AND votos_part_5 < votos_part_4) THEN 'votos_part_5'
						ELSE 'votos_part_7'
					END AS mayor14_2,

					CASE WHEN votos_part_4 >= votos_part_5 THEN 'votos_part_4'
					ELSE 'votos_part_5'
					END AS mayor15,

					CASE WHEN votos_part_4 >= votos_part_7 THEN 'votos_part_4'
					ELSE 'votos_part_7'
					END AS mayor16,

					CASE WHEN votos_part_5 >= votos_part_7 THEN 'votos_part_5'
					ELSE 'votos_part_7'
					END AS mayor17
					FROM
					suma_igual
				),
				distribucion_residuo AS (
					    SELECT
        *,
						votos_part_1 + 
							CASE WHEN residuo10=2 AND (mayor10_1 = 'votos_part_1' OR mayor10_2 = 'votos_part_1') THEN  1 ELSE 0 END +
							CASE WHEN residuo10=1 AND (mayor10_1 = 'votos_part_1' OR mayor10_2 = 'votos_part_1') THEN  1 ELSE 0 END +
							CASE WHEN mayor11 = 'votos_part_1' THEN residuo11 ELSE 0 END +
							CASE WHEN mayor12 = 'votos_part_1' THEN residuo12 ELSE 0 END AS votos_part_1_S,
						votos_part_2 + CASE WHEN residuo10=2 AND (mayor10_1 = 'votos_part_2' OR mayor10_2 = 'votos_part_2') THEN  1 ELSE 0 END +
							CASE WHEN mayor11 = 'votos_part_2' THEN residuo11 ELSE 0 END +
							CASE WHEN mayor13 = 'votos_part_2' THEN residuo13 ELSE 0 END AS votos_part_2_S,
						votos_part_3 + CASE WHEN residuo10=2 AND (mayor10_1 = 'votos_part_3' OR mayor10_2 = 'votos_part_3') THEN  1 ELSE 0 END +
							CASE WHEN mayor12 = 'votos_part_3' THEN  residuo12 ELSE 0 END +
							CASE WHEN mayor13 = 'votos_part_3' THEN residuo13 ELSE 0 END AS votos_part_3_S,
						votos_part_4 + 
							CASE WHEN residuo14 = 2 AND (mayor14_1 = 'votos_part_4' OR mayor14_2 = 'votos_part_4') THEN  1 ELSE 0 END +
							CASE WHEN residuo14 = 1 AND (mayor14_1 = 'votos_part_4' OR mayor14_2 = 'votos_part_4') THEN  1 ELSE 0 END +
							CASE WHEN mayor15 = 'votos_part_4' THEN residuo15 ELSE 0 END +
							CASE WHEN mayor16 = 'votos_part_4' THEN residuo16 ELSE 0 END AS votos_part_4_S,
						votos_part_5 + CASE WHEN residuo14=2 AND (mayor14_1 = 'votos_part_5' OR mayor14_2 = 'votos_part_5') THEN  1 ELSE 0 END +
							CASE WHEN mayor15 = 'votos_part_5' THEN residuo15 ELSE 0 END +
							CASE WHEN mayor17 = 'votos_part_5' THEN residuo17 ELSE 0 END AS votos_part_5_S,
						votos_part_7 + CASE WHEN residuo14=2 AND (mayor14_1 = 'votos_part_7' OR mayor14_2 = 'votos_part_7') THEN  1 ELSE 0 END +
							CASE WHEN mayor16 = 'votos_part_7' THEN  residuo16 ELSE 0 END +
							CASE WHEN mayor17 = 'votos_part_7' THEN residuo17 ELSE 0 END AS votos_part_7_S
					FROM
						mayores
				)
				select votos_part_1_S as votos_part_1, votos_part_2_S as votos_part_2, votos_part_3_S as votos_part_3, 
				votos_part_4_S as votos_part_4, votos_part_5_S as votos_part_5, votos_part_6, votos_part_7_S as votos_part_7, votos_part_8, votos_part_9,
				votos_cand_no_reg, votos_nulos, votacion_total from distribucion_residuo " ;
	
		}
		else{
			
			$qryParticipantesITEM = "WITH distribucion AS (
					SELECT id_distrito, 
					 sum(V.votos_part_1) as 'votos_part_1', sum(V.votos_part_2) as 'votos_part_2', sum(V.votos_part_3) as 'votos_part_3', 
					 sum(V.votos_part_4) as 'votos_part_4', sum(V.votos_part_5) as 'votos_part_5',
					 sum(V.votos_part_6) as 'votos_part_6', sum(V.votos_part_7) as votos_part_7, sum(V.votos_part_8) as votos_part_8, 
					 sum(V.votos_part_9) as 'votos_part_9', sum(votos_cand_no_reg) as 'votos_cand_no_reg', 
					 sum(votos_nulos) as 'votos_nulos', sum(votacion_total) as 'votacion_total',
					 CAST(sum(V.votos_part_10)/3 as INT) as parte10, 
					 CAST(sum(V.votos_part_11)/2 as INT) as parte11,
					 CAST(sum(V.votos_part_12)/2 as INT) as parte12, 
					 CAST(sum(V.votos_part_13)/2 as INT) as parte13,
					 CAST(sum(V.votos_part_14)/3 as INT) as parte14,
					 CAST(sum(V.votos_part_15)/2 as INT) as parte15,
					 CAST(sum(V.votos_part_16)/2 as INT) as parte16,
					 CAST(sum(V.votos_part_17)/2 as INT) as parte17,
					 sum(V.votos_part_10)%3 as residuo10, 
					 sum(V.votos_part_11)%2 as residuo11, 
					 sum(V.votos_part_12)%2 as residuo12, 
					 sum(V.votos_part_13)%2 as residuo13, 
					 sum(V.votos_part_14)%3 as residuo14,
					 sum(V.votos_part_15)%2 as residuo15,
					 sum(V.votos_part_16)%2 as residuo16,
					 sum(V.votos_part_17)%2 as residuo17
					FROM scd_votos as V 
					where id_tipo_eleccion=".$type." and contabilizar='T' and substr(tipo_casilla,1,1) = 'M' group by id_distrito
				),
				suma_igual AS (
					SELECT id_distrito, 
					 (votos_part_1 + parte10 + parte11 + parte12) as votos_part_1, 
					 (votos_part_2 + parte10 + parte11 + parte13) as votos_part_2,
					 (votos_part_3 + parte10 + parte12 + parte13) as votos_part_3,
					 (votos_part_4 + parte14 + parte15 + parte16) as votos_part_4,
					 (votos_part_5 + parte14 + parte15 + parte17) as votos_part_5,
					 votos_part_6, 
					 (votos_part_7 + parte14 + parte16 + parte17) as votos_part_7,
					 votos_part_8, votos_part_9, votos_cand_no_reg, votos_nulos, votacion_total,
					 parte10, parte11, parte12, parte13, parte14, parte15, parte16, parte17, residuo10, residuo11, residuo12, residuo13, residuo14,
					 residuo15, residuo16, residuo17
					 from distribucion
				),
				mayores AS (
					SELECT  *,
	
						CASE
							WHEN votos_part_1 >= votos_part_2 AND votos_part_1 >= votos_part_3 THEN 'votos_part_1'
							WHEN votos_part_2 >= votos_part_1 AND votos_part_2 >= votos_part_3 THEN 'votos_part_2'
							ELSE 'votos_part_3'
						END AS mayor10_1,
						CASE
								WHEN (votos_part_1 >= votos_part_2 AND votos_part_1 < votos_part_3) OR (votos_part_1 >= votos_part_3 AND votos_part_1 < votos_part_2) THEN 'votos_part_1'
								WHEN (votos_part_2 >= votos_part_1 AND votos_part_2 < votos_part_3) OR (votos_part_2 >= votos_part_3 AND votos_part_2 < votos_part_1) THEN 'votos_part_2'
							ELSE 'votos_part_3'
						END AS mayor10_2,
				
						CASE WHEN votos_part_1 >= votos_part_2 THEN 'votos_part_1'
						ELSE 'votos_part_2'
						END AS mayor11,
			
						CASE WHEN votos_part_1 >= votos_part_3 THEN 'votos_part_1'
						ELSE 'votos_part_3'
						END AS mayor12,
			
						CASE WHEN votos_part_2 >= votos_part_3 THEN 'votos_part_2'
						ELSE 'votos_part_3'
						END AS mayor13,
						
						CASE
							WHEN votos_part_4 >= votos_part_5 AND votos_part_4 >= votos_part_7 THEN 'votos_part_4'
							WHEN votos_part_5 >= votos_part_4 AND votos_part_5 >= votos_part_7 THEN 'votos_part_5'
							ELSE 'votos_part_7'
						END AS mayor14_1,
						CASE
								WHEN (votos_part_4 >= votos_part_5 AND votos_part_4 < votos_part_7) OR (votos_part_4 >= votos_part_7 AND votos_part_4 < votos_part_5) THEN 'votos_part_4'
								WHEN (votos_part_5 >= votos_part_4 AND votos_part_5 < votos_part_7) OR (votos_part_5 >= votos_part_7 AND votos_part_5 < votos_part_4) THEN 'votos_part_5'
							ELSE 'votos_part_7'
						END AS mayor14_2,

						CASE WHEN votos_part_4 >= votos_part_5 THEN 'votos_part_4'
						ELSE 'votos_part_5'
						END AS mayor15,
	
						CASE WHEN votos_part_4 >= votos_part_7 THEN 'votos_part_4'
						ELSE 'votos_part_7'
						END AS mayor16,
		
						CASE WHEN votos_part_5 >= votos_part_7 THEN 'votos_part_5'
						ELSE 'votos_part_7'
						END AS mayor17
						FROM
						suma_igual
				),
				distribucion_residuo AS (
					SELECT
						*,
						votos_part_1 + 
							CASE WHEN residuo10=2 AND (mayor10_1 = 'votos_part_1' OR mayor10_2 = 'votos_part_1') THEN  1 ELSE 0 END +
							CASE WHEN residuo10=1 AND (mayor10_1 = 'votos_part_1' OR mayor10_2 = 'votos_part_1') THEN  1 ELSE 0 END +
							CASE WHEN mayor11 = 'votos_part_1' THEN residuo11 ELSE 0 END +
							CASE WHEN mayor12 = 'votos_part_1' THEN residuo12 ELSE 0 END AS votos_part_1_S,
						votos_part_2 + CASE WHEN residuo10=2 AND (mayor10_1 = 'votos_part_2' OR mayor10_2 = 'votos_part_2') THEN  1 ELSE 0 END +
							CASE WHEN mayor11 = 'votos_part_2' THEN residuo11 ELSE 0 END +
							CASE WHEN mayor13 = 'votos_part_2' THEN residuo13 ELSE 0 END AS votos_part_2_S,
						votos_part_3 + CASE WHEN residuo10=2 AND (mayor10_1 = 'votos_part_3' OR mayor10_2 = 'votos_part_3') THEN  1 ELSE 0 END +
							CASE WHEN mayor12 = 'votos_part_3' THEN  residuo12 ELSE 0 END +
							CASE WHEN mayor13 = 'votos_part_3' THEN residuo13 ELSE 0 END AS votos_part_3_S,
						votos_part_4 + 
							CASE WHEN residuo14 = 2 AND (mayor14_1 = 'votos_part_4' OR mayor14_2 = 'votos_part_4') THEN  1 ELSE 0 END +
							CASE WHEN residuo14 = 1 AND (mayor14_1 = 'votos_part_4' OR mayor14_2 = 'votos_part_4') THEN  1 ELSE 0 END +
							CASE WHEN mayor15 = 'votos_part_4' THEN residuo15 ELSE 0 END +
							CASE WHEN mayor16 = 'votos_part_4' THEN residuo16 ELSE 0 END AS votos_part_4_S,
						votos_part_5 + CASE WHEN residuo14=2 AND (mayor14_1 = 'votos_part_5' OR mayor14_2 = 'votos_part_5') THEN  1 ELSE 0 END +
							CASE WHEN mayor15 = 'votos_part_5' THEN residuo15 ELSE 0 END +
							CASE WHEN mayor17 = 'votos_part_5' THEN residuo17 ELSE 0 END AS votos_part_5_S,
						votos_part_7 + CASE WHEN residuo14=2 AND (mayor14_1 = 'votos_part_7' OR mayor14_2 = 'votos_part_7') THEN  1 ELSE 0 END +
							CASE WHEN mayor16 = 'votos_part_7' THEN  residuo16 ELSE 0 END +
							CASE WHEN mayor17 = 'votos_part_7' THEN residuo17 ELSE 0 END AS votos_part_7_S
					FROM
						mayores
				)
				select sum(votos_part_1_S) as sumvotos_part_1, sum(votos_part_2_S) as votos_part_2, sum(votos_part_3_S) as votos_part_3, 
				sum(votos_part_4_S) as votos_part_4, sum(votos_part_5_S) as votos_part_5, sum(votos_part_6) as votos_part_6, 
				sum(votos_part_7_S) as votos_part_7, sum(votos_part_8) as votos_part_8, sum(votos_part_9) as votos_part_9,
				 sum(votos_cand_no_reg) as votos_cand_no_reg, sum(votos_nulos) as votos_nulos, sum(votacion_total) as votacion_total
				 from distribucion_residuo  " ;

	}
	
	

	return $qryParticipantesITEM;

};
// Consigue los datos de partidos con distribución de votos


function getRecordsetExtranjero_JG($tmpSQL =""){	
		// apertura de BD
		$db = new SQLite3('db/database.db3');
		$res_catch = $db->query($tmpSQL);
		if(!$res_catch) return null;	
		$value_recordset = array();
		
		$db->enableExceptions(false);
		$registro = array();

		// Recorro los registros
		$i=1;
		$total_votos=0;
		while ($row = $res_catch->fetchArray(SQLITE3_ASSOC))
		{
			$rowTMP = [];
			$total_votos = $row["votacion_total"];
			
			foreach ($row as $clave => $valor) {
				$claveTMP = $clave;
				
				
				
				$id = $i."";
				$partidoTMP = $id."jpg";
				if($clave=="votos_cand_no_reg"){
					$id ="no_reg";
					$partidoTMP = "no_reg.jpg";
					
				}
				if($clave=="votos_nulos"){
					$id ="nulos";
					$partidoTMP = "nulos.jpg";
				}
				if($clave=="votacion_total"){
					$id ="";
					$partidoTMP = "votacion_total";
				}
				$porcenToken=0;
				if($total_votos<=0){
					$porcenToken=0;
				}
				else{
					$porcenToken = ($valor*100) / $total_votos;
				}
				
				$porTmp = sprintf("%01.4f", $porcenToken)."%";
				
				$rowTMP["id"]=$id;
				$rowTMP["campo"]=$claveTMP;
				$rowTMP["valor"]=$valor!=null?$valor:0;
				$rowTMP["partido"]=$id;
				$rowTMP["porcentaje"]=$porTmp;
				$i++;
				array_push($value_recordset, $rowTMP);
			}
			
		}
			
			//array_push($value_recordset, $row);
			//$reg_data++;
			// if($type==1) break;
			
		
		$db->close();
		unset($db);	
		return $value_recordset;
}


// Consigue los datos de partidos con distribución de votos
function consigueDatosALC($type, $item){
	$campoCorte = "";
	$qryParticipantesITEM = "";
	$inde1="";
	$inde2="";
	if(trim($item)!=""){
			switch ($type) {
				case 1:

					$campoCorte ="id_distrito";
					$where="";
					$inde1="";
					$inde2="";
					break;
					
				case 2:

					$campoCorte ="id_distrito";
					$inde1="sum(votos_part_8) as 'votos_part_8', ";
					$inde2="";
					$where="";
					break;
				case 3:  //RP
					$qryParticipantesITEM = '';
					$campoCorte ="";
					$inde1="";
					$inde2="";
					break;
				case 4:
						$campoCorte ="id_delegacion";
						$where="";
						$inde1="";
						$inde2="sum(votos_part_9) as 'votos_part_9', ";
					break;
			}
	
		
			$qryParticipantesITEM = "
				WITH distribucion AS (
				SELECT ".$campoCorte.", 
					 sum(V.votos_part_1) as 'votos_part_1', 
					 sum(V.votos_part_2) as 'votos_part_2', 
					 sum(V.votos_part_3) as 'votos_part_3', 
					 CASE WHEN id_delegacion in (11) THEN
						 sum(votos_part_4) 
					 ELSE
						CAST(sum(votos_part_14) * 0.2667 as INT)
					 END as 'votos_part_4', 
					 CASE WHEN id_delegacion in (11) THEN
						sum(votos_part_5) 
					 ELSE
						CAST(sum(votos_part_14) * 0.2 as INT)
					 END  as 'votos_part_5',
					 sum(V.votos_part_6) as 'votos_part_6', 
					 CASE WHEN id_delegacion in (11) THEN
						sum(votos_part_7) 
					 ELSE
						CAST(sum(votos_part_14) * 0.5333 as INT) +
						(sum(votos_part_14) - (CAST(sum(votos_part_14) * 0.2667 as INT)+CAST(sum(votos_part_14) * 0.2 as INT)+ CAST(sum(votos_part_14) * 0.5333 as INT)))
					 END  as 'votos_part_7',  
					 sum(V.votos_part_9) as 'votos_part_9', 
					 sum(votos_cand_no_reg) as 'votos_cand_no_reg', 
					 sum(votos_nulos) as 'votos_nulos', sum(votacion_total) as 'votacion_total',
					 CAST(sum(V.votos_part_10)/3 as INT) as parte10, 
					 CAST(sum(V.votos_part_11)/2 as INT) as parte11,
					 CAST(sum(V.votos_part_12)/2 as INT) as parte12, 
					 CAST(sum(V.votos_part_13)/2 as INT) as parte13,
					 CAST(sum(V.votos_part_14)/3 as INT) as parte14,
					 CAST(sum(V.votos_part_15)/2 as INT) as parte15,
					 CAST(sum(V.votos_part_16)/2 as INT) as parte16,
					 CAST(sum(V.votos_part_17)/2 as INT) as parte17,
					 sum(V.votos_part_10)%3 as residuo10, 
					 sum(V.votos_part_11)%2 as residuo11, 
					 sum(V.votos_part_12)%2 as residuo12, 
					 sum(V.votos_part_13)%2 as residuo13, 
					 0 as residuo14,
					 0 as residuo15,
					 0 as residuo16,
					 0 as residuo17
					FROM scd_votos as V 
					where id_tipo_eleccion=".$type." and ".$campoCorte." = ".$item." and contabilizar='T' group by ".$campoCorte."
				),
				suma_igual AS (
					SELECT id_delegacion, 
					 (votos_part_1 + parte10 + parte11 + parte12) as votos_part_1, 
					 (votos_part_2 + parte10 + parte11 + parte13) as votos_part_2,
					 (votos_part_3 + parte10 + parte12 + parte13) as votos_part_3,
					 votos_part_4,
					 votos_part_5,
					 votos_part_6, 
					 votos_part_7,
					 votos_part_9, votos_cand_no_reg, votos_nulos, votacion_total,
					 parte10, parte11, parte12, parte13, parte14, parte15, parte16, parte17, residuo10, residuo11, residuo12, residuo13, residuo14,
					 residuo15, residuo16, residuo17
					 from distribucion
				),
				mayores AS (
					SELECT  *,
				
						CASE
							WHEN votos_part_1 >= votos_part_2 AND votos_part_1 >= votos_part_3 THEN 'votos_part_1'
							WHEN votos_part_2 >= votos_part_1 AND votos_part_2 >= votos_part_3 THEN 'votos_part_2'
							ELSE 'votos_part_3'
						END AS mayor10_1,
						CASE
								WHEN (votos_part_1 >= votos_part_2 AND votos_part_1 < votos_part_3) OR (votos_part_1 >= votos_part_3 AND votos_part_1 < votos_part_2) THEN 'votos_part_1'
								WHEN (votos_part_2 >= votos_part_1 AND votos_part_2 < votos_part_3) OR (votos_part_2 >= votos_part_3 AND votos_part_2 < votos_part_1) THEN 'votos_part_2'
							ELSE 'votos_part_3'
						END AS mayor10_2,
				
						CASE WHEN votos_part_1 >= votos_part_2 THEN 'votos_part_1'
						ELSE 'votos_part_2'
						END AS mayor11,
					
						CASE WHEN votos_part_1 >= votos_part_3 THEN 'votos_part_1'
						ELSE 'votos_part_3'
						END AS mayor12,
			
						CASE WHEN votos_part_2 >= votos_part_3 THEN 'votos_part_2'
						ELSE 'votos_part_3'
						END AS mayor13
						FROM
						suma_igual
				),
				distribucion_residuo AS (
						SELECT
							*,
							votos_part_1 + 
								CASE WHEN residuo10=2 AND (mayor10_1 = 'votos_part_1' OR mayor10_2 = 'votos_part_1') THEN  1 ELSE 0 END +
								CASE WHEN residuo10=1 AND (mayor10_1 = 'votos_part_1' OR mayor10_2 = 'votos_part_1') THEN  1 ELSE 0 END +
								CASE WHEN mayor11 = 'votos_part_1' THEN residuo11 ELSE 0 END +
								CASE WHEN mayor12 = 'votos_part_1' THEN residuo12 ELSE 0 END AS votos_part_1_S,
							votos_part_2 + CASE WHEN residuo10=2 AND (mayor10_1 = 'votos_part_2' OR mayor10_2 = 'votos_part_2') THEN  1 ELSE 0 END +
								CASE WHEN mayor11 = 'votos_part_2' THEN residuo11 ELSE 0 END +
								CASE WHEN mayor13 = 'votos_part_2' THEN residuo13 ELSE 0 END AS votos_part_2_S,
							votos_part_3 + CASE WHEN residuo10=2 AND (mayor10_1 = 'votos_part_3' OR mayor10_2 = 'votos_part_3') THEN  1 ELSE 0 END +
								CASE WHEN mayor12 = 'votos_part_3' THEN  residuo12 ELSE 0 END +
								CASE WHEN mayor13 = 'votos_part_3' THEN residuo13 ELSE 0 END AS votos_part_3_S
						FROM
							mayores
					)
				select votos_part_1_S as votos_part_1, votos_part_2_S as votos_part_2, votos_part_3_S as votos_part_3, 
				votos_part_4, votos_part_5, votos_part_6, votos_part_7,  votos_part_9,
				votos_cand_no_reg, votos_nulos, votacion_total from distribucion_residuo " ;
	
		}
		else{
			
			$qryParticipantesITEM = "WITH distribucion AS (
					SELECT id_delegacion, 
					 sum(V.votos_part_1) as 'votos_part_1', 
					 sum(V.votos_part_2) as 'votos_part_2', 
					 sum(V.votos_part_3) as 'votos_part_3', 
					 CASE WHEN id_delegacion in (6, 11) THEN
						 sum(votos_part_4) 
					 ELSE
						CAST(sum(votos_part_14) * 0.2667 as INT)
					 END as 'votos_part_4', 
					 CASE WHEN id_delegacion in (6, 11) THEN
						sum(votos_part_5) 
					 ELSE
						CAST(sum(votos_part_14) * 0.2 as INT)
					 END  as 'votos_part_5',
					 sum(V.votos_part_6) as 'votos_part_6', 
					 CASE WHEN id_delegacion in (6, 11) THEN
						sum(votos_part_7) 
					 ELSE
						CAST(sum(votos_part_14) * 0.5333 as INT) +
						(sum(votos_part_14) - (CAST(sum(votos_part_14) * 0.2667 as INT)+CAST(sum(votos_part_14) * 0.2 as INT)+ CAST(sum(votos_part_14) * 0.5333 as INT)))
					 END  as 'votos_part_7',  
					 sum(V.votos_part_9) as 'votos_part_9', 
					 sum(votos_cand_no_reg) as 'votos_cand_no_reg', 
					 sum(votos_nulos) as 'votos_nulos', sum(votacion_total) as 'votacion_total',
					 CAST(sum(V.votos_part_10)/3 as INT) as parte10, 
					 CAST(sum(V.votos_part_11)/2 as INT) as parte11,
					 CAST(sum(V.votos_part_12)/2 as INT) as parte12, 
					 CAST(sum(V.votos_part_13)/2 as INT) as parte13,
					 CAST(sum(V.votos_part_14)/3 as INT) as parte14,
					 CAST(sum(V.votos_part_15)/2 as INT) as parte15,
					 CAST(sum(V.votos_part_16)/2 as INT) as parte16,
					 CAST(sum(V.votos_part_17)/2 as INT) as parte17,
					 sum(V.votos_part_10)%3 as residuo10, 
					 sum(V.votos_part_11)%2 as residuo11, 
					 sum(V.votos_part_12)%2 as residuo12, 
					 sum(V.votos_part_13)%2 as residuo13, 
					 0 as residuo14,
					 0 as residuo15,
					 0 as residuo16,
					 0 as residuo17
					FROM scd_votos as V 
					where id_tipo_eleccion= ".$type." and contabilizar='T' group by id_delegacion
				),
				suma_igual AS (
					SELECT id_delegacion, 
					 (votos_part_1 + parte10 + parte11 + parte12) as votos_part_1, 
					 (votos_part_2 + parte10 + parte11 + parte13) as votos_part_2,
					 (votos_part_3 + parte10 + parte12 + parte13) as votos_part_3,
					 votos_part_4,
					 votos_part_5,
					 votos_part_6, 
					 votos_part_7,
					 votos_part_9, votos_cand_no_reg, votos_nulos, votacion_total,
					 parte10, parte11, parte12, parte13, parte14, parte15, parte16, parte17, residuo10, residuo11, residuo12, residuo13, residuo14,
					 residuo15, residuo16, residuo17
					 from distribucion
				),
				mayores AS (
					SELECT  *,

						CASE
							WHEN votos_part_1 >= votos_part_2 AND votos_part_1 >= votos_part_3 THEN 'votos_part_1'
							WHEN votos_part_2 >= votos_part_1 AND votos_part_2 >= votos_part_3 THEN 'votos_part_2'
							ELSE 'votos_part_3'
						END AS mayor10_1,
						CASE
								WHEN (votos_part_1 >= votos_part_2 AND votos_part_1 < votos_part_3) OR (votos_part_1 >= votos_part_3 AND votos_part_1 < votos_part_2) THEN 'votos_part_1'
								WHEN (votos_part_2 >= votos_part_1 AND votos_part_2 < votos_part_3) OR (votos_part_2 >= votos_part_3 AND votos_part_2 < votos_part_1) THEN 'votos_part_2'
							ELSE 'votos_part_3'
						END AS mayor10_2,
			
						CASE WHEN votos_part_1 >= votos_part_2 THEN 'votos_part_1'
						ELSE 'votos_part_2'
						END AS mayor11,
		
						CASE WHEN votos_part_1 >= votos_part_3 THEN 'votos_part_1'
						ELSE 'votos_part_3'
						END AS mayor12,
			
						CASE WHEN votos_part_2 >= votos_part_3 THEN 'votos_part_2'
						ELSE 'votos_part_3'
						END AS mayor13
						FROM
						suma_igual
				),
				distribucion_residuo AS (
					SELECT
						*,
						votos_part_1 + 
							CASE WHEN residuo10=2 AND (mayor10_1 = 'votos_part_1' OR mayor10_2 = 'votos_part_1') THEN  1 ELSE 0 END +
							CASE WHEN residuo10=1 AND (mayor10_1 = 'votos_part_1' OR mayor10_2 = 'votos_part_1') THEN  1 ELSE 0 END +
							CASE WHEN mayor11 = 'votos_part_1' THEN residuo11 ELSE 0 END +
							CASE WHEN mayor12 = 'votos_part_1' THEN residuo12 ELSE 0 END AS votos_part_1_S,
						votos_part_2 + CASE WHEN residuo10=2 AND (mayor10_1 = 'votos_part_2' OR mayor10_2 = 'votos_part_2') THEN  1 ELSE 0 END +
							CASE WHEN mayor11 = 'votos_part_2' THEN residuo11 ELSE 0 END +
							CASE WHEN mayor13 = 'votos_part_2' THEN residuo13 ELSE 0 END AS votos_part_2_S,
						votos_part_3 + CASE WHEN residuo10=2 AND (mayor10_1 = 'votos_part_3' OR mayor10_2 = 'votos_part_3') THEN  1 ELSE 0 END +
							CASE WHEN mayor12 = 'votos_part_3' THEN  residuo12 ELSE 0 END +
							CASE WHEN mayor13 = 'votos_part_3' THEN residuo13 ELSE 0 END AS votos_part_3_S
					FROM
						mayores
				)
				select sum(votos_part_1_S) as votos_part_1, sum(votos_part_2_S) as votos_part_2, 
				sum(votos_part_3_S) as votos_part_3, sum(votos_part_4) as votos_part_4, sum(votos_part_5) as votos_part_5, sum(votos_part_6) as votos_part_6,  
				sum(votos_part_7) as votos_part_7,
				sum(votos_part_9) as votos_part_9, sum(votos_cand_no_reg) as votos_cand_no_reg, sum(votos_nulos) as votos_nulos, 
				sum(votacion_total) as votacion_total from distribucion_residuo " ;

	}
	
	

	return $qryParticipantesITEM;	

};

// Consigue la sentencia los datos de partidos con distribución de votos
function consigueDatosALC_EXTRANJERO($type, $item){
	$campoCorte = "";
	$qryParticipantesITEM = "";
	$inde1="";
	$inde2="";
	if(trim($item)!=""){
			switch ($type) {
				case 1:

					$campoCorte ="id_distrito";
					$where="";
					$inde1="";
					$inde2="";
					break;
					
				case 2:

					$campoCorte ="id_distrito";
					$inde1="sum(votos_part_8) as 'votos_part_8', ";
					$inde2="";
					$where="";
					break;
				case 3:  //RP
					$qryParticipantesITEM = '';
					$campoCorte ="";
					$inde1="";
					$inde2="";
					break;
				case 4:
						$campoCorte ="id_delegacion";
						$where="";
						$inde1="";
						$inde2="sum(votos_part_9) as 'votos_part_9', ";
					break;
			}
	
		
			$qryParticipantesITEM = "
				WITH distribucion AS (
				SELECT ".$campoCorte.", 
					 sum(V.votos_part_1) as 'votos_part_1', 
					 sum(V.votos_part_2) as 'votos_part_2', 
					 sum(V.votos_part_3) as 'votos_part_3', 
					 CASE WHEN id_delegacion in (6, 11) THEN
						 sum(votos_part_4) 
					 ELSE
						CAST(sum(votos_part_14) * 0.2667 as INT)
					 END as 'votos_part_4', 
					 CASE WHEN id_delegacion in (11) THEN
						sum(votos_part_5) 
					 ELSE
						CAST(sum(votos_part_14) * 0.2 as INT)
					 END  as 'votos_part_5',
					 sum(V.votos_part_6) as 'votos_part_6', 
					 CASE WHEN id_delegacion in (11) THEN
						sum(votos_part_7) 
					 ELSE
						CAST(sum(votos_part_14) * 0.5333 as INT) +
						(sum(votos_part_14) - (CAST(sum(votos_part_14) * 0.2667 as INT)+CAST(sum(votos_part_14) * 0.2 as INT)+ CAST(sum(votos_part_14) * 0.5333 as INT)))
					 END  as 'votos_part_7',  
					 sum(V.votos_part_9) as 'votos_part_9', 
					 sum(votos_cand_no_reg) as 'votos_cand_no_reg', 
					 sum(votos_nulos) as 'votos_nulos', sum(votacion_total) as 'votacion_total',
					 CAST(sum(V.votos_part_10)/3 as INT) as parte10, 
					 CAST(sum(V.votos_part_11)/2 as INT) as parte11,
					 CAST(sum(V.votos_part_12)/2 as INT) as parte12, 
					 CAST(sum(V.votos_part_13)/2 as INT) as parte13,
					 CAST(sum(V.votos_part_14)/3 as INT) as parte14,
					 CAST(sum(V.votos_part_15)/2 as INT) as parte15,
					 CAST(sum(V.votos_part_16)/2 as INT) as parte16,
					 CAST(sum(V.votos_part_17)/2 as INT) as parte17,
					 sum(V.votos_part_10)%3 as residuo10, 
					 sum(V.votos_part_11)%2 as residuo11, 
					 sum(V.votos_part_12)%2 as residuo12, 
					 sum(V.votos_part_13)%2 as residuo13, 
					 0 as residuo14,
					 0 as residuo15,
					 0 as residuo16,
					 0 as residuo17
					FROM scd_votos as V 
					where id_tipo_eleccion=".$type." and ".$campoCorte." = ".$item." and substr(tipo_casilla,1,1) = 'M' and contabilizar='T' group by ".$campoCorte."
				),
				suma_igual AS (
					SELECT id_delegacion, 
					 (votos_part_1 + parte10 + parte11 + parte12) as votos_part_1, 
					 (votos_part_2 + parte10 + parte11 + parte13) as votos_part_2,
					 (votos_part_3 + parte10 + parte12 + parte13) as votos_part_3,
					 votos_part_4,
					 votos_part_5,
					 votos_part_6, 
					 votos_part_7,
					 votos_part_9,votos_cand_no_reg, votos_nulos, votacion_total,
					 parte10, parte11, parte12, parte13, parte14, parte15, parte16, parte17, residuo10, residuo11, residuo12, residuo13, residuo14,
					 residuo15, residuo16, residuo17
					 from distribucion
				),
				mayores AS (
					SELECT  *,
				
						CASE
							WHEN votos_part_1 >= votos_part_2 AND votos_part_1 >= votos_part_3 THEN 'votos_part_1'
							WHEN votos_part_2 >= votos_part_1 AND votos_part_2 >= votos_part_3 THEN 'votos_part_2'
							ELSE 'votos_part_3'
						END AS mayor10_1,
						CASE
								WHEN (votos_part_1 >= votos_part_2 AND votos_part_1 < votos_part_3) OR (votos_part_1 >= votos_part_3 AND votos_part_1 < votos_part_2) THEN 'votos_part_1'
								WHEN (votos_part_2 >= votos_part_1 AND votos_part_2 < votos_part_3) OR (votos_part_2 >= votos_part_3 AND votos_part_2 < votos_part_1) THEN 'votos_part_2'
							ELSE 'votos_part_3'
						END AS mayor10_2,
				
						CASE WHEN votos_part_1 >= votos_part_2 THEN 'votos_part_1'
						ELSE 'votos_part_2'
						END AS mayor11,
					
						CASE WHEN votos_part_1 >= votos_part_3 THEN 'votos_part_1'
						ELSE 'votos_part_3'
						END AS mayor12,
			
						CASE WHEN votos_part_2 >= votos_part_3 THEN 'votos_part_2'
						ELSE 'votos_part_3'
						END AS mayor13
						FROM
						suma_igual
				),
				distribucion_residuo AS (
						SELECT
							*,
							votos_part_1 + 
								CASE WHEN residuo10=2 AND (mayor10_1 = 'votos_part_1' OR mayor10_2 = 'votos_part_1') THEN  1 ELSE 0 END +
								CASE WHEN residuo10=1 AND (mayor10_1 = 'votos_part_1' OR mayor10_2 = 'votos_part_1') THEN  1 ELSE 0 END +
								CASE WHEN mayor11 = 'votos_part_1' THEN residuo11 ELSE 0 END +
								CASE WHEN mayor12 = 'votos_part_1' THEN residuo12 ELSE 0 END AS votos_part_1_S,
							votos_part_2 + CASE WHEN residuo10=2 AND (mayor10_1 = 'votos_part_2' OR mayor10_2 = 'votos_part_2') THEN  1 ELSE 0 END +
								CASE WHEN mayor11 = 'votos_part_2' THEN residuo11 ELSE 0 END +
								CASE WHEN mayor13 = 'votos_part_2' THEN residuo13 ELSE 0 END AS votos_part_2_S,
							votos_part_3 + CASE WHEN residuo10=2 AND (mayor10_1 = 'votos_part_3' OR mayor10_2 = 'votos_part_3') THEN  1 ELSE 0 END +
								CASE WHEN mayor12 = 'votos_part_3' THEN  residuo12 ELSE 0 END +
								CASE WHEN mayor13 = 'votos_part_3' THEN residuo13 ELSE 0 END AS votos_part_3_S
						FROM
							mayores
					)
				select votos_part_1_S as votos_part_1, votos_part_2_S as votos_part_2, votos_part_3_S as votos_part_3, 
				votos_part_4, votos_part_5, votos_part_6, votos_part_7,  votos_part_9,
				votos_cand_no_reg, votos_nulos, votacion_total from distribucion_residuo " ;
	
		}
		else{
			
			$qryParticipantesITEM = "WITH distribucion AS (
					SELECT id_delegacion, 
					 sum(V.votos_part_1) as 'votos_part_1', 
					 sum(V.votos_part_2) as 'votos_part_2', 
					 sum(V.votos_part_3) as 'votos_part_3', 
					 CASE WHEN id_delegacion in (11) THEN
						 sum(votos_part_4) 
					 ELSE
						CAST(sum(votos_part_14) * 0.2667 as INT)
					 END as 'votos_part_4', 
					 CASE WHEN id_delegacion in (11) THEN
						sum(votos_part_5) 
					 ELSE
						CAST(sum(votos_part_14) * 0.2 as INT)
					 END  as 'votos_part_5',
					 sum(V.votos_part_6) as 'votos_part_6', 
					 CASE WHEN id_delegacion in (11) THEN
						sum(votos_part_7) 
					 ELSE
						CAST(sum(votos_part_14) * 0.5333 as INT) +
						(sum(votos_part_14) - (CAST(sum(votos_part_14) * 0.2667 as INT)+CAST(sum(votos_part_14) * 0.2 as INT)+ CAST(sum(votos_part_14) * 0.5333 as INT)))
					 END  as 'votos_part_7',  
					 sum(V.votos_part_9) as 'votos_part_9', 
					 sum(votos_cand_no_reg) as 'votos_cand_no_reg', 
					 sum(votos_nulos) as 'votos_nulos', sum(votacion_total) as 'votacion_total',
					 CAST(sum(V.votos_part_10)/3 as INT) as parte10, 
					 CAST(sum(V.votos_part_11)/2 as INT) as parte11,
					 CAST(sum(V.votos_part_12)/2 as INT) as parte12, 
					 CAST(sum(V.votos_part_13)/2 as INT) as parte13,
					 CAST(sum(V.votos_part_14)/3 as INT) as parte14,
					 CAST(sum(V.votos_part_15)/2 as INT) as parte15,
					 CAST(sum(V.votos_part_16)/2 as INT) as parte16,
					 CAST(sum(V.votos_part_17)/2 as INT) as parte17,
					 sum(V.votos_part_10)%3 as residuo10, 
					 sum(V.votos_part_11)%2 as residuo11, 
					 sum(V.votos_part_12)%2 as residuo12, 
					 sum(V.votos_part_13)%2 as residuo13, 
					 0 as residuo14,
					 0 as residuo15,
					 0 as residuo16,
					 0 as residuo17
					FROM scd_votos as V 
					where id_tipo_eleccion= ".$type." and contabilizar='T' and substr(tipo_casilla,1,1) = 'M' group by id_delegacion
				),
				suma_igual AS (
					SELECT id_delegacion, 
					 (votos_part_1 + parte10 + parte11 + parte12) as votos_part_1, 
					 (votos_part_2 + parte10 + parte11 + parte13) as votos_part_2,
					 (votos_part_3 + parte10 + parte12 + parte13) as votos_part_3,
					 votos_part_4,
					 votos_part_5,
					 votos_part_6, 
					 votos_part_7,
					 votos_part_9, votos_cand_no_reg, votos_nulos, votacion_total,
					 parte10, parte11, parte12, parte13, parte14, parte15, parte16, parte17, residuo10, residuo11, residuo12, residuo13, residuo14,
					 residuo15, residuo16, residuo17
					 from distribucion
				),
				mayores AS (
					SELECT  *,

						CASE
							WHEN votos_part_1 >= votos_part_2 AND votos_part_1 >= votos_part_3 THEN 'votos_part_1'
							WHEN votos_part_2 >= votos_part_1 AND votos_part_2 >= votos_part_3 THEN 'votos_part_2'
							ELSE 'votos_part_3'
						END AS mayor10_1,
						CASE
								WHEN (votos_part_1 >= votos_part_2 AND votos_part_1 < votos_part_3) OR (votos_part_1 >= votos_part_3 AND votos_part_1 < votos_part_2) THEN 'votos_part_1'
								WHEN (votos_part_2 >= votos_part_1 AND votos_part_2 < votos_part_3) OR (votos_part_2 >= votos_part_3 AND votos_part_2 < votos_part_1) THEN 'votos_part_2'
							ELSE 'votos_part_3'
						END AS mayor10_2,
			
						CASE WHEN votos_part_1 >= votos_part_2 THEN 'votos_part_1'
						ELSE 'votos_part_2'
						END AS mayor11,
		
						CASE WHEN votos_part_1 >= votos_part_3 THEN 'votos_part_1'
						ELSE 'votos_part_3'
						END AS mayor12,
			
						CASE WHEN votos_part_2 >= votos_part_3 THEN 'votos_part_2'
						ELSE 'votos_part_3'
						END AS mayor13
						FROM
						suma_igual
				),
				distribucion_residuo AS (
					SELECT
						*,
						votos_part_1 + 
							CASE WHEN residuo10=2 AND (mayor10_1 = 'votos_part_1' OR mayor10_2 = 'votos_part_1') THEN  1 ELSE 0 END +
							CASE WHEN residuo10=1 AND (mayor10_1 = 'votos_part_1' OR mayor10_2 = 'votos_part_1') THEN  1 ELSE 0 END +
							CASE WHEN mayor11 = 'votos_part_1' THEN residuo11 ELSE 0 END +
							CASE WHEN mayor12 = 'votos_part_1' THEN residuo12 ELSE 0 END AS votos_part_1_S,
						votos_part_2 + CASE WHEN residuo10=2 AND (mayor10_1 = 'votos_part_2' OR mayor10_2 = 'votos_part_2') THEN  1 ELSE 0 END +
							CASE WHEN mayor11 = 'votos_part_2' THEN residuo11 ELSE 0 END +
							CASE WHEN mayor13 = 'votos_part_2' THEN residuo13 ELSE 0 END AS votos_part_2_S,
						votos_part_3 + CASE WHEN residuo10=2 AND (mayor10_1 = 'votos_part_3' OR mayor10_2 = 'votos_part_3') THEN  1 ELSE 0 END +
							CASE WHEN mayor12 = 'votos_part_3' THEN  residuo12 ELSE 0 END +
							CASE WHEN mayor13 = 'votos_part_3' THEN residuo13 ELSE 0 END AS votos_part_3_S
					FROM
						mayores
				)
				select sum(votos_part_1_S) as votos_part_1, sum(votos_part_2_S) as votos_part_2, 
				sum(votos_part_3_S) as votos_part_3, sum(votos_part_4) as votos_part_4, sum(votos_part_5) as votos_part_5, sum(votos_part_6) as votos_part_6,  
				sum(votos_part_7) as votos_part_7,
				sum(votos_part_9) as votos_part_9, sum(votos_cand_no_reg) as votos_cand_no_reg, sum(votos_nulos) as votos_nulos, 
				sum(votacion_total) as votacion_total from distribucion_residuo " ;

	}
	
	

	return $qryParticipantesITEM;	

};




// Consigue los datos de partidos con distribución de votos
function consigueDatosDMR($type, $item){
	$campoCorte = "";
	$qryParticipantesITEM = "";
	$inde1="";
	$inde2="";
	if(trim($item)!=""){
			switch ($type) {
				case 1:

					$campoCorte ="id_distrito";
					$where="";
					$inde1="";
					$inde2="";
					break;
					
				case 2:

					$campoCorte ="id_distrito";
					$inde1="sum(votos_part_8) as 'votos_part_8', ";
					$inde2="";
					$where="";
					break;
				case 3:  //RP
					$qryParticipantesITEM = '';
					$campoCorte ="";
					$inde1="";
					$inde2="";
					break;
				case 4:
						$campoCorte ="id_delegacion";
						$where="";
						$inde1="";
						$inde2="sum(votos_part_9) as 'votos_part_9', ";
					break;
			}
	
		
			$qryParticipantesITEM = "
				WITH distribucion AS (
				SELECT ".$campoCorte.", 
						sum(V.votos_part_1) as 'votos_part_1', 
						 sum(V.votos_part_2) as 'votos_part_2', 
						 sum(V.votos_part_3) as 'votos_part_3', 
						 CASE WHEN id_distrito in (7,14,29,31) THEN
							 sum(votos_part_4) 
						 ELSE
							sum(votos_part_4) + CAST(sum(votos_part_14) * 0.2667 as INT)
						 END as 'votos_part_4', 
						 CASE WHEN id_distrito in (7,14,29,31) THEN
							sum(votos_part_5) 
						 ELSE
							sum(votos_part_5) + CAST(sum(votos_part_14) * 0.2 as INT)
						 END  as 'votos_part_5',
						 sum(V.votos_part_6) as 'votos_part_6', 
						 CASE WHEN id_distrito in (7,14,29,31) THEN
							sum(votos_part_7) 
						 ELSE
							 sum(votos_part_7) + CAST(sum(votos_part_14) * 0.5333 as INT) +
							(sum(votos_part_14) - (CAST(sum(votos_part_14) * 0.2667 as INT)+CAST(sum(votos_part_14) * 0.2 as INT)+ CAST(sum(votos_part_14) * 0.5333 as INT)))
						 END  as 'votos_part_7',  
						 sum(V.votos_part_8) as 'votos_part_8', 
						 sum(V.votos_part_9) as 'votos_part_9', sum(votos_cand_no_reg) as 'votos_cand_no_reg', 
						 sum(votos_nulos) as 'votos_nulos', sum(votacion_total) as 'votacion_total',
						 CAST(sum(V.votos_part_10)/3 as INT) as parte10, 
						 CAST(sum(V.votos_part_11)/2 as INT) as parte11,
						 CAST(sum(V.votos_part_12)/2 as INT) as parte12, 
						 CAST(sum(V.votos_part_13)/2 as INT) as parte13,
						 CAST(sum(V.votos_part_14)/3 as INT) as parte14,
						 CAST(sum(V.votos_part_15)/2 as INT) as parte15,
						 CAST(sum(V.votos_part_16)/2 as INT) as parte16,
						 CAST(sum(V.votos_part_17)/2 as INT) as parte17,
						 sum(V.votos_part_10)%3 as residuo10, 
						 sum(V.votos_part_11)%2 as residuo11, 
						 sum(V.votos_part_12)%2 as residuo12, 
						 sum(V.votos_part_13)%2 as residuo13, 
						 0 as residuo14,
						 0 as residuo15,
						 0 as residuo16,
						 0 as residuo17
					FROM scd_votos as V 
					where id_tipo_eleccion in(2,3) and ".$campoCorte." = ".$item." and contabilizar='T' group by ".$campoCorte."
				),
				suma_igual AS (
					SELECT id_distrito, 
					 (votos_part_1 + parte10 + parte11 + parte12) as votos_part_1, 
					 (votos_part_2 + parte10 + parte11 + parte13) as votos_part_2,
					 (votos_part_3 + parte10 + parte12 + parte13) as votos_part_3,
					 votos_part_4,
					 votos_part_5,
					 votos_part_6, 
					 votos_part_7,
					 votos_part_8, votos_part_9, votos_cand_no_reg, votos_nulos, votacion_total,
					 parte10, parte11, parte12, parte13, parte14, parte15, parte16, parte17, residuo10, residuo11, residuo12, residuo13, residuo14,
					 residuo15, residuo16, residuo17
					 from distribucion
				),
				mayores AS (
					SELECT  *,
						
						CASE
							WHEN votos_part_1 >= votos_part_2 AND votos_part_1 >= votos_part_3 THEN 'votos_part_1'
							WHEN votos_part_2 >= votos_part_1 AND votos_part_2 >= votos_part_3 THEN 'votos_part_2'
							ELSE 'votos_part_3'
						END AS mayor10_1,
						CASE
								WHEN (votos_part_1 >= votos_part_2 AND votos_part_1 < votos_part_3) OR (votos_part_1 >= votos_part_3 AND votos_part_1 < votos_part_2) THEN 'votos_part_1'
								WHEN (votos_part_2 >= votos_part_1 AND votos_part_2 < votos_part_3) OR (votos_part_2 >= votos_part_3 AND votos_part_2 < votos_part_1) THEN 'votos_part_2'
							ELSE 'votos_part_3'
						END AS mayor10_2,
						
						CASE WHEN votos_part_1 >= votos_part_2 THEN 'votos_part_1'
						ELSE 'votos_part_2'
						END AS mayor11,
						
						CASE WHEN votos_part_1 >= votos_part_3 THEN 'votos_part_1'
						ELSE 'votos_part_3'
						END AS mayor12,
						
						CASE WHEN votos_part_2 >= votos_part_3 THEN 'votos_part_2'
						ELSE 'votos_part_3'
						END AS mayor13
						FROM
						suma_igual
				),
				distribucion_residuo AS (
					SELECT
						*,
						votos_part_1 + 
							CASE WHEN residuo10=2 AND (mayor10_1 = 'votos_part_1' OR mayor10_2 = 'votos_part_1') THEN  1 ELSE 0 END +
							CASE WHEN residuo10=1 AND (mayor10_1 = 'votos_part_1' OR mayor10_2 = 'votos_part_1') THEN  1 ELSE 0 END +
							CASE WHEN mayor11 = 'votos_part_1' THEN residuo11 ELSE 0 END +
							CASE WHEN mayor12 = 'votos_part_1' THEN residuo12 ELSE 0 END AS votos_part_1_S,
						votos_part_2 + CASE WHEN residuo10=2 AND (mayor10_1 = 'votos_part_2' OR mayor10_2 = 'votos_part_2') THEN  1 ELSE 0 END +
							CASE WHEN mayor11 = 'votos_part_2' THEN residuo11 ELSE 0 END +
							CASE WHEN mayor13 = 'votos_part_2' THEN residuo13 ELSE 0 END AS votos_part_2_S,
						votos_part_3 + CASE WHEN residuo10=2 AND (mayor10_1 = 'votos_part_3' OR mayor10_2 = 'votos_part_3') THEN  1 ELSE 0 END +
							CASE WHEN mayor12 = 'votos_part_3' THEN  residuo12 ELSE 0 END +
							CASE WHEN mayor13 = 'votos_part_3' THEN residuo13 ELSE 0 END AS votos_part_3_S
					FROM
						mayores
				)
				select id_distrito,votos_part_1_S as votos_part_1, votos_part_2_S as votos_part_2, votos_part_3_S as votos_part_3, 
				votos_part_4, votos_part_5, votos_part_6,  votos_part_7, votos_part_8,
				 votos_cand_no_reg, votos_nulos, votacion_total from distribucion_residuo " ;
	
		}
		else{
			
			$qryParticipantesITEM = "WITH distribucion AS (
					SELECT id_distrito, 
						 sum(V.votos_part_1) as 'votos_part_1', 
						 sum(V.votos_part_2) as 'votos_part_2', 
						 sum(V.votos_part_3) as 'votos_part_3', 
						 CASE WHEN id_distrito in (7,14,29,31) THEN
							 sum(votos_part_4) 
						 ELSE
							sum(votos_part_4) + CAST(sum(votos_part_14) * 0.2667 as INT)
						 END as 'votos_part_4', 
						 CASE WHEN id_distrito in (7,14,29,31) THEN
							sum(votos_part_5) 
						 ELSE
							sum(votos_part_5) + CAST(sum(votos_part_14) * 0.2 as INT)
						 END  as 'votos_part_5',
						 sum(V.votos_part_6) as 'votos_part_6', 
						 CASE WHEN id_distrito in (7,14,29,31) THEN
							sum(votos_part_7) 
						 ELSE
							 sum(votos_part_7) + CAST(sum(votos_part_14) * 0.5333 as INT) +
							(sum(votos_part_14) - (CAST(sum(votos_part_14) * 0.2667 as INT)+CAST(sum(votos_part_14) * 0.2 as INT)+ CAST(sum(votos_part_14) * 0.5333 as INT)))
						 END  as 'votos_part_7',  
						 sum(V.votos_part_8) as 'votos_part_8', 
						 sum(V.votos_part_9) as 'votos_part_9', sum(votos_cand_no_reg) as 'votos_cand_no_reg', 
						 sum(votos_nulos) as 'votos_nulos', sum(votacion_total) as 'votacion_total',
						 CAST(sum(V.votos_part_10)/3 as INT) as parte10, 
						 CAST(sum(V.votos_part_11)/2 as INT) as parte11,
						 CAST(sum(V.votos_part_12)/2 as INT) as parte12, 
						 CAST(sum(V.votos_part_13)/2 as INT) as parte13,
						 CAST(sum(V.votos_part_14)/3 as INT) as parte14,
						 CAST(sum(V.votos_part_15)/2 as INT) as parte15,
						 CAST(sum(V.votos_part_16)/2 as INT) as parte16,
						 CAST(sum(V.votos_part_17)/2 as INT) as parte17,
						 sum(V.votos_part_10)%3 as residuo10, 
						 sum(V.votos_part_11)%2 as residuo11, 
						 sum(V.votos_part_12)%2 as residuo12, 
						 sum(V.votos_part_13)%2 as residuo13, 
						 0 as residuo14,
						 0 as residuo15,
						 0 as residuo16,
						 0 as residuo17
						FROM scd_votos as V 
						where id_tipo_eleccion in(2,3) and contabilizar='T' group by id_distrito
				),
				suma_igual AS (
					SELECT id_distrito, 
					 (votos_part_1 + parte10 + parte11 + parte12) as votos_part_1, 
					 (votos_part_2 + parte10 + parte11 + parte13) as votos_part_2,
					 (votos_part_3 + parte10 + parte12 + parte13) as votos_part_3,
					 votos_part_4,
					 votos_part_5,
					 votos_part_6, 
					 votos_part_7,
					 votos_part_8, votos_part_9, votos_cand_no_reg, votos_nulos, votacion_total,
					 parte10, parte11, parte12, parte13, parte14, parte15, parte16, parte17, residuo10, residuo11, residuo12, residuo13, residuo14,
					 residuo15, residuo16, residuo17
					 from distribucion
				),
				mayores AS (
					SELECT  *,
						
						CASE
							WHEN votos_part_1 >= votos_part_2 AND votos_part_1 >= votos_part_3 THEN 'votos_part_1'
							WHEN votos_part_2 >= votos_part_1 AND votos_part_2 >= votos_part_3 THEN 'votos_part_2'
							ELSE 'votos_part_3'
						END AS mayor10_1,
						CASE
								WHEN (votos_part_1 >= votos_part_2 AND votos_part_1 < votos_part_3) OR (votos_part_1 >= votos_part_3 AND votos_part_1 < votos_part_2) THEN 'votos_part_1'
								WHEN (votos_part_2 >= votos_part_1 AND votos_part_2 < votos_part_3) OR (votos_part_2 >= votos_part_3 AND votos_part_2 < votos_part_1) THEN 'votos_part_2'
							ELSE 'votos_part_3'
						END AS mayor10_2,
						
						CASE WHEN votos_part_1 >= votos_part_2 THEN 'votos_part_1'
						ELSE 'votos_part_2'
						END AS mayor11,
						
						CASE WHEN votos_part_1 >= votos_part_3 THEN 'votos_part_1'
						ELSE 'votos_part_3'
						END AS mayor12,
						
						CASE WHEN votos_part_2 >= votos_part_3 THEN 'votos_part_2'
						ELSE 'votos_part_3'
						END AS mayor13
						FROM
						suma_igual
				),
				distribucion_residuo AS (
					SELECT
						*,
						votos_part_1 + 
							CASE WHEN residuo10=2 AND (mayor10_1 = 'votos_part_1' OR mayor10_2 = 'votos_part_1') THEN  1 ELSE 0 END +
							CASE WHEN residuo10=1 AND (mayor10_1 = 'votos_part_1' OR mayor10_2 = 'votos_part_1') THEN  1 ELSE 0 END +
							CASE WHEN mayor11 = 'votos_part_1' THEN residuo11 ELSE 0 END +
							CASE WHEN mayor12 = 'votos_part_1' THEN residuo12 ELSE 0 END AS votos_part_1_S,
						votos_part_2 + CASE WHEN residuo10=2 AND (mayor10_1 = 'votos_part_2' OR mayor10_2 = 'votos_part_2') THEN  1 ELSE 0 END +
							CASE WHEN mayor11 = 'votos_part_2' THEN residuo11 ELSE 0 END +
							CASE WHEN mayor13 = 'votos_part_2' THEN residuo13 ELSE 0 END AS votos_part_2_S,
						votos_part_3 + CASE WHEN residuo10=2 AND (mayor10_1 = 'votos_part_3' OR mayor10_2 = 'votos_part_3') THEN  1 ELSE 0 END +
							CASE WHEN mayor12 = 'votos_part_3' THEN  residuo12 ELSE 0 END +
							CASE WHEN mayor13 = 'votos_part_3' THEN residuo13 ELSE 0 END AS votos_part_3_S
					FROM
						mayores
				)
				select sum(votos_part_1_S) as votos_part_1, sum(votos_part_2_S) as votos_part_2, 
				sum(votos_part_3_S) as votos_part_3, sum(votos_part_4) as votos_part_4, sum(votos_part_5) as votos_part_5, sum(votos_part_6) as votos_part_6,  
				sum(votos_part_7) as votos_part_7,
				sum(votos_part_8) as votos_part_8, sum(votos_cand_no_reg) as votos_cand_no_reg, sum(votos_nulos) as votos_nulos, 
				sum(votacion_total) as votacion_total from distribucion_residuo " ;

	}
	
	

	return $qryParticipantesITEM;	

};

// Consigue la sentencia los datos de partidos con distribución de votos
function consigueDatosDMR_EXTRANJERO($type, $item){
	$campoCorte = "";
	$qryParticipantesITEM = "";
	$inde1="";
	$inde2="";
	if(trim($item)!=""){
			switch ($type) {
				case 1:

					$campoCorte ="id_distrito";
					$where="";
					$inde1="";
					$inde2="";
					break;
					
				case 2:

					$campoCorte ="id_distrito";
					$inde1="sum(votos_part_8) as 'votos_part_8', ";
					$inde2="";
					$where="";
					break;
				case 3:  //RP
					$qryParticipantesITEM = '';
					$campoCorte ="";
					$inde1="";
					$inde2="";
					break;
				case 4:
						$campoCorte ="id_delegacion";
						$where="";
						$inde1="";
						$inde2="sum(votos_part_9) as 'votos_part_9', ";
					break;
			}
	
		
			$qryParticipantesITEM = "
				WITH distribucion AS (
				SELECT ".$campoCorte.", 
						sum(V.votos_part_1) as 'votos_part_1', 
						 sum(V.votos_part_2) as 'votos_part_2', 
						 sum(V.votos_part_3) as 'votos_part_3', 
						 CASE WHEN id_distrito in (7,14,29,31) THEN
							 sum(votos_part_4) 
						 ELSE
							sum(votos_part_4) + CAST(sum(votos_part_14) * 0.2667 as INT)
						 END as 'votos_part_4', 
						 CASE WHEN id_distrito in (7,14,29,31) THEN
							sum(votos_part_5) 
						 ELSE
							sum(votos_part_5) + CAST(sum(votos_part_14) * 0.2 as INT)
						 END  as 'votos_part_5',
						 sum(V.votos_part_6) as 'votos_part_6', 
						 CASE WHEN id_distrito in (7,14,29,31) THEN
							sum(votos_part_7) 
						 ELSE
							 sum(votos_part_7) + CAST(sum(votos_part_14) * 0.5333 as INT) +
							(sum(votos_part_14) - (CAST(sum(votos_part_14) * 0.2667 as INT)+CAST(sum(votos_part_14) * 0.2 as INT)+ CAST(sum(votos_part_14) * 0.5333 as INT)))
						 END  as 'votos_part_7',  
						 sum(V.votos_part_8) as 'votos_part_8', 
						 sum(V.votos_part_9) as 'votos_part_9', sum(votos_cand_no_reg) as 'votos_cand_no_reg', 
						 sum(votos_nulos) as 'votos_nulos', sum(votacion_total) as 'votacion_total',
						 CAST(sum(V.votos_part_10)/3 as INT) as parte10, 
						 CAST(sum(V.votos_part_11)/2 as INT) as parte11,
						 CAST(sum(V.votos_part_12)/2 as INT) as parte12, 
						 CAST(sum(V.votos_part_13)/2 as INT) as parte13,
						 CAST(sum(V.votos_part_14)/3 as INT) as parte14,
						 CAST(sum(V.votos_part_15)/2 as INT) as parte15,
						 CAST(sum(V.votos_part_16)/2 as INT) as parte16,
						 CAST(sum(V.votos_part_17)/2 as INT) as parte17,
						 sum(V.votos_part_10)%3 as residuo10, 
						 sum(V.votos_part_11)%2 as residuo11, 
						 sum(V.votos_part_12)%2 as residuo12, 
						 sum(V.votos_part_13)%2 as residuo13, 
						 0 as residuo14,
						 0 as residuo15,
						 0 as residuo16,
						 0 as residuo17
					FROM scd_votos as V 
					where id_tipo_eleccion in(2,3) and ".$campoCorte." = ".$item." and substr(tipo_casilla,1,1) = 'M' and contabilizar='T' group by ".$campoCorte."
				),
				suma_igual AS (
					SELECT id_distrito, 
					 (votos_part_1 + parte10 + parte11 + parte12) as votos_part_1, 
					 (votos_part_2 + parte10 + parte11 + parte13) as votos_part_2,
					 (votos_part_3 + parte10 + parte12 + parte13) as votos_part_3,
					 votos_part_4,
					 votos_part_5,
					 votos_part_6, 
					 votos_part_7,
					 votos_part_8, votos_part_9, votos_cand_no_reg, votos_nulos, votacion_total,
					 parte10, parte11, parte12, parte13, parte14, parte15, parte16, parte17, residuo10, residuo11, residuo12, residuo13, residuo14,
					 residuo15, residuo16, residuo17
					 from distribucion
				),
				mayores AS (
					SELECT  *,
						
						CASE
							WHEN votos_part_1 >= votos_part_2 AND votos_part_1 >= votos_part_3 THEN 'votos_part_1'
							WHEN votos_part_2 >= votos_part_1 AND votos_part_2 >= votos_part_3 THEN 'votos_part_2'
							ELSE 'votos_part_3'
						END AS mayor10_1,
						CASE
								WHEN (votos_part_1 >= votos_part_2 AND votos_part_1 < votos_part_3) OR (votos_part_1 >= votos_part_3 AND votos_part_1 < votos_part_2) THEN 'votos_part_1'
								WHEN (votos_part_2 >= votos_part_1 AND votos_part_2 < votos_part_3) OR (votos_part_2 >= votos_part_3 AND votos_part_2 < votos_part_1) THEN 'votos_part_2'
							ELSE 'votos_part_3'
						END AS mayor10_2,
						
						CASE WHEN votos_part_1 >= votos_part_2 THEN 'votos_part_1'
						ELSE 'votos_part_2'
						END AS mayor11,
						
						CASE WHEN votos_part_1 >= votos_part_3 THEN 'votos_part_1'
						ELSE 'votos_part_3'
						END AS mayor12,
						
						CASE WHEN votos_part_2 >= votos_part_3 THEN 'votos_part_2'
						ELSE 'votos_part_3'
						END AS mayor13
						FROM
						suma_igual
				),
				distribucion_residuo AS (
					SELECT
						*,
						votos_part_1 + 
							CASE WHEN residuo10=2 AND (mayor10_1 = 'votos_part_1' OR mayor10_2 = 'votos_part_1') THEN  1 ELSE 0 END +
							CASE WHEN residuo10=1 AND (mayor10_1 = 'votos_part_1' OR mayor10_2 = 'votos_part_1') THEN  1 ELSE 0 END +
							CASE WHEN mayor11 = 'votos_part_1' THEN residuo11 ELSE 0 END +
							CASE WHEN mayor12 = 'votos_part_1' THEN residuo12 ELSE 0 END AS votos_part_1_S,
						votos_part_2 + CASE WHEN residuo10=2 AND (mayor10_1 = 'votos_part_2' OR mayor10_2 = 'votos_part_2') THEN  1 ELSE 0 END +
							CASE WHEN mayor11 = 'votos_part_2' THEN residuo11 ELSE 0 END +
							CASE WHEN mayor13 = 'votos_part_2' THEN residuo13 ELSE 0 END AS votos_part_2_S,
						votos_part_3 + CASE WHEN residuo10=2 AND (mayor10_1 = 'votos_part_3' OR mayor10_2 = 'votos_part_3') THEN  1 ELSE 0 END +
							CASE WHEN mayor12 = 'votos_part_3' THEN  residuo12 ELSE 0 END +
							CASE WHEN mayor13 = 'votos_part_3' THEN residuo13 ELSE 0 END AS votos_part_3_S
					FROM
						mayores
				)
				select id_distrito,votos_part_1_S as votos_part_1, votos_part_2_S as votos_part_2, votos_part_3_S as votos_part_3, 
				votos_part_4, votos_part_5, votos_part_6,  votos_part_7, votos_part_8,
				 votos_cand_no_reg, votos_nulos, votacion_total from distribucion_residuo " ;
	
		}
		else{
			
			$qryParticipantesITEM = "WITH distribucion AS (
					SELECT id_distrito, 
						 sum(V.votos_part_1) as 'votos_part_1', 
						 sum(V.votos_part_2) as 'votos_part_2', 
						 sum(V.votos_part_3) as 'votos_part_3', 
						 CASE WHEN id_distrito in (7,14,29,31) THEN
							 sum(votos_part_4) 
						 ELSE
							sum(votos_part_4) + CAST(sum(votos_part_14) * 0.2667 as INT)
						 END as 'votos_part_4', 
						 CASE WHEN id_distrito in (7,14,29,31) THEN
							sum(votos_part_5) 
						 ELSE
							sum(votos_part_5) + CAST(sum(votos_part_14) * 0.2 as INT)
						 END  as 'votos_part_5',
						 sum(V.votos_part_6) as 'votos_part_6', 
						 CASE WHEN id_distrito in (7,14,29,31) THEN
							sum(votos_part_7) 
						 ELSE
							 sum(votos_part_7) + CAST(sum(votos_part_14) * 0.5333 as INT) +
							(sum(votos_part_14) - (CAST(sum(votos_part_14) * 0.2667 as INT)+CAST(sum(votos_part_14) * 0.2 as INT)+ CAST(sum(votos_part_14) * 0.5333 as INT)))
						 END  as 'votos_part_7',  
						 sum(V.votos_part_8) as 'votos_part_8', 
						 sum(V.votos_part_9) as 'votos_part_9', sum(votos_cand_no_reg) as 'votos_cand_no_reg', 
						 sum(votos_nulos) as 'votos_nulos', sum(votacion_total) as 'votacion_total',
						 CAST(sum(V.votos_part_10)/3 as INT) as parte10, 
						 CAST(sum(V.votos_part_11)/2 as INT) as parte11,
						 CAST(sum(V.votos_part_12)/2 as INT) as parte12, 
						 CAST(sum(V.votos_part_13)/2 as INT) as parte13,
						 CAST(sum(V.votos_part_14)/3 as INT) as parte14,
						 CAST(sum(V.votos_part_15)/2 as INT) as parte15,
						 CAST(sum(V.votos_part_16)/2 as INT) as parte16,
						 CAST(sum(V.votos_part_17)/2 as INT) as parte17,
						 sum(V.votos_part_10)%3 as residuo10, 
						 sum(V.votos_part_11)%2 as residuo11, 
						 sum(V.votos_part_12)%2 as residuo12, 
						 sum(V.votos_part_13)%2 as residuo13, 
						 0 as residuo14,
						 0 as residuo15,
						 0 as residuo16,
						 0 as residuo17
						FROM scd_votos as V 
						where id_tipo_eleccion in(2,3) and contabilizar='T' and substr(tipo_casilla,1,1) = 'M' group by id_distrito
				),
				suma_igual AS (
					SELECT id_distrito, 
					 (votos_part_1 + parte10 + parte11 + parte12) as votos_part_1, 
					 (votos_part_2 + parte10 + parte11 + parte13) as votos_part_2,
					 (votos_part_3 + parte10 + parte12 + parte13) as votos_part_3,
					 votos_part_4,
					 votos_part_5,
					 votos_part_6, 
					 votos_part_7,
					 votos_part_8, votos_part_9, votos_cand_no_reg, votos_nulos, votacion_total,
					 parte10, parte11, parte12, parte13, parte14, parte15, parte16, parte17, residuo10, residuo11, residuo12, residuo13, residuo14,
					 residuo15, residuo16, residuo17
					 from distribucion
				),
				mayores AS (
					SELECT  *,
						
						CASE
							WHEN votos_part_1 >= votos_part_2 AND votos_part_1 >= votos_part_3 THEN 'votos_part_1'
							WHEN votos_part_2 >= votos_part_1 AND votos_part_2 >= votos_part_3 THEN 'votos_part_2'
							ELSE 'votos_part_3'
						END AS mayor10_1,
						CASE
								WHEN (votos_part_1 >= votos_part_2 AND votos_part_1 < votos_part_3) OR (votos_part_1 >= votos_part_3 AND votos_part_1 < votos_part_2) THEN 'votos_part_1'
								WHEN (votos_part_2 >= votos_part_1 AND votos_part_2 < votos_part_3) OR (votos_part_2 >= votos_part_3 AND votos_part_2 < votos_part_1) THEN 'votos_part_2'
							ELSE 'votos_part_3'
						END AS mayor10_2,
						
						CASE WHEN votos_part_1 >= votos_part_2 THEN 'votos_part_1'
						ELSE 'votos_part_2'
						END AS mayor11,
						
						CASE WHEN votos_part_1 >= votos_part_3 THEN 'votos_part_1'
						ELSE 'votos_part_3'
						END AS mayor12,
						
						CASE WHEN votos_part_2 >= votos_part_3 THEN 'votos_part_2'
						ELSE 'votos_part_3'
						END AS mayor13
						FROM
						suma_igual
				),
				distribucion_residuo AS (
					SELECT
						*,
						votos_part_1 + 
							CASE WHEN residuo10=2 AND (mayor10_1 = 'votos_part_1' OR mayor10_2 = 'votos_part_1') THEN  1 ELSE 0 END +
							CASE WHEN residuo10=1 AND (mayor10_1 = 'votos_part_1' OR mayor10_2 = 'votos_part_1') THEN  1 ELSE 0 END +
							CASE WHEN mayor11 = 'votos_part_1' THEN residuo11 ELSE 0 END +
							CASE WHEN mayor12 = 'votos_part_1' THEN residuo12 ELSE 0 END AS votos_part_1_S,
						votos_part_2 + CASE WHEN residuo10=2 AND (mayor10_1 = 'votos_part_2' OR mayor10_2 = 'votos_part_2') THEN  1 ELSE 0 END +
							CASE WHEN mayor11 = 'votos_part_2' THEN residuo11 ELSE 0 END +
							CASE WHEN mayor13 = 'votos_part_2' THEN residuo13 ELSE 0 END AS votos_part_2_S,
						votos_part_3 + CASE WHEN residuo10=2 AND (mayor10_1 = 'votos_part_3' OR mayor10_2 = 'votos_part_3') THEN  1 ELSE 0 END +
							CASE WHEN mayor12 = 'votos_part_3' THEN  residuo12 ELSE 0 END +
							CASE WHEN mayor13 = 'votos_part_3' THEN residuo13 ELSE 0 END AS votos_part_3_S
					FROM
						mayores
				)
				select sum(votos_part_1_S) as votos_part_1, sum(votos_part_2_S) as votos_part_2, 
				sum(votos_part_3_S) as votos_part_3, sum(votos_part_4) as votos_part_4, sum(votos_part_5) as votos_part_5, sum(votos_part_6) as votos_part_6,  
				sum(votos_part_7) as votos_part_7,
				sum(votos_part_8) as votos_part_8, sum(votos_cand_no_reg) as votos_cand_no_reg, sum(votos_nulos) as votos_nulos, 
				sum(votacion_total) as votacion_total from distribucion_residuo " ;

	}
	

	

	return $qryParticipantesITEM;	

};


function consigueCat($db, $id_campo, $type){
		
	$tabla_cat = "";
	$name_item_cat = "";
	$where_temp = "";
	switch ($type) {
		case 1:
			$tabla_cat = "scd_participantes_jgob";
			$name_item_cat ="id_distrito";
			$where_temp = "";
			break;
		case 2:
			$tabla_cat = "scd_participantes_mr";
			$name_item_cat ="id_distrito";
			if($id_campo==''){
				$where_temp = " ".$name_item_cat." = ".$id_campo." AND ";
			}
			else{
				$where_temp = " ";
			}
			//$where_temp = " ".$name_item_cat." = ".$id_campo." AND ";
			break;
		case 3:
			$tabla_cat = "scd_participantes_rp";
			$name_item_cat ="id_distrito";
			if($id_campo==''){
				$where_temp = " ".$name_item_cat." = ".$id_campo." AND ";
			}
			else{
				$where_temp = " ";
			}
			//$where_temp = " ".$name_item_cat." = ".$id_campo." AND ";
			break;
		case 4:
			$tabla_cat = "scd_participantes_jdel";
			$name_item_cat ="id_delegacion";
			if($id_campo==''){
				$where_temp = " ".$name_item_cat." = ".$id_campo." AND ";
			}
			else{
				$where_temp = " ";
			}
			//$where_temp = " ".$name_item_cat." = ".$id_campo." AND ";
			break;
	}
		// Lamamos catálogo de porcentajes
		$sqlCatPor = "WITH RECURSIVE neat(
id, tag1, etc1, tag2, etc2 
		) AS(
			SELECT
				id_participante
				, ''
				, integrantesPartidosCC || '&'
				, '' 
				, porcentaje || '&' 
			FROM ".$tabla_cat." 
			WHERE ".$where_temp." porcentaje != ''
			UNION ALL
			SELECT 
				id
				, SUBSTR(etc1, 0, INSTR(etc1, '&'))
				, SUBSTR(etc1, INSTR(etc1, '&')+1)
				, SUBSTR(etc2, 0, INSTR(etc2, '&')) 
				, SUBSTR(etc2, INSTR(etc2, '&')+1)
			FROM neat
			WHERE etc1 <> '' OR etc2 <> '' 
		)
		SELECT 
			id AS id_participante,
			('votos_part_' || tag1) AS nombre_campo1, 
			CAST(tag1 AS INT) AS id,
			CAST(tag2 AS NUMBER) AS porcent
		FROM neat
		WHERE tag1 <> '' OR tag2 <> '' 
		ORDER BY 
			id ASC,
			tag1 ASC; 
	SELECT 
		id as id_participante, (\"votos_part_\" || tag) as nombre_campo ,CAST(tag AS INT) as tag
		FROM neat
		WHERE tag <> ''
		ORDER BY 
			id ASC
			, tag ASC
		;  
		";

	$res_catchTMP = $db->query($sqlCatPor);
	
	$itemRecordsTMP = array();
	$db->enableExceptions(false);
	while ($rowTMP = $res_catchTMP->fetchArray(SQLITE3_ASSOC))
	{
		array_push($itemRecordsTMP, $rowTMP);
	}
	//	echo "<br><br>CAT : ".$sqlCatPor."<br><br>";
	return $itemRecordsTMP;
				
}

// Consigue todos nombres de PARTIDOS participantes(incluyendos los que componen
// las canidaturas comunes) de los campos a consultar en votación según la elección, estó 
// para armar el la sentencia SELECT que traerá toda la información.
// $where y $campoExtra1 los uso para traer todo por DTO / DEL. El campo $campoExtra2
// lo utilizo por si se filtra por SECCION
function getFieldNameParticipan($type, $where="", $campoExtra1="", $where2="" ){
		$db = new SQLite3('db/database.db3');
		// AQUI CONSIGO LOS CAMPOS A MOSTRAR POR ELECCION
		$qryParticipantes = "";  
		
		
		$campoCorte = $campoExtra1 =="" ? "id_distrito, ": $campoExtra1;
		
		switch ($type) {
			case 1:
				$qryParticipantes = 'select DISTINCT JG.id_participante, P.siglas, P.descripcion from 
				scd_participantes_jgob JG
				left join scd_cat_participantes P
				on JG.id_participante = P.id_participante where JG.id_participante<10  '.
				' order by JG.prelacion;';
				$campoCorte ="";
				$where="";
				break;
				
			case 2:
				$qryParticipantes = 'select DISTINCT  MR.id_participante, 	P.siglas, P.descripcion from 
				scd_participantes_mr MR
				left join scd_cat_participantes P
				on MR.id_participante = P.id_participante where MR.id_participante<10 '.
				' order by P.id_participante';
				$campoCorte ="";
				$where="";
				break;
			case 3:  //RP
				$qryParticipantes = '';
				break;
			case 4:
					$qryParticipantes = 'select DISTINCT JD.id_participante, P.siglas, P.descripcion from scd_participantes_jdel JD
					left join scd_cat_participantes P
					on JD.id_participante = P.id_participante where JD.id_participante<10 '.
					' order by JD.prelacion;';
					$campoCorte ="";
					$where="";
				break;
		}
		
		
			
//echo "!!!! ".$qryParticipantes; die();		
		// apertura de BD
		$reg_data=0;
		//$db = new SQLite3('db/database.db3');
		$res_catch = $db->query($qryParticipantes);
	
		if(!$res_catch) return null;
		
		$value_records = array();
		
		$db->enableExceptions(false);
		while ($row = $res_catch->fetchArray(SQLITE3_ASSOC))
		{
			//$var_catch = $row_catch['value']." - ".$row_catch['label']."<br>";
			array_push($value_records, $row);
			$reg_data++;
			// if($type==1) break;
		}
		
		$coma = "";
		$participanSQL="";
		// Recorro para armar SQL
		foreach ($value_records as $clave => $valor) {
				$participanSQL .= ($coma.' SUM(votos_part_'.$valor["id_participante"].') as votos_part_'.$valor["id_participante"]);
				$coma = ", ";
		}
		
		$participanSQL =  "Select ".$campoCorte.$participanSQL.", sum (ifnull(votos_part_1,0)+ifnull(votos_part_2,0)+ifnull(votos_part_3,0)+ifnull(votos_part_10,0)+ifnull(votos_part_11,0)+ifnull(votos_part_12,0)+ifnull(votos_part_13,0)) as total_votos_cc1, sum(total_votos_cc2) as total_votos_cc2, sum(total_votos_cc3) as total_votos_cc3, sum(total_votos_cc4) as total_votos_cc4, sum (ifnull(votos_part_4,0)+ifnull(votos_part_5,0)+ifnull(votos_part_7,0)+ifnull(votos_part_14, 0) +ifnull(votos_part_15,0)+ifnull(votos_part_16,0)+ifnull(votos_part_17,0)) as total_votos_cc5, sum(total_votos_cc6) as total_votos_cc6, sum(total_votos_cc7) as total_votos_cc7, sum(total_votos_cc8) as total_votos_cc8, sum(total_votos_cc9) as total_votos_cc9 , sum(votos_cand_no_reg) as votos_cand_no_reg, sum(votos_nulos) as votos_nulos, sum(votacion_total) as votacion_total from scd_votos ".$where.$where2;
		
//echo $participanSQL; die();
	
	/*	
		$participanSQL =  "Select ".$campoCorte.$participanSQL.", votos_cand_no_reg as [Candidatos no registrados], votos_nulos as [Votos nulos], votacion_total as [Votación total] from scd_votos ".$where.$where2;
	*/	
		
		$db->close();
		unset($db);	
		return $participanSQL;
}

function getRecordsetExtranjero($tmpSQL =""){	
		// apertura de BD
		$db = new SQLite3('db/database.db3');
		
//////echo $tmpSQL; die;

		$res_catch = $db->query($tmpSQL);
	
		if(!$res_catch) return null;
		
		$value_recordset = array();
		
		$db->enableExceptions(false);
		while ($row = $res_catch->fetchArray(SQLITE3_ASSOC))
		{
			$total_votos = $row["votacion_total"];

			$rowTMP = [];
			
			foreach ($row as $clave => $valor) {
					
				$partido = '';
				$id = '';
				$porcenToken = 0;
				
				if (str_contains2($clave, 'vot') && $clave!="votacion_total") {
					$token = explode("_", $clave);
					$countToken = count($token);
					if($countToken>1){
						// traemos el nombre de campo, si es número es un partido
						if(is_numeric($token[$countToken-1])){
							$partido = $token[$countToken-1].".jpg";
							$id = $token[$countToken-1];
							// calculamos porcentaje
							//porcenToken = ($valor*100) / $total_votos;
							if($total_votos<=0){
								$porcenToken=0;
							}
							else{
								$porcenToken = ($valor*100) / $total_votos;
							}
						}
						// Si no es númerico retornamos el nombre completo del campo
						else
						{
							if($clave=='votos_nulos') {
								$partido = "nulos.jpg";
							}
							else{
								$partido = "no_reg.jpg";
							}
							//$partido = $clave;
							
							// No es partido, pero si nulos o sin partido
							// calculamos porcentaje
							//$porcenToken = ($valor*100) / $total_votos;
							if($total_votos<=0){
								$porcenToken=0;
							}
							else{
								$porcenToken = ($valor*100) / $total_votos;
							}
						}
					}
					
				}
				else{
					$partido = '';
					if($clave == "votacion_total")  $porcenToken =100;
				}
				
				$porTmp = '0';
				if($porcenToken>0){
					$porTmp = sprintf("%01.4f", $porcenToken)."%";
				}
				
				if($clave=='votos_nulos'){
					$id ='nulos';
				}
				if($clave=='votos_cand_no_reg'){
					$id ='no_reg';
				}
				if($clave=='votacion_total'){
					$id ='votacion_total';
				}

				$rowTMP["id"]=$id;
				$rowTMP["campo"]=$clave;
				$rowTMP["valor"]=$valor!=null? $valor:0;
				$rowTMP["partido"]=$partido;
				$rowTMP["porcentaje"]=$porTmp;
				
				array_push($value_recordset, $rowTMP);
			}
			
			
			//array_push($value_recordset, $row);
			//$reg_data++;
			// if($type==1) break;
		}		
		
		$db->close();
		unset($db);	
		return $value_recordset;
}

// 02/abril/2024 se agrgan campos a votos_cand_no_reg y votos_nulos
function getRecordsetCarrousel($tmpSQL ="", $type = "1"){	
		// apertura de BD
		$db = new SQLite3('db/database.db3');
		
		$res_catch = $db->query($tmpSQL);
	
		if(!$res_catch) return null;
		
		$value_recordset = array();
		
		$db->enableExceptions(false);
		$registro = array();	
		// Recorro los registros
		while ($row = $res_catch->fetchArray(SQLITE3_ASSOC))
		{
			$itemCat = array();	
			$itemCat = consigueCat($db, 2 , $type );
			$registro = $row;
			
			$total_votos = $row["votacion_total"];

			$rowTMP = [];
			
			// CONTEO---------------------------------_
			$cuantos_par =0;
			$mod = 0;
			if(count($itemCat)>0){ 
				// Entró? existe datos a distribuir
				$id = 0;
				foreach ($itemCat as $rowTmp) {
					
						$id = $rowTmp["id_participante"];
						
						$votos_cc = 0;
						if($id ==10) {
							//PAN/PRI/PRD
							$votos_cc = $registro["total_votos_cc1"];
							$cuantos_par = 3;
						}
						if($id ==14) {
							//PVEM/PT/MORENA
							$votos_cc = $registro["total_votos_cc5"];
							$cuantos_par = 3;
						}
						
						
						$nombre = $rowTmp["nombre_campo1"];
						$porcent = $rowTmp["porcent"];
						
						$votos_a_repartir = ($porcent * $votos_cc)/100;
						
						$parte_entera = intval($votos_a_repartir);
						
						$anterior = $registro[$nombre];
						
						$registro[$nombre] = $parte_entera;
						
						/*
						echo "<br><br>Original ".$anterior." , con reparto ".$registro[$nombre]." Campo ".$nombre.", % ".$porcent.", aplicado% ".$parte_entera." mod ".$mod."<br><br>";
					*/	
				}
			}					
			
			//------------------------------------------
			$modC1=0;
			$cc1 = $registro["votos_part_1"] + $registro["votos_part_2"] + $registro["votos_part_3"];
			$totC1 = $registro["total_votos_cc1"];
			$modC1= $totC1 % 3;
			if($modC1>0){
				$registro["votos_part_1"] += 1;
			}
			
			//Checo reparto de mod en cc5 MORENA
			$modC5=0;
			$cc5 = $registro["votos_part_4"] + $registro["votos_part_5"] + $registro["votos_part_7"];
			$totC5 = $registro["total_votos_cc5"];
			$modC5= $totC5 % 3;
			if($modC5>0){
				$registro["votos_part_7"] += $modC5;
			}
			
			//Recorro el array de campos del registro
			foreach ($registro as $clave => $valor) {
				
				$claveCampo = $clave;
				$valorCampo = $valor;
			
				
				$partido = '';
				$id = '';
				$porcenToken = 0;
				if ((str_contains2($claveCampo, 'vot') && $claveCampo!="votacion_total") || str_contains2($claveCampo, 'votos_nulos') || str_contains2($claveCampo, 'votos_cand_no_reg')) {
					$token = explode("_", $claveCampo);
					$countToken = count($token);
					
					if($countToken>1){
						// traemos el nombre de campo, si es número es un partido
						if(is_numeric($token[$countToken-1])){
							$partido = $token[$countToken-1].".jpg";
							$id = $token[$countToken-1];
							// calculamos porcentaje
							if($total_votos<=0){
								$porcenToken=0;
							}
							else{
								$porcenToken=0;
								if($total_votos>0){
									$porcenToken = ($valorCampo*100) / $total_votos;
								}
								//$porcenToken = ($valorCampo*100) / $total_votos;
							}
						}
						// Si no es númerico retornamos el nombre completo del campo
						else
						{
							if($claveCampo=='votos_nulos') {
								$partido = "nulos.jpg";
							}
							else{
								$partido = "no_reg.jpg";
							}
							//$partido = $claveCampo;
							
							// No es partido, pero si nulos o sin partido
							// calculamos porcentaje
							if($total_votos<=0){
								$porcenToken=0;
							}
							else{
								$porcenToken = ($valorCampo*100) / $total_votos;
							}
						}
					}
					
				}
				else{
					$partido = '';
					if($claveCampo == "votacion_total")  $porcenToken =100;
				}
				
				$porTmp = '';
				if($porcenToken>0){
					$porTmp = sprintf("%01.4f", $porcenToken)."%";
				}
				
				
				if (str_contains2($claveCampo, 'votos_nulos')) {
					$id = "nulos";
					$desc = "Votos nulos";
					$siglas = "nulos";
				}
				if (str_contains2($claveCampo, 'votos_cand_no_reg') ) {
					$id = "no_reg";
					$desc = "No registrados";
					$siglas = "noreg";
				}
				
				// VERIFICO
				
				
				$rowTMP["id"]=$id;
				$rowTMP["campo"]=$claveCampo;
				$rowTMP["valor"]=$valorCampo!=null?$valorCampo:0;
				$rowTMP["partido"]=$partido;
				$rowTMP["porcentaje"]=$porTmp;
				
				array_push($value_recordset, $rowTMP);
			}
			
			
			//array_push($value_recordset, $row);
			//$reg_data++;
			// if($type==1) break;
		}		
		
		$db->close();
		unset($db);	
		return $value_recordset;
}

try {
	if($_SERVER['REQUEST_METHOD']=="GET"){
		$item ="";
		$type = trim(htmlentities($_GET["type"]));
		$item = trim(htmlentities($_GET["item"]));
		$item_2=""; $item_3="";
		if(isset($_GET["item_2"])) $item_2 = trim(htmlentities($_GET["item_2"]));
		if(isset($_GET["item_3"])) $item_3 = trim(htmlentities($_GET["item_3"]));
		
		$name_item ="";
		$whare = "";
		
		switch ($type) {
			case "1": // JG
				$name_item ="id_distrito";
				$whare = "  where id_distrito = ".$item;
				break;
			case "2":	//DMR
				$name_item ="id_distrito";
				$whare = "  where id_distrito = ".$item;
				break;
			case "3": //RP
				$name_item ="id_distrito";
				$whare = "  where id_distrito = ".$item;
				break;
			case "4": //ALC
				$name_item ="id_delegacion";
				$whare = "  where id_delegacion = ".$item;
				break;
		}
		
		// --- ABro bd
		//$db = new SQLite3('db/database.db3');
		//getFieldNameParticipan("4", " where id_delegacion = 2 ", "id_delegacion, ", " and id_seccion=22");
		$qryParticipantes =  getFieldNameParticipan($type, " where ".$name_item." = ".$item." ", $name_item.", ");
		
	//	echo $qryParticipantes; die();
		
		
		
		if($item_2!=""){
			$qryParticipantes .= " and id_seccion = ".$item_2;
			if($item_3!=""){
				$qryParticipantes .= " and tipo_casilla = '".$item_3."'";
			}
		}
		
		
	
		
		/*
		if($type==1){
			$qryParticipantes .= " where id_tipo_eleccion=".$type.";";
		}
		else{
			$qryParticipantes .= " and id_tipo_eleccion=".$type.";";	
		}
		*/
		$qryParticipantes .= " where contabilizar='T' and id_tipo_eleccion=".$type;
		if($item!=""){
			$qryParticipantes .= " and ".$name_item." = ".$item." ";

		}
///echo "!!!!! ".$qryParticipantes; die();
		//echo $qryParticipantes; return;
		if($type==1){
			$qryParticipantes = consigueDatosJG($type, $item);
			
		}
		if($type==2){
			$qryParticipantes = consigueDatosDMR($type, $item);
			
		}
		if($type==4){
			$qryParticipantes = consigueDatosALC($type, $item);
			
		}
		
		$db = new SQLite3('db/database.db3');
		$res_catch = $db->query($qryParticipantes);
		if(!$res_catch) return null;	
		$value_recordset = array();
		
		$db->enableExceptions(false);
		$registro = array();

		// Recorro los registros
		$i=1;
		$total_votos=0;
		while ($row = $res_catch->fetchArray(SQLITE3_ASSOC))
		{
			$rowTMP = [];
			$total_votos = $row["votacion_total"];
			
			foreach ($row as $clave => $valor) {
				$claveTMP = $clave;
				
				
				
				$id = $i."";
				$partidoTMP = $id."jpg";
				if($clave=="votos_part_8"){
					$id = "8";
				}
				if($clave=="votos_part_9"){
					$id = "9";
				}
				
				if($clave=="votos_cand_no_reg"){
					$id ="no_reg";
					$partidoTMP = "no_reg.jpg";
					
				}
				if($clave=="votos_nulos"){
					$id ="nulos";
					$partidoTMP = "nulos.jpg";
				}
				if($clave=="votacion_total"){
					$id ="";
					$partidoTMP = "votacion_total";
				}
				$porcenToken=0;
				if($total_votos<=0){
					$porcenToken=0;
				}
				else{
					$porcenToken = ($valor*100) / $total_votos;
				}
				
				$porTmp = sprintf("%01.4f", $porcenToken)."%";
				
				$rowTMP["id"]=$id;
				$rowTMP["campo"]=$claveTMP;
				$rowTMP["valor"]=$valor!=null?$valor:0;
				$rowTMP["partido"]=$id;
				$rowTMP["porcentaje"]=$porTmp;
				$i++;
				array_push($value_recordset, $rowTMP);
			}
			
		}
		
		
		if($type==1){
			$qryParticipantes = consigueDatosJG_EXTRANJERO($type, $item);
			$recordsExtranjero = getRecordsetExtranjero_JG($qryParticipantes);
		}
		
		
		if($type==2){
			$qryParticipantes = consigueDatosDMR_EXTRANJERO($type, $item);
			$recordsExtranjero = getRecordsetExtranjero_JG($qryParticipantes);
		}
		if($type==4){
			$qryParticipantes = consigueDatosALC_EXTRANJERO($type, $item);
			$recordsExtranjero = getRecordsetExtranjero_JG($qryParticipantes);
		}
		
		$itemRecords = array();
		
		$itemRecords["data"] = $value_recordset;
		$itemRecords["dataExtranjero"] = $recordsExtranjero;
		
		//echo json_encode($records);	
		echo json_encode($itemRecords);	
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