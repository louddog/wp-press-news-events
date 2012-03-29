<?php

new PNE_Event;
class PNE_Event extends PNE_Custom_Post_Type {
	var $slug = 'event';
	
	function __construct() {
		parent::__construct();

		$this->new_rules();
		add_filter('rewrite_rules_array', array($this, 'insert_rewrite_rules'));
		add_filter('query_vars', array($this, 'insert_query_vars'));
		add_action('wp_loaded', array($this, 'flush_rules'));
		add_filter('posts_join', array($this, 'posts_join'));
		add_filter('posts_where', array($this, 'posts_where'));
		add_filter('posts_orderby', array($this, 'posts_orderby'));
		add_filter('post_limits', array($this, 'post_limits'));
	}
	
	function register() {
		$this->archive_slug = _x("events", 'url segment', 'press-news-and-events');
		$this->singular = _n("Event", "Events", 1, 'press-news-and-events');
		$this->plural = _n("Event", "Events", 2, 'press-news-and-events');
		
		$this->labels = array(
			'name' => _n("Event", "Events", 1, 'press-news-and-events'),
			'singular_name' => _n("Event", "Events", 2, 'press-news-and-events'),
			'add_new' => __("Add New Event", 'press-news-and-events'),
			'add_new_item' => __("Add New Event", 'press-news-and-events'),
			'edit_item' => __("Edit Event", 'press-news-and-events'),
			'new_item' => __("New Event", 'press-news-and-events'),
			'view_item' => __("View Event", 'press-news-and-events'),
			'search_items' => __("Search Events", 'press-news-and-events'),
			'not_found' => __("No Events found", 'press-news-and-events'),
			'not_found_in_trash' => __("No Events found in Trash", 'press-news-and-events'),
		);

		parent::register();
	}

