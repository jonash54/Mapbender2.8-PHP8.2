<?php
require_once(__DIR__."/../php/mb_validateSession.php");
require_once(__DIR__."/../classes/class_json.php");



if($_SERVER['REQUEST_METHOD'] == "POST") {
	$ajaxResponse = new AjaxResponse($_POST);
	try{
		switch ($ajaxResponse->getMethod()) {

			case "createFile":
				$data = $ajaxResponse->getParameter('data');
				$fileId = createFile($data);
				$e = new mb_exception("fileid ".$fileId );
				if($fileId == false){ throw new Exception("Could not create file");}
				$url = "../tmp/$fileId";
				$result = ['url'=>$url];
			break;

			default:
				throw new Exception("method invalid");

		}
		$ajaxResponse->setSuccess(true);
		$ajaxResponse->setResult($result);
	}
	catch (Exception $E){
		$ajaxResponse->setSuccess(false);
		$ajaxResponse->setMessage($E->getMessage(). " [". $E->getLine() ."]");

	}

	$ajaxResponse->send();

}else if ($_SERVER['REQUEST_METHOD'] == "GET"){
	ob_start();
		$fileid = $_GET['file'];
		header("Content-Type","application/x-json");
		header("Content-Disposition", "attachment; filename=\"".  $filename ."\"");
		$result = file_get_contents(realpath(__DIR__."/../../http/tmp/$id"));
		print $result;
	ob_end_flush();
} 

function createFile($data){
	$filename = sha1(date("Y-m-d-His".random_int(0, mt_getrandmax()))).".gjson";
	$filepath = __DIR__."/../tmp/$filename";
	$e 	= new mb_exception( $filepath);
	if (file_put_contents($filepath,$data) === false){
		return false;
	}
	return $filename;
}

?>
