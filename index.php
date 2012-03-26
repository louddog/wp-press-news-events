<?php
/*
Plugin Name: PR for WP by Loud Dog (LDWPPR)
Description: Create events, press releases and links to news stories.
Author: Loud Dog
Version: 1.0
Author URI: http://www.louddog.com
*/

new LDWPPR;
class LDWPPR {
	function __construct() {
		$dir = dirname(__FILE__);
		require $dir.'/cpt.php';
		require $dir.'/event.php';
		require $dir.'/press-release.php';
		require $dir.'/news.php';

		add_action('admin_enqueue_scripts', array($this, 'scripts_styles'));
	}
	
	function scripts_styles() {
	    wp_enqueue_style(
			'ldwppr', // handle 
			plugins_url('ldwppr.css', __FILE__), // path
			array(), // dependencies
			'1.0' // version
		);

	    wp_register_script(
			'ldwppr_datepicker', // handle 
			plugins_url('datepicker.js', __FILE__), // path
			array('jquery'), // dependencies
			'1.0', // version
			true // in footer
		);
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