<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
<!DOCTYPE html>
<html>
  <head>
    <style>
      #map-canvas {
        width: 100%;
        height: 575px;
      }
    </style>
    <script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAw_MgYFtKfL6HRoJSOFwR51VEKkqSkfbU&sensor=false"></script>
    <script>
    var map;
    var marker;
    
    var x = document.getElementById("map-canvas");
    
    function gmap_initialize() {
      var center_lat = <?php echo $lat;?>;
      var center_long = <?php echo $long?>;

      markers = [['Debitur',center_lat,center_long]];

      var mapOptions = {
        center: new google.maps.LatLng(center_lat,center_long),
        zoom: 17,
        mapTypeId: 'hybrid',
        mapTypeControl: false
      };

      map = new google.maps.Map(document.getElementById("map-canvas"),
          mapOptions);
  
      for (var i = 0; i < markers.length; i++) {
          marker = new google.maps.Marker({
          map: map,
          position: new google.maps.LatLng(markers[i][1],markers[i][2])
        });
      }
    }
      
    google.maps.event.addDomListener(window, 'load', gmap_initialize);
    </script>
  </head>
  <body>
    <div id="map-canvas"></div>
  </body>
</html>
