=== Nicemap ===
Contributors: tmcw
Donate link: 
Tags: map, geo, gis, wms, wp-geo, geo-mashup, maps, 
Requires at least: 2.7
Tested up to: 2.7
Stable tag: trunk

Nice Map is a WMS client for Wordpress. It offers compatibility with geo-mashup and a clean interface for displaying maps.

== Description ==

Attention: *[check out the launch blog post and screencast at developmentseed.org](http://www.developmentseed.org/blog/2009/jan/09/just-released-nice-map-module-wordpress)*

This is a Wordpress port of the [Drupal Nice Map](http://drupal.org/project/nicemap)
module, which also provides an interface for an administrator to set up a WMS server 
with various layers and projections.

Please Read:

Currently this module doesn't provide a method for actually inputting geo-information to your blog posts. Instead, it allows you to 
pull information that has already been saved by [WP-Geo]((http://wordpress.org/extend/plugins/wp-geo/) or [geo-mashup](http://code.google.com/p/wordpress-geo-mashup/). The ability to input information may be added soon, but as it currently stands, either of the aforementioned plugins provide a nice way to select locations, while this plugin gives you ultimate flexibility to use different map data from different sources. 

Currently, like the Drupal module, this mainly provides support for the EPSG:4326 
(Equirectangular) and EPSG:900913 (Google Mercator) projections. A good server to 
start off with is NASA's free WMS server, which you can use by setting the 
*Map Server URL* to http://onearth.jpl.nasa.gov/wms.cgi.

You can use this plugin by adding code like this to your templates:

`<?php nicemap_map(array('width' => 410, 'height' => 400)) ?>`

And setting the display options in the administration interface. Currently this template tag will display maps based on all the posts it can find: if it's on an archive page, it'll show all of the posts on that page of the archive on one map. If this is in a single post template, it will show that one post on a map.

Options for nicemap_map:

* width: in px
* height: in px

The plugin also now provides a shortcode for use in posts: The format is:

`[nicemap lat="40.86" lon="-74.68" height="500" width="500" title="Hello, world"]`

height & width are not required.



*This plugin is sponsored by [Development Seed](http://www.developmentseed.org/), makers of some great [mapping solutions](http://www.developmentseed.org/solutions/geodata-mapping).*


== Installation ==

1. Check out the [intro blog post & screencast for a visual installation & usage guide.](http://www.developmentseed.org/blog/2009/jan/09/just-released-nice-map-module-wordpress)
1. First note that you'll need either geo-mashup or WP Geo installed in order for Nice Map to be of any use: it depends on existing geotagged data.
1. Upload the folder `nicemap` to the `/wp-content/plugins/` directory or by installing it via Wordpress's automatic installation method.
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Edit the plugin settings and choose at least one WMS layer and one compatibility driver (WP-Geo or geo-mashup, currently) is selected, so that a map is displayed on posts. You should also edit the positioning of maps on posts if you want them automatically added.

== Frequently Asked Questions ==

== Screenshots ==

1. Here's the admin interface as it looks with the NASA map server set up.
2. Here's a post with it's geo-information displayed in both [WP-Geo](http://wordpress.org/extend/plugins/wp-geo/) and Nice Map.



