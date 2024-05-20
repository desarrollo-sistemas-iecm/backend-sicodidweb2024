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
/*
-- Así se arma fila:
	$fila = ['P1'=>$totalVotosPar[0], 'P2'=>$totalVotosPar[1], 'P3'=>$totalVotosPar[2], 'P4'=>$totalVotosPar[3], 'P5'=>$totalVotosPar[4], 'P6'=>$totalVotosPar[5], 'P7'=>$totalVotosPar[6], 'P8'=>$totalVotosPar[7], 'P9'=>$totalVotosPar[8],'P10'=>$totalVotosPar[9],'P11'=>$totalVotosPar[10], 'COAL1'=>$totalVotosPar[11], 'COAL2'=>$totalVotosPar[12], 'COAL3'=>$totalVotosPar[13], 'COAL4'=>$totalVotosPar[14], 'COAL5'=>$totalVotosPar[15], 'COAL6'=>$totalVotosPar[16], 'COAL7'=>$totalVotosPar[17], 'COAL8'=>$totalVotosPar[18], 'CI1'=>$totalVotosPar[19], 'CI2'=>$totalVotosPar[20], 'CI3'=>$totalVotosPar[21], 'CI4'=>$totalVotosPar[22], 'CI9'=>$totalVotosPar[23], 'CI11'=>$totalVotosPar[24], 'CI12'=>$totalVotosPar[25]];
	
-- Y se usa así
	// Saco mayor
	$rankedScores = setRankings($fila);
*/

/* FUNCIÓN PARA OBTENER RANKING DE UN ARRAY  */
function setRankingsItems($standings) {
    $rankings = array();
    arsort($standings);
    $rank = 1;
    $tie_rank = 0;
    $prev_score = -1;

	$empatado="";
    foreach ($standings as $name => $score) {
        if ($score != $prev_score) {  //this score is not a tie
            $count = 0;
            $prev_score = $score;
            $rankings[$name] = array('score' => $score, 'rank' => $rank, 'tie' => 0 , 'empatado' => '');
        } else { //this score is a tie
            $prev_score = $score;
            if ($count++ == 0) {
                $tie_rank = $rank - 1;
            }
            $rankings[$name] = array('score' => $score, 'rank' => $tie_rank, 'tie' => 1, 'empatado' => $empatado);
        }
        $rank++;
		if($empatado!=$name) $empatado = $name;
    }
    return $rankings;
}

// FUNCION QUE RETORNA EL GANADOR DEL RANKEO DE UN REGISTRO (ROW[0])
function returnWinner($standings){
	/*
	"P1": {
		"score": 9092,
		"rank": 1,
		"tie": 0,
		"empatado": ""
	  },
	*/
	if(!$standings){
		return ["Nulo" => "0"];
	}
	if(count($standings)>=2){
		$rankedScores = setRankingsItems($standings);
		// Checamos si el ranking mayor es cero
		// $name -> partido, $score [array con resultados]
		$itera = 0;
		$nameItem1="";
		$nameItem2="";
		$item1 = null;
		$item2 = null;
		foreach ($rankedScores as $name => $score) {
			//echo $name."----".$score["score"];
			if($itera==0){
				// cero votos en 1er lugar
				if($score["score"]<=0){
					return ["Sin datos" => "0"];
				}
				$nameItem1 = $name;
				//$item1 = [$name => $score];
				$item1[$name] = $score;
				/*
				echo "ITEM 1<br>";
				var_dump($item1);
				echo "<br><br>";
				*/
			}
			if($itera==1){
				// empate
				$nameItem2 = $name;
				$item2[$name] = $score;
				/*
				echo "ITEM 2<br>";
				var_dump($item2);
				echo "<br><br>";
				*/
				break;
			}		
			$itera++;
		}
		
		if($item2[$nameItem2]["tie"]==1){
			return ["Empate" => $item2[$nameItem2]["tie"]];
		}
		else{
			return $item1;
		}
		
	}
	else
	{
		return null;
	}
	
	//return $rankedScores;
}



// Consigue todos nombres de los campos a consultar en votación según la elección, estó 
// para armar el la sentencia SELECT que traerá toda la información.
// $where y $campoExtra los uso para traer todo por DTO / DEL
function getFieldNameElection($type, $db, $where="", $campoExtra=""){
			
		// AQUI CONSIGO LOS CAMPOS A MOSTRAR POR ELECCION
		$qryParticipantes = "";  
		switch ($type) {
			case 1:
				$qryParticipantes = 'select DISTINCT '.$campoExtra.' JG.id_participante, JG.campo_votos, P.siglas, P.descripcion from scd_candidatos_jgob JG
				left join scd_cat_participantes P
				on JG.id_participante = P.id_participante'.$where.
				' order by '.$campoExtra.' JG.prelacion;';
				break;
			case 2:
				$qryParticipantes = 'select DISTINCT '.$campoExtra.' MR.id_participante, MR.campo_votos, 	P.siglas, P.descripcion from scd_candidatos_mr MR
				left join scd_cat_participantes P
				on MR.id_participante = P.id_participante'.$where.
				' order by '.$campoExtra.' MR.prelacion';
				break;
			case 3:  //RP
				$qryParticipantes = '';
				break;
			case 4:
					$qryParticipantes = 'select DISTINCT '.$campoExtra.' JD.id_participante, JD.campo_votos, P.siglas, P.descripcion from scd_candidatos_jdel JD
					left join scd_cat_participantes P
					on JD.id_participante = P.id_participante'.$where.
					' order by '.$campoExtra.' JD.prelacion;';
				break;
		}
		
		
			
//echo $qryParticipantes; die();		
		// apertura de BD
		$reg_data=0;
		//$db = new SQLite3('db/database.db3');
		$res_catch = $db->query($qryParticipantes);
	
		if(!$res_catch) return null;
		
		$value_records["value_fields"] = array();
		
		$db->enableExceptions(false);
		while ($row = $res_catch->fetchArray(SQLITE3_ASSOC))
		{
			//$var_catch = $row_catch['value']." - ".$row_catch['label']."<br>";
			array_push($value_records["value_fields"], $row);
			$reg_data++;
			// if($type==1) break;
		}
		
		return $value_records;
}

