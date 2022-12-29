(function( $ ) {
  'use strict';

  // Set up popup
  var popup = new Popup();

  function Popup() {
      this.popup = null;
      this.active = false;
  }

  Popup.prototype.create = function(evt) {
      this.destroy();
      this.popup = new OpenLayers.Popup.FramedCloud
          (
              "data",
              evt.object.customLonLat,
              new OpenLayers.Size(200,200),
              evt.object.customContent,
              null,
              true,
              function() {
                  this.toggle();
              }
          );
      evt.object.customMarkers.map.addPopup(this.popup);
      OpenLayers.Event.stop(evt);
  }

  Popup.prototype.destroy = function() {
      if(this.active) {
          this.popup.destroy();
          this.popup = null;
      }
  }

  if (window.addEventListener) {
      window.addEventListener("load", initMap, false);
  } else if (window.attachEvent) {
      document.attachEvent("onreadystatechange", initMap);
  }

  function initMap() {
    var map = new OpenLayers.Map("osm_map");
    // Map Theme: https://wiki.openstreetmap.org/wiki/Tile_servers
    map.addLayer(new OpenLayers.Layer.OSM("CARTO OSM", [

      // Standard
	    "https://a.tile.openstreetmap.org/${z}/${x}/${y}.png",
      "https://b.tile.openstreetmap.org/${z}/${x}/${y}.png",
      "https://c.tile.openstreetmap.org/${z}/${x}/${y}.png",

      // Humanitarian option
      // "http://a.tile.openstreetmap.fr/hot/${z}/${x}/${y}.png",
      // "http://b.tile.openstreetmap.fr/hot/${z}/${x}/${y}.png",

      // Light option
      // "https://cartodb-basemaps-2.global.ssl.fastly.net/light_all/${z}/${x}/${y}.png",
      // "https://cartodb-basemaps-3.global.ssl.fastly.net/light_all/${z}/${x}/${y}.png",
      // "https://cartodb-basemaps-4.global.ssl.fastly.net/light_all/${z}/${x}/${y}.png",
    ], {
        attribution: 'Data &copy; <a href="http://www.openstreetmap.org/copyright" target="_blank">OpenStreetMap</a>. Map tiles &copy; <a href="https://carto.com/attribution" target="_blank">CARTO</a>.'
    }));

    setMapOptions(map, mylocation);
  };

  function createMarker(map, markers, point, data, image) {
   var marker = new OpenLayers.Marker(point);

   // Image for marker url
   if (image) {
     var size = new OpenLayers.Size(20,30);
     var offset = new OpenLayers.Pixel(-(size.w/2), -size.h);
     marker.icon.size = size;
     marker.icon.offset = offset;
     marker.icon.url = image;
   }

   marker.customContent = data;
   marker.customLonLat = point;
   marker.customMarkers = markers;

   marker.events.register('mousedown', marker, markerClick);

   markers.addMarker(marker);
 }

 function markerClick(evt) {
   popup.create(evt);
 }

  function setMapOptions(map, mylocation, lonLat) {

    var lonLat = new OpenLayers.LonLat(mylocation.long, mylocation.lat).transform(
      new OpenLayers.Projection("EPSG:4326"),
      map.getProjectionObject()
    );

    var markers = new OpenLayers.Layer.Markers("Markers");
    map.addLayer(markers);
    var bounds = map.calculateBounds();
    $.each(locations, function(contactId, contactDetails) {
      var data = contactDetails.text;
      var link = contactDetails.logoLink;
      var point = new OpenLayers.LonLat(
        contactDetails.long,
        contactDetails.lat).transform(
          new OpenLayers.Projection("EPSG:4326"),
          map.getProjectionObject()
      );
      createMarker(map, markers, point, data, link);
      // bounds.extend(point);
    });

    if (mylocation.long != -100.372127 && mylocation.lat != 38.891033) {
      createMarker(map, markers, lonLat, "My Location", 'default');
    }

    var newBound = markers.getDataExtent();
    map.zoomToExtent(newBound);
    $('.olControlAttribution').css('bottom','0px');
  }

})(jQuery);
