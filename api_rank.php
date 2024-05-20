<?php
// Cabecera para evitar CORS
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
//header("Allow: GET, POST, OPTIONS, PUT, DELETE");
header("Allow: GET, POST");
//calculate rank for multi dimensional array
function calculate_rank($rank_values) {
	$rank = 0;
	$r_last = null;
	foreach ($rank_values as $key => $arr) {
		if ($arr['mark'] != $r_last) {
			if($arr['mark'] > 0){ //if you want to set zero rank for values zero
				$rank++;
			}
			$r_last = $arr['mark'];
		}

		$rank_values[$key]['rank'] = $arr['mark'] > 0 ? $rank: 0; //if you want to set zero rank for values zero
	}
	return $rank_values;
}

try {
		$array =[];

		$array[] = ['number' => 1117, 'name'=>'x'];
		$array[] =	['number' => 1097, 'name'=>'y'];
		$array[] =	['number' => 1162, 'name'=>'xx'];
		$array[] =	['number' => 1158, 'name'=>'s'];
		$array[] =	['number' => 1162, 'name'=>'f'];
		$array[] =	['number' => 1157, 'name'=>'z'];
		$array[] =	['number' => 1086, 'name'=>'a'];
		$array[] =	['number' => 1157, 'name'=>'b'];
		$array[] =	['number' => 1130, 'name'=>'c'];
	

		$maxRank = 5;

		rsort($array);  // sort DESC
		$rank = 0;
		$result = [];
		
		$array_num = count($array);
		for ($i = 0; $i < $array_num; ++$i){
		//foreach ($array as ['number' => $number, 'name'=> $name]) {
			$ranks[$array[$i]['number']] = isset($ranks[$array[$i]['number']]) ? $ranks[$array[$i]['number']] : ++$rank;

			//$ranks[$array[$i]['number']] ??= ++$rank;
			$result[] =[ $array[$i]['number'], $array[$i]['name'], 'rank' => $ranks[$array[$i]['number']]];
		}
		
	echo json_encode($result);
	
	
} catch (Exception $e) {

	$host = $_SERVER['HTTP_HOST'];
	$ruta = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
	$html = 'actualizando_bd.html';
	$url = "http://$host$ruta/$html";
	header("Location: $url");

} finally {

}

