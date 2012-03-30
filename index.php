<?php
/*
Plugin Name: Press, News, Events
Description: Create custom post types for press releases, references to external news stories, and events.
Author: Loud Dog
Version: 1.0
Author URI: http://www.louddog.com
*/

define('PRESS_NEWS_EVENTS_VERSION', '1.0');

new Press_News_Events;
class Press_News_Events {
	function __construct() {
		add_action('init', array($this, 'locale'));
		register_activation_hook(__FILE__, array($this, 'activate'));
		
		$this->include_files(array(
			'custom-post-type.php',
			'custom-post-types',
		));

		add_theme_support('post-thumbnails');
		add_action('admin_enqueue_scripts', array($this, 'scripts_styles'));
		add_action('admin_menu', array($this, 'add_options_page'));	
		add_action('admin_init', array($this, 'save_options'));
		add_filter("plugin_action_links_".plugin_basename(__FILE__), array($this, 'settings_link'));
		add_action('admin_notices', array(__CLASS__, 'admin_notices'));
	}
	
	function include_files($files) {
		$dir = dirname(__FILE__);
		foreach ($files as $file) {
			$file = "$dir/$file";
			if (is_dir($file)) {
				foreach (glob("$file/*.php") as $file) {
					include $file;
				}
			} else include $file;
		}
	}
	
	function locale() {
		load_plugin_textdomain('press-news-events', false, dirname(plugin_basename(__FILE__)).'/languages/');
	}
	
	function activate() {
		add_action('init', array($this, '_activate'));
	}
	
	function _activate() {
		flush_rewrite_rules();
	}
	
	function scripts_styles() {
	    wp_enqueue_style(
			'pne_admin', // handle 
			plugins_url('css/admin.css', __FILE__), // path
			array(), // dependencies
			PRESS_NEWS_EVENTS_VERSION // version
		);

	    wp_register_script(
			'pne_datepicker', // handle 
			plugins_url('js/datepicker.js', __FILE__), // path
			array('jquery'), // dependencies
			PRESS_NEWS_EVENTS_VERSION, // version
			true // in footer
		);

	    wp_enqueue_script(
			'pne_admin', // handle 
			$path = plugins_url('js/admin.js', __FILE__), // path
			array('jquery', 'pne_datepicker'), // dependencies
			PRESS_NEWS_EVENTS_VERSION, // version
			true // in footer
		);
	}
	
	function add_options_page() {
		add_options_page(
			_x("Press, News and Events Options", "admin options page title", 'press-news-events'),
			_x("Press, News, Events", "admin options page menu title", 'press-news-events'),
			'manage_options',
			'press-news-and-events-options',
			array($this, 'options_page')
		);
	}
	
