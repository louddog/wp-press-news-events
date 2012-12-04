<?php

new PNE_Settings;
class PNE_Settings {
	function __construct() {
		add_action('admin_menu', array($this, 'add_settings_page'));
		add_action('admin_init', array($this, 'register_auto_archive'));
		add_action('admin_init', array($this, 'register_inject_meta'));
		add_action('admin_init', array($this, 'register_press_release'));
		add_filter("plugin_action_links_".plugin_basename(__FILE__), array($this, 'settings_link'));
	}

	// Settings Page ----------------------------------------------------------

	function add_settings_page() {
		add_options_page(
			_x("Press, News and Events Options", "admin options page title", 'press-news-events'),
			_x("Press, News, Events", "admin options page menu title", 'press-news-events'),
			'manage_options',
			'press-news-and-events-options',
			array($this, 'settings_page')
		);
	}
	
	function settings_page() { ?>
		<div class="wrap pne_settings_page">
			<h2><?php echo _x("Press, News and Events Settings", "admin settings page title", 'press-news-events'); ?></h2>
			
			<form action="options.php" method="post">
				<?php
					settings_fields('pne_settings');
					do_settings_sections('pne_settings_auto_archive');
					do_settings_sections('pne_settings_inject_meta');
					do_settings_sections('pne_settings_press_releases');
				?>
				<input type="submit" class="button-primary" value="<?php echo esc_attr(__("Save Settings")); ?>" />
			</form>
		</div> <!-- .wrap -->
	<?php }
		
	// Auto Archive -----------------------------------------------------------
	
	function register_auto_archive() {
		register_setting(
			'pne_settings',
			'pne_settings_auto_archive',
			array($this, 'checkboxes_to_array')
		);
		
		add_settings_section(
			'pne_settings_section_auto_archive',
			__("Custom Post Type Archives", 'press-news-events'),
			array($this, 'auto_archive_settings'),
			'pne_settings_auto_archive'
		);
		
		add_settings_field(
			'pne_settings_auto_archive',
			__("Create an archive page for:", 'press-news-events'),
			array($this, 'auto_archive_input'),
			'pne_settings_auto_archive',
			'pne_settings_section_auto_archive'
		);
	}
		
	function auto_archive_settings() { ?>
		<p><?php _e("The plugin can create archive pages for each of the post types, similar to your blog index.  This is a handy way to display all you press, news and events.  But if you want to make your own page, using the same urls shown below, then these archives will conflict, and should be turned off.  If you do decide to keep them, you might be interested in creating <a href='http://codex.wordpress.org/Post_Types#Template_Files' target='_blank'>special template files</a> for each archive.  If for no other reason, it's good to change the top heading on the page.", 'press-news-events'); ?></p>
	<?php }
	
	function auto_archive_input() {
		foreach (array(
			'event' => _n("Event", "Events", 2, 'press-news-events'),
			'press-release' => _n("Press Release", "Press Releases", 2, 'press-news-events'),
			'news' => _n("News Story", "News Stories", 2, 'press-news-events'),
		) as $slug => $label) { ?>
			<input
				type="checkbox"
				name="pne_settings_auto_archive[<?php echo $slug; ?>]"
				id="pne_options_auto_archive_<?php echo $slug; ?>"
				<?php if (self::auto_archive($slug)) echo 'checked'; ?>
			/>
			<label for="pne_options_auto_archive_<?php echo $slug; ?>"><?php echo $label; ?></label>
			<br />
		<?php }
	}
	
	function auto_archive($slug) {
		return in_array($slug, get_option('pne_settings_auto_archive', array('event', 'news', 'press-release')));
	}
	
	// Inject Meta ------------------------------------------------------------
	
	function register_inject_meta() {
		register_setting(
			'pne_settings',
			'pne_settings_inject_meta',
			array($this, 'checkboxes_to_array')
		);
	
		add_settings_section(
			'pne_settings_section_auto_archive',
			__("Add Information", 'press-news-events'),
			array($this, 'inject_meta_settings'),
			'pne_settings_inject_meta'
		);
		
		add_settings_field(
			'pne_settings_inject_meta',
			__("Add info for:", 'press-news-events'),
			array($this, 'inject_meta_input'),
			'pne_settings_inject_meta',
			'pne_settings_section_auto_archive'
		);	
	}
	
	function inject_meta_settings() { ?>
		<p><?php _e("The plugin can add post type specific information to pages and pages.  For instance, it can put the date and location in the content of each event.", 'press-news-events'); ?></p>
	<?php }
	
	
	function inject_meta_input() {
		foreach (array(
			'event' => _n("Event", "Events", 2, 'press-news-events'),
			'press-release' => _n("Press Release", "Press Releases", 2, 'press-news-events'),
			'news' => _n("News Story", "News Stories", 2, 'press-news-events'),
		) as $slug => $label) { ?>
			<input
				type="checkbox"
				name="pne_settings_inject_meta[<?php echo $slug; ?>]"
				id="pne_options_inject_meta_<?php echo $slug; ?>"
				<?php if (self::inject_meta($slug)) echo 'checked'; ?>
			/>
			<label for="pne_options_inject_meta_<?php echo $slug; ?>"><?php echo $label; ?></label><br />
		<?php }
	}
	
	function inject_meta($slug) {
		return in_array($slug, get_option('pne_settings_inject_meta', array('event', 'news', 'press-release')));
	}
	
	// Press Release Settings ------------------------------------------------------------
	
	function register_press_release() {
		register_setting(
			'pne_settings',
			'pne_settings_press_releases',
			array($this, 'checkboxes_to_array')
		);
	
		add_settings_section(
			'pne_settings_section_press_releases',
			__("Press Release Options", 'press-news-events'),
			array($this, 'press_releases_settings'),
			'pne_settings_press_releases'
		);
		
		add_settings_field(
			'pne_settings_press_releases',
			__("Boilerplate:", 'press-news-events'),
			array($this, 'press_releases_input'),
			'pne_settings_press_releases',
			'pne_settings_section_press_releases'
		);	
	}
	
	function press_releases_settings() { ?>
		<!-- no directions -->
	<?php }
	
	function press_releases_input() {
		$options = get_option('pne_settings_press_releases', array(
			'use_boilerplate'
		));
		?>
		<input
			type="checkbox"
			name="pne_settings_press_releases[use_boilerplate]"
			id="pne_options_inject_meta_use_boilerplate"
			<?php echo checked(in_array('use_boilerplate', $options)); ?>
		/>
		<label for="pne_options_inject_meta_use_boilerplate"><?php _e("Use a boilerplate for press releases"); ?></label>
	<?php }
	
	// Misc -------------------------------------------------------------------
	
	// TODO: flush rules
	
	function checkboxes_to_array($input) {
		Press_News_Events::flush_rules();
		return is_array($input) ? array_keys($input) : array();
	}
	
	function settings_link($links) { 
		$settings_link = '<a href="options-general.php?page=press-news-and-events-options">Settings</a>';
		array_unshift($links, $settings_link); 
		return $links; 
	}
}