<?php
header("Content-type: application/json");

function endsWith( $haystack, $needle ) {
    $length = strlen( (string) $needle );
    if( !$length ) {
        return true;
    }
    return substr( (string) $haystack, -$length ) === $needle;
}

function startsWith( $haystack, $needle ) {
    $length = strlen( (string) $needle );
    return substr( (string) $haystack, 0, $length ) === $needle;
}

$cacheObjectJson = json_encode(apcu_cache_info());

$cacheObject = json_decode($cacheObjectJson);

$memoryUsageMapbender = 0;

$mapbenderCaches = new stdClass();
$mapbenderCaches->variables = [];

foreach ($cacheObject->cache_list as $cacheEntry) {
    if ( startsWith($cacheEntry->info, "mapbender:") ) {
        foreach ($cacheEntry as $key => $value){
            if ( endsWith( $key, "time" ) ) {
                $cacheEntry->{$key} = date('d-m-Y H:i:s', $value);
            }
        }
        $mapbenderCaches->variables[] = $cacheEntry;
        $memoryUsageMapbender = $memoryUsageMapbender + $cacheEntry->mem_size;
    }
}
$mapbenderCaches->mem_usage = $memoryUsageMapbender / 1000000;

$apcInfoJson = json_encode($mapbenderCaches);


print($apcInfoJson);
?>
