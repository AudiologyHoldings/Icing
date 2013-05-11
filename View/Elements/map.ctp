<?php
/**
* Google Map Element
* @author Nick Baker
* @version 1.0
* 
* Usage

$this->element('Icing.map', array(
	'center' => array('lat' => 0, 'lon' => 0),
	'points' => array(
		array('lat' => 0, 'lon' => 0, 'info' => 'This is the info window', 'title' => 'Marker Title'),
		array('lat' => 1, 'lon' => 1, 'info' => 'This is second info window', 'title' => 'Marker Title 2'),
	),
	'width' => '460px',
	'height' => 250px',
	'zoom' => 14,
));

Required, you need to have $this->Js->writeBuffer(); in your layout or view somewhere, or you have to call it after you call the element to render the javascript to run the map.

*/
$center = isset($center) ? $center : array('lat' => 0, 'lon' => 0); 
?>
<?php if(isset($center['lat']) && isset($center['lon'])): ?>
	<?php
	$width = isset($width) ? $width : '460px';
	$height = isset($height) ? $height : '350px';
	$points = isset($points) ? $points : array(array('lat' => 0, 'lon' => 0, 'info' => '', 'title' => 'Title'));
	$json_points = json_encode($points);
	$zoom = isset($zoom) ? $zoom : 14;
	?>
	<?php echo $this->Html->script('http://maps.google.com/maps/api/js?sensor=false'); ?>
	
	<script type="text/javascript">
	function icingLoadMap(){
		var latlon = new google.maps.LatLng(<?php echo $center['lat']; ?>, <?php echo $center['lon']; ?>);
		var myOptions = {
			zoom: <?php echo $zoom; ?>,
			center: latlon,
			mapTypeId: google.maps.MapTypeId.ROADMAP
		};
		var map = new google.maps.Map(document.getElementById("map_canvas"), myOptions);
		var points = <?php echo $json_points; ?>;
		var marker;
		var infowindow;
		for(var i = 0; i < points.length ; i++){
			infowindow = new google.maps.InfoWindow({
				content: "<div class='map_info_window'>" + points[i].info + "</div>"
			});
			marker = new google.maps.Marker({
				position: new google.maps.LatLng(points[i].lat, points[i].lon),
				map: map,
				title: points[i].title
			});
			icingAddMarker(map, marker, infowindow);
			if(points.length == 1){
				infowindow.open(map,marker);
			}
		}
	}
	function icingAddMarker(map, marker, infowindow){
		google.maps.event.addListener(marker, 'click', function() {
			infowindow.open(map,marker);
		});
		google.maps.event.addListener(map, 'click', function() {
			infowindow.close();
		});
	}
	</script>
	
	<div id="map_canvas" style="width: <?php echo $width; ?>; height: <?php echo $height; ?>;">
	</div>
	
	<?php echo $this->Js->buffer('icingLoadMap();'); ?>
<?php endif ;?>