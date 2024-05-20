<?php
	$codigo = "echo 'Esto es una prueba: '.funcion1();
	
	function funcion1(){
		return \" Otro hola \" ; 
	}
	
	";
	
	eval($codigo);
?>