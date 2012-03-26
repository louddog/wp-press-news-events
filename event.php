<?php

new LDWPPR_Events;
class LDWPPR_Events extends LDWPPR_CustomPostType {
	var $slug = 'event';
	var $archive_slug = 'events';
	var $singular = "Event";
	var $plural = "Events";
	
	function __construct() {
		parent::__construct();
		
		add_action('admin_enqueue_scripts', array($this, 'scripts_styles'));

		$this->new_rules();
		add_filter('rewrite_rules_array', array($this, 'insert_rewrite_rules'));
		add_filter('query_vars', array($this, 'insert_query_vars'));
		add_action('wp_loaded', array($this, 'flush_rules'));
		add_filter('posts_join', array($this, 'posts_join'));
		add_filter('posts_where', array($this, 'posts_where'));
		add_filter('posts_orderby', array($this, 'posts_orderby'));
		add_filter('post_limits', array($this, 'post_limits'));
	}
	
	function scripts_styles() {
	    wp_enqueue_script(
			'ldwppr_events', // handle 
			$path = plugins_url('events.js', __FILE__), // path
			array('jquery', 'ldwppr_datepicker'), // dependencies
			'1.0', // version
			true // in footer
		);
	}

	// Admin ------------------------------------------------------------------
	
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
		
		<table>
			<tr>
				<td><label>Location:</label></td>
				<td><textarea name="ldwppr_event[location]" rows="4"><?=$location?></textarea></td>
			</tr>
			<tr>
				<td><label>Date:</label></td>
				<td>
					<div class="ldwppr_date_picker"></div>
					<input type="text" class="ldwppr_event_date" name="ldwppr_event[date]" value="<?=esc_attr($date_string)?>" />
				</td>
			</tr>
			<tr>
				<td><label>Time:</label></td>
				<td>
					<p>
						<input
							type="checkbox"
							name="ldwppr_event[all_day]"
							id="ldwppr_event_all_day"
							<?php if ($all_day) echo 'checked'; ?>
							<?php if ($starts != $ends) echo 'disabled'; ?>
						/>
						<label for="ldwppr_event_all_day">All Day</label>
					</p>
					
					<p id="ldwppr_event_time_options">
						<input type="text" size="7" name="ldwppr_event[start_time]" value="<?=esc_attr(date('g:ia', $starts))?>" placeholder="6:30pm" />
						to
						<input type="text" size="7" name="ldwppr_event[end_time]" value="<?=esc_attr(date('g:ia', $ends))?>" placeholder="9:30pm" />
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
		
		if ($options = $_POST['ldwppr_event']) {
			$start_date = $end_date = current_time('timestamp');
			
			if (!empty($options['date'])) {
				$dates = explode(',', $options['date']);
				$start_date = strtotime($dates[0]);
				$end_date = count($dates) > 1 ? strtotime($dates[1]) : $start_date;
			}

			$start_time = strtotime(trim($options['start_time']));
			$end_time = strtotime(trim($options['end_time']));
			
			// TODO: if all-day, set start and end times to 12:00am and 11:59pm
			
			update_post_meta($post_id, '_location', trim($options['location']));
			update_post_meta($post_id, '_all_day', isset($options['all_day']) || !$start_time);
			update_post_meta($post_id, '_starts', $this->combined_date($start_date, $start_time));
			update_post_meta($post_id, '_ends', $this->combined_date($end_date, $end_time));
		}
	}
	
	// Admin Columns ----------------------------------------------------------

	function columns($columns) {
		unset($columns['comments']);
		unset($columns['date']);
		$columns[$this->slug.'_date'] = "Date";
		
		return $columns;
	}
	
	function column($column) {
		global $post;
		
		switch ($column) {
			case $this->slug.'_date':
				echo LDWPPR::pretty_date_range(
					get_post_meta($post->ID, '_starts', true),
					get_post_meta($post->ID, '_ends', true),
					get_post_meta($post->ID, '_all_day', true)
				);
				
				break;
		}
	}

	// Manipulate archive order -----------------------------------------------
	
	function can_modify_query() {
		return !is_admin() && is_post_type_archive($this->slug);
	}
	
	function new_rules() {
		$this->rewrite_rules = array(
			$this->archive_slug.'/archive$' => 'index.php?post_type='.$this->slug.'&archive_type=past',
			$this->archive_slug.'/archive/page/([0-9]+)$' => 'index.php?post_type='.$this->slug.'&archive_type=past&paged=$matches[1]',
		);
	}
	
	function insert_rewrite_rules($rules) {
		return  $this->rewrite_rules + $rules;
	}

	function insert_query_vars($vars) {
	    array_push($vars, 'archive_type');
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
			$compare = get_query_var('archive_type') == 'past' ? '<' : '>';
			$time = current_time('timestamp') - 43200; // compare against 12 hours ago
			$where .= " AND COALESCE(ends.meta_value, starts.meta_value) $compare $time";
		}
		return $where;
	}
	
	function posts_orderby($orderby) {
		global $wpdb;
		if ($this->can_modify_query()) {
			$order = get_query_var('archive_type') == 'past' ? 'DESC' : 'ASC';
			$orderby = "starts.meta_value $order, $wpdb->posts.post_date $order";
		}
		return $orderby;
	}
	
	function post_limits($limit) {
		if ($this->can_modify_query() && get_query_var('archive_type') != 'past') {
			$limit = '';
		}
		return $limit;
	}
}