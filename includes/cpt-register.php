<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Register the yak_feedback custom post type.
 */
add_action( 'init', 'yak_feedback_register_cpt' );

function yak_feedback_register_cpt() {
	register_post_type( 'yak_feedback', [
		'labels' => [
			'name'          => 'Feedback',
			'singular_name' => 'Feedback Entry',
			'add_new'       => 'Add Feedback',
			'edit_item'     => 'Edit Feedback',
			'view_item'     => 'View Feedback',
			'search_items'  => 'Search Feedback',
		],
		'public'              => false,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'show_in_admin_bar'   => false,
		'capability_type'     => 'post',
		'hierarchical'        => false,
		'supports'            => [ 'title' ],
		'menu_icon'           => 'dashicons-feedback',
		'has_archive'         => false,
		'show_in_rest'        => false,
		'exclude_from_search' => true,
		'capabilities' => [
			'create_posts' => 'do_not_allow', // Disable Add New
		],
	] );
}
