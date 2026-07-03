<?php
// __MB_PHP8_GUARD__ — when this JS template is reached directly, ensure the
// Mapbender class is loaded; in normal use it is included from a context
// that has already loaded mapbender.conf + the lib classes.
if (!class_exists('Mapbender')) {
    require_once __DIR__ . '/../../core/globalSettings.php';
    require_once __DIR__ . '/../../lib/class_Mapbender.php';
}
?>
options.$target.each(function () {
	var map = $(this).mapbender();
	if (map && map.zoomToExtent) {
		var coordinates = '<?php echo Mapbender::session()->get("mb_myBBOX") ?>';
		var c = coordinates.split(",");
		if (c.length === 4) {
			var b =	new OpenLayers.Bounds();
			b.extend(new OpenLayers.LonLat(
				parseFloat(c[0], 10),
				parseFloat(c[1], 10)
			));
			b.extend(new OpenLayers.LonLat(
				parseFloat(c[2], 10),
				parseFloat(c[3], 10)
			));
			map.mapbenderEvents.mapReady.register(function () {
				map.zoomToExtent(b);
			});
		}
	}			
});
