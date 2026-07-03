<?php
require_once(__DIR__."/../../core/globalSettings.php");
require_once(__DIR__."/../classes/class_owsConstraints.php"); 
$constraints = new OwsConstraints();
$result = $constraints->getRequestParameters();
//$constraints->returnDirect = false;
$constraints->returnDirect = true;
if (!$result['success']) {
	echo $result['message'];
	die();
}
$result = $constraints->getDisclaimer();
?>
