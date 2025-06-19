<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Add custom meta box to display feedback data
 */
add_action( 'add_meta_boxes', function() {
	add_meta_box(
		'yak_feedback_details',
		'Feedback Details',
		'yak_feedback_render_meta_box',
		'yak_feedback',
		'normal',
		'default'
	);
});

/**
 * Render the feedback meta box
 */
function yak_feedback_render_meta_box( $post ) {
	$post_id    = (int) get_post_meta( $post->ID, '_feedback_post_id', true );
    $answers    = get_post_meta( $post->ID, '_feedback_responses', true );
    $user_agent = get_post_meta( $post->ID, '_feedback_user_agent', true );
    $ip         = get_post_meta( $post->ID, '_feedback_ip', true );


	echo '<p><strong>Related Post:</strong> ';
	if ( $post_id ) {
		printf(
			'<a href="%s">%s</a>',
			esc_url( get_edit_post_link( $post_id ) ),
			esc_html( get_the_title( $post_id ) )
		);
	} else {
		echo 'N/A';
	}
	echo '</p>';

	if ( is_array( $answers ) ) {
		echo '<h4>Responses</h4><ul>';
		foreach ( $answers as $entry ) {
			printf(
				'<li><strong>%s:</strong> %s</li>',
				esc_html( $entry['question'] ?? '(No question)' ),
				esc_html( $entry['answer'] ?? '(No answer)' )
			);
		}
		echo '</ul>';
	} else {
		echo '<p><em>No feedback data found.</em></p>';
	}

	echo '<h4>Technical Info</h4>';
	echo '<p><strong>IP:</strong> ' . esc_html( $ip ?: 'Unknown' ) . '</p>';
	echo '<p><strong>User Agent:</strong> <code style="font-size:smaller">' . esc_html( $user_agent ?: 'N/A' ) . '</code></p>';

    echo '<pre style="font-size:smaller;">';
    print_r( get_post_meta( $post->ID ) );
    echo '</pre>';

}