	// Admin ------------------------------------------------------------------
	
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
			'location' => $meta['_location'][0],
			'starts' => $meta['_starts'][0],
			'ends' => $meta['_ends'][0],
			'all_day' => $meta['_all_day'][0],
		));
		
		$date_string = '';
		if ($starts) $date_string = date('Y-n-j', $starts);
		if ($ends) $date_string .= ','.date('Y-n-j', $ends);

		if (!isset($meta['_all_day'])) $all_day = true;
		?>
		
		<table class="pne_event_options">
			<tr>
				<td><label><?=__("Location:", 'press-news-and-events')?></label></td>
				<td><textarea name="pne_event[location]" rows="4"><?=$location?></textarea></td>
			</tr>
			<tr>
				<td><label><?=__("Event Date:", 'press-news-and-events')?></label></td>
				<td>
					<div class="date_picker"></div>
					<input
						type="text"
						class="date_range"
						name="pne_event[date]"
						value="<?=esc_attr($date_string)?>"
					/>
				</td>
			</tr>
			<tr>
				<td><label><?=__("Time:", 'press-news-and-events')?></label></td>
				<td>
					<p>
						<input
							type="checkbox"
							class="all_day"
							name="pne_event[all_day]"
							<?php if ($all_day) echo 'checked'; ?>
							<?php if ($starts != $ends) echo 'disabled'; ?>
						/>
						<label for="pne_event_all_day"><?=__("All Day", 'press-news-and-events')?></label>
					</p>
					
					<p class="event_times">
						<input
							type="text"
							size="7"
							name="pne_event[start_time]"
							value="<?=esc_attr(date('g:ia', $starts))?>"
							placeholder="<?=esc_attr(__("6:30pm", 'press-news-and-events'))?>"
						/>
						<?=_x("to", 'starting time *to* ending time', 'press-news-and-events')?>
						<input
							type="text"
							size="7"
							name="pne_event[end_time]"
							value="<?=esc_attr(date('g:ia', $ends))?>"
							placeholder="<?=esc_attr(__("9:30pm", 'press-news-and-events'))?>"
						/>
					</p>
				</td>
			</tr>
		</table>

	<?php }
	
	function combined_date($date, $time) {
		$getdate = getdate($date);
		$gettime = getdate($time);
		return mktime(
			$time ? $gettime['hours'] : 0,
			$time ? $gettime['minutes'] : 0,
			$time ? $gettime['seconds'] : 0,
			$getdate['mon'],
			$getdate['mday'],
			$getdate['year']
		);
	}
	
	function save($post_id) {
		if (parent::save($post_id)) return $post_id;
		
		if ($meta = $_POST['pne_event']) {
			$start_date = $end_date = current_time('timestamp');
			
			if (!empty($meta['date'])) {
				$dates = explode(',', $meta['date']);
				$start_date = strtotime($dates[0]);
				$end_date = count($dates) > 1 ? strtotime($dates[1]) : $start_date;
			}

			$start_time = strtotime(trim($meta['start_time']));
			$end_time = strtotime(trim($meta['end_time']));
			
			// TODO: if all-day, set start and end times to 12:00am and 11:59pm
			
			update_post_meta($post_id, '_location', trim($meta['location']));
			update_post_meta($post_id, '_all_day', isset($meta['all_day']) || !$start_time);
			update_post_meta($post_id, '_starts', $this->combined_date($start_date, $start_time));
			update_post_meta($post_id, '_ends', $this->combined_date($end_date, $end_time));
		}
	}
	
	// Admin Columns ----------------------------------------------------------

	function columns($columns) {
		unset($columns['comments']);
		unset($columns['date']);
		$columns['pne_event_date'] = _x("Date", 'column header', 'press-news-and-events');
		
		return $columns;
	}
	
	function column($column) {
		global $post;
		
		switch ($column) {
			case 'pne_event_date':
				$meta = get_post_custom($post->ID);
				extract(array(
					'starts' => $meta['_starts'][0],
					'ends' => $meta['_ends'][0],
					'all_day' => $meta['_all_day'][0],
				));
				
				if ($starts) echo Press_News_Events::pretty_date_range($starts, $ends, $all_day);
				
				break;
		}
	}

	// Manipulate archive order -----------------------------------------------
	
	function can_modify_query() {
		return !is_admin() && is_post_type_archive($this->slug);
	}
	
	function new_rules() {
		$this->rewrite_rules = array(
			$this->archive_slug.'/archive$' => 'index.php?post_type='.$this->slug.'&pne_archive_type=past',
			$this->archive_slug.'/archive/page/([0-9]+)$' => 'index.php?post_type='.$this->slug.'&pne_archive_type=past&paged=$matches[1]',
		);
	}
	
	function insert_rewrite_rules($rules) {
		return  $this->rewrite_rules + $rules;
	}

	function insert_query_vars($vars) {
	    array_push($vars, 'pne_archive_type');
	    return $vars;
	}
	
	function flush_rules() {
		$rules = get_option('rewrite_rules');
		foreach ($this->rewrite_rules as $rule => $rewrite) {
			if (!isset($rules[$rule])) {
				global $wp_rewrite;
				$wp_rewrite->flush_rules();
				break;
			}
		}
	}

	function posts_join($join) {
		global $wpdb;
		if ($this->can_modify_query()) {
			$join .= " LEFT JOIN $wpdb->postmeta starts on ($wpdb->posts.ID = starts.post_id AND starts.meta_key = '_starts') ";
			$join .= " LEFT JOIN $wpdb->postmeta ends on ($wpdb->posts.ID = ends.post_id AND ends.meta_key = '_ends') ";
		}
		return $join;
	}
	
	function posts_where($where) {
		global $wpdb;
		if ($this->can_modify_query()) {
			$compare = get_query_var('pne_archive_type') == 'past' ? '<' : '>';
			$time = current_time('timestamp') - 43200; // compare against 12 hours ago
			$where .= " AND COALESCE(ends.meta_value, starts.meta_value) $compare $time";
		}
		return $where;
	}
	
	function posts_orderby($orderby) {
		global $wpdb;
		if ($this->can_modify_query()) {
			$order = get_query_var('pne_archive_type') == 'past' ? 'DESC' : 'ASC';
			$orderby = "starts.meta_value $order, $wpdb->posts.post_date $order";
		}
		return $orderby;
	}
	
	function post_limits($limit) {
		if ($this->can_modify_query() && get_query_var('pne_archive_type') != 'past') {
			$limit = '';
		}
		return $limit;
	}
}