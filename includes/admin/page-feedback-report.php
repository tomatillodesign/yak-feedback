<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Access control: Admin, Editor, Site Manager only
if (
	! current_user_can( 'administrator' ) &&
	! current_user_can( 'editor' ) &&
	! current_user_can( 'site_manager' )
) {
	wp_die( 'You do not have permission to view this page.' );
}

// Load and parse the JSON flow file
$flow_file = plugin_dir_path( __FILE__ ) . '../../data/feedback-flow.json';
$question_types = [];

if ( file_exists( $flow_file ) ) {
	$flow_json = file_get_contents( $flow_file );
	$flow_data = json_decode( $flow_json, true );

	if ( is_array( $flow_data ) ) {
		foreach ( $flow_data as $step_id => $step ) {
			if ( isset( $step['question'] ) ) {
				// Default to 'choice' if no type defined
				$question_types[ $step['question'] ] = $step['type'] ?? 'choice';
			}
		}
	}
} else {
	// Could log or handle error here if needed
}

// Hook into Genesis content area
add_action( 'genesis_entry_content', function() use ( $question_types ) {

	// Query all private feedback posts
	$feedback_posts = get_posts( [
		'post_type'      => 'yak_feedback',
		'post_status'    => 'private',
		'posts_per_page' => -1,
		'orderby'        => 'date',
		'order'          => 'DESC',
	] );

	$button_answers = []; // multiple choice tally
	$text_answers   = []; // open-ended answers grouped by question

	foreach ( $feedback_posts as $fb_post ) {
		$fb_data = get_post_meta( $fb_post->ID, '_feedback_responses', true );
		if ( ! is_array( $fb_data ) ) {
			continue;
		}

		foreach ( $fb_data as $response ) {
			$q = sanitize_text_field( $response['question'] ?? '' );
			$a_raw = $response['answer'] ?? '';
			$a_text = sanitize_textarea_field( $a_raw );

			if ( empty( $q ) || $a_raw === '' ) {
				continue;
			}

			$type = $question_types[ $q ] ?? 'choice';

			if ( 'text' === $type ) {
				if ( ! isset( $text_answers[ $q ] ) ) {
					$text_answers[ $q ] = [];
				}
				$text_answers[ $q ][] = $a_text;
			} else {
				$a = sanitize_text_field( $a_raw );
				if ( ! isset( $button_answers[ $q ] ) ) {
					$button_answers[ $q ] = [];
				}
				if ( ! isset( $button_answers[ $q ][ $a ] ) ) {
					$button_answers[ $q ][ $a ] = 0;
				}
				$button_answers[ $q ][ $a ]++;
			}
		}
	}

	echo '<div class="wrap yak-feedback-report">';

	// Multiple-choice answers summary
	echo '<h2>Summary: Multiple-Choice Responses</h2>';
	if ( empty( $button_answers ) ) {
		echo '<p>No multiple-choice responses found.</p>';
	} else {
		foreach ( $button_answers as $question => $answers ) {
			echo '<h3>' . esc_html( $question ) . '</h3>';
			echo '<table class="wp-list-table widefat fixed striped">';
			echo '<thead><tr><th>Answer</th><th>Count</th></tr></thead><tbody>';
			foreach ( $answers as $answer => $count ) {
				echo '<tr><td>' . esc_html( $answer ) . '</td><td>' . intval( $count ) . '</td></tr>';
			}
			echo '</tbody></table>';
		}
	}

	echo '<h2>Open-Ended Responses</h2>';
        if ( empty( $text_answers ) ) {
            echo '<p>No open-ended responses found.</p>';
        } else {
            $entry_counter = 0; // unique IDs
            foreach ( $text_answers as $question => $answers ) {
                echo '<h3>' . esc_html( $question ) . '</h3>';
                foreach ( $answers as $text_response ) {
                    $entry_counter++;
                    $modal_id = 'yak-feedback-modal-' . $entry_counter;

                    // Snippet preview (first 150 chars)
                    $preview = wp_trim_words( $text_response, 30, '...' );

                    echo '<div style="margin-bottom:1.5rem; padding:0.5rem; background:#f9f9f9; border-radius:4px; white-space: pre-wrap;">';
                    echo esc_html( $preview );
                    echo '<br><a href="#" class="yak-feedback-view-entry" data-modal-id="' . esc_attr( $modal_id ) . '">View full entry</a>';
                    echo '</div>';

                    // Hidden modal content
                    echo '<div id="' . esc_attr( $modal_id ) . '" class="yak-feedback-modal" style="display:none;">';
                    echo '<div class="yak-feedback-modal-content">';
                    echo '<button class="yak-feedback-modal-close" aria-label="Close">&times;</button>';
                    echo '<h4>' . esc_html( $question ) . '</h4>';
                    echo '<pre style="white-space: pre-wrap; background:#eee; padding:1rem; border-radius:4px; max-height:400px; overflow:auto;">' . esc_html( $text_response ) . '</pre>';
                    echo '</div>';
                    echo '</div>';
                }
            }
        }


	echo '</div>';

    clb_yak_feedback_report_modal_script();

} );











function clb_yak_feedback_report_modal_script() {
	?>
	<script>
	document.addEventListener('DOMContentLoaded', () => {
		// Open modal on "View full entry" click
		document.querySelectorAll('.yak-feedback-view-entry').forEach(link => {
			link.addEventListener('click', e => {
				e.preventDefault();
				const modalId = link.getAttribute('data-modal-id');
				const modal = document.getElementById(modalId);
				if (modal) modal.style.display = 'flex';
			});
		});

		// Close modal on close button click
		document.querySelectorAll('.yak-feedback-modal-close').forEach(btn => {
			btn.addEventListener('click', e => {
				const modal = btn.closest('.yak-feedback-modal');
				if (modal) modal.style.display = 'none';
			});
		});

		// Close modal on clicking outside modal content
		document.querySelectorAll('.yak-feedback-modal').forEach(modal => {
			modal.addEventListener('click', e => {
				if (e.target === modal) {
					modal.style.display = 'none';
				}
			});
		});
	});
	</script>
	<?php
}










// Run Genesis loop and output
genesis();