// Función para mapear el nombre de campo y sustituir por las SIGLAS
function getMapByName($value_records){
	$mapeo_name = [];
	if(!$value_records) return null;
	
	foreach ($value_records["value_fields"] as $clave => $valor) {
				
				// Para buscar las Siglas con el campo llave de "campo_votos"
				$mapeo_name[$valor["campo_votos"]]["siglas"] = $valor["campo_votos"];//$valor["siglas"];
				$mapeo_name[$valor["campo_votos"]]["descripcion"] = $valor["descripcion"];
				$mapeo_name[$valor["campo_votos"]]["id_participante"] = $valor["id_participante"];
				
	}
	
	return $mapeo_name;
}

// Función para mapear el nombre de campo y traer por el ID
function getMapById($value_records){
	if(!$value_records) return null;
	$mapeo_id = [];
	foreach ($value_records["value_fields"] as $clave => $valor) {
		$mapeo_id[ $valor["campo_votos"]] = $valor["id_participante"];		
	}
	
	return $mapeo_id;
}

// Función para armar la sentencia SQL con los los nombres de campos obtenidos de catálogo
function getSQLdata($value_records, $type){
	
	if(!$value_records) return null;
	$participanData = "";
	$coma = "";
	
	//18/Abril/2024
	$corteControl ="";
	$tipo_eleccion = "";
	
	// Tipo de filtro y corte de control
	$corteControl = ($type==4? "id_delegacion":"id_distrito");
	
	//22/Abril/2024
	// Separamos tipo de elección de la variable GROUP
	if($type==2){
		$tipo_eleccion = " id_tipo_eleccion in(2,3) ";
	}
	else{
		$tipo_eleccion = " id_tipo_eleccion = ".$type;
	}
	
	// Tipo de filtro y corte de control
	$corteControl = ($type==4? "id_delegacion":"id_distrito");
	
	
	/////------------------------------------------------------------
	/////------------------------------------------------------------
	/////------------------------------------------------------------
	///// OJO!!!!  AQUI PUSE LO DE CERO
	/////------------------------------------------------------------
	/////------------------------------------------------------------
	/////------------------------------------------------------------
	
	
	$group = " from prep_votos where ".$tipo_eleccion." and contabilizar='T' and id_distrito>0 and id_delegacion>0 group by ".$corteControl;
	
	foreach ($value_records["value_fields"] as $clave => $valor) {
		
		$participanData .= ($coma.$valor["campo_votos"]);
		
		//$participanSUM .= ($coma.' SUM('.$valor["campo_votos"].') as '.$valor["campo_votos"]);
		
		$coma = ", ";
	}

//echo "select ".$corteControl.", ".$participanData.$group.";----- <br><br><br>"; die();

	return "select ".$corteControl.", ".$participanData.$group.";";
	
	
}

// Función para armar la sentencia SQL de sumatoria con los los nombres de campos obtenidos de catálogo
function getSQLsum($value_records, $type){
	
	if(!$value_records) return null;
	$participanSuma = "";
	$coma = "";
	
	// Typo de filtro y corte de control
	//$corteControl = ($type==4? "id_delegacion":"id_distrito");
	// Tipo de filtro y corte de control
	$corteControl = ($type==4? "id_delegacion":"id_distrito");
	
	//22/Abril/2024
	// Separamos tipo de elección de la variable GROUP
	$tipo_eleccion="";
	if($type==2){
		$tipo_eleccion = " id_tipo_eleccion in(2,3) ";
	}
	else{
		$tipo_eleccion = " id_tipo_eleccion = ".$type;
	}
	
	$group = " from prep_votos where contabilizar='T' and ".$tipo_eleccion." and ".$corteControl.">0 group by ".$corteControl;
	
	
	foreach ($value_records["value_fields"] as $clave => $valor) {
				$participanSuma .= ($coma.' SUM('.$valor["campo_votos"].') as '.$valor["campo_votos"]);
				$coma = ", ";
	}
	
	return "select ".$corteControl.", ".$participanSuma.$group.";";
}

function getSQLsumJoinLN($value_records, $type, $typeCasilla =""){
	
	if(!$value_records) return null;
	
	// Agrego group by de letra}

	$caracter = "";
	if($typeCasilla!=""){
		$caracter = " and substr(P.tipo_casilla,1,1) = '".$typeCasilla."'";

	}
	
	
	$participanSuma = "";
	$coma = "";
	$extras = " sum(votacion_total) as [votacion_total], sum(boletas_sob) as [boletas_sob], sum(ciudadanos_votaron) as [ciudadanos_votaron], sum(representantes_votaron) as [representantes_votaron], sum(total_votaron) as [total_votaron], sum(boletas_extraidas) as [boletas_extraidas], sum(lista_nominal) as lista_nominal, sum(votos_cand_no_reg) as votos_cand_no_reg, sum(votos_nulos) as votos_nulos ";
	
	// Typo de filtro y corte de control
	$corteControl = ($type==4? "id_delegacion":"id_distrito");
	
	$from = " from prep_votos P ";
	$join = " left join scd_casillas C
		on P.id_distrito = C.id_distrito and P.id_delegacion = C.id_delegacion and 
		P.id_seccion = C.id_seccion and P.tipo_casilla = C.tipo_casilla ";
	$group = " group by id_tipo_eleccion, P.".$corteControl." ";
	
	// 16/abril/2024
	// cambio en DIPUTADOS
	$where = " where P.contabilizar='T' and id_tipo_eleccion=".$type.$caracter;
	if($type==2){
		$where = " where P.contabilizar='T' and id_tipo_eleccion in(2,3)".$caracter;
	}
	
	
	foreach ($value_records["value_fields"] as $clave => $valor) {
				$participanSuma .= ($coma.' SUM('.$valor["campo_votos"].') as '.$valor["campo_votos"]);
				$coma = ", ";
	}
	/*
	echo "select C.".$corteControl.", P.id_tipo_eleccion, ".$participanSuma.", ".$extras.$from.$join.$where.$group."";
	die();
*/
	return "select C.".$corteControl.", P.id_tipo_eleccion, ".$participanSuma.", ".$extras.$from.$join.$where.$group."";
}

function getPartidos($value_records){
	$mapeo_id =[];
	
	if(!$value_records) return null;
	foreach ($value_records["value_fields"] as $clave => $valor) {
				//$mapeo_id[$valor["siglas"]] = $valor["id_participante"];
				$mapeo_id[ $valor["campo_votos"]] = $valor["siglas"];
	}
	
	return $mapeo_id;
}


