<?php

new PNE_News;
class PNE_News extends PNE_Custom_Post_Type {
	var $slug = 'news';
	var $supports = array('title', 'editor', 'thumbnail', 'custom-fields', 'revisions');

	function regiser() {
		$this->archive_slug = _x("news", 'url segment', 'press-news-and-events');
		$this->singular = _n("News Story", "News Stories", 1, 'press-news-and-events');
		$this->plural = _n("News Story", "News Stories", 2, 'press-news-and-events');

		$this->labels = array(
			'name' => _n("News Story", "News Stories", 1, 'press-news-and-events'),
			'singular_name' => _n("News Story", "News Stories", 2, 'press-news-and-events'),
			'add_new' => __("Add New News Story", 'press-news-and-events'),
			'add_new_item' => __("Add New News Story", 'press-news-and-events'),
			'edit_item' => __("Edit News Story", 'press-news-and-events'),
			'new_item' => __("New News Story", 'press-news-and-events'),
			'view_item' => __("View News Story", 'press-news-and-events'),
			'search_items' => __("Search News Stories", 'press-news-and-events'),
			'not_found' => __("No News Stories found", 'press-news-and-events'),
			'not_found_in_trash' => __("No News Stories found in Trash", 'press-news-and-events'),
		);

		parent::regiser();
	}
	
	function meta_boxes() {
		parent::meta_boxes();
		
		add_meta_box(
			$this->slug."-options",
			sprintf(__("Options", 'press-news-and-events'), $this->singular),
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
				<td><label><?=__("Link:", 'press-news-and-events')?></label></td>
				<td>
					<input
						type="text"
						name="pne_news[link]"
						value="<?=esc_attr($link)?>"
					/>
					<?php if (!empty($link)) { ?>
						<a href="<?=$link?>" target="_blank"><?=__("link", 'press-news-and-events')?></a>
					<?php } ?>
				</td>
			</tr>
			<tr>
				<td><label><?=__("News Date:", 'press-news-and-events')?></label></td>
				<td>
					<div class="date_picker"></div>
					<input
						type="text"
						class="date"
						name="pne_news[date]"
						value="<?=esc_attr($date_string)?>"
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
		$columns['pne_news_date'] = _x("Date", 'column header', 'press-news-and-events');
		$columns['pne_news_link'] = _x("Link", 'column header', 'press-news-and-events');
		
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
}