	function options_page() { ?>
		<div class="wrap pne_options_page">
			<h2><?=_x("Press, News and Events Options", "admin options page title", 'press-news-events')?></h2>
			
			<form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="post">
				<?php wp_nonce_field(plugin_basename(__FILE__), 'pne_nonce_options'); ?>
				
				<h3><?=__("Custom Post Type Archives", 'press-news-events')?></h3>
				<p><?=__("The plugin can create archive pages for each of the post types, similar to your blog index.  This is a handy way to display all you press, news and events.  But if you want to make your own page, using the same urls shown below, then these archives will conflict, and should be turned off.  If you do decide to keep them, you might be interested in creating <a href='http://codex.wordpress.org/Post_Types#Template_Files' target='_blank'>special template files</a> for each archive.  If for no other reason, it's good to change the top heading on the page.", 'press-news-events')?></p>
				
				<p>
					<?=__("Create an archive page for:", 'press-news-events')?><br />
					<input
						type="checkbox"
						name="pne_options[auto_archive][events]"
						id="pne_options_auto_archive_events"
						<?php if (self::auto_archive('events')) echo 'checked'; ?>
					/>
					<label for="pne_options_auto_archive_events">
						<?=_n("Event", "Events", 2, 'press-news-events')?>
						<a href="<?=get_post_type_archive_link('event')?>" target="_blank"><?=get_post_type_archive_link('event')?></a>
					</label><br />

					<input
						type="checkbox"
						name="pne_options[auto_archive][news]"
						id="pne_options_auto_archive_news"
						<?php if (self::auto_archive('news')) echo 'checked'; ?>
					/>
					<label for="pne_options_auto_archive_news">
						<?=_n("News Story", "News Stories", 2, 'press-news-events')?>
						<a href="<?=get_post_type_archive_link('news')?>" target="_blank"><?=get_post_type_archive_link('news')?></a>
					</label><br />

					<input
						type="checkbox"
						name="pne_options[auto_archive][press-releases]"
						id="pne_options_auto_archive_press_releases"
						<?php if (self::auto_archive('press-releases')) echo 'checked'; ?>
					/>
					<label for="pne_options_auto_archive_press_releases">
						<?=_n("Press Release", "Press Releases", 2, 'press-news-events')?>
						<a href="<?=get_post_type_archive_link('press-release')?>" target="_blank"><?=get_post_type_archive_link('press-release')?></a>
					</label><br />
				</p>
				
				<h3><?=__("Add Information", 'press-news-events')?></h3>
				<p><?=__("The plugin can add post type specific information to pages and pages.  For instance, it can put the date and location in the content of each event.", 'press-news-events')?></p>
				
				<p>
					<?=__("Add info for:", 'press-news-events')?><br />
					<input
						type="checkbox"
						name="pne_options[inject_meta][events]"
						id="pne_options_inject_meta_events"
						<?php if (self::inject_meta('events')) echo 'checked'; ?>
					/>
					<label for="pne_options_inject_meta_events">
						<?=_n("Event", "Events", 2, 'press-news-events')?>
					</label><br />

					<input
						type="checkbox"
						name="pne_options[inject_meta][news]"
						id="pne_options_inject_meta_news"
						<?php if (self::inject_meta('news')) echo 'checked'; ?>
					/>
					<label for="pne_options_inject_meta_news">
						<?=_n("News Story", "News Stories", 2, 'press-news-events')?>
					</label><br />

					<input
						type="checkbox"
						name="pne_options[inject_meta][press-releases]"
						id="pne_options_inject_meta_press_releases"
						<?php if (self::inject_meta('press-releases')) echo 'checked'; ?>
					/>
					<label for="pne_options_inject_meta_press_releases">
						<?=_n("Press Release", "Press Releases", 2, 'press-news-events')?>
					</label><br />
				</p>
				
				<input type="submit" class="button-primary" value="<?=esc_attr(__("Save"))?>" />
			</form>
		</div> <!-- .wrap -->
	<?php }
	
	function save_options() {
		if (isset($_POST['pne_nonce_options']) && check_admin_referer(plugin_basename(__FILE__), 'pne_nonce_options')) {
			update_option('pne_auto_archive', isset($_POST['pne_options']['auto_archive']) ? array_keys($_POST['pne_options']['auto_archive']) : array());
			update_option('pne_inject_meta', isset($_POST['pne_options']['inject_meta']) ? array_keys($_POST['pne_options']['inject_meta']) : array());
			
			flush_rewrite_rules();

			self::add_admin_notice(__("options saved", 'press-news-events'));
			header("Location: ".$_SERVER['REQUEST_URI']);
			die;
		}
	}
	
	function auto_archive($slug) {
		return in_array($slug, get_option('pne_auto_archive', array('events', 'news', 'press-releases')));
	}
	
	function inject_meta($slug) {
		return in_array($slug, get_option('pne_inject_meta', array('events', 'news', 'press-releases')));
	}
	
	function settings_link($links) { 
		$settings_link = '<a href="options-general.php?page=press-news-and-events-options">Settings</a>';
		array_unshift($links, $settings_link); 
		return $links; 
	}
	
	// Static Functions ----------------------------------------------------------
	
	static function admin_notices() {
		$notices = get_option('pne_admin_notices', array());
		
		if (count($notices)) {
			foreach ($notices as $notice) { ?>
				<div class="updated">
					<p><?php echo $notice; ?></p>
				</div>
			<?php }
			delete_option('pne_admin_notices');
		}
	}

	static function add_admin_notice($notice) {
		$notices = get_option('pne_admin_notices', array());
		$notices[] = $notice;
		update_option('pne_admin_notices', $notices);
	}

	static function date_i18n($date) {
		return date_i18n(get_option('date_format', $date));
	}
	
	static function pretty_date_range($starts = false, $ends = false, $all_day = true) {
		if (!$starts) $starts = current_time('timestamp');
		
		$pne = 'press-news-events';

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
	
	static function debug($var, $die = false) {
		echo "<pre style='padding:5px;background-color:#EEE;white-space:pre-wrap;'>".htmlentities(print_r($var,1))."</pre>";
		if ($die) die;
	}
}