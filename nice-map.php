<?php

require 'wms_client.inc';
require 'nicemap_admin.php';
require 'nicemap.theme.php';

/*
Plugin Name: Nicemap
Plugin URI: 
Description: Adds WMS Client Support
Version: 0.1
Author: Tom MacWright
Author URI: http://www.developmentseed.org/
*/

/*  Copyright 2009  Tom MacWright  (email : tom@developmentseed.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

add_action('admin_menu', 'nicemap_menu');
add_action('init', 'nicemap_page_init');
add_action('admin_head', 'nicemap_admin_head');
add_action('wp_head', 'nicemap_wp_head');
add_filter('the_content', 'nicemap_the_content');


function set_server() {
  update_option('nicemap_wms_url', 'http://onearth.jpl.nasa.gov/wms.cgi');
}

add_action('activate_nicemap', 'set_server');

/**
 * A proxy to the nicemap compatibilities cache
 * @param $base_url the base URL of the WMS server
 * @return array on success, false on error
 */
function nicemap_cache($base_url = NULL, $reset = FALSE) {
  $base_url = $base_url ? $base_url : get_option('nicemap_wms_url');
  $cache = get_option('nicemap_cache');
  if (!isset($cache[$base_url]) || $reset) {
    try {
      $map = new nicemap_map($base_url);
    } catch(Exception $e) {
      return new WP_Error('WMS Server Error', $e->getMessage());
    }
    $cache[$base_url] = $map->dump();
    update_option('nicemap_cache', $cache);
  }
  else {
    // Create a new map object without re-querying the WMS server
    $map = new nicemap_map($base_url, $cache[$base_url]);
  }
  return $map;
}

function nicemap_menu() {
  add_options_page('Nicemap Options', 'Nicemap', 8, __FILE__, 'nicemap_options');
}

function nicemap_page_init() {
  wp_enqueue_script('jquery');
}

function nicemap_init() {
  register_settings('server-settings', 'nicemap_wms_url');
  register_settings('server-settings', 'nicemap_bgcolor');
  register_settings('server-settings', 'nicemap_wms_crs');
  register_settings('server-settings', 'nicemap_layers');
}

/**
 * Generate map array.
 */
function build_map($bounds = array()) {    
  // Set defaults
  // Much like the Drupal version gets defaults
  // set by views.
  /*
  $options['minx'] = -100;
  $options['miny'] = -40;
  $options['maxx'] = 100;
  $options['maxy'] = 40;
   */

  // Allow override of default bounds.
  // TODO: default bounds should be wider
  foreach($bounds as $k=>$v) {
    $options[$k] = $v;
  }
  //TODO: cache this!
  $map = nicemap_cache( 
    get_option('nicemap_wms_url'));
  $map->bgcolor = get_option('nicemap_wms_bgcolor');
  $map->bgcolor = $options['bgcolor'];
  $map->bounds = array(
    'miny' => $options['miny'],
    'maxy' => $options['maxy'],
    'minx' => $options['minx'],
    'maxx' => $options['maxx'],
  );

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

  return $map;
}

function nicemap_map($overrides = array()) {
	global $wp_query, $posts, $post;
  require_once('nicemap.theme.php');
  $map_options['width'] =  450;
  $map_options['height'] = 220;

  foreach($overrides as $k=>$v) {
    $map_options[$k] = $v;
  }
  $options = array('post_ids' => '');
  $points = array();
	if ($wp_query->current_post == -1)
	{
		$comma = '';
		while ($wp_query->have_posts()) {
			$wp_query->the_post();
      //TODO: more efficient, join-based multiple ID function
      if($c = nicemap_get_coords($wp_query->post->ID)) {
        $p = array(
          'title' => $wp_query->post->post_title,
          'href' => get_permalink($wp_query->post->ID),
        );
        // Add coordinates to the root of that array
        $p = array_merge($p, $c);
        $points[] = $p;
      }
		}

	} else {
    // We know that this is a single post view.
    $coords = nicemap_get_coords($post->ID);
    if(!$coords['lat'] || !$coords['lon']) {
      return false;
    }
    // The post or page has a location - show a local map
    $points[] = array(
      'title' => $wp_query->post->post_title,
      'href' => get_permalink($wp_query->post->ID),
      'lat'   => $coords['lat'],
      'lon'   => $coords['lon'],);
    $point_bounds = array(
      'minx' => $coords['lon'] - 10,
      'maxx' => $coords['lon'] + 10,
      'miny' => $coords['lat'] - 10,
      'maxy' => $coords['lat'] + 10,);
  }


  $bounds = ($overrides['bounds']) ? $overrides['bounds'] : array();
  try {
    $map = build_map($bounds);
    echo theme_nicemap_map($points,$map,$map_options['width'],$map_options['height']);
  } catch ( Exception $e ) {
    // TODO: less red error messages?
    echo "<div style='color: red; padding: 10px;'>".$e->getMessage()."</div>";
  }
}

/**
 * Get coordinates array from post ID depending on preset compat layer
 * @param $id ID of a post
 * @return array of post coordinates in form
 * \code
 * array(
 *  'lat' => 5.5,
 *  'lon' => 5.5);
 * \endcode
 */
function nicemap_get_coords($id) {
  switch(get_option('nicemap_compat')) {
    case 'geomashup':
      return get_coords_geomashup($id);
      break;
    case 'wpgeo':
      return get_coords_wpgeo($id);
      break;
    default:
      // No compatibility set and no
      // current native driver
      return false;
  }
}

/**
 * Single-post WP-Geo handler
 * @param $post_id
 * @return array in the form
 * \code
 * array(
 *  'lat' => 5.5,
 *  'lon' => 5.5);
 * \endcode
 */

function get_coords_wpgeo($post_id) {
  $coords = array(
   'lat' => get_post_meta($post_id, '_wp_geo_latitude', true),
   'lon' => get_post_meta($post_id, '_wp_geo_longitude', true));
  if($coords['lat'] && $coords['lon']) {
    return $coords;
  }
  else {
    return false;
  }
}

/**
 * Single-post geomashup handler
 * (c) Dylan Khun
 * Modified from the geo-mashup project.
 * Licensed GPL.
 * @param $post_id
 * @return array in the form
 * \code
 * array(
 *  'lat' => 5.5,
 *  'lon' => 5.5);
 * \endcode
 */
function get_coords_geomashup($post_id) {
  $meta = trim(get_post_meta($post_id, '_geo_location', true));
  $coordinates = array();
  $places = 10;
  if (strlen($meta)>1) {
    list($lat, $lng) = split(',', $meta);
    $lat_dec_pos = strpos($lat,'.');
    if ($lat_dec_pos !== false) {
      $lat = substr($lat, 0, $lat_dec_pos+$places+1);
    }
    $lng_dec_pos = strpos($lng,'.');
    if ($lng_dec_pos !== false) {
      $lng = substr($lng, 0, $lng_dec_pos+$places+1);
    }
    if($lat && $lng) {
      $coordinates['lat'] = $lat;
      $coordinates['lon'] = $lng;
    }
    else {
      return false;
    }
  }
  return $coordinates;
}





?>