// Consigue Los datos a mostrar en Mapas/Grids de ganadores por elección
function getRecordSetMap($type, $db){
		$tmp = [];		
		// Querys para traer datos para ranking
		$sqlRegistros ="";
		$sqlSumatorias ="";
		// Typo de filtro y corte de control
		//$corteControl = ($type==4? "id_delegacion":"id_distrito");
		//$group = " from prep_votos where id_tipo_eleccion=".$type." group by ".$corteControl;
				
		//$db = new SQLite3('db/database.db3');
		
		$participan = "";
		$participanSUM = "";
		$coma = "";
		$mapeo_nombre = [];
		$mapeo_id =[];
		
		// 1. Consigue campos a consultar para armar la consulta
		$value_records = getFieldNameElection($type, $db);
		
//echo "<br><br>SUMA ".var_dump($value_records)."<br><br>";die();

		// 2. Mapeamos nombre
		$mapeo_nombre = getMapByName($value_records);

		//---------------------------------------------------
		// 3. Mapeamos ID para obteners id dependiendo de campo guardado en BD
		$mapeo_id = getMapById($value_records);
		
//		echo "<br><br>SUMA ".var_dump($mapeo_id)."<br><br>";die();
		// 4. Conseguimos SQL armado para datos
		//----------------------------------------------
		// Datos: Armamos solo los campos a traer con el recorset de PARTICIPANTES
		//        y los querys para los datos a consultar por elección
		//----------------------------------------------------
		$sqlRegistros = getSQLdata($value_records, $type);   // 
		
		//echo $sqlRegistros; echo "<br><br>"; 
		
		$sqlSumatorias = getSQLsum($value_records, $type);
		
		//echo $sqlSumatorias; die;
	
	//echo "<br><br>SUMA ".$sqlSumatorias."<br><br>";die();
	
		$sqlDataAll = getSQLsumJoinLN($value_records, $type);

		
		//---------------------------------------------
		// Cargamos las sumatorias
		
		$items_winner = array();
		
		if(!$sqlSumatorias) return null;
		
		$res_catch = $db->query($sqlSumatorias);
		while ($row = $res_catch->fetchArray(SQLITE3_ASSOC))
		{
			$registro = [];
			$pos = "";
			foreach ($row as $clave => $valor) {
				if($clave!='id_delegacion' && $clave!='id_distrito'){
					$registro[$clave] = $valor;
					//$registro[$mapeo_nombre[$clave]["siglas"]] = $valor;
				}
				else{
					$pos = $valor;
				}
				
			}
			
/*
			echo " <br> <br>REGISTRO $pos: <br> ";
			var_dump($registro);
			return;
*/			
			
			// Obtenemos GANADOR:
			$rankedScores = returnWinner($registro);
			

			/////echo "<br><br>GANADOR!!!<br><br>";
			
			$tmp = [];
			
			// 09/abril/2024
			// Se programa validacion de inicio ya que cuando inicia trae un array vacio: LINEAS 392 a 1
			foreach ($rankedScores as $clave => $valor) {
					//////if($clave=='Empate') continue;
					//echo ".-----".$clave."<br>";
					//echo ".+++++".$valor["score"]."<br>";
					//echo "<br><br>";
					if(isset($valor["score"])){
						$tmp = [
							"item"=>$pos,
							"number"=> $valor["score"]? $valor["score"]: 0, 
							"name"=>$clave, 
							"rank"=>1, 
							"id_participante" => $mapeo_id[$clave].""
						];
					}
					else{
						$tmp = [
						/*
							"item"=>0,
							"number"=> 0, 
							"name"=>'NA!!!', 
							"rank"=>1, 
							"id_participante" => 'SN'.""
						*/];
						
					}
					break;
			}
			
			
			////var_dump($rankedScores);
			////echo "<br><br>"; 
			// GUARDAMOS GANADORES
			$items_winner[] = $tmp;	
		}
		
		return $items_winner;
}

// Consigue Los datos de las elecciones por elección
function getRecordSetData($type, $db){
		$tmp = [];		
		// Querys para traer datos para ranking
		$sqlRegistros ="";
		$sqlDataAll ="";
		// Typo de filtro y corte de control
		//$corteControl = ($type==4? "id_delegacion":"id_distrito");
		//$group = " from prep_votos where id_tipo_eleccion=".$type." group by ".$corteControl;
				
		//$db = new SQLite3('db/database.db3');
		
		$participan = "";
		$participanSUM = "";
		
		// 1. Consigue campos a consultar para armar la consulta
		$value_records = getFieldNameElection($type, $db);
		
		// 2. Conseguimos SQL armado para datos
		//----------------------------------------------
		// Datos: Armamos solo los campos a traer con el recorset de PARTICIPANTES
		//        y los querys para los datos a consultar por elección
		//----------------------------------------------------
		if($type!=3){
			$sqlDataAll = getSQLsumJoinLN($value_records, $type);
			
//echo $sqlDataAll; die;
			
		}
		else{
			$sqlDataAll ="select C.id_distrito, P.id_tipo_eleccion, sum(votos_part_1) as [votos_part_1], sum(votos_part_2) as [votos_part_2], sum(votos_part_3) as [votos_part_3], sum(votos_part_4) as [votos_part_4], sum(votos_part_5) as [votos_part_5], sum(votos_part_6) as [votos_part_6], sum(votos_part_7) as [votos_part_7], sum(votos_part_8) as [votos_part_8], sum(votos_part_9) as [votos_part_9], sum(votos_part_10) as [votos_part_10],  sum(votos_cand_no_reg) as [votos_cand_no_reg], sum(votos_nulos) as [votos_nulos], sum(votacion_total) as [votacion_total], sum(boletas_sob) as [boletas_sob], sum(ciudadanos_votaron) as [ciudadanos_votaron], sum(representantes_votaron) as [representantes_votaron], sum(total_votaron) as [total_votaron], sum(boletas_extraidas) as [boletas_extraidas] from scd_casillas C
			left join prep_votos P
			on C.id_distrito = P.id_distrito and C.id_delegacion = P.id_delegacion and 
			C.id_seccion = P.id_seccion and C.tipo_casilla = P.tipo_casilla
			where P.id_tipo_eleccion = 3
			group by P.id_tipo_eleccion, C.id_distrito";
		}
		//echo "<br><br>".$sqlDataAll."<br><br>"; return;

		
		//---------------------------------------------
		// Cargamos las sumatorias
		$registros = array();
		
		$items_winner = array();
		
		if(!$sqlDataAll) return null;
		
		$res_catch = $db->query($sqlDataAll);
		while ($row = $res_catch->fetchArray(SQLITE3_ASSOC))
		{
			$registros[] = $row;
			
		}
		
		return $registros;
}


