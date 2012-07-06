<?php

# Turn Error Reporting ON for All ++ Set Error Displaying
error_reporting(E_ALL);
ini_set('display_errors', true);

include('../lada/lada.php');

try {
	$Lada = new Avrelia\Lada();
	$html = $Lada->fromFile('./sample-3.lada');
	echo $html->asExample();
	# echo $html->asString();
}
catch (Avrelia\LadaException $e) {
	echo $e->getMessage();
}