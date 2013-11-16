Jobify.Map = ( function($) {
	var geocoder,
	    $map,
	    items = new Array();

	function setupMap() {
		$map     = $( '#jobify-map-canvas' );
		geocoder = new google.maps.Geocoder();

		$map.gmap( {
			mapTypeId          : google.maps.MapTypeId.ROADMAP,
			streetViewControl  : false,
			scrollwheel        : false,
			center             : new google.maps.LatLng( jobifyMapSettings.center.lat, jobifyMapSettings.center.long ),
			zoom               : jobifyMapSettings.zoom == 'auto' ? 8 : parseFloat( jobifyMapSettings.zoom ),
			zoomControlOptions : {
				position : google.maps.ControlPosition.LEFT_CENTER
			}
		} ).bind( 'init', function(evt, map) {
			addLocations( jobifyMapSettings.points );

			$( '.map-filter' ).show();
		} );
	}

	function convertAddress( address, callback, item ) {
		geocoder.geocode( { 
			'address' : address 
		}, function(results, status) {
			if ( status == google.maps.GeocoderStatus.OK ) {
				callback( results[0].geometry.location );
			}

			cords = [ results[0].geometry.location.lat(), results[0].geometry.location.lng() ];
			
			$.post( jobifySettings.ajaxurl, { action : 'jobify_cache_cords', cords : cords, job : item.job } );
		});
	}

	function addLocations( points ) {
		$.each( points, function( index, value ) {
			var _item = value;

			if ( ! _item.location )
				return;

			if ( $.isArray( _item.location ) && _item.location[0] && _item.location[1] ) {
				$map.gmap( 'addMarker', {
					'position'  : new google.maps.LatLng( _item.location[0], _item.location[1] ),
					'bounds'    : jobifyMapSettings.center.long && ( jobifyMapSettings.zoom != 'auto' ) ? false : true,
					'animation' : google.maps.Animation.DROP,
					'title'     : _item.title,
					'tooltip'   : false
				}, function(map, marker) {
					new Tooltip({
						marker   : marker,
						content  : _item.title,
						cssClass : 'map-tooltip'
					});
				}).click(function(event, map) {
					window.location = _item.permalink;
				});
			} else {
				convertAddress( _item.location, function( latlong ) {
					$map.gmap( 'addMarker', {
						'position'  : latlong,
						'bounds'    : jobifyMapSettings.center.long && ( jobifyMapSettings.zoom != 'auto' ) ? false : true,
						'animation' : google.maps.Animation.DROP,
						'title'     : _item.title,
						'tooltip'   : false
					}, function(map, marker) {
						new Tooltip({
							marker   : marker,
							content  : _item.title,
							cssClass : 'map-tooltip'
						});
					}).click(function(event, map) {
						window.location = _item.permalink;
					});
				}, _item );
			}
		});
	}

	function bindFilter() {
		var data,
		    xhr;

		$( '.live-map' ).submit(function() {
			data = {
				'action'          : 'jobify_update_map',
				'search_keywords' : $( '#search_keywords' ).val(),
				'search_location' : $( '#search_location' ).val(),
				'search_category' : $( '#search_category' ).val()
			}

			xhr = $.ajax({
				type    : 'POST',
				url     : jobifySettings.ajaxurl,
				data    : data,
				success : function( response ) {
					points = response;

					$map.gmap( 'clear', 'markers' );
					
					addLocations( $.parseJSON( response ) );
				}
			});

			return false;
		});
	}

	return {
		init : function() {
			setupMap();
			bindFilter();

			$( '#search_keywords, #search_location, .job_types input, #search_category' ).change( function() {
				$( '.live-map' ).trigger( 'submit' );
			} );
		}
	}
} )(jQuery);