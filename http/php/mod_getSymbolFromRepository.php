<?php

// __MB_PHP8_GUARD__ — load Mapbender globals defensively. In PHP 7 these
// files relied on the implicit "undefined constant -> string" behaviour, but
// PHP 8 fatals there. Guard prevents the fatal when the file is reached
// directly (e.g. via include from a stand-alone HTTP entry).
if (!defined('MB_VERSION_NUMBER') || !function_exists('_mb')) {
    require_once __DIR__ . '/../../core/globalSettings.php';
}
require_once(__DIR__."/../../core/globalSettings.php");
//apache2 mod_rewrite -  RewriteRule ^/icons/maki/([^/]+)/([^/]+)/([^[/]+).png$ http://127.0.0.1/mb_trunk/php/mod_getSymbolFromRepository.php?marker-color=$1&marker-size=$2&marker-symbol=$3 [P,L,QSA,NE]
//http://127.0.0.1/icons/maki/7e7e7e/large/airfield.png
//read variables from path - INFO: marker-color without # as first element!!!!!!
//read from filesystem and recode from svg to png
//path
$symbolPath = __DIR__."/../extensions/makiicons/mapbox-maki-463a9ff/icons/";
//get name from parameter

//read list of possible filenames
$svgFiles = scandir($symbolPath);

array_walk($svgFiles, 'cutEndPart');
$svgFiles = array_unique($svgFiles);
//echo implode(',', $svgFiles);
$searchString = "airfield";
$marker = "marker";
$markerSize = "medium";
$markerColor = "#7e7e7e";

if (isset($_REQUEST["marker-symbol"]) & $_REQUEST["marker-symbol"] != "") {
	$marker = $_REQUEST["marker-symbol"];
}

if (isset($_REQUEST["marker-size"]) & $_REQUEST["marker-size"] != "") {
	//validate to inside / outside - TODO implement other ones than intersects which is default
	$testMatch = $_REQUEST["marker-size"];	
 	if (!($testMatch == 'medium' or $testMatch == 'large' or $testMatch == 'small')){ 
		//echo 'searchTypeBbox: <b>'.$testMatch.'</b> is not valid.<br/>';
		echo 'Parameter <b>marke-size</b> is not valid (small, medium, large).<br/>'; 
		die(); 		
 	}
	$markerSize = $testMatch; //TODO activate this
	$testMatch = NULL;
}

if (isset($_REQUEST["marker-color"]) & $_REQUEST["marker-color"] != "") {
	//validate to hex color value
	//
	//add # before, because # cannot be part of the url
	$testMatch = "#".$_REQUEST["marker-color"];	
	//https://stackoverflow.com/questions/12837942/regex-for-matching-css-hex-colors
	$pattern = '/#([a-f0-9]{3}){1,2}\b/i';
	
	if (!preg_match($pattern,$testMatch)){ 
		echo 'Parameter <b>marker-color</b> is not a valid hex color code.<br/>'; 
		die(); 		
 	}
	$markerColor = $testMatch;
	$testMatch = NULL;
}

//$e = new mb_exception("color: ".$markerColor);

$key = array_search($marker, $svgFiles);

if (gettype($key) == "integer") {
	$svgGraphicFilename = $symbolPath.$svgFiles[$key]."-15.svg";
} else {
	$svgGraphicFilename = $symbolPath."marker-15.svg";
}

$svgGraphic = file_get_contents($svgGraphicFilename);

//replace color
$svgGraphic = str_replace('<path ', '<path style="fill:'.$markerColor.'" ', $svgGraphic);

//https://stackoverflow.com/questions/9226232/how-to-read-an-svg-with-a-given-size-using-php-imagick
$im = new Imagick();
$im->setBackgroundColor(new ImagickPixel('transparent'));

// PHP 8 + Imagick on Debian 12: readImageBlob can't auto-detect the SVG
// blob without a hint. Pass it through a temp file with .svg suffix so the
// librsvg delegate is invoked by extension.
$svgTmp = tempnam(sys_get_temp_dir(), 'mb-svg-') . '.svg';
file_put_contents($svgTmp, $svgGraphic);
try {
    $im->readImage($svgTmp);
} finally {
    @unlink($svgTmp);
}

switch ($markerSize){
	case "medium":
		$im->scaleImage(20,20);
		break;
	case "small":
		$im->scaleImage(15,15);
		break;
	case "large":
		$im->scaleImage(25,25);
		break;
}

$im->setImageFormat("png32");
header('Content-type: image/png;filename="'.$svgFiles[$key].'-15.svg"');
echo $im;

function cutEndPart(&$item) {
    $name = explode('-', (string) $item);
    $item = $name[0];
}
?>
