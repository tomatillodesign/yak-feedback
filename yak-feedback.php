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
require_once YAK_FEEDBACK_DIR . 'includes/enqueue-assets.php';
require_once YAK_FEEDBACK_DIR . 'includes/feedback-save.php';
require_once YAK_FEEDBACK_DIR . 'includes/render-button.php';
require_once YAK_FEEDBACK_DIR . 'includes/admin-columns.php';
require_once YAK_FEEDBACK_DIR . 'includes/admin-feedback-view.php';

// Optional utilities and reporting
// require_once YAK_FEEDBACK_DIR . 'includes/utils.php';
// require_once YAK_FEEDBACK_DIR . 'includes/reporting.php';

// Init hooks
add_action( 'init', 'yak_feedback_register_cpt' );
add_action( 'wp_enqueue_scripts', 'yak_feedback_enqueue_assets' );
add_action( 'admin_enqueue_scripts', 'yak_feedback_enqueue_assets_admin' );
add_action( 'template_redirect', 'yak_feedback_render_button' );