// Consigue Los datos de las elecciones por elección
function getCorte($db){
		$sqlDate ="SELECT corte_fecha, corte_hora, dia, mes, anio, hora, minuto, segundo FROM corte;";
		
		$corte = array();
		
		$res_catch = $db->query($sqlDate);
		while ($row = $res_catch->fetchArray(SQLITE3_ASSOC))
		{
			$corte = $row;
			
		}
		//echo var_dump($corte); die;
		return $corte;
}

// Consigue Los datos de las elecciones por elección
function getRecordSetDataGroupByTypeCasillaDTO($type, $db){
		$tmp = [];		
		// Querys para traer datos para ranking
		$sqlRegistros ="";
		$sqlDataAll ="";
		// Typo de filtro y corte de control
		//$corteControl = ($type==4? "id_delegacion":"id_distrito");
		//$group = " from prep_votos where id_tipo_eleccion=".$type." group by ".$corteControl;
				
		//$db = new SQLite3('db/database.db3');
		
		$participan = "";
		$participanSUM = "";
		
		// 1. Consigue campos a consultar para armar la consulta
		$value_records = getFieldNameElection($type, $db);
		
		// 2. Conseguimos SQL armado para datos
		//----------------------------------------------
		// Datos: Armamos solo los campos a traer con el recorset de PARTICIPANTES
		//        y los querys para los datos a consultar por elección
		//----------------------------------------------------
		if($type!=3){
			$sqlDataAll = getSQLsumJoinLN($value_records, $type, "M");
			
		}
		else{
			$sqlDataAll ="select C.id_distrito, P.id_tipo_eleccion, sum(votos_part_1) as [votos_part_1], sum(votos_part_2) as [votos_part_2], sum(votos_part_3) as [votos_part_3], sum(votos_part_4) as [votos_part_4], sum(votos_part_5) as [votos_part_5], sum(votos_part_6) as [votos_part_6], sum(votos_part_7) as [votos_part_7], sum(votos_part_8) as [votos_part_8], sum(votos_part_9) as [votos_part_9], sum(votos_part_10) as [votos_part_10],  sum(votos_cand_no_reg) as [votos_cand_no_reg], sum(votos_nulos) as [votos_nulos], sum(votacion_total) as [votacion_total], sum(boletas_sob) as [boletas_sob], sum(ciudadanos_votaron) as [ciudadanos_votaron], sum(representantes_votaron) as [representantes_votaron], sum(total_votaron) as [total_votaron], sum(boletas_extraidas) as [boletas_extraidas] from scd_casillas C
			left join prep_votos P
			on C.id_distrito = P.id_distrito and C.id_delegacion = P.id_delegacion and 
			C.id_seccion = P.id_seccion and C.tipo_casilla = P.tipo_casilla
			where P.id_tipo_eleccion = 3
			and contabilizar='T'
			group by P.id_tipo_eleccion, C.id_distrito";
		}
		
		
	///	echo "<br><br>".$sqlDataAll."<br><br>"; return;

		
		//---------------------------------------------
		// Cargamos las sumatorias
		$registros = array();
		
		$items_winner = array();
		
		if(!$sqlDataAll) return null;
		
		$res_catch = $db->query($sqlDataAll);
		while ($row = $res_catch->fetchArray(SQLITE3_ASSOC))
		{
			$registros[] = $row;
			
		}
		
		return $registros;
}


function sumArrayTotal($registros, $db, $type, $tomaCamposExtra = false){
	$sumaRecorset = array();
	if(!isset($registros) || count($registros)<=0) return $sumaRecorset;
	
	$candidatos = getCandidatos($type, $db);
	/*
	echo "SUMA: <br><br>";
	echo var_dump($candidatos);  die();
	 return;
*/
	foreach ($registros as $registro) {
			foreach ($registro as $clave => $valor) {
				switch ($clave) {
					case 'id_distrito':
						break;
					case 'id_delegacion':
						break;
					case 'id_tipo_eleccion':
						$sumaRecorset[$clave] = $valor;
						break;
					default:
					   if(!isset($sumaRecorset[$clave])) $sumaRecorset[$clave] = 0;
					   $sumaRecorset[$clave] += $valor;
				}

			}		
	}
	
	/*
	echo "<br><br>SUMA<br><br>";
	echo var_dump($sumaRecorset);
	echo "<br><br>";
	return;
*/
	
	
	// DESGLOSO
	$candidatosDesglose = array();
	$data = array();
	$id = "0";
	$total_votaron = 0;
	
	// CARGO VOTACION TOTAL!!! 04/Abril/2024
	foreach ($sumaRecorset as $clave=>$valor) {
		if($clave=="votacion_total") {
			$total_votaron = $valor;
		}
	}
	
	
	// 02/abril/2024 se agrgan campos a votos_cand_no_reg y votos_nulos
	foreach ($sumaRecorset as $clave=>$valor) {
		$porcent = 0;
		
		if (str_contains2($clave, 'total_votos_') || str_contains2($clave, 'votos_part_') || str_contains2($clave, 'votos_nulos') || str_contains2($clave, 'votos_cand_no_reg') ) {
			
			//echo "<br>ENTRE!!!!".$valor."<br>";
			
			$id = "0";
			$desc = "";
			$siglas ="";
			foreach ($candidatos as $item )
			{

				if(trim($item["campo_votos"]) == trim($clave)){
					$id = $item["id_participante"];
					$desc = $item["descripcion"];
					$siglas = $item["siglas"];
					break;
				}
				//	echo "ENCONTRE: ".$item["id_participante"]."=>".$item["campo_votos"]."<br><br>";	
				
			}
			 
			$porcent = 0;
			// echo "Totalvotaron: ".$total_votaron."<br>";
			
			
			
			if($valor>0 && $total_votaron>0){
				//echo "<br>222ENTRE!!!!".$valor."<br>";
				$porcent = ($valor*100) / $total_votaron;
			}
			 /*
			 $candidatosDesglose[$clave] = [
				"id"=>$id.".jpg", 
				"valor"=>$valor, 
				"descripcion"=>$desc,
				"siglas"=>$siglas,
				"campo"=>$clave, 
				"porcentaje"=>sprintf("%01.4f", $porcent)."%"
			];
			*/
			if (str_contains2($clave, 'votos_nulos')) {
				$id = "nulos";
				$desc = "Votos nulos";
				$siglas = "nulos";
			}
			if (str_contains2($clave, 'votos_cand_no_reg') ) {
				$id = "no_reg";
				$desc = "No registrados";
				$siglas = "noreg";
			}
			
			$data[]= [
				"id"=>$id, 
				"campo"=>$clave,
				"valor"=>$valor, 
				"descripcion"=>$desc,
				"siglas"=>$siglas, 
				"imagen"=>$id.".jpg",
				"porcentaje"=>sprintf("%01.4f", $porcent)."%"
			];
		}
		else
		{
			$candidatosDesglose[$clave] = $valor;
		}


	}
	//echo var_dump($sumaRecorset); die;	
	
	/*
	echo "<br>SUMA DATA<br><br>";
	echo var_dump($data); die; return;
	
	
*/
	
	$candidatosDesglose["data"] = $data;
	
	return $candidatosDesglose;
	//return $sumaRecorset;

}

