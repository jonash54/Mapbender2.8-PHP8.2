<?php

// __MB_PHP8_GUARD__ — load Mapbender globals defensively. In PHP 7 these
// files relied on the implicit "undefined constant -> string" behaviour, but
// PHP 8 fatals there. Guard prevents the fatal when the file is reached
// directly (e.g. via include from a stand-alone HTTP entry).
if (!defined('MB_VERSION_NUMBER') || !function_exists('_mb')) {
    require_once __DIR__ . '/../../core/globalSettings.php';
}
echo "<table width='100%' style='background-color:#e2e2e2'><tr align='center'><td><br><br><br><br><img alt='ajax-loader' src='../img/ajax-loader.gif'>"."&nbsp;&nbsp;&nbsp;&nbsp;"."<img alt='logo' src='../geoportal/geoportal_logo.png'>"."&nbsp;&nbsp;&nbsp;&nbsp;"."<img alt='ajax-loader' src='../img/ajax-loader.gif'></td></tr><tr align='center'><td><br><strong>"._mb('please wait ... ')."</strong></td></tr>"."<tr  align='center'><td><br>"._mb('Loading application: ')."" . (isset($this) && isset($this->guiId) ? $this->guiId : "") . "</td></tr></table>";
?>
