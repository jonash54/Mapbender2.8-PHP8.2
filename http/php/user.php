<?php
/*******************************************************************************
 * 
 *******************************************************************************/
//$e_id="user";
require_once(__DIR__."/../php/mb_validateSession.php");

$user = (int)Mapbender::session()->get("mb_user_id");

if($user <= 0) {
    die('Error: No permissions.');
}

if(!$_POST['searchterm']) {
    die('Error: Searchterm not found.');
}

if(!defined('SEARCH_COLUMN1') || !defined('SEARCH_COLUMN2') || !defined('SEARCH_LIMIT')) {
    die('SEARCH_COLUMN1, SEARCH_COLUMN2 or SEARCH_LIMIT not found in mapbender.conf');
}

if($_POST['userCheck'] == "on") {
    if(SEARCH_COLUMN2 != "") {
        $sql = "SELECT * FROM mb_user WHERE mb_user_owner = $1 AND (" . SEARCH_COLUMN1 . " LIKE '%' || $2 || '%' OR " . SEARCH_COLUMN2 . " LIKE '%' || $3 || '%') " .     
        	"ORDER BY " . SEARCH_COLUMN1 ."," . SEARCH_COLUMN2 . " LIMIT " . SEARCH_LIMIT;
        
        $sqlCnt = "SELECT count(*) as cnt FROM mb_user WHERE mb_user_owner = $1 AND (" . SEARCH_COLUMN1 . " LIKE '%' || $2 || '%' OR " . SEARCH_COLUMN2 . " LIKE '%' || $3 || '%')";

        $v = [$user, $_POST['searchterm'], $_POST['searchterm']];
        $t = ["i", "s", "s"];
    }
    else {
        $sql = "SELECT * FROM mb_user WHERE mb_user_owner = $1 AND " . SEARCH_COLUMN1 . " LIKE '%' || $2 || '%' " .     
        	"ORDER BY " . SEARCH_COLUMN1 ." LIMIT " . SEARCH_LIMIT;
        
        $sqlCnt = "SELECT count(*) as cnt FROM mb_user WHERE mb_user_owner = $1 AND " . SEARCH_COLUMN1 . " LIKE '%' || $2 || '%' ";
        
        $v = [$user, $_POST['searchterm']];
        $t = ["i", "s"];
    }     
}
else {
    if(SEARCH_COLUMN2 != "") {
        $sql = "SELECT * FROM mb_user WHERE " . SEARCH_COLUMN1 . " LIKE '%' || $1 || '%' OR " . SEARCH_COLUMN2 . " LIKE '%' || $2 || '%' " .     
        	"ORDER BY " . SEARCH_COLUMN1 ."," . SEARCH_COLUMN2 . " LIMIT " . SEARCH_LIMIT;
        
        $sqlCnt = "SELECT count(*) as cnt FROM mb_user WHERE " . SEARCH_COLUMN1 . " LIKE '%' || $1 || '%' OR " . SEARCH_COLUMN2 . " LIKE '%' || $2 || '%' ";
        
        $v = [$_POST['searchterm'], $_POST['searchterm']];
        $t = ["s", "s"];
    }
    else {
        $sql = "SELECT * FROM mb_user WHERE " . SEARCH_COLUMN1 . " LIKE '%' || $1 || '%' " .     
        	"ORDER BY " . SEARCH_COLUMN1 ." LIMIT " . SEARCH_LIMIT;
        
        $sqlCnt = "SELECT count(*) as cnt FROM mb_user WHERE " . SEARCH_COLUMN1 . " LIKE '%' || $1 || '%' ";
        
        $v = [$_POST['searchterm']];
        $t = ["s"];
    } 
}

$result = db_prep_query($sql,$v,$t);
$resultCnt = db_prep_query($sqlCnt,$v,$t);

if($result) {
    $userArray = [];
    
    while($users = db_fetch_assoc($result)) {
        $userArray[] = ['id' => $users['mb_user_id'], 'login' => $users['mb_user_name'], 'firstname' => $users['mb_user_firstname'], 'lastname' => $users['mb_user_lastname'], 'name' => $users['mb_user_name'], 'email' => $users['mb_user_email']];
    }
    
    $userCnt = db_fetch_assoc($resultCnt);
    if($userCnt['cnt'] < SEARCH_LIMIT) {
        $limit = $userCnt['cnt'];
    }
    else {
        $limit = SEARCH_LIMIT;
    }
    $resultArray = ["hits" => $userCnt['cnt'], "limit" => $limit, "users" => $userArray];
    
    #$resultArray = array($userArray, $userInfoArray);
    
    die(json_encode($resultArray));
    
} else die('Error: Searchresult.');