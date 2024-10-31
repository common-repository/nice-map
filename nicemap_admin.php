<?php

/**
 * Nice Map admin functions
 */


function nicemap_sort($a, $b) {
  global $layer_settings;
  return $layer_settings['weights'][$a] > $layer_settings['weights'][$b];
}


/**
 * Generate nicemap options page.
 * Admin-side.
 */

function nicemap_options() {
    if ($wms = get_option('nicemap_wms_url', '')) {
      $spec = nicemap_cache($wms);
      if(is_wp_error($spec)) {
        $message = '<div class="error">'.
          $spec->get_error_message().'</div>';
      }
      else {
        $crs = $layers = $styles = array();
        global $layer_settings;
        $layer_settings = get_option('nicemap_layers');
        if(!$layer_settings) $layer_settings = array();
        $layer_weights_serialized = get_option('nicemap_layer_order');
        // This function couldn't be safe. But it's fine in this situation.
        parse_str($layer_weights_serialized);

        // This could use a quick explanation
        // n - layer weight is just as good as reversing
        // the array
        $layer_number = sizeof($layer_weights_serialized);
        if($nicemap_layer_list) {
          foreach($nicemap_layer_list as $k=>$v) {
            $layer_settings['weights'][$v] = $layer_number - $k;
          }
        }
        update_option('nicemap_layers', $layer_settings);
        // projections
        if (count($spec->crs)) {
          foreach ($spec->crs as $code) {
            $crs[$code] = $code;
          }
        }

        $existing_crs = get_option('nicemap_wms_crs');
        foreach($crs as $k => $v) {
          $selected = ($k == $existing_crs) ? 'selected = "selected"' : '';
          $crs_select_options .= 
            '<option '.$selected.' value="'.$k.'">'.$v;
        }

        $display_on_post = array(
          'top', 'bottom');
        $display_options = get_option('nicemap_display');
        foreach($display_on_post as $d) {
          $selected = ($d == $display_options['display_on_post']) ? 'selected="selected"' : '';
          $display_on_post_select .= "<option $selected value='$d'>$d";
        }


        $where_show = array(
          'lists and single posts', 'lists', 'single posts', 'manually');
        foreach($where_show as $d) {
          $selected = ($d == $display_options['where_show']) ? 'selected="selected"' : '';
          $where_show_select .= "<option $selected value='$d'>$d";
        }



        // compatibility options
        // TODO: add geopress
        // This list is in the form 'handler' => englishname
        $compat = array(
          '' => 'None',
          'wpgeo' => 'WP-Geo',
          'geomashup' => 'Geo-Mashup',
        );
        $existing_compat = get_option('nicemap_compat');
        foreach($compat as $k=>$v) {
          $selected = ($existing_compat == $k) ? 'selected = "selected"' : '';
          $compat_select .= "<option $selected value='$k'>$v";
        }
        // layers and styles
        $l = $spec->layers;
        foreach ($l as $layer => $info) {
          $layers[$layer] = $info['title'];
          $styles[$layer] = $info['styles'];
        }
        if($layer_settings) {
          uksort($l, 'nicemap_sort');
        }
        $l = array_reverse($l);
        foreach($l as $k=>$layer) {
          $checked = (($layer_settings['layers'][$k])) ? 'checked="checked"' : '';
          $selected = (($layer_settings['layers'][$k])) ? 
            'style="background-color: #fff"' : '';
          // Here's a kind of shady html hack: adding a hidden element before each checkbox
          // so that a value of 0 is sent when it isn't checked and a value of 1 is
          // sent when it is.
          $layer_select .= '<li id="'.$k.'" '.$selected.' class="layer-sortable">
            <input type="hidden" value="0" name="nicemap_layers[layers]['.$k.']" />
            <input type="checkbox" value="1" class="layer_enable" '.$checked.' 
            name="nicemap_layers[layers]['.$k.']"><h4 class="layer-title">'.
              $layer['title'].'</h4>';
          $layer_select .= '<input type="hidden" 
            name="nicemap_layers[weights]['.$k.']" value="0">';

          $layer_select .= '<select name="nicemap_layers[styles]['.$k.']">';
          // r is the url-safe key for the english description $s.
          // Sorry to use cryptic variable names
          if(is_array($styles[$k])) {
            foreach($styles[$k] as $r=>$s) {
              if(isset($layer_settings['styles'][$k]) && $layer_settings['styles'][$k] == $r) {
                $selected = 'selected = "selected"';
              } else {
                $selected = "";
              }
              $layer_select .= '<option '.$selected.' value="'.$r.'">'.$s;
            }
          }
          $layer_select .= '</select>';
        }

      }
    }
  $optionspage = '
<div class="wrap">
<h2>Nice Map Options</h2>
'.$message.'
<form id="nicemap_settings_form" method="post" action="options.php">
'.wp_nonce_field('update-options').'
<table class="form-table">
<tr valign="top">
  <th scope="row">'.__('Map Server URL').'</th>
  <td>
  <input size="50" type="text" 
    name="nicemap_wms_url" 
    value="'.get_option('nicemap_wms_url').'" />
  <br />

  </td>
</tr>
<tr valign="top">
  <th scope="row">'.__('Background Color').'</th>
  <td><input type="text" 
    name="nicemap_bgcolor" 
    value="'.get_option('nicemap_bgcolor').'" />
    <span class="howto">Enter a hex color or leave blank to 
    use a transparent background.</span>
    </td>
</tr>
<tr valign="top">
  <th scope="row">'.__('CRS Code').'</th>
  <td>
  <select name="nicemap_wms_crs">'.$crs_select_options.'</select>
  <span class="howto">'.__('The CRS code defines how flat maps are projected from the sphere of the earth. 
  Map servers commonly provide mercator and equirectangular projections. If in doubt, choose the default.').'
  </td>
</tr>
<tr valign="top">
  <th scope="row">'.__('Compatibility').'</th>
  <td>
    <select name="nicemap_compat">
    '.$compat_select.'
    </select>
  <span class="howto">If you choose a compatibility mode,
   Nice Map will use existing geo-data from other WordPress
   geo plugins.</span>
 </td>
</tr>
<tr valign="top">
  <th scope="row">'.__('Display').'</th>
  <td>


    <select name="nicemap_display[where_show]">
    '.$where_show_select.'
    </select>
    '.__('Where to automatically display maps on mapped posts, or choose manual to add code to templates to do this.').'
    <br />

    <select name="nicemap_display[display_on_post]">
    '.$display_on_post_select.'
    </select>
    '.__('Show on posts (only valid if the previous option is not set to manual)').'
    <br />
    <input type="text" name="nicemap_display[width]" value="'.$display_options['width'].'" /> px wide X
    <input type="text" name="nicemap_display[height]" value="'.$display_options['height'].'" /> px height<br />
    <input type="text" name="nicemap_display[zoom]" value="'.$display_options['zoom'].'" /> zoom (in degrees, values from 0.01-20 are appropriate)
  </td>
</tr>
</table>
<fieldset id="layer_set"><legend>'.__('Layer Display Options').'</legend>
<ul id="nicemap_layer_list">'.$layer_select.'
</ul>
</fieldset>

<input type="hidden" id="nicemap_layer_order" name="nicemap_layer_order" value="" />
<input type="hidden" name="action" value="update" />
<input type="hidden" name="page_options" 
  value="nicemap_wms_url,nicemap_wms_crs,nicemap_bgcolor,nicemap_display,nicemap_compat,nicemap_layer_order,nicemap_layers" />
<p class="submit">
<input type="submit" name="Submit" value="'.__('Save Changes') .'" />
</p>
</form>
</div>';
  echo $optionspage;
}

