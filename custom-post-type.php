<?php

abstract class PNE_Custom_Post_Type {
	var $slug = 'custom_post_type';
	var $archive_slug = false; // use pluralized string if you want an archive page
	var $singular = false;
	var $plural = false;
	
	var $labels = false;
	
	var $public = true;
	var $show_ui = true;
	var $menu_position = 21;
	var $menu_icon = null;
	var $hierarchical = false;
	var $supports = array('title', 'editor', 'excerpt', 'custom-fields', 'comments', 'revisions');
	
	function __construct() {
		add_action('init', array($this, 'register'));
		add_action('admin_init', array($this, 'meta_boxes'));
		add_action('save_post', array($this, 'save'));
		add_action('manage_edit-'.$this->slug.'_columns', array($this, 'columns'));
		add_action('manage_posts_custom_column', array($this, 'column'));
		add_action('manage_pages_custom_column', array($this, 'column'));
	}
	
	function register() {
		register_post_type($this->slug, array_merge(array(
			'labels' => $this->labels ? $this->labels : array(),
			'public' => $this->public,
			'show_ui' => $this->show_ui,
			'menu_position' => $this->menu_position,
			'menu_icon' => $this->menu_icon ? plugins_url("/icons/$this->menu_icon.png", __FILE__) : null,
			'capability_type' => 'post',
			'hierarchical' => $this->hierarchical,
			'supports' => $this->supports,
			'has_archive' => $this->archive_slug,
			'rewrite' => array(
				'slug' => $this->archive_slug,
				'with_front' => false,
			),
		)));
	}
	
	function meta_boxes() { /* do nothing */ }
	
	function options($post) {
		wp_nonce_field(plugin_basename(__FILE__), 'pne_nonce_'.$this->slug);
	}

	function save($post_id) {
		if (!isset($_POST['pne_nonce_'.$this->slug])) return $post_id;
		if (!wp_verify_nonce($_POST['pne_nonce_'.$this->slug], plugin_basename(__FILE__))) return $post_id;
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return $post_id;
		if ($_POST['post_type'] != $this->slug) return $post_id;
		if (!current_user_can('edit_post', $post_id)) return $post_id;
	}
	
	function columns($columns) { return $columns; }
	function column($column) { /* do nothing */ }
}