<?php

new PNE_News;
class PNE_News extends PNE_Custom_Post_Type {
	var $slug = 'news';
	var $archive_slug = 'news';
	var $supports = array('title', 'editor', 'thumbnail', 'custom-fields', 'revisions');
	
	function __construct() {
		parent::__construct();
		
		add_filter('posts_join', array($this, 'posts_join'));
		add_filter('posts_orderby', array($this, 'posts_orderby'));
	}

	function register() {
		$this->singular = _n("News Story", "News Stories", 1, 'press-news-events');
		$this->plural = _n("News Story", "News Stories", 2, 'press-news-events');

		$this->labels = array(
			'name' => _n("News Story", "News Stories", 2, 'press-news-events'),
			'singular_name' => _n("News Story", "News Stories", 1, 'press-news-events'),
			'add_new' => __("Add New News Story", 'press-news-events'),
			'add_new_item' => __("Add New News Story", 'press-news-events'),
			'edit_item' => __("Edit News Story", 'press-news-events'),
			'new_item' => __("New News Story", 'press-news-events'),
			'view_item' => __("View News Story", 'press-news-events'),
			'search_items' => __("Search News Stories", 'press-news-events'),
			'not_found' => __("No News Stories found", 'press-news-events'),
			'not_found_in_trash' => __("No News Stories found in Trash", 'press-news-events'),
		);

		parent::register();
	}
	
	function meta_boxes() {
		parent::meta_boxes();
		
		add_meta_box(
			$this->slug."-options",
			sprintf(__("Options", 'press-news-events'), $this->singular),
			array($this, 'options'),
			$this->slug,
			'side'
		);
	}
	
	function options($post) {
		parent::options($post);
		
		$meta = get_post_custom($post->ID);
		extract(array(
			'link' => $meta['_link'][0],
			'date' => $meta['_date'][0],
		));
		
		$date_string = $date ? date('Y-m-j', $date) : '';
		?>
		
		<table class="pne_news_options">
			<tr>
				<td><label><?php _e("Link:", 'press-news-events'); ?></label></td>
				<td>
					<input
						type="text"
						name="pne_news[link]"
						value="<?php echo esc_attr($link); ?>"
					/>
					<?php if (!empty($link)) { ?>
						<a href="<?php echo $link; ?>" target="_blank"><?php _e("link", 'press-news-events'); ?></a>
					<?php } ?>
				</td>
			</tr>
			<tr>
				<td><label><?php _e("News Date:", 'press-news-events'); ?></label></td>
				<td>
					<div class="date_picker"></div>
					<input
						type="text"
						class="date"
						name="pne_news[date]"
						value="<?php echo esc_attr($date_string); ?>"
					/>
				</td>
			</tr>
		</table>

	<?php }
	
	function save($post_id) {
		if (parent::save($post_id)) return $post_id;
		
		if ($meta = $_POST['pne_news']) {
			$link = trim($meta['link']);
			if ($link && !preg_match("/^https?:\/\//", $link)) $link = "http://$link";

			update_post_meta($post_id, '_link', $link);
			update_post_meta($post_id, '_date', strtotime($meta['date']));
		}
	}
	
	// Admin Columns ----------------------------------------------------------

	function columns($columns) {
		unset($columns['comments']);
		unset($columns['date']);
		$columns['pne_news_date'] = _x("Date", 'column header', 'press-news-events');
		$columns['pne_news_link'] = _x("Link", 'column header', 'press-news-events');
		
		return $columns;
	}
	
	function column($column) {
		global $post;
		
		switch ($column) {
			case 'pne_news_date':
				echo Press_News_Events::pretty_date_range(get_post_meta($post->ID, '_date', true));
				break;
			
			case 'pne_news_link':
				$link = get_post_meta($post->ID, '_link', true);
				if (!empty($link)) echo "<a href='$link' target='_blank'>$link</a>";
				break;
		}
	}
	
	// Manipulate archive order -----------------------------------------------
	
	function can_modify_query() {
		return !is_admin() && is_post_type_archive($this->slug);
	}
	
	function posts_join($join) {
		global $wpdb;
		if ($this->can_modify_query()) {
			$join .= " LEFT JOIN $wpdb->postmeta news_date on ($wpdb->posts.ID = news_date.post_id AND news_date.meta_key = '_date') ";
		}
		return $join;
	}
	
	function posts_orderby($orderby) {
		global $wpdb;
		if ($this->can_modify_query()) {
			$orderby = "news_date.meta_value DESC, $wpdb->posts.post_date DESC";
		}
		return $orderby;
	}
	
	// Shortcode --------------------------------------------------------------
	
	function meta_shortcode_pieces($atts) {
		$pieces = parent::meta_shortcode_pieces($atts);

		extract(shortcode_atts(array(
			'show' => "link date",
			'date_formatter' => array('Press_News_Events', 'date_i18n'),
		), $atts));
		
		$show = explode(' ', $show);
		
		$meta = get_post_custom(get_the_ID());
		extract(array(
			'link' => $meta['_link'][0],
			'date' => $meta['_date'][0],
		));
		
		if ($link && in_array('link', $show)) $pieces[] = sprintf('<a href="%1$s">%1$s</a>', $link);
		if ($date && in_array('date', $show)) $pieces[] = call_user_func($date_formatter, $date);
		
		return $pieces;
	}
}