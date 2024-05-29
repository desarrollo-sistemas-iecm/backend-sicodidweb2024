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


function urbanasNoUrbanasCategoria($type, $item, $item_2, $item_3, $db)
{
	$type_param ="";
	if($type!="")
	{
		$type_param =$type;
	}
	else
	{
		$type_param=1;
	}
	$urbanas = "";
	$no_urbanas = "";
	$sqlNO_URBANA = "";
	$sqlURBANA = "";
	
	$record = array();
//	echo "URBANA";
//	echo $sqlURBANA ;
	
	
	$name_item ="";
	$whare = "";
	$and = " and ";
	
	switch ($type_param) {
		case "1": // JG
			$name_item ="id_distrito";
			$whare = " and id_distrito = ".$item;
			if($item==""){
				$name_item ="";
				$whare = "";
				
			}
			break;
		case "2":	//DMR
			$name_item ="id_distrito";
			$whare = " and id_distrito = ".$item;
			if($item==""){
				$name_item ="";
				$whare = "";
				
			}
			break;
		case "3": //RP
			$name_item ="id_distrito";
			$whare = " and id_distrito = ".$item;
			if($item==""){
				$name_item ="";
				$whare = "";
				
			}
			break;
		case "4": //ALC
			$name_item ="id_delegacion";
			$whare = " and id_delegacion = ".$item;
			if($item==""){
				$name_item ="";
				$whare = "";
				
			}
			break;
	}
		
	$sqlNO_URBANA = "SELECT count(id_distrito) as [NO_URBANAS] FROM scd_votos where clave_mdc in(SELECT clave_mdc FROM nourbanas) and id_tipo_eleccion='".$type_param."' and contabilizar='T' ".$whare;

	$sqlURBANA = "SELECT count(id_distrito) as [URBANAS] FROM scd_votos where clave_mdc not in(SELECT clave_mdc FROM nourbanas) and id_tipo_eleccion='".$type_param."' and contabilizar='T' ".$whare;
	
	
	
	if($item_2!=""){
		$sqlNO_URBANA .= " and id_seccion = ".$item_2;
		$sqlURBANA .= " and id_seccion = ".$item_2;
	}
	if($item_3!=""){
		$sqlNO_URBANA .= " and tipo_casilla = '".$item_3."';";
		$sqlURBANA .= " and tipo_casilla = '".$item_3."';";
	}
		
	
///	echo $sqlNO_URBANA; die();
	
	
	$res_catch = $db->query($sqlNO_URBANA);
	if(!$res_catch) return null;
	$db->enableExceptions(false);
	
	while ($row = $res_catch->fetchArray(SQLITE3_ASSOC))
	{
		//$var_catch = $row_catch['value']." - ".$row_catch['label']."<br>";
		$no_urbanas = "".$row["NO_URBANAS"]."";
		// if($type_param==1) break;
	}

	$res_catch = $db->query($sqlURBANA);
	if(!$res_catch) return null;
	$db->enableExceptions(false);
	
	while ($row = $res_catch->fetchArray(SQLITE3_ASSOC))
	{
		//$var_catch = $row_catch['value']." - ".$row_catch['label']."<br>";
		$urbanas = "".$row["URBANAS"]."";
		// if($type_param==1) break;
	}
		
	$recordUrbanasNoUrbanas = array(
			"urbanas" => $urbanas,
			"nourbanas" => $no_urbanas,
	);
	return $recordUrbanasNoUrbanas;
}



	function getEstadisticaResumen($db, $type, $item){
			
			$sqlEsperadas ="";
			$celTotalCaptura_F=0;  // actas capturadas
			$celTotalVotos_F =0;  // votación recibida
			$celTotalLN_F = 0;    // ln recibidos
			$celTotalNoReg_F =0;  // Votos de no registrados
			$celTotalNulos_F =0;		 // Votos nulos
			
			// Variables para filtro
			$sumaRP = 0;
			
			$campoFiltro = "id_distrito";
			if($type==4){
				$campoFiltro = "id_delegacion";
			}
			
			// SUFIJO
			$sufijo = "";

			switch ($type) {
				case 1:
					$sufijo = "_jg";
					break;
				case 2:
					$sufijo = "_dmr";
					break;
				case 3:
					$sufijo = "_rp";
					break;
				case 4:
					$sufijo = "_alc";
					break;					
				default:
				   die();
			}
			

			
			$sqlEsperadas = "SELECT count(estatus) as cuantos, sum(lista_nominal) as ln FROM scd_casillas where estatus='T' and ".$campoFiltro." = ".$item;

			// Nuevo: 24/Mayo/2021
			$sumaRP = 16;

			
			// apertura de BD
			//$db = new SQLite3('db/database.db3');
			// Contadores:
			$reg_data = 0;
			$reg_cat =0;
			
			$resTMP = $db->query($sqlEsperadas);
			$rowTMP = $resTMP->fetchArray();
				  $celTotalEsperadas_F = ($rowTMP["cuantos"] + $sumaRP);
				  $celTotalLNesperada_F = $rowTMP["ln"];
			 unset($rowTMP);
			 unset($resTMP);
			 
			// Registro general de BD
			$itemRecords = array();	
			
			
			// -----------------------------------------------------
			// PARA CAPTURADAS: 13/Mayo/2021
			//---------------------------------------------------------
			// SUMATORIAS PARA RESUMEN
			$acumulado =0;
			$no_reg =0;
			$nulo =0; 
			$total =0;
			$votos_acumulados_por ="0%";
			$candidatos_no_reg_por = "0%";
			$nulos_por = "0%";
			$total_por = "0%";
			/*
			echo "SELECT id_tipo_eleccion,  count(C.id_distrito) as [cuantos], sum(C.lista_nominal) as ln, sum(votos_cand_no_reg) as [votos_cand_no_reg], sum(votos_nulos)  as [votos_nulos],
			 sum(votacion_total) as votacion_total FROM scd_votos P
			left join scd_casillas C 
			on P.id_distrito = C.id_distrito and P.id_delegacion = C.id_delegacion and P.id_seccion = C.id_seccion and P.tipo_casilla = C.tipo_casilla where P.".$campoFiltro." = ".$item." and id_tipo_eleccion = ".$type;
			die();*/
			
			$res_catch = $db->query("SELECT id_tipo_eleccion,  count(C.id_distrito) as [cuantos], sum(C.lista_nominal) as ln, sum(votos_cand_no_reg) as [votos_cand_no_reg], sum(votos_nulos)  as [votos_nulos],
			 sum(votacion_total) as votacion_total FROM scd_votos P
			left join scd_casillas C 
			on P.id_distrito = C.id_distrito and P.id_delegacion = C.id_delegacion and P.id_seccion = C.id_seccion and P.tipo_casilla = C.tipo_casilla where P.".$campoFiltro." = ".$item." and id_tipo_eleccion = ".$type);	
			

			$itemRecords["capturadas_jg"] = array();
			$itemRecords["capturadas_alc"] = array();
			$itemRecords["capturadas_dmr"] = array();
			$itemRecords["capturadas_rp"] = array();
			
			$db->enableExceptions(false);
			while ($row = $res_catch->fetchArray(SQLITE3_ASSOC))
			{
					$celTotalCaptura_F = $row["cuantos"];
					$celTotalLN_F = $row["ln"];
					$celTotalVotos_F = $row["votacion_total"];
					// Cargamos variables
					if($celTotalLN_F>0)
					 {
						$celPartipa_F = number_format((($celTotalVotos_F*100)/$celTotalLN_F), 4, '.', ',');
					 }
					 else
					 {
						 $celTotalLN_F ="0";
						$celPartipa_F ="0.0000";
					 }


					 if($celTotalEsperadas_F>0)
					 {
						$celPorcentRecivido_F = number_format((($celTotalCaptura_F*100)/$celTotalEsperadas_F), 4, '.', ',');
						
					 }
					 else
					 {
						 $celTotalEsperadas_F = "0";
						 $celPorcentRecivido_F = "0.0000";
					 }	 
					$capturadas = array(
						"actas_capturadas" => $celTotalCaptura_F,
						"actas_capturadas_de" => $celTotalEsperadas_F,
						"actas_cap_porcen" => $celPorcentRecivido_F,
						"participacion_porcen" => $celPartipa_F,
						"ln" => $celTotalLN_F
					);	
					
					switch ($row['id_tipo_eleccion']) {
						case 1:
							// Guardo datos en array para JSON
							$itemRecords["capturadas_jg"]= [$capturadas];
							break;
						case 2:
							// Guardo datos en array para JSON
							$itemRecords["capturadas_dmr"]= [$capturadas];

							break;
						case 3:
							// Guardo datos en array para JSON
							$itemRecords["capturadas_rp"]= [$capturadas];

							break;
						case 4:
							// Guardo datos en array para JSON
							$itemRecords["capturadas_alc"]= [$capturadas];

							break;
					}				
			}
			
			
			
			 
			// -----------------------------------------------------
			// PARA CAPTURADAS Y CONTABILIZADAS: 13/Mayo/2021
			//---------------------------------------------------------
			$res_catch = $db->query("SELECT id_tipo_eleccion, count(C.id_distrito) as [cuantos], sum(C.lista_nominal) as ln, sum(votos_cand_no_reg) as [votos_cand_no_reg], sum(votos_nulos)  as [votos_nulos],
			 sum(votacion_total) as votacion_total FROM scd_votos P
			left join scd_casillas C 
			on P.id_distrito = C.id_distrito and P.id_delegacion = C.id_delegacion and P.id_seccion = C.id_seccion and P.tipo_casilla = C.tipo_casilla
			where id_tipo_eleccion = ".$type." and P.".$campoFiltro." = ".$item." group by  id_tipo_eleccion");	
			
			// Contabilizadas
			$cont_t1 =0;
			$cont_t2 =0;
			$cont_t3 =0;
			$cont_t4 =0;
			
			$cont_ln1 =0;
			$cont_ln2 =0;
			$cont_ln3 =0;
			$cont_ln4 =0;
			
			// SUMATORIAS PARA RESUMEN
			$acumulado =0;
			$no_reg =0;
			$nulo =0; 
			$total =0;
			$votos_acumulados_por ="0%";
			$candidatos_no_reg_por = "0%";
			$nulos_por = "0%";
			$total_por = "0%";
			
			$res_catch2 = $db->query("SELECT id_tipo_eleccion, count(C.id_distrito) as [cuantos], sum(C.lista_nominal) as ln, sum(votos_cand_no_reg) as [votos_cand_no_reg], sum(votos_nulos)  as [votos_nulos],
			 sum(votacion_total) as votacion_total FROM scd_votos P
			left join scd_casillas C 
			on P.id_distrito = C.id_distrito and P.id_delegacion = C.id_delegacion and P.id_seccion = C.id_seccion and P.tipo_casilla = C.tipo_casilla
			where contabilizar='T' and id_tipo_eleccion = ".$type." and P.".$campoFiltro." = ".$item." group by  id_tipo_eleccion");	
			while ($row = $res_catch2->fetchArray(SQLITE3_ASSOC))
			{
				// Cargamos variables
				$celTotalCaptura_F = $row["cuantos"];
				$celTotalVotos_F = $row["votacion_total"];
				// 19/marzo/2024
				$celLNcont = $row["ln"];
				 if($celTotalEsperadas_F>0)
				 {
					$celPorcentRecivido_F = number_format((($celTotalCaptura_F*100)/$celTotalEsperadas_F), 4, '.', ',');
					
				 }
				 else
				 {
					 $celTotalEsperadas_F = "0";
					 $celPorcentRecivido_F = "0.0000";
				 }	 

				 switch ($row['id_tipo_eleccion']) {
						case 1:
							// Guardo datos en array para JSON
							$cont_t1 =$celTotalCaptura_F;
							$cont_ln1 =$celLNcont;
							break;
						case 2:
							// Guardo datos en array para JSON
							$cont_t2 =$celTotalCaptura_F;
							$cont_ln2 =$celLNcont;

							break;
						case 3:
							$cont_t3 =$celTotalCaptura_F;
							// Guardo datos en array para JSON
							$cont_ln3 =$celLNcont;

							break;
						case 4:
							$cont_t4 =$celTotalCaptura_F;
							// Guardo datos en array para JSON
							$cont_ln4 =$celLNcont;

							break;
					}		
				 				
			}
			
			// ESPECIALES
			//SELECT sum(votacion_total)  FROM scd_votos  where id_tipo_eleccion=1 and substr(tipo_casilla,1,1) ='S';
			$cont_esp1 =0;
			$cont_esp2 =0;
			$cont_esp3 =0;
			$cont_esp4 =0;
			$res_catch2 = $db->query("SELECT id_tipo_eleccion, count(C.id_distrito) as [cuantos], sum(C.lista_nominal) as ln, sum(votos_cand_no_reg) as [votos_cand_no_reg], sum(votos_nulos)  as [votos_nulos],
			 sum(votacion_total) as votacion_total FROM scd_votos P
			left join scd_casillas C 
			on P.id_distrito = C.id_distrito and P.id_delegacion = C.id_delegacion and P.id_seccion = C.id_seccion and P.tipo_casilla = C.tipo_casilla
			where substr(P.tipo_casilla,1,1) ='S' and contabilizar='T' and id_tipo_eleccion = ".$type." and P.".$campoFiltro." = ".$item." group by  id_tipo_eleccion");	
			while ($row = $res_catch2->fetchArray(SQLITE3_ASSOC))
			{
				// Cargamos variables
				$celTotalCaptura_F = $row["cuantos"];
				$celTotalVotos_F = $row["votacion_total"];
				// 19/marzo/2024

				 if($celTotalVotos_F>0)
				 {
					$celTotalVotos_F = number_format($celTotalVotos_F , 0, '.', ',');
					
				 }
				 else
				 {
					 $celTotalVotos_F = "0";
				 }	 

				 switch ($row['id_tipo_eleccion']) {
						case 1:
							// Guardo datos en array para JSON
							$cont_esp1 =$celTotalVotos_F;
							break;
						case 2:
							// Guardo datos en array para JSON
							$cont_esp2 =$celTotalVotos_F;
		
							break;
						case 3:
							$cont_esp3 =$celTotalVotos_F;

							break;
						case 4:
							$cont_esp4 =$celTotalVotos_F;

							break;
					}		
				 				
			}
			
			//---------------------------------------
			
			// SIN ESPECIALES
			//SELECT sum(votacion_total)  FROM scd_votos  where id_tipo_eleccion=1 and substr(tipo_casilla,1,1) ='S';
			$cont_s_esp1 =0;
			$cont_s_esp2 =0;
			$cont_s_esp3 =0;
			$cont_s_esp4 =0;
			$res_catch2 = $db->query("SELECT id_tipo_eleccion, count(C.id_distrito) as [cuantos], sum(C.lista_nominal) as ln, sum(votos_cand_no_reg) as [votos_cand_no_reg], sum(votos_nulos)  as [votos_nulos],
			 sum(votacion_total) as votacion_total FROM scd_votos P
			left join scd_casillas C 
			on P.id_distrito = C.id_distrito and P.id_delegacion = C.id_delegacion and P.id_seccion = C.id_seccion and P.tipo_casilla = C.tipo_casilla
			where substr(P.tipo_casilla,1,1) <>'S' and contabilizar='T' and id_tipo_eleccion = ".$type." and P.".$campoFiltro." = ".$item." group by  id_tipo_eleccion");	
			while ($row = $res_catch2->fetchArray(SQLITE3_ASSOC))
			{
				// Cargamos variables
				$celTotalCaptura_F = $row["cuantos"];
				$celTotalVotos_F = $row["votacion_total"];
				// 19/marzo/2024

				 if($celTotalVotos_F>0)
				 {
					$celTotalVotos_F = number_format($celTotalVotos_F , 0, '.', ',');
					
				 }
				 else
				 {
					 $celTotalVotos_F = "0";
				 }	 

				 switch ($row['id_tipo_eleccion']) {
						case 1:
							// Guardo datos en array para JSON
							$cont_s_esp1 =$celTotalVotos_F;
							break;
						case 2:
							// Guardo datos en array para JSON
							$cont_s_esp2 =$celTotalVotos_F;
		
							break;
						case 3:
							$cont_s_esp3 =$celTotalVotos_F;

							break;
						case 4:
							$cont_s_esp4 =$celTotalVotos_F;

							break;
					}		
				 				
			}		
			
			
			
			
			//---------------------------------------
			
			$itemRecords["avance_jg"] = array();
			$itemRecords["avance_alc"] = array();
			$itemRecords["avance_dmr"] = array();
			$itemRecords["avance_rp"] = array();
			

			$itemRecords["resumen_jg"] = array();
			$itemRecords["resumen_alc"] = array();
			$itemRecords["resumen_dmr"] = array();
			$itemRecords["resumen_rp"] = array();
			
			$db->enableExceptions(false);
			
			
			// SUMATORIAS PARA RESUMEN
			$acumulado =0;
			$no_reg =0;
			$nulo =0; 
			$total =0;
			$votos_acumulados_por ="0%";
			$candidatos_no_reg_por = "0%";
			$nulos_por = "0%";
			$total_por = "0%";
				
			while ($row = $res_catch->fetchArray(SQLITE3_ASSOC))
			{
				$celTotalCaptura_F = $row["cuantos"];
				$celTotalLN_F = $row["ln"];
				$celTotalVotos_F = $row["votacion_total"];
		
		///echo $celTotalLN_F; die();		
				// Cargamos variables
				if($celTotalLN_F>0)
				 {
					$celPartipa_F = number_format((($celTotalVotos_F*100)/$celTotalLN_F), 4, '.', ',');
				 }
				 else
				 {
					$celTotalLN_F ="0";
					$celPartipa_F ="0.0000";
				 }


				 if($celTotalEsperadas_F>0)
				 {
					$celPorcentRecivido_F = number_format((($celTotalCaptura_F*100)/$celTotalEsperadas_F), 4, '.', ',');
					
				 }
				 else
				 {
					 $celTotalEsperadas_F = "0";
					 $celPorcentRecivido_F = "0.0000";
				 }	 
				 $tmpSum =0;
				 
				 $tmpln =0;
				 
				 $tmpEsp = 0;
				 
				 $tmpSinEsp =0;
				switch ($row['id_tipo_eleccion']) {
						case 1:
							// Guardo datos en array para JSON
							$tmpSum = $cont_t1;
							$tmpln = $cont_ln1;
							
							$tmpEsp = $cont_esp1;
							$tmpSinEsp = $cont_s_esp1;
							break;
						case 2:
							// Guardo datos en array para JSON
							$tmpSum = $cont_t2;
							$tmpln = $cont_ln2;
								$tmpEsp = $cont_esp2;
								$tmpSinEsp = $cont_s_esp2;
							break;
						case 3:
							$tmpSum = $cont_t3;
							$tmpln = $cont_ln3;
							// Guardo datos en array para JSON
							$tmpEsp = $cont_esp3;
							$tmpSinEsp = $cont_s_esp3;
							

							break;
						case 4:
							$tmpSum = $cont_t4;
							// Guardo datos en array para JSON
							$tmpln = $cont_ln4;
							$tmpEsp = $cont_esp4;
							$tmpSinEsp = $cont_s_esp4;
							break;
				}	
				
				$avance = array(
					"actas_capturadas" => $celTotalCaptura_F,
					"actas_capturadas_de" => $celTotalEsperadas_F,
					"actas_cap_porcen" => $celPorcentRecivido_F,
					"participacion_porcen" => $celPartipa_F,
					"ln" => $celTotalLN_F,
					"contabilizadas"=> $tmpSum,
					"ln_contabilizadas"=>$tmpln
				);
				
				
				// SUMATORIAS PARA RESUMEN
				$acumulado = $row["votacion_total"] - $row["votos_cand_no_reg"] - $row["votos_nulos"];
				$no_reg = $row["votos_cand_no_reg"]; 
				$nulo =  $row["votos_nulos"]; 
				$total = $row["votacion_total"];
				
				$votos_acumulados_por =0;
				$candidatos_no_reg_por = "0%";
				$nulos_por = "0%";
				$total_por = "0%";
				
				//
				$lista_nominal = $row["ln"];
				// registro de resumen
				//$acu_por = 0;
				if($total>0){
					if($acumulado>0){
						$votos_acumulados_por = ($acumulado *100) /$total;
					}
					
					if($no_reg>0){
						$candidatos_no_reg_por = ($no_reg *100) /$total;
					}
					
					if($nulo>0){
						$nulos_por = ($nulo *100) /$total;
					}
				}
				
				$texto_sin_coma1 = str_replace(',', '', $tmpEsp);
				$texto_sin_coma2 = str_replace(',', '', $tmpSinEsp);
				$tmpSumaEspNoEsp = intval($texto_sin_coma1) + intval($texto_sin_coma2);
				$tmpSumaEspNoEsp= number_format($tmpSumaEspNoEsp, 0, '.', ',');
				$resumen = array(
					"votos_acumulados" => $acumulado,
					"candidatos_no_reg" => $no_reg,
					"nulos" => $nulo,
					"total" => $total,
					"votos_acumulados_por" => sprintf("%01.4f", $votos_acumulados_por)."%",
					"candidatos_no_reg_por" => sprintf("%01.4f", $candidatos_no_reg_por)."%",
					"nulos_por" => sprintf("%01.4f", $nulos_por)."%",
					"total_por" => "100.0000%",
					"voto_especiales"=>$tmpEsp,
					"voto_sin_especiales"=>$tmpSinEsp,
					"suma_esp_sinesp"=> $tmpSumaEspNoEsp,
					"ln"=>$celTotalLNesperada_F
				);
				
				switch ($row['id_tipo_eleccion']) {
					case 1:
						// Guardo datos en array para JSON
						$itemRecords["avance_jg"]= [$avance];
						$itemRecords["resumen_jg"]= [$resumen];
						break;
					case 2:
						// Guardo datos en array para JSON
						$itemRecords["avance_dmr"]= [$avance];
						$itemRecords["resumen_dmr"]= [$resumen];
						break;
					case 3:
						// Guardo datos en array para JSON
						$itemRecords["avance_rp"]= [$avance];
						$itemRecords["resumen_rp"]= [$resumen];
						break;
					case 4:
						// Guardo datos en array para JSON
						
						$itemRecords["avance_alc"]= [$avance];
						$itemRecords["resumen_alc"]= [$resumen];
						break;
				}
			}
			///----------------------------------------------------------
			
			// Para retornar solo pericion:
			
			switch ($type) {
				case 1:
					$sufijo = "_jg";
					break;
				case 2:
					$sufijo = "_dmr";
					break;
				case 3:
					$sufijo = "_rp";
					break;
				case 4:
					$sufijo = "_alc";
					break;					
				default:
				   die();
			}
			
			$itemFilter["eleccion"] = $type;
			$itemFilter["item"] = $item;
			$itemFilter["avance"] = array();
			$itemFilter["capturadas"] = array();
			$itemFilter["resumen"] = array();
			
			$itemFilter["avance"] = $itemRecords["avance".$sufijo];
			$itemFilter["capturadas"] = $itemRecords["capturadas".$sufijo];
			$itemFilter["resumen"] = $itemRecords["resumen".$sufijo];
			
			// checar urbanas y no urbanas
			$recorUrbanas = urbanasNoUrbanasCategoria($type, $item, "", "", $db);
			$itemFilter["urbanas_nourbanas"] = [$recorUrbanas];
			return $itemFilter;
		
	}
	
try {
	if($_SERVER['REQUEST_METHOD']=="GET"){
			
		$type = trim(htmlentities($_GET["type"]));
		$item = trim(htmlentities($_GET["item"]));		
		
		$db = new SQLite3('db/database.db3');

		$records = getEstadisticaResumen($db, $type, $item);
		echo json_encode($records);	
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
?>