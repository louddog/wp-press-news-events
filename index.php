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
		add_action('init', array($this, 'locale'));

		$dir = dirname(__FILE__);
		require "$dir/custom-post-type.php";
		foreach (glob("$dir/custom-post-types/*.php") as $file) {
			require($file);
		}

		add_theme_support('post-thumbnails');
		add_action('admin_enqueue_scripts', array($this, 'scripts_styles'));
	}
	
	function locale() {
		load_plugin_textdomain('press-news-and-events', false, dirname(plugin_basename(__FILE__)).'/languages/');
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
	
	// Static Functions ----------------------------------------------------------

	static function pretty_date_range($starts = false, $ends = false, $all_day = true) {
		if (!$starts) $starts = current_time('timestamp');
		
		$pne = 'press-news-and-events';

		$same_day = !$ends || date_i18n(__('F j, Y', $pne), $starts) == date_i18n(__('F j, Y', $pne), $ends);
		$same_time = $same_day && date_i18n(__('H i', $pne), $starts) == date_i18n(__('H i', $pne), $ends);
		$same_month = date_i18n(__('F Y', $pne), $starts) == date_i18n(__('F Y', $pne), $ends);
		$same_year = date_i18n(__('Y', $pne), $starts) ==  date_i18n(__('Y', $pne), $ends);
		
		if ($same_time) {
			return $all_day
				? date_i18n(__('F j, Y', $pne), $starts)
				: date_i18n(__('F j g:ia, Y', $pne), $starts);
		} else if ($same_day) {
			return $all_day
				? date_i18n(__('F j, Y', $pne), $starts)
				: sprintf('%s, %s - %s', date_i18n(__('F j, Y', $pne), $starts), date_i18n(__('g:ia', $pne), $starts), date_i18n(__('g:ia', $pne), $ends));
		} else if ($same_month) {
			return $all_day
				? sprintf('%s - %s, %s', date_i18n(__('F j', $pne), $starts), date_i18n(__('j', $pne), $ends), date_i18n(__('Y', $pne), $starts))
				: sprintf('%s - %s, %s', date_i18n(__('F j g:ia', $pne), $starts), date_i18n(__('j g:ia', $pne), $ends), date_i18n(__('Y', $pne), $starts));
		} else if ($same_year) {
			return $all_day
				? sprintf('%s - %s, %s', date_i18n(__('F j', $pne), $starts), date_i18n(__('F j', $pne), $ends), date_i18n(__('Y', $pne), $starts))
				: sprintf('%s - %s, %s', date_i18n(__('F j g:ia', $pne), $starts), date_i18n(__('F j g:ia', $pne), $ends), date_i18n(__('Y', $pne), $starts));
		} else {
			return $all_day
				? sprintf('%s - %s', date_i18n(__('F j, Y', $pne), $starts), date_i18n(__('F j, Y', $pne), $ends))
				: sprintf('%s - %s', date_i18n(__('F j g:ia, Y', $pne), $starts), date_i18n(__('F j g:ia, Y', $pne), $ends));
		}
	}
}