// FUNCION PARA OBTENER LOS ARRAY CON LOS CANDIDATOS POR ELECCION
// 12/Marzo/2024
function getCandidatos($type, $db){
	// SELECT DISTINCT id_participante, campo_votos FROM "scd_candidatos_jdel" order by id_participante
	$recordsTMP = array();
	$qryParticipantes ="";
	switch ($type) {
			case 1:  // JG
				$qryParticipantes = 'SELECT DISTINCT C.id_participante, P.descripcion, P.siglas, C.campo_votos FROM scd_candidatos_jgob as C
				left join scd_cat_participantes as P
				on P.id_participante = C.id_participante;';
				break;
			case 2: // MR
				$qryParticipantes = 'SELECT DISTINCT C.id_participante, P.descripcion, P.siglas, C.campo_votos FROM scd_candidatos_mr as C
				left join scd_cat_participantes as P
				on P.id_participante = C.id_participante;';
				break;
			case 3:  //RP
				$qryParticipantes = '';
				break;
			case 4:  //ALC
				$qryParticipantes = 'SELECT DISTINCT C.id_participante, P.descripcion, P.siglas, C.campo_votos FROM scd_candidatos_jdel as C
				left join scd_cat_participantes as P
				on P.id_participante = C.id_participante;';
				break;
	}
	
	$res_catch = $db->query($qryParticipantes);	
	$reg_data = 0;
	
	if(!$res_catch) return $recordsTMP;
	
	$db->enableExceptions(false);
	while ($row = $res_catch->fetchArray(SQLITE3_ASSOC))
	{
		array_push($recordsTMP, $row);
		$reg_data++;
	}
	
	return $recordsTMP;
}


