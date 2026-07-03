<?php
require_once __DIR__ . "/../../core/globalSettings.php";
require_once __DIR__ . "/../classes/class_user.php";
require_once __DIR__ . "/../classes/class_wmc.php";
require_once(__DIR__."/../classes/class_wmc_factory.php");
require_once(__DIR__."/../classes/class_mb_exception.php");

$ajaxResponse = new AjaxResponse($_POST);

function getAsArray($object, $asArray){
    if(is_object($object)) {
        $tmpArr = (array)$object;
    } else {
        $tmpArr = $object;
    }
    if(array_keys($tmpArr) !== range(0, count($tmpArr) - 1)) { //associative array
        foreach ($tmpArr as $key => $value) {
            if(is_string($value)) {
                $asArray[$key] = $value;
            } else {
                $tmp = getAsArray($value, []);
                $asArray[$key] = $tmp;
            }
        }
    } else {
        foreach ($tmpArr as $value) {
            if(is_string($value)) {
                $asArray[] = $value;
            } else {
                $tmp = getAsArray($value, []);
                $asArray[] = $tmp;
            }
        }
    }
    return $asArray;
}

function abort ($message): never {
	global $ajaxResponse;
	$ajaxResponse->setSuccess(false);
	$ajaxResponse->setMessage($message);
	$ajaxResponse->send();
	die;
};

function getWmc ($wmcId = null) {
	$user = new User(Mapbender::session()->get("mb_user_id"));
	$wmcIdArray = $user->getWmcByOwner();
	//getAccessibleWmcs();

	if (!is_null($wmcId) && !in_array($wmcId, $wmcIdArray)) {
		abort(_mb("You are not allowed to access this WMC."));
	}
	return $wmcIdArray;
}