/**
 * Clean up the potentially large
 * variables stored by nicemap when a user uninstalls it
 */
function nicemap_deactivate() {
  delete_option('nicemap_wms_url');
  delete_option('nicemap_wms_crs');
  delete_option('nicemap_bgcolor');
  delete_option('nicemap_layers');
}

function nicemap_activate() {
  //TODO: set stored options
}

//TODO: prevent inclusion on all pages.
function nicemap_admin_head($not_used)
{
  global $pagenow;
    $link_url = get_bloginfo('wpurl').'/wp-content/plugins/nice-map';
    // TODO: fix EMBEDDED URL. BAD STUFF HERE
    echo '
      <script src="http://wordpress.dev/wp-includes/js/jquery/interface.js"
      type="text/javascript"></script>
      <link href="'.$link_url.'/nicemap-admin.css" rel="stylesheet" type="text/css"/>
      <script src="'.$link_url.'/nicemap-admin.js" type="text/javascript"></script>';
}

// TODO: only display on pertinent pages

function nicemap_wp_head() {
//TODO: http://is.gd/edXc
  $link_url = get_bloginfo('wpurl').'/wp-content/plugins/nice-map';

  echo '<link href="'.$link_url.'/nicemap.css" rel="stylesheet" type="text/css"/>';
  echo '<script src="'.$link_url.'/nicemap.js" type="text/javascript"></script>';
}
?>
