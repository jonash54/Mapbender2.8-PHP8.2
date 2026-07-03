<?php

require_once(__DIR__."/../php/mb_validateSession.php");

ob_start();

$download = [];

$download["dir"]  = TMPDIR . "/";
$download["file"] = trim((string) $_REQUEST["download"]);

if(!(bool)$download["file"]) {
	die("No filename given.");
}
/*
 * @security_patch fdl done
 * This allows filenames like ../../
 */
if(str_contains($download["file"],"..")) {
	die("Illegal filename given.");
}

if(!file_exists(implode('', $download)) || !is_readable(implode('', $download))) {
	die("An error occured.");
}
/*
switch(substr($download["file"],-3)) {
	case "gpx":
		$filename = "GPS-Track.gpx";
		break;
	case "kml":
		$filename = "Google-Earth-Flug.kml";
		break;
	default:
		die("An error occured.");
}
*/
$filename = $download["file"];

#"header("Content-Type: application/octet-stream");
header("Content-Type: application/vnd.google-earth.kml+xml");
header("Content-Disposition: attachment; filename=\"".$filename."\"");

readfile(implode('', $download));

?>
