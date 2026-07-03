<?php

// __MB_PHP8_GUARD__ — load Mapbender globals defensively. In PHP 7 these
// files relied on the implicit "undefined constant -> string" behaviour, but
// PHP 8 fatals there. Guard prevents the fatal when the file is reached
// directly (e.g. via include from a stand-alone HTTP entry).
if (!defined('MB_VERSION_NUMBER') || !function_exists('_mb')) {
    require_once __DIR__ . '/../../core/globalSettings.php';
}
echo "<table width='100%' style='background-color:#FFFFe0'><tr align='center'><td style='padding:30px'><img alt='logo' src='../img/Mapbender_logo_and_text.png'></td></tr>" . 
	"<tr align='center'><td style='padding:30px'>".MB_VERSION_NUMBER . " " . strtolower(MB_VERSION_APPENDIX) . "</strong>..." .
	"loading application '" . (isset($this) && isset($this->guiId) ? $this->guiId : "") . "'</td></tr>".
	"<tr align='center'><td style='padding:30px'><img alt='indicator wheel' src='../img/indicator_wheel.gif'></td></tr>" . 
	"<tr align='center'><td style='padding:30px'><strong>please wait...</strong></td></tr></table>";
?>
