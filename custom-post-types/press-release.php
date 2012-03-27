<?php

new PNE_Press_Release;
class PNE_Press_Release extends PNE_Custom_Post_Type {
	var $slug = 'press-release';
	var $archive_slug = 'press-releases';
	var $singular = "Press Release";
	var $plural = "Press Releases";
	
	function __construct() {
		parent::__construct();
		add_action('admin_menu', array($this, 'boilder_plate_menu'));
		add_action('admin_init', array(&$this, 'save_boiler_plate'));
		add_filter('the_content', array($this, 'inject_boilerplate'));
	}
	
	function boilder_plate_menu() {
		add_submenu_page(
			'edit.php?post_type='.$this->slug,
			"Press Release Boiler Plate",
			"Boiler Plate",
			'edit_post',
			'pne_press_release_boiler_plate',
			array($this, 'boiler_plate')
		);
	}
	
	function boiler_plate() { ?>
		<div class="wrap">
			<h2>Press Releases' Boiler Plate</h2>
			<p>This press release boiler plate is shown at the bottom of all press releases.  Updating the boiler plate will update all past and future press releases.</p>
			
			<form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="post">
				<?php wp_nonce_field(plugin_basename(__FILE__), 'pne_nonce_press_release_boiler_plate'); ?>
				
				<?php wp_editor(get_option('pne_press_release_boilderplate'), 'pnepressreleaseboilderplate'); ?>
				
				<input type="submit" class="button-primary" value="Save" />
			</form>
		</div> <!-- .wrap -->
	<?php }
	
	function save_boiler_plate() {
		if (isset($_POST['pne_nonce_press_release_boiler_plate']) && check_admin_referer(plugin_basename(__FILE__), 'pne_nonce_press_release_boiler_plate')) {
			update_option('pne_press_release_boilderplate', trim(stripslashes($_POST['pnepressreleaseboilderplate'])));
			$this->add_admin_notice('boiler plate saved');
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