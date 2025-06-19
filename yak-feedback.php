<?php
/**
 * Plugin Name: Yak Feedback
 * Description: Adds a stepwise feedback tool for posts like prayers, poems, and liturgies.
 * Version: 1.0.0
 * Author: You
 */

// Define constants
if ( ! defined( 'YAK_FEEDBACK_DIR' ) ) {
	define( 'YAK_FEEDBACK_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'YAK_FEEDBACK_URL' ) ) {
	define( 'YAK_FEEDBACK_URL', plugin_dir_url( __FILE__ ) );
}

// Autoload core includes
require_once YAK_FEEDBACK_DIR . 'includes/cpt-register.php';
require_once YAK_FEEDBACK_DIR . 'includes/ajax-save-feedback.php';
require_once YAK_FEEDBACK_DIR . 'includes/enqueue-assets.php';
require_once YAK_FEEDBACK_DIR . 'includes/admin/meta-boxes.php';
// require_once YAK_FEEDBACK_DIR . 'includes/feedback-save.php';
// require_once YAK_FEEDBACK_DIR . 'includes/render-button.php';
// require_once YAK_FEEDBACK_DIR . 'includes/admin-columns.php';
// require_once YAK_FEEDBACK_DIR . 'includes/admin-feedback-view.php';

// Optional utilities and reporting
// require_once YAK_FEEDBACK_DIR . 'includes/utils.php';
// require_once YAK_FEEDBACK_DIR . 'includes/reporting.php';

// Init hooks
add_action( 'init', 'yak_feedback_register_cpt' );
// add_action( 'wp_enqueue_scripts', 'yak_feedback_enqueue_assets' );
// add_action( 'admin_enqueue_scripts', 'yak_feedback_enqueue_assets_admin' );
// add_action( 'template_redirect', 'yak_feedback_render_button' );


add_filter( 'template_include', function( $template ) {
	if ( is_page( 'feedback-report' ) ) {
		$new_template = plugin_dir_path( __FILE__ ) . 'includes/admin/page-feedback-report.php';
		if ( file_exists( $new_template ) ) {
			return $new_template;
		}
	}
	return $template;
});


add_action( 'admin_menu', function() {
	remove_submenu_page( 'edit.php?post_type=yak_feedback', 'post-new.php?post_type=yak_feedback' );
}, 999 );
