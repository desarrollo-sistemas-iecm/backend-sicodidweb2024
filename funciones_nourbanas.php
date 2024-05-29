<?php


function urbanasNoUrbanas($type, $item, $item_2, $item_3, $db)
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
		
	
	//echo $sqlNO_URBANA; die();
	
	
	
	$res_catch = $db->query($sqlNO_URBANA);
	if(!$res_catch) return null;
	$db->enableExceptions(false);
	
	while ($row = $res_catch->fetchArray(SQLITE3_ASSOC))
	{
		//$var_catch = $row_catch['value']." - ".$row_catch['label']."<br>";
		$no_urbanas = $row["NO_URBANAS"];
		// if($type_param==1) break;
	}

	$res_catch = $db->query($sqlURBANA);
	if(!$res_catch) return null;
	$db->enableExceptions(false);
	
	while ($row = $res_catch->fetchArray(SQLITE3_ASSOC))
	{
		//$var_catch = $row_catch['value']." - ".$row_catch['label']."<br>";
		$urbanas = $row["URBANAS"];
		// if($type_param==1) break;
	}
		
	$record["urbanas_nourbanas"] = array(
			"urbanas" => $urbanas,
			"nourbanas" => $no_urbanas,
	);
	return $record;
}

?>