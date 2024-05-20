<?php

function str_contains2($haystack, $needle) 
{
    return strpos($haystack, $needle) !== false;
}

// Ejemplo de uso:
/*
$string = "Esta es una cadena de ejemplo";
$substring = "cadena";

if (str_contains($string, $substring)) {
    echo "La cadena contiene la subcadena.";
} else {
    echo "La cadena NO contiene la subcadena.";
}
*/
?>