<?php

/**
 * @file
 * All theme functions for the nicemap module.
 */

/**
 * Hook: the_content
 */
function nicemap_the_content($content = '')
{
  global $posts, $post;
  $nicemap_display = get_option('nicemap_display');
  if($nicemap_display['where_show'] != 'manually') {
    if(
      ( is_single() && ($nicemap_display['where_show'] == 'single posts')) ||
      (!is_single() && ($nicemap_display['where_show'] == 'lists')) ||
      $nicemap_display['where_show'] == 'lists and single posts') {

      if(isset($nicemap_display['height'], $nicemap_display['width'])) {
        $dims = array('width' => $nicemap_display['width'],
          'height' => $nicemap_display['height']);
      }
      else {
        $dims = array();
      }
      // Get the post
      $id = $post->ID;
      if ($nicemap_display['display_on_post'] == 'top')
      {
        // Show at top of post
        return nicemap_map($dims).$content;
      }
      elseif ($nicemap_display['display_on_post'] == 'bottom')
      {
        // Show at bottom of post
        return $content.nicemap_map($dims);
      }
    }
  }
  return $content;
}

/**
 * Wrapper theme function allows for easy overriding of map style
 *
 * @param $points
 *   Array An array of points in the form:
 *       array('title' => 'Chester',
 *           'content' => 'Test',
 *           'lat' => 40.47,
 *           'lon' => -74.41,
 *           'weight' => 5,
 *           ),
 *  @param $map
 *   nicemap_map object
 *  @param $width
 *   int Desired width of the resulting image
 *  @param $height 
 *   int Desire height of the resulting image
 *
 *  @return
 *   A fully themed map
 */
function theme_nicemap_map($points = array(), $map, 
  $width = 450, $height = 220, $js = TRUE) {
  // Expand map to fit all points.
  // TODO: There should be an override where users can set their own projections
  $display = get_option('nicemap_display');
  if(!$display['zoom']) {
    $display['zoom'] = 10;
  }
  $map->expand($points, $display['zoom']);
  $points = $map->process($points, 
    array(
      'width' => $width, 
      'height' => $height), get_option('nicemap_wms_crs'));
  $options = array(
    'width' => $width,
    'height' => $height,
  );
  return _theme_nicemap_map_full($points, $map, $options);
}

/**
 * Theme a point on a map
 *
 * @param $point 
 *   an array which, by default requires the keys
 *   title, weight, x, y and an optional array of attributes
 * 
 */
function theme_point($point) {
  $weight = $point['weight'] ? ' weight-'. $point['weight'] : '';
  $title = "<span>". 
    substr(strip_tags($point['title']), 0, 18) 
    ."</span>";
  $class = $class ?
    $class ." geopoint $weight" :
    "geopoint $weight";

  // sorry, we're gonna bulldoze your style attributes
  $style = 'left: '. $point['x'] .'%; top: '. $point['y'] .'%;';
  // and your id attributes
  $id = "geopoint-". $point['i'];
  return '<a href="'.$point['href'].'" class="'.$class.'" style="'.$style.'">'.$title.'</a>';
}

function theme_content($point) {
  $content = $point['content'];
  $close = "<span class='close'>". 'Close' ."</span>";
  return "<div class='geoitem' id='geoitem-". $point['i'] ."'>$close $content</div>";
}

function _theme_nicemap_map_full($points, $map, $options) {
  $w = $options['width']  .'px';
  $h = $options['height'] .'px';
  $options['srs'] = get_option('nicemap_wms_crs');
  // Set the default if the SRS isn't set
  if(!$options['srs']) {
    $options['srs'] = "EPSG:900913";
  }

  if ($options['custom']) {      
    $layers = $options['layers'];
    $weights = $options['weights'];
    $styles = $options['styles'];
  }
  else {
    $defaults  = get_option('nicemap_layers', '');
    // sort by weights
    $layers = array_combine($defaults['weights'], array_keys($defaults['layers']));
    ksort($layers);
    foreach($layers as $l) {
      // is the layer enabled?
      if($defaults['layers'][$l]) {
        $options['layers'][] = $l;
        $options['styles'][] = $defaults['styles'][$l];
      }
    }
  }
  $options['layers'] = implode(',', $options['layers']);
  $options['styles'] = implode(',', $options['styles']);



  $map_url = $map->url($options);
  if ($map_url) {
    $map_bg = "background-image:url('$map_url');";
  }

  // use a hardcoded index to associate point to item
  $i = 0;
  foreach ($points as $point) {
    $point['i'] = $i;
    $geopoints  .= theme_point($point);
    $geocontent .= theme_content($point);
    $i++;
  }

  $attr = 'class = "nicemap-map" style = "'.$map_bg.' width:'.$w.'; height:'.$h.';"';

  return "
    <div $attr>
      $geopoints
      <div class='hidden'>$geocontent</div>
    </div>
  ";
}



/**
 * Shortcode support
 */

function nicemap_shortcode($atts) {
	extract(shortcode_atts(array(
		'lat' => '0.0',
		'lon' => '0.0',
    'width' => 420,
    'height' => 220,
    'title' => '',
  ), $atts));
  $atts['width'] = ($width) ? $width : 420;
  $atts['height'] = ($height) ? $height : 220;
  $points[] = 
    array(
      'lat' => $atts['lat'],
      'lon' => $atts['lon'],
      'title' => $atts['title']);
  $map = build_map(array());
  return theme_nicemap_map($points, $map, $atts['width'], $atts['height']);
}
add_shortcode('nicemap', 'nicemap_shortcode');



?>
