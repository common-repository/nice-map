nicemap = {};

nicemap.iehover = function() {
  if (jQuery('div.nicemap-map').size() > 0) {
    // Provide consistent hovering
    jQuery('div.nicemap-map a.geopoint').hover(function(){
        jQuery(this).find('span').show();
    }, function() {
        jQuery(this).find('span').hide();
    });
  }
}

  jQuery(document).ready(function() {
    if (jQuery.browser.msie) {
      nicemap.iehover();
    }
    if (jQuery('div.nicemap-map div.geoitem').size() > 0) {
    }

  });
