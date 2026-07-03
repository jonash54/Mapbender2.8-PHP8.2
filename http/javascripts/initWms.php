<?php
//
// Load WMS
// 
require_once(__DIR__."/../php/mb_validateSession.php");
require_once(__DIR__."/../classes/class_wms.php");
require_once(__DIR__."/../classes/class_administration.php");

$wmsArray = wms::selectMyWmsByApplication($gui_id);

for ($i = 0; $i < count($wmsArray); $i++) {
	$currentWms = $wmsArray[$i];

	$output = $currentWms->createJsObjFromWMS_();
	echo administration::convertOutgoingString($output);
	unset($output);
}
?>
