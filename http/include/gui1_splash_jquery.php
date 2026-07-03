<?php

// __MB_PHP8_GUARD__ — load Mapbender globals defensively. In PHP 7 these
// files relied on the implicit "undefined constant -> string" behaviour, but
// PHP 8 fatals there. Guard prevents the fatal when the file is reached
// directly (e.g. via include from a stand-alone HTTP entry).
if (!defined('MB_VERSION_NUMBER') || !function_exists('_mb')) {
    require_once __DIR__ . '/../../core/globalSettings.php';
}
echo "<img alt='indicator wheel' src='../img/indicator_wheel.gif'>&nbsp;" .
	"<strong>Ma<span style='font-color:#0000CE'>p</span>" .
	"<span style='font-color:#C00000'>bTESTTEST</span>ender " .
	MB_VERSION_NUMBER . " " . strtolower(MB_VERSION_APPENDIX) . "</strong>..." .
	"loading application '" . (isset($this) && isset($this->guiId) ? $this->guiId : "") . "'";
?>
