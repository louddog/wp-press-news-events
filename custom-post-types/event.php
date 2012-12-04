<?php

new PNE_Event;
class PNE_Event extends PNE_Custom_Post_Type {
	var $slug = 'event';
	var $archive_slug = 'events';
	
	function __construct() {
		parent::__construct();
		
		if (PNE_Settings::auto_archive($this->slug)) {
			$this->new_rules();
			add_filter('rewrite_rules_array', array($this, 'insert_rewrite_rules'));
			add_filter('query_vars', array($this, 'insert_query_vars'));
			add_action('wp_loaded', array($this, 'flush_rules'));
		}

		add_filter('posts_join', array($this, 'posts_join'));
		add_filter('posts_where', array($this, 'posts_where'));
		add_filter('posts_orderby', array($this, 'posts_orderby'));
		add_filter('post_limits', array($this, 'post_limits'));
	}
	
	function register() {
		$this->singular = _n("Event", "Events", 1, 'press-news-events');
		$this->plural = _n("Event", "Events", 2, 'press-news-events');
		
		$this->labels = array(
			'name' => _n("Event", "Events", 2, 'press-news-events'),
			'singular_name' => _n("Event", "Events", 1, 'press-news-events'),
			'add_new' => __("Add New Event", 'press-news-events'),
			'add_new_item' => __("Add New Event", 'press-news-events'),
			'edit_item' => __("Edit Event", 'press-news-events'),
			'new_item' => __("New Event", 'press-news-events'),
			'view_item' => __("View Event", 'press-news-events'),
			'search_items' => __("Search Events", 'press-news-events'),
			'not_found' => __("No Events found", 'press-news-events'),
			'not_found_in_trash' => __("No Events found in Trash", 'press-news-events'),
		);

		parent::register();
	}

	// Admin ------------------------------------------------------------------
	
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
				<td><label><?php _e("Location:", 'press-news-events'); ?></label></td>
				<td><textarea name="pne_event[location]" rows="4"><?php echo $location; ?></textarea></td>
			</tr>
			<tr>
				<td><label><?php _e("Event Date:", 'press-news-events'); ?></label></td>
				<td>
					<div class="date_picker"></div>
					<input
						type="text"
						class="date_range"
						name="pne_event[date]"
						value="<?php echo esc_attr($date_string); ?>"
					/>
				</td>
			</tr>
			<tr>
				<td><label><?php _e("Time:", 'press-news-events'); ?></label></td>
				<td>
					<p>
						<input
							type="checkbox"
							class="all_day"
							name="pne_event[all_day]"
							<?php if ($all_day) echo 'checked'; ?>
							<?php if ($starts != $ends) echo 'disabled'; ?>
						/>
						<label for="pne_event_all_day"><?php _e("All Day", 'press-news-events'); ?></label>
					</p>
					
					<p class="event_times">
						<input
							type="text"
							size="7"
							name="pne_event[start_time]"
							value="<?php echo esc_attr(date('g:ia', $starts)); ?>"
							placeholder="<?php echo esc_attr(__("6:30pm", 'press-news-events')); ?>"
						/>
						<?php echo _x("to", 'starting time *to* ending time', 'press-news-events'); ?>
						<input
							type="text"
							size="7"
							name="pne_event[end_time]"
							value="<?php echo esc_attr(date('g:ia', $ends)); ?>"
							placeholder="<?php echo esc_attr(__("9:30pm", 'press-news-events')); ?>"
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
		$columns['pne_event_date'] = _x("Date", 'column header', 'press-news-events');
		
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
				Press_News_Events::flush_rules();
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
			$time = self::cutoff_time();
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
	
	static function cutoff_time() {
		return current_time('timestamp') - 43200;  // compare against 12 hours ago
	}
	
	// Shortcode --------------------------------------------------------------
	
	function meta_shortcode_pieces($atts) {
		$pieces = parent::meta_shortcode_pieces($atts);
		
		extract(shortcode_atts(array(
			'show' => "location date",
			'location_string' => _x("at <span class='location'>%s</span>", "(an event being held) at [location name]", 'press-news-events'),
			'date_string' => '%s',
			'date_formatter' => array('Press_News_Events', 'pretty_date_range'),
		), $atts));

		$show = explode(' ', $show);

		$meta = get_post_custom(get_the_ID());
		extract(array(
			'location' => $meta['_location'][0],
			'starts' => $meta['_starts'][0],
			'ends' => $meta['_ends'][0],
			'all_day' => $meta['_all_day'][0],
		));

		if (in_array('location', $show) && !empty($location)) $pieces[] = sprintf($location_string, $location);
		if (in_array('date', $show) && $starts) $pieces[] = sprintf($date_string, call_user_func($date_formatter, $starts, $ends, $all_day));

		return $pieces;
	}
	
	// Static -----------------------------------------------------------------
	
	static function past_events_count() {
		global $wpdb;
		$time = self::cutoff_time();
		return $wpdb->get_var(
			"SELECT count(post.ID)
			FROM $wpdb->posts post
			LEFT JOIN $wpdb->postmeta starts on (post.ID = starts.post_id AND starts.meta_key = '_starts')
			LEFT JOIN $wpdb->postmeta ends on (post.ID = ends.post_id AND ends.meta_key = '_ends')
			WHERE 1=1 AND post.post_type = 'event'
			AND (post.post_status = 'publish' OR post.post_status = 'private')
			AND COALESCE(ends.meta_value, starts.meta_value) < $time
			ORDER BY starts.meta_value DESC, post.post_date DESC"
		);
	}
	
}