<?php
require_once(__DIR__."/../php/mb_validateSession.php");
require_once(__DIR__."/../classes/class_json.php");

/*
	copies an uploaded file from a field called 'geoJSONUpload'
	to temporary directoery and returns an url to it
*/

if($_SERVER['REQUEST_METHOD'] == "POST") {
	$url = "";
	$error = "";
	$filename = "wfs_upload".sha1(date("Y-m-d-His".random_int(0, mt_getrandmax()))).".gjson";
	$filepath = __DIR__."/../tmp/$filename";
	if(copy($_FILES['geoJSONUpload']['tmp_name'],$filepath) === False){
		$error =  $_FILES['geoJSONUpload']['tmp_name'] ."-> $filepath";
	}else{
		$url  = "/../tmp/".$filename;
	}
	echo getForm($url,$error);

}else if ($_SERVER['REQUEST_METHOD'] == "GET"){
	echo getForm("","");
} 



function getForm($url,$error)
{
$form = <<<FORM
	<html>
	<head>
	<script>
	function loadResult(){
		var url = document.forms[0].url.value;
		if(url == ""){
			alert("$error");
		}else{
			alert(url);
		}
	}
	</script>
	<head>
	<body onload="loadResult(event);">
		<span>$error</span>
		<form name="uploadWfsResults"  id="uploadWfsResults" action="../php/mod_echoFileUpload.php" method="post">
		<label for="geoJSONUpload">Upload WFsResults</label><input type="file" name="geoJSONUpload" />
		<input type="hidden" name="url" value="$url" />
		<input type="submit" />
		</form>
	</body>
	</html>

FORM;
return $form;

}


?>
