<?php

# Turn Error Reporting ON for All ++ Set Error Displaying
error_reporting(E_ALL);
ini_set('display_errors', true);

include('../lada/lada.php');

try {
	$Lada = new Avrelia\Lada();
	echo $Lada->fromFile('./simple.lada')->asExample(true);
}
catch (Avrelia\LadaException $e) {
	echo $e->getMessage();
}