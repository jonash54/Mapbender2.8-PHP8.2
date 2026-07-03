<?php

/**
 * @version   Changed: ### 2015-04-27 12:31:33 UTC ###
 * @author    Raphael.Syed <raphael.syed@WhereGroup.com> http://WhereGroup.com
 */

require_once(__DIR__."/../php/mb_validateSession.php");
// require_once(dirname(__FILE__) . "/../classes/class_wmc.php");
/**
 * unpublish the the given wmc
 */

$user_id = Mapbender::session()->get("mb_user_id");
$wmc_serial_id = $_POST["wmc_serial_id"];
$dataMode = $_POST['mode'];
$dataLicense = $_POST['license'];

if ($dataMode === 'getLicenseMode') {
    $sql = 'SELECT symbollink,description,isopen FROM termsofuse WHERE name = $1';
    $v = [$dataLicense];
    $t = ["s"];
    header('Content-Type: application/json');
    $res = db_prep_query($sql, $v, $t);
    while ($row = db_fetch_array($res)) {
        echo json_encode($row);
    }
} else if ($dataMode === 'saveLicenseMode') {
    // #1 Update wmc_local_data_public
    $sql =  'UPDATE mb_user_wmc mb  SET wmc_local_data_public = 1, wmc_local_data_fkey_termsofuse_id = t.termsofuse_id from termsofuse t WHERE mb.wmc_serial_id = $1 AND mb.fkey_user_id = $2 AND t.name = $3';
    $v = [$wmc_serial_id, $user_id, $dataLicense];
    $t = ["s", "i", "s"];
    header('Content-Type: application/json');
    $res = db_prep_query($sql, $v, $t);



} else if ($dataMode === 'getAllLicencesMode') {
    $openData_only  = $_POST['openData_only'];
    $resultArray = [];
    $sql = 'SELECT name FROM termsofuse where isopen = $1';
    $v = [$openData_only];
    $t = ["c"];
    $res = db_prep_query($sql, $v, $t);
    header('Content-Type: application/json');
    //echo db_fetch_array($res);
    while ($row = db_fetch_array($res)) {
        $resultArray[]= $row;
    }
    echo json_encode($resultArray);

}
