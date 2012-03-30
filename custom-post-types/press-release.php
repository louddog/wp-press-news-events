<?php

new PNE_Press_Release;
class PNE_Press_Release extends PNE_Custom_Post_Type {
	var $slug = 'press-release';
	var $archive_slug = 'press-releases';
	
	function __construct() {
		parent::__construct();
		add_action('admin_menu', array($this, 'boilder_plate_menu'));
		add_action('admin_init', array($this, 'save_boiler_plate'));
		add_filter('the_content', array($this, 'inject_boilerplate'));
	}
	
	function register() {
		$this->singular = _n("Press Release", "Press Releases", 1, 'press-news-events');
		$this->plural = _n("Press Release", "Press Releases", 2, 'press-news-events');
		
		$this->labels = array(
			'name' => _n("Press Release", "Press Releases", 2, 'press-news-events'),
			'singular_name' => _n("Press Release", "Press Releases", 1, 'press-news-events'),
			'add_new' => __("Add New Press Release", 'press-news-events'),
			'add_new_item' => __("Add New Press Release", 'press-news-events'),
			'edit_item' => __("Edit Press Release", 'press-news-events'),
			'new_item' => __("New Press Release", 'press-news-events'),
			'view_item' => __("View Press Release", 'press-news-events'),
			'search_items' => __("Search Press Releases", 'press-news-events'),
			'not_found' => __("No Press Releases found", 'press-news-events'),
			'not_found_in_trash' => __("No Press Releases found in Trash", 'press-news-events'),
		);
		
		parent::register();
	}
	
	function boilder_plate_menu() {
		add_submenu_page(
			'edit.php?post_type='.$this->slug,
			__("Press Release Boilerplate", 'press-news-events'),
			__("Boilerplate", 'press-news-events'),
			'edit_posts',
			'pne_press_release_boiler_plate',
			array($this, 'boiler_plate')
		);
	}
	
	function boiler_plate() { ?>
		<div class="wrap">
			<h2><?=__("Press Release Boilerplate", 'press-news-events')?></h2>
			<p><?=__("This press release boilerplate is shown at the bottom of all press releases.  Updating the boilerplate will update all past and future press releases.", 'press-news-events')?></p>
			
			<form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="post">
				<?php wp_nonce_field(plugin_basename(__FILE__), 'pne_nonce_press_release_boiler_plate'); ?>
				
				<?php wp_editor(get_option('pne_press_release_boilderplate'), 'pnepressreleaseboilderplate'); ?>
				
				<input type="submit" class="button-primary" value="<?=esc_attr(__("Save"))?>" />
			</form>
		</div> <!-- .wrap -->
	<?php }
	
	function save_boiler_plate() {
		if (isset($_POST['pne_nonce_press_release_boiler_plate']) && check_admin_referer(plugin_basename(__FILE__), 'pne_nonce_press_release_boiler_plate')) {
			update_option('pne_press_release_boilderplate', trim(stripslashes($_POST['pnepressreleaseboilderplate'])));
			Press_News_Events::add_admin_notice(__("boilerplate saved", 'press-news-events'));
			header("Location: ".$_SERVER['REQUEST_URI']);
			die;
		}
	}

	function inject_boilerplate($content) {
		global $post;
		if ($post->post_type == $this->slug) {
			$content .= get_option('pne_press_release_boilderplate');
		}
		return $content;
	}
}