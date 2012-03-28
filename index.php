<?php
/*
Plugin Name: Press, News and Events
Description: Create custom post types for press releases, references to external news stories, and events.
Author: Loud Dog
Version: 1.0
Author URI: http://www.louddog.com
*/

new Press_News_Events;
class Press_News_Events {
	function __construct() {
		$dir = dirname(__FILE__);
		require "$dir/custom-post-type.php";
		foreach (glob("$dir/custom-post-types/*.php") as $file) {
			require($file);
		}

		add_theme_support('post-thumbnails');
		add_action('admin_enqueue_scripts', array($this, 'scripts_styles'));
		
		add_action('init', array($this, 'locale'));
	}
	
	function scripts_styles() {
	    wp_enqueue_style(
			'pne_admin', // handle 
			plugins_url('css/admin.css', __FILE__), // path
			array(), // dependencies
			'1.0' // version
		);

	    wp_register_script(
			'pne_datepicker', // handle 
			plugins_url('js/datepicker.js', __FILE__), // path
			array('jquery'), // dependencies
			'1.0', // version
			true // in footer
		);

	    wp_enqueue_script(
			'pne_admin', // handle 
			$path = plugins_url('js/admin.js', __FILE__), // path
			array('jquery', 'pne_datepicker'), // dependencies
			'1.0', // version
			true // in footer
		);
	}
	
	function locale() {
		load_plugin_textdomain('press-news-and-events', false, dirname(plugin_basename(__FILE__)).'/languages/');
	}
	
	// Static Functions ----------------------------------------------------------

	static function pretty_date_range($starts = false, $ends = false, $all_day = true) {
		if (!$starts) $starts = current_time('timestamp');
		if (!$ends) return $all_day
			? date('F j, Y', $starts)
			: date('F j g:ia, Y', $starts);

		$sep = ' - ';
		$output = '';

		if (date('F j, Y', $starts) == date('F j, Y', $ends)) { // same day
			$output = date('F j, Y', $starts);
			if (!$all_day) {
				if (date('H i', $starts) == date('H i', $ends)) { // same time
					$output .= date(', g:ia', $starts);
				} else {
					$output .= date(', g:ia', $starts).$sep.date('g:ia', $ends);
				}
			}
		} else {
			$starts_time = $all_day ? '' : date(', g:ia', $starts);
			$ends_time = $all_day ? '' : date(', g:ia', $ends);

			if (date('F Y', $starts) == date('F Y', $ends) && $all_day) { // same month, all_day
				$output = date('F j', $starts).$starts_time.$sep.date('j', $ends).$ends_time.date(', Y', $ends);
			} else if (date('Y', $starts) == date('Y', $ends)) { // same year/all day, or same month/timed, or same year/timed
				$output = date('F j', $starts).$starts_time.$sep.date('F j', $ends).$ends_time.date(', Y', $ends);
			} else { // otherwise
				$output = date('F j', $starts).$starts_time.date(', Y', $starts).$sep.date('F j', $ends).$ends_time.date(', Y', $ends);
			}
		}

		return $output;
	}
}