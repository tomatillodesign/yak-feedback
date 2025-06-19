<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Handle AJAX submission of yak-feedback responses
 */
add_action( 'wp_ajax_yak_feedback_submit', 'yak_feedback_handle_submission' );
add_action( 'wp_ajax_nopriv_yak_feedback_submit', 'yak_feedback_handle_submission' );

function yak_feedback_handle_submission() {
	// Check nonce for security
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'yak_feedback_nonce' ) ) {
		wp_send_json_error( [ 'message' => 'Invalid nonce' ], 403 );
	}

	// Sanitize and validate inputs
	$post_id   = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
	$responses = isset( $_POST['responses'] ) ? wp_unslash( $_POST['responses'] ) : '';

	if ( ! $post_id || empty( $responses ) ) {
		wp_send_json_error( [ 'message' => 'Missing data' ], 400 );
	}

	// Decode and validate JSON
	$decoded = json_decode( $responses, true );
	if ( json_last_error() !== JSON_ERROR_NONE || ! is_array( $decoded ) ) {
		wp_send_json_error( [ 'message' => 'Invalid JSON' ], 400 );
	}

	// Create feedback post
	$feedback_id = wp_insert_post( [
		'post_type'   => 'yak_feedback',
		'post_status' => 'private', // or maybe 'private' if you want to hide them from queries
		'post_title'  => 'Feedback on "' . get_the_title($post_id) . '" at ' . current_time( 'mysql' ),
	] );

	if ( is_wp_error( $feedback_id ) ) {
		wp_send_json_error( [ 'message' => 'Could not save feedback' ], 500 );
	}

	// Save meta
	update_post_meta( $feedback_id, '_feedback_post_id', $post_id );
    update_post_meta( $feedback_id, '_feedback_responses', $decoded );
    update_post_meta( $feedback_id, '_feedback_user_agent', sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ?? '' ) );
    update_post_meta( $feedback_id, '_feedback_ip', sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? '' ) );

	wp_send_json_success( [ 'message' => 'Feedback saved' ] );
}
