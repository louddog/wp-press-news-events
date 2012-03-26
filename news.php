<?php

new LDWPPR_News;
class LDWPPR_News extends LDWPPR_CustomPostType {
	var $slug = 'news';
	var $archive_slug = 'news';
	var $singular = "News Story";
	var $plural = "News Stories";
	var $supports = array('title', 'editor', 'thumbnail', 'custom-fields', 'revisions');
	
	function __construct() {
		parent::__construct();
		add_theme_support('post-thumbnails');
		add_action('admin_enqueue_scripts', array($this, 'scripts_styles'));
		add_shortcode('news', array($this, 'shortcode'));
	}

	function scripts_styles() {
	    wp_enqueue_script(
			'ldwppr_news', // handle 
			$path = plugins_url('news.js', __FILE__), // path
			array('jquery', 'ldwppr_datepicker'), // dependencies
			'1.0', // version
			true // in footer
		);
	}

	function meta_boxes() {
		parent::meta_boxes();
		
		add_meta_box(
			$this->slug."-options",
			$this->singular." Options",
			array($this, 'options'),
			$this->slug,
			'side'
		);
	}
	
	function options($post) {
		parent::options($post);
		
		$meta = get_post_custom($post->ID);
		extract(wp_parse_args(array(
			'link' => $meta['_link'][0],
			'date' => $meta['_date'][0],
		), $this->defaults));
		
		$date_string = $date ? date('Y-m-j', $date) : '';
		?>
		
		<table>
			<tr>
				<td><label>Link:</label></td>
				<td>
					<input
						type="text"
						name="ldwppr_news[link]"
						value="<?=esc_attr($link)?>"
					/>
					<?php if (!empty($link)) { ?>
						<a href="<?=$link?>" target="_blank">link</a>
					<?php } ?>
				</td>
			</tr>
			<tr>
				<td><label>News Date:</label></td>
				<td>
					<div class="ldwppr_date_picker"></div>
					<input
						type="text"
						class="ldwppr_news_date"
						name="ldwppr_news[date]"
						value="<?=esc_attr($date_string)?>"
					/>
				</td>
			</tr>
		</table>

	<?php }
	
	function save($post_id) {
		if (parent::save($post_id)) return $post_id;
		
		if ($options = $_POST['ldwppr_news']) {
			extract(wp_parse_args($_POST['ldwppr_news'], array(
				'link' => false,
				'date' => false,
			)));
			
			if ($link) {
				$link = trim($link);
				if (!preg_match("/^https?:\/\//", $link)) $link = "http://$link";
			}
			
			$date = strtotime($date);

			update_post_meta($post_id, '_link', $link);
			update_post_meta($post_id, '_date', $date);
		}
	}
	
	// Admin Columns ----------------------------------------------------------

	function columns($columns) {
		unset($columns['comments']);
		unset($columns['date']);
		$columns[$this->slug.'_date'] = "Date";
		$columns[$this->slug.'_link'] = "Link";
		
		return $columns;
	}
	
	function column($column) {
		global $post;
		
		switch ($column) {
			case $this->slug.'_date':
				echo LDWPPR::pretty_date_range(get_post_meta($post->ID, '_date', true));
				break;
			
			case $this->slug.'_link':
				$link = get_post_meta($post->ID, '_link', true);
				if (!empty($link)) echo "<a href='$link' target='_blank'>$link</a>";
				break;
		}
	}
}