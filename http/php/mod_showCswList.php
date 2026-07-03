<?php
/*
 * Show list of publish available csw resources that can be used for remote searching
 */
require_once(__DIR__."/../../core/globalSettings.php");
if (file_exists ( __DIR__ . "/../../conf/remoteCsw.json" )) {
    $configObject = json_decode ( file_get_contents ( "../../conf/remoteCsw.json" ) );
}
if (isset ( $configObject ) && isset ( $configObject->available_services ) && count($configObject->available_services) != 0) {
    $availableCsw = $configObject->available_services;
}
$sql = "SELECT cat_id, cat_title FROM cat WHERE cat_id in (" . implode(",", $availableCsw) . ");";
$res = db_query($sql);
$jsonOutput = new stdClass();
$jsonOutput->catalogues = [];
$numberOfCsw = 0;
while($row = db_fetch_array($res)){
    $jsonOutput->catalogues[$numberOfCsw] = new stdClass();
    $jsonOutput->catalogues[$numberOfCsw]->{'id'} = $row['cat_id'];
    $jsonOutput->catalogues[$numberOfCsw]->{'title'} = $row['cat_title'];
    $numberOfCsw++;
}
$json = json_encode($jsonOutput);
header('Content-Type: application/json');
echo $json;
?>