function getAvanceContabilizadaResumen($db){

		$sqlEsperadas ="";
		$celTotalCaptura_F=0;  // actas capturadas
		$celTotalVotos_F =0;  // votación recibida
		$celTotalLN_F = 0;    // ln recibidos
		$celTotalNoReg_F =0;  // Votos de no registrados
		$celTotalNulos_F =0; // Votos nulos
		
		// Variables para filtro
		$sumaRP = 0;
		
		// LISTA NOMINAL TOTAL CDMX 
		$celTotalLN_CDMX = 0;

		$sqlEsperadas = "SELECT count(estatus) as cuantos, sum(lista_nominal) as ln FROM scd_casillas where estatus='T'";

		// Nuevo: 24/Mayo/2021
	    $sumaRP = 0;// 44;

		
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
		 
		 //16/04/2024
		// LISTA REAL
		$celTotalLN_CDMX = $celTotalLNesperada_F; 
				
		// Registro general de BD
		$itemRecords = array();	
		
				
				
		// -----------------------------------------------------
		// PARA COMPUTADAS: 11/Abril/2021
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

		// COMPUTADAS
		$res_catch = $db->query("SELECT id_tipo_eleccion, count(C.id_distrito) as [cuantos], sum(C.lista_nominal) as ln, sum(votos_cand_no_reg) as [votos_cand_no_reg], sum(votos_nulos)  as [votos_nulos],
		 sum(votacion_total) as votacion_total FROM prep_votos P
		left join scd_casillas C 
		on P.id_distrito = C.id_distrito and P.id_delegacion = C.id_delegacion and P.id_seccion = C.id_seccion and P.tipo_casilla = C.tipo_casilla
		where P.contabilizar='T'
		 group by  id_tipo_eleccion");	
		//		where P.contabilizar='T'

		$itemRecords["computadas_jg"] = array();
		$itemRecords["computadas_alc"] = array();
		$itemRecords["computadas_dmr"] = array();
		$itemRecords["computadas_rp"] = array();

		
		$db->enableExceptions(false);
		while ($row = $res_catch->fetchArray(SQLITE3_ASSOC))
		{
				$celTotalCaptura_F = $row["cuantos"];
				$celTotalLN_F = $row["ln"];
				$celTotalVotos_F = $row["votacion_total"];
				// Cargamos variables
				if($celTotalLN_F>0)
				 {
					$celPartipa_F = number_format((($celTotalVotos_F*100)/$celTotalLN_F), 14, '.', ',');
				 }
				 else
				 {
					 $celTotalLN_F ="0";
					$celPartipa_F ="0.0000";
				 }


				 if($celTotalEsperadas_F>0)
				 {
					$celPorcentRecivido_F = number_format((($celTotalCaptura_F*100)/$celTotalEsperadas_F), 14, '.', ',');
					
				 }
				 else
				 {
					 $celTotalEsperadas_F = "0";
					 $celPorcentRecivido_F = "0.0000";
				 }	 
				$capturadas = array(
					"actas_computadas" => $celTotalCaptura_F,
					"actas_computadas_de" => $celTotalEsperadas_F,
					"actas_computadas_porcen" => $celPorcentRecivido_F,
					"participacion_computadas" => $celPartipa_F,
					"ln" => $celTotalLN_F
				);	
				
				
				switch ($row['id_tipo_eleccion']) {
					case 1:
						// Guardo datos en array para JSON
						$itemRecords["computadas_jg"]= [$capturadas];
						break;
					case 2:
						//$capturadas["actas_capturadas_de"] += 44;
						// Guardo datos en array para JSON
						$itemRecords["computadas_dmr"]= [$capturadas];

						break;
					case 3:
						// Guardo datos en array para JSON
						$itemRecords["computadas_rp"]= [$capturadas];
						
						break;
					case 4:
						// Guardo datos en array para JSON
						$itemRecords["computadas_alc"]= [$capturadas];

						break;
				}				
		}		
				
		
		
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
		
		// CAPTURADAS
		$res_catch = $db->query("SELECT id_tipo_eleccion, count(C.id_distrito) as [cuantos], sum(C.lista_nominal) as ln, sum(votos_cand_no_reg) as [votos_cand_no_reg], sum(votos_nulos)  as [votos_nulos],
		 sum(votacion_total) as votacion_total FROM prep_votos P
		left join scd_casillas C 
		on P.id_distrito = C.id_distrito and P.id_delegacion = C.id_delegacion and P.id_seccion = C.id_seccion and P.tipo_casilla = C.tipo_casilla
		 group by  id_tipo_eleccion");	
		//		where P.contabilizar='T'

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
					$celPartipa_F = number_format((($celTotalVotos_F*100)/$celTotalLN_F), 14, '.', ',');
				 }
				 else
				 {
					 $celTotalLN_F ="0";
					$celPartipa_F ="0.0000";
				 }


				 if($celTotalEsperadas_F>0)
				 {
					$celPorcentRecivido_F = number_format((($celTotalCaptura_F*100)/$celTotalEsperadas_F), 14, '.', ',');
					
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
						
						// Nuevo: 24/Mayo/2021
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
		// PARA ESPERADAS 19/Mayo/2021
		//---------------------------------------------------------
		$res_catch2 = $db->query("SELECT id_tipo_eleccion, count(C.id_distrito) as [cuantos], sum(C.lista_nominal) as ln, sum(votos_cand_no_reg) as [votos_cand_no_reg], sum(votos_nulos)  as [votos_nulos],
		 sum(votacion_total) as votacion_total FROM prep_votos P
		left join scd_casillas C 
		on P.id_distrito = C.id_distrito and P.id_delegacion = C.id_delegacion and P.id_seccion = C.id_seccion and P.tipo_casilla = C.tipo_casilla
		group by id_tipo_eleccion");	
		
		$itemRecords["esperadas_jg"] = array();
		$itemRecords["esperadas_alc"] = array();
		$itemRecords["esperadas_dmr"] = array();
		$itemRecords["esperadas_rp"] = array();
		while ($row = $res_catch->fetchArray(SQLITE3_ASSOC))
		{
			$esperadas = array(
				"actas_esperadas" => $row["cuantos"],
				"ln_esperadas"=>$row["ln"]
			);
			
			switch ($row['id_tipo_eleccion']) {
				case 1:
					// Guardo datos en array para JSON
					$itemRecords["esperadas_jg"]= [$esperadas];
					break;
				case 2:
					// Guardo datos en array para JSON
					$itemRecords["esperadas_dmr"]= [$esperadas];
					break;
				case 3:
					// Guardo datos en array para JSON
					$itemRecords["esperadas_rp"]= [$esperadas];
					break;
				case 4:
					// Guardo datos en array para JSON
					$itemRecords["esperadas_alc"]= [$esperadas];
					break;
			}
		}
		
		/////
	
		
		//--------------------------------------------------------------------------------------------------
		// SIN ESPECIALES
		//SELECT sum(votacion_total)  FROM prep_votos  where id_tipo_eleccion=1 and substr(tipo_casilla,1,1) ='S';
			$cont_s_esp1 =0;
			$cont_s_esp2 =0;
			$cont_s_esp3 =0;
			$cont_s_esp4 =0;
			
			$suma_s_esp1 = 0;
			$suma_s_esp2 = 0;
			$suma_s_esp3 = 0;
			$suma_s_esp4 = 0;
			$suma_s_esp=0;
			$res_catch2 = $db->query("SELECT id_tipo_eleccion, count(C.id_distrito) as [cuantos], sum(C.lista_nominal) as ln, sum(votos_cand_no_reg) as [votos_cand_no_reg], sum(votos_nulos)  as [votos_nulos],
			 sum(votacion_total) as votacion_total FROM prep_votos P
			left join scd_casillas C 
			on P.id_distrito = C.id_distrito and P.id_delegacion = C.id_delegacion and P.id_seccion = C.id_seccion and P.tipo_casilla = C.tipo_casilla
			where substr(P.tipo_casilla,1,1) <>'S' and contabilizar='T' group by  id_tipo_eleccion");	
			while ($row = $res_catch2->fetchArray(SQLITE3_ASSOC))
			{
				// Cargamos variables
				$celTotalVotos_F = $row["votacion_total"];
				
				$suma_s_esp = $row["votacion_total"];
				// 19/marzo/2024

				 if($celTotalVotos_F>0)
				 {
					$celTotalVotos_F = number_format($celTotalVotos_F , 0, '.', ',');
					
				 }
				 else
				 {
					 $celTotalVotos_F = "0";
					 $suma_s_esp = 0;
				 }	 

				 switch ($row['id_tipo_eleccion']) {
						case 1:
							// Guardo datos en array para JSON
							$cont_s_esp1 =$celTotalVotos_F;
							$suma_s_esp1 = $suma_s_esp;
							break;
						case 2:
							// Guardo datos en array para JSON
							$cont_s_esp2 =$celTotalVotos_F;
							$suma_s_esp2 = $suma_s_esp;
							break;
						case 3:
							$cont_s_esp3 =$celTotalVotos_F;
							$suma_s_esp3 = $suma_s_esp;
							break;
						case 4:
							$cont_s_esp4 =$celTotalVotos_F;
							$suma_s_esp4 = $suma_s_esp;
							break;
					}		
				 				
			}		
			
		//--------------------------------------------------------------------------------------------------
		// FIN SIN ESPECIALES
			

		//SOLO ESPECIALES
		//SELECT sum(votacion_total)  FROM prep_votos  where id_tipo_eleccion=1 and substr(tipo_casilla,1,1) ='S';
		$cont_esp1 =0;
		$cont_esp2 =0;
		$cont_esp3 =0;
		$cont_esp4 =0;
		
		$suma_esp1 = 0;
		$suma_esp2 = 0;
		$suma_esp3 = 0;
		$suma_esp4 = 0;
		$suma_esp = 0;
		$res_catch2 = $db->query("SELECT id_tipo_eleccion, count(C.id_distrito) as [cuantos], sum(C.lista_nominal) as ln, sum(votos_cand_no_reg) as [votos_cand_no_reg], sum(votos_nulos)  as [votos_nulos],
		 sum(votacion_total) as votacion_total FROM prep_votos P
		left join scd_casillas C 
		on P.id_distrito = C.id_distrito and P.id_delegacion = C.id_delegacion and P.id_seccion = C.id_seccion and P.tipo_casilla = C.tipo_casilla
		where substr(P.tipo_casilla,1,1) ='S' and contabilizar='T' group by  id_tipo_eleccion");	
		while ($row = $res_catch2->fetchArray(SQLITE3_ASSOC))
		{
			// Cargamos variables
			$celTotalVotos_F = $row["votacion_total"];
			// 19/marzo/2024
			$suma_esp = $row["votacion_total"];
			 if($celTotalVotos_F>0)
			 {
				$celTotalVotos_F = number_format($celTotalVotos_F , 0, '.', ',');
				
			 }
			 else
			 {
				 $celTotalVotos_F = "0";
				 $suma_esp =0;
			 }	 

			 switch ($row['id_tipo_eleccion']) {
					case 1:
						// Guardo datos en array para JSON
						$cont_esp1 =$celTotalVotos_F;
						$suma_esp1 =$suma_esp;
						break;
					case 2:
						// Guardo datos en array para JSON
						$cont_esp2 =$celTotalVotos_F;
						$suma_esp2 =$suma_esp;
						break;
					case 3:
						$cont_esp3 =$celTotalVotos_F;
						$suma_esp3 =$suma_esp;
						break;
					case 4:
						$cont_esp4 =$celTotalVotos_F;
						$suma_esp4 =$suma_esp;
						break;
				}		
							
		}
			
			//---------------------------------------


			
			
		// -----------------------------------------------------
		// PARA CAPTURADAS Y CONTABILIZADAS: 13/Mayo/2021
		//---------------------------------------------------------
		
		// 1 CAPTURADAS avance
		$res_catch = $db->query("SELECT id_tipo_eleccion, contabilizar, count(C.id_distrito) as [cuantos], sum(C.lista_nominal) as ln, sum(votos_cand_no_reg) as [votos_cand_no_reg], sum(votos_nulos)  as [votos_nulos],
		 sum(votacion_total) as votacion_total FROM prep_votos P
		left join scd_casillas C 
		on P.id_distrito = C.id_distrito and P.id_delegacion = C.id_delegacion and P.id_seccion = C.id_seccion and P.tipo_casilla = C.tipo_casilla 
		group by  id_tipo_eleccion");	
			//	where contabilizar='T' 

		$itemRecords["avance_jg"] = array();
		$itemRecords["avance_alc"] = array();
		$itemRecords["avance_dmr"] = array();
		$itemRecords["avance_rp"] = array();
		
		
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
			$celTotalLN_F = $celTotalLN_CDMX; //$row["ln"];
			$celTotCapLN = $row["ln"];
			$celTotalVotos_F = $row["votacion_total"];
			// Cargamos variables
			if($celTotalLN_F>0)
			 {
				 if($row["cuantos"]>0){
					 $celPartipa_F = number_format((($celTotalVotos_F*100)/$celTotalLN_F), 4, '.', ',');
				 }
				 else{
					 $celTotalLN_F ="0";
					$celPartipa_F ="0.0000";
				 }
				
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
			 
			$avance = array(
				"actas_capturadas" => $celTotalCaptura_F,
				"actas_capturadas_de" => $celTotalEsperadas_F,
				"actas_cap_porcen" => $celPorcentRecivido_F,
				"participacion_porcen" => $celPartipa_F,
				"ln" => $celTotalLN_F,
				"ln_capturadas"=>$celTotCapLN 
			);
			
			
			switch ($row['id_tipo_eleccion']) {
				case 1:
					// Guardo datos en array para JSON
					$itemRecords["avance_jg"]= [$avance];

					break;
				case 2:
					//$avance["actas_capturadas_de"] += 44;
					 
					 
					 
					// Guardo datos en array para JSON
					$itemRecords["avance_dmr"]= [$avance];

					break;
				case 3:
					// Guardo datos en array para JSON
					$itemRecords["avance_rp"]= [$avance];

					break;
				case 4:
					// Guardo datos en array para JSON
					$itemRecords["avance_alc"]= [$avance];

					break;
			}
		}
		///----------------------------------------------------------
		
		
	
		// 2 RESUMEN 
		$res_catch = $db->query("SELECT id_tipo_eleccion, contabilizar, count(C.id_distrito) as [cuantos], sum(C.lista_nominal) as ln, sum(votos_cand_no_reg) as [votos_cand_no_reg], sum(votos_nulos)  as [votos_nulos],
		 sum(votacion_total) as votacion_total FROM prep_votos P
		left join scd_casillas C 
		on P.id_distrito = C.id_distrito and P.id_delegacion = C.id_delegacion and P.id_seccion = C.id_seccion and P.tipo_casilla = C.tipo_casilla 
		where contabilizar='T' 
		group by  id_tipo_eleccion");	
			//	where contabilizar='T' 

		
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
			
			
			// SUMATORIAS PARA RESUMEN
			if($row["contabilizar"]=='T'){
				$acumulado = $row["votacion_total"] - $row["votos_cand_no_reg"] - $row["votos_nulos"];
				$no_reg = $row["votos_cand_no_reg"]; 
				$nulo =  $row["votos_nulos"]; 
				$total = $row["votacion_total"];
			}
			
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
			
			$tmp_s_esp = 0;
			$tmp_esp = 0;
			$tmp_esp_y_noesp = 0;
			
			switch ($row['id_tipo_eleccion']) {
				case 1:
					// Guardo datos en array para JSON
					$tmp_s_esp =$cont_s_esp1;
					$tmp_esp = $cont_esp1;
					$tmp_esp_y_noesp = $suma_esp1 + $suma_s_esp1;
					break;
				case 2:
					// Guardo datos en array para JSON
					$tmp_s_esp =$cont_s_esp2;
					$tmp_esp = $cont_esp2;
					$tmp_esp_y_noesp = $suma_esp2 + $suma_s_esp2;
					break;
				case 3:
					// Guardo datos en array para JSON
					$tmp_s_esp =$cont_s_esp3;
					$tmp_esp = $cont_esp3;
					$tmp_esp_y_noesp = $suma_esp3 + $suma_s_esp3;
					break;
				case 4:
					// Guardo datos en array para JSON
					$tmp_s_esp =$cont_s_esp4;
					$tmp_esp = $cont_esp4;
					$tmp_esp_y_noesp = $suma_esp4 + $suma_s_esp4;
					break;
			}
			
			$suma_esp_y_noesp = $suma_esp + $suma_s_esp;
			
			
			$resumen = array(
				"votos_acumulados" => $acumulado,
				"candidatos_no_reg" => $no_reg,
				"nulos" => $nulo,
				"total" => $total,
				"votos_acumulados_por" => sprintf("%01.4f", $votos_acumulados_por)."%",
				"votos_sin_especiales"=> $tmp_s_esp? $tmp_s_esp: 0,
				"votos_especiales"=> $tmp_esp? $tmp_esp: 0,
				"suma_esp_s_esp"=>$tmp_esp_y_noesp? $tmp_esp_y_noesp: 0,
				"candidatos_no_reg_por" => sprintf("%01.4f", $candidatos_no_reg_por)."%",
				"nulos_por" => sprintf("%01.4f", $nulos_por)."%",
				"total_por" => "100.0000%"
			);
			
			switch ($row['id_tipo_eleccion']) {
				case 1:
					// Guardo datos en array para JSON
		
					$itemRecords["resumen_jg"]= [$resumen];
					break;
				case 2:
					// Guardo datos en array para JSON
				
					$itemRecords["resumen_dmr"]= [$resumen];
					break;
				case 3:
					// Guardo datos en array para JSON
	
					$itemRecords["resumen_rp"]= [$resumen];
					break;
				case 4:
					// Guardo datos en array para JSON

					$itemRecords["resumen_alc"]= [$resumen];
					break;
			}
		}
		///----------------------------------------------------------
		
		return $itemRecords;
	
}


// Función para contar los items ganados por candidato
/*
function contarItemsPorParticipante($datos) {
    $conteo = array();

    foreach ($datos as $dato) {
        $id_participante = $dato['id_participante'];

        if (isset($conteo[$id_participante])) {
            $conteo[$id_participante]++;
        } else {
            $conteo[$id_participante] = 1;
        }
    }

    return $conteo;
}
*/

/*
	1. Color del PAN :  #006faa
	2. Color PRI:  verde #009f65. Rojo: #f83946
	3. Color del PRD:  #ffcd04

	4. Color Paritdo VERDE: #4eb45b
	5. Partido PT: #ef1c1e
	6. Color Movimiento Ciudadano: #f58100
	7. MORENA: #7b2629
	
	8. EBM Efraín Bautista Mejorada
	9. JRAA José Rodolfo Ávila Ayala
	
	10. Coalición PAN-PRI-PRD
	14. Candidatura Común o Coalición PVEM-PT-MORENA
*/
function contarItemsPorParticipante($datos) {
	$background_colors = array('red', '#006faa', '#009f65', '#ffcd04', '#4eb45b',
	'#ef1c1e', '#f58100','#7b2629', '#e6057e' , '#fd8204', 'silver', '#ec62a0', '#e6057e', '#C8BECB', 'gray', 'gray', 'gray', 'gray', 'gray', 'gray', 'gray', 'gray', 'gray', 'gray', 'gray', 'gray', 'gray', 'gray', 'navy', 'gray', 'gray', 'gray', 'gray', 'gray', 'gray', 'brown', 'gray');


    $conteo = array();

    foreach ($datos as $dato) {
		// ACTIVE ESTO EL 30/ABRIL/2024
		if(!isset($dato['id_participante'])) continue;
		
        $id_participante = $dato['id_participante'];
		
        if (isset($conteo[$id_participante])) {
            $conteo[$id_participante]++;
        } else {
            $conteo[$id_participante] = 1;
        }
    }
	// -----
	
	$resultado = array();
	foreach ($conteo as $key => $value) {
		$resultado[] = [
			"id"=>$key, 
			"name"=>"Nombre(s) Apellido(s) Candidato ".$key,
			"color"=>$background_colors[intval($key)],
			"valor"=>$value, 
			"porcentaje"=>0,
			"icono"=>$key,
			"ganadas"=>$value
		];
	}

	//------
    return $resultado;
}

function getObtieneDistribucionPartidos ($db, $dttoAlc, $eleccion){
	$sql;
	if( $eleccion != 4 ){ // Alcaldías
		$sql = "SELECT 
			id_distrito, id_delegacion, votos_part_1, votos_part_2, votos_part_3, votos_part_4, votos_part_5, votos_part_6, votos_part_7, votos_part_8,
			votos_part_9, votos_part_10, votos_part_11, votos_part_12, votos_part_13, votos_part_14, votos_part_15, votos_part_16, votos_part_17, 
			votos_part_18, votos_part_19, votos_part_20, votos_part_21, votos_part_22, votos_part_23, votos_part_24, votos_part_25, votos_part_26,
			votos_part_27, votos_part_28, votos_part_29, votos_part_30, votos_part_31, votos_part_32, votos_part_33, votos_part_34, votos_part_35,
			total_votos_cc1, total_votos_cc2, total_votos_cc3, total_votos_cc4, total_votos_cc5, total_votos_cc6, total_votos_cc7, total_votos_cc8,
			total_votos_cc9
			FROM prep_votos WHERE id_distrito = ".$dttoAlc." AND contabilizar = 'T' AND id_tipo_eleccion =".$eleccion;
	} else { // Distritos
		$sql = "SELECT 
			id_distrito, id_delegacion, votos_part_1, votos_part_2, votos_part_3, votos_part_4, votos_part_5, votos_part_6, votos_part_7, votos_part_8,
			votos_part_9, votos_part_10, votos_part_11, votos_part_12, votos_part_13, votos_part_14, votos_part_15, votos_part_16, votos_part_17, 
			votos_part_18, votos_part_19, votos_part_20, votos_part_21, votos_part_22, votos_part_23, votos_part_24, votos_part_25, votos_part_26,
			votos_part_27, votos_part_28, votos_part_29, votos_part_30, votos_part_31, votos_part_32, votos_part_33, votos_part_34, votos_part_35,
			total_votos_cc1, total_votos_cc2, total_votos_cc3, total_votos_cc4, total_votos_cc5, total_votos_cc6, total_votos_cc7, total_votos_cc8,
			total_votos_cc9
			FROM prep_votos WHERE id_delegacion = ".$dttoAlc." AND contabilizar = 'T' AND id_tipo_eleccion =".$eleccion;
	}
	if(!$sql) return null;
		
	$res_catch = $db->query($sql);

	while ($row = $res_catch->fetchArray(SQLITE3_ASSOC))
	{
		$datosMSSQL[] = $row;
		
	}
	
	return $datosMSSQL;
}


// Consigue Los datos de las elecciones por elección
function getHostsImages($db){	
		
	
		$sqlData ="SELECT * FROM configura where tipo ='PREP'";
		
		//echo "<br><br>".$sqlDataAll."<br><br>"; return;

		
		//---------------------------------------------
		// Cargamos las sumatorias
		$hosts = array();

		
		if(!$sqlData) return null;
		
		$res_catch = $db->query($sqlData);
		while ($row = $res_catch->fetchArray(SQLITE3_ASSOC))
		{
			$hosts[] = $row;
			
		}
		
		return $hosts;
}

?>