switch ($ajaxResponse->getMethod()) {
	case "getWmc" :
		$wmcIdArray = getWmc();
		$wmcList = implode(",", $wmcIdArray);
		$sql = <<<SQL
	
SELECT mb_user_wmc.wmc_serial_id as wmc_id, mb_user_wmc.wmc_title, mb_user_wmc.wmc_timestamp, wmc_load_count.load_count FROM mb_user_wmc 
LEFT JOIN wmc_load_count ON wmc_load_count.fkey_wmc_serial_id = mb_user_wmc.wmc_serial_id WHERE wmc_serial_id IN ($wmcList);

SQL;
		$res = db_query($sql);
		$resultObj = ["header" => ["WMC ID", "Titel", "Timestamp", "Load Count", ""], "data" => []];

		while ($row = db_fetch_row($res)) {
			// convert NULL to '', NULL values cause datatables to crash
			$walk = array_walk($row, function (&$s) {
       $s = strval($s);
   });
			$link = "<a class='cancelClickEvent' target='_blank' href='../php/mod_showMetadata.php?".
					"languageCode=".Mapbender::session()->get("mb_lang")."&resource=wmc&id=".$row[0]."'>"._mb("Metadata")."</a>";
			array_push($row, $link);
			$resultObj["data"][]= $row;
		}
		$ajaxResponse->setResult($resultObj);
		$ajaxResponse->setSuccess(true);
		break;

	case "getWmcTemplatedata" :
		$wmcId = $ajaxResponse->getParameter("id");
        $v = [$wmcId];
        $t = ['i'];
		$sql = "SELECT wmc_serial_id as wmc_id, abstract, wmc_title"
        .", wmc_timestamp, wmc_timestamp_create, wmc_public"
        ." FROM mb_user_wmc WHERE wmc_serial_id = $1";

		$result = db_prep_query($sql,$v,$t);
		$row = db_fetch_assoc($result);
		
		$resultObj = [];
		$resultObj['wmc_id'] = $row['wmc_id'];
		$resultObj['wmc_abstract'] = $row['abstract'];
		$resultObj['wmc_title'] = $row['wmc_title'];
		$resultObj['wmc_timestamp'] = $row['wmc_timestamp'] != "" ? date('d.m.Y', $row['wmc_timestamp']) : "";
		$resultObj['wmc_timestamp_create'] = $row['wmc_timestamp_create'] != "" ? date('d.m.Y', $row['wmc_timestamp_create']) : "";
		$resultObj['public'] = $row['wmc_public'] == 1 ? true : false;
		$resultObj['linkHref'] = "../php/mod_showMetadata.php?languageCode=".Mapbender::session()->get("mb_lang")."&resource=wmc&id=".$row['wmc_id'];	

        unset($result);
        unset($row);

        $sql  = "SELECT target,type,key,value";
        $sql .= " FROM mb_user_wmc_template WHERE fkey_wmc_id = $1";
        
        $result = db_prep_query($sql,$v,$t);
        $elements = [];
        while($row = db_fetch_array($result)){
            $elements[$row["target"]][][$row["type"]][$row["key"]] = $row["value"];
        }
        
        $resultObj['elements'] = $elements;

		$ajaxResponse->setResult($resultObj);
		$ajaxResponse->setSuccess(true);
		break;
		
	case "save":
		global $firephp;
		$data = $ajaxResponse->getParameter("data");
        $dataObj = json_decode((string) $data);
		try {
			$wmcId = intval($dataObj->wmc_id);
		}
		catch (Exception) {
			$ajaxResponse->setSuccess(false);
			$ajaxResponse->setMessage(_mb("Invalid WMC ID.").$wmcId);
			$ajaxResponse->send();						
		}
        $targets = getAsArray($dataObj->elements);
//        new mb_notice("########### SCHABLONE:".print_r($targets,true));
        foreach ($targets as $target => $targetParamList) {
            foreach ($targetParamList as $targetParam) {
                foreach ($targetParam as $type => $element) {
                    foreach($element as $key => $value){
                        $sql_ = "SELECT count(fkey_wmc_id) as num FROM mb_user_wmc_template"
                            ." WHERE fkey_wmc_id = $1 AND target = $2 AND type = $3 AND key = $4";
                        $v_ = [$wmcId, $target, $type, $key];
                        $t_ = ['i', 's', 's', 's'];
//                        new mb_notice("########### SCHABLONE:".$sql_.implode(",",$v_));
                        $result = db_prep_query($sql_,$v_,$t_);
                        $row = db_fetch_assoc($result);
                        if(intval($row["num"])== 0) { // insert
                            if($value != null && $value != ""){
                                $sql_ = "INSERT INTO mb_user_wmc_template"
                                    ."(fkey_wmc_id,target,type,key,value) VALUES($1,$2,$3,$4,$5)";
                                $v_ = [$wmcId, $target, $type, $key, $value];
                                $t_ = ['i', 's', 's', 's', 's'];
//                                new mb_notice("########### SCHABLONE:".$sql_.implode(",",$v_));
                                $result = db_prep_query($sql_,$v_,$t_);
                            }
                        } else if(intval($row["num"])== 1) {
                            if($value != null && $value != "") { // update
                                $sql_ = "UPDATE mb_user_wmc_template"
                                    ." SET target = $1,type = $2,key = $3,value = $4"
                                    ." WHERE fkey_wmc_id = $5 AND target = $6 AND type = $7 AND key = $8";
                                $v_ = [$target, $type, $key, $value, $wmcId, $target, $type, $key];
                                $t_ = ['s', 's', 's', 's', 'i', 's', 's', 's'];
//                                new mb_notice("########### SCHABLONE:".$sql_.implode(",",$v_));
                                $result = db_prep_query($sql_,$v_,$t_);
                            } else { // delete
                                $sql_ = "DELETE FROM mb_user_wmc_template"
                                    ." WHERE fkey_wmc_id = $1 AND target = $2 AND type = $3 AND key = $4";
                                $v_ = [$wmcId, $target, $type, $key];
                                $t_ = ['i', 's', 's', 's'];
//                                new mb_notice("########### SCHABLONE:".$sql_.implode(",",$v_));
                                $result = db_prep_query($sql_,$v_,$t_);
                            }
                        } else { // break not unique
                            $ajaxResponse->setSuccess(false);
                            $ajaxResponse->setMessage(_mb("Template element for WMC ID.").$wmcId._mb(" is not unique."));
                            $ajaxResponse->send();
                            die();
                        }
                    }
                }
            }
        }
		$ajaxResponse->setMessage("Updated WMC Template for ID " . $wmcId);
		$ajaxResponse->setSuccess(true);		
		
		break;
	default: 
		$ajaxResponse->setSuccess(false);
		$ajaxResponse->setMessage(_mb("An unknown error occured."));
		break;
}

$ajaxResponse->send();
?>
