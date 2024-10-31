
	var layerSortable;
jQuery(function($) {
	$('.noscript-action').remove();

	var layerSortableInit = function() {
		try { // a hack to make sortables work in jQuery 1.2+ and IE7
			$('#nicemap_layer_list').SortableDestroy();
		} catch(e) {}
		layerSortable = $('#nicemap_layer_list').Sortable( {
			accept: 'layer-sortable',
			helperclass: 'sorthelper',
			handle: 'h4.layer-title',
      stop: setWeights,
			onStop: layerSortableInit,
		} );
	}

	// initialize sortable
	layerSortableInit();

  jQuery('#nicemap_settings_form').submit(
      function() {
          jQuery('#nicemap_layer_order').val(jQuery.SortSerialize('nicemap_layer_list').hash);
        }
      );
});

function setWeights() {
  alert("test");
}

