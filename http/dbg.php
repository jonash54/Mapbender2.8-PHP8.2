<?php error_log("DEBUG-START", 3, "/var/www/html/mapbender/log/debug.log");
file_put_contents("/var/www/html/mapbender/log/debug.log", "L1\n", FILE_APPEND);
require_once "/var/www/html/mapbender/http/plugins/mb_downloadFeedServer.php";
file_put_contents("/var/www/html/mapbender/log/debug.log", "L2-OK\n", FILE_APPEND);
