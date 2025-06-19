<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// Access control: Admin, Editor, Site Manager only
if (
	! current_user_can( 'administrator' ) &&
	! current_user_can( 'editor' ) &&
	! current_user_can( 'site_manager' )
) {
	wp_die( 'You do not have permission to view this page.' );
}

// Load and parse the JSON flow file for question types
$flow_file = plugin_dir_path( __FILE__ ) . '../../data/feedback-flow.json';
$question_types = [];

if ( file_exists( $flow_file ) ) {
	$flow_json = file_get_contents( $flow_file );
	$flow_data = json_decode( $flow_json, true );
	if ( is_array( $flow_data ) ) {
		foreach ( $flow_data as $step_id => $step ) {
			if ( isset( $step['question'] ) ) {
				$question_types[ $step['question'] ] = $step['type'] ?? 'choice';
			}
		}
	}
}

// Hook before the loop to show our report
add_action( 'genesis_entry_content', function() use ( $question_types ) {

	// Fetch all private feedback posts
	$feedback_posts = get_posts( [
		'post_type'      => 'yak_feedback',
		'post_status'    => 'private',
		'posts_per_page' => -1,
		'orderby'        => 'date',
		'order'          => 'DESC',
	] );

	$button_answers = [];
	$text_answers   = [];
	$post_map       = [];

	foreach ( $feedback_posts as $fb_post ) {
		$fb_data = get_post_meta( $fb_post->ID, '_feedback_responses', true );
		if ( ! is_array( $fb_data ) ) continue;

		$post_map[ $fb_post->ID ] = [
			'title'      => get_the_title( $fb_post ),
			'date'       => get_the_date( 'M j, Y H:i', $fb_post ),
			'responses'  => $fb_data,
			'user_agent' => get_post_meta( $fb_post->ID, '_feedback_user_agent', true ),
			'ip'         => get_post_meta( $fb_post->ID, '_feedback_ip', true ),
		];

		foreach ( $fb_data as $response ) {
			$q = sanitize_text_field( $response['question'] ?? '' );
			$a_raw = $response['answer'] ?? '';
			$a_text = sanitize_textarea_field( $a_raw );

			if ( empty( $q ) || $a_raw === '' ) continue;

			$type = $question_types[ $q ] ?? 'choice';

			if ( 'text' === $type ) {
				if ( ! isset( $text_answers[ $q ] ) ) {
					$text_answers[ $q ] = [];
				}
				$text_answers[ $q ][] = [
					'post_id' => $fb_post->ID,
					'answer'  => $a_text,
				];
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

	// Multiple-choice summary
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

	// Open-ended responses with modals
	echo '<h2>Open-Ended Responses</h2>';
	if ( empty( $text_answers ) ) {
		echo '<p>No open-ended responses found.</p>';
	} else {
		$entry_counter = 0;
		foreach ( $text_answers as $question => $answers ) {
			echo '<h3>' . esc_html( $question ) . '</h3>';
			foreach ( $answers as $entry ) {
				$entry_counter++;
				$post_id = intval( $entry['post_id'] );
				$modal_id = 'yak-feedback-modal-' . $entry_counter;

				$preview = wp_trim_words( $entry['answer'], 30, '...' );

				echo '<div class="clb-yak-feedback-text-wrapper">';
				echo esc_html( $preview );
				echo '<br><a href="#" class="yak-feedback-view-entry" data-modal-id="' . esc_attr( $modal_id ) . '" data-post-id="' . esc_attr( $post_id ) . '">View full entry</a>';
				echo '</div>';

				if ( isset( $post_map[ $post_id ] ) ) {
					$full_entry = $post_map[ $post_id ];
					echo '<div id="' . esc_attr( $modal_id ) . '" class="yak-feedback-modal" style="display:none;">';
					echo '<div class="yak-feedback-modal-content">';
					echo '<button class="yak-feedback-modal-close" aria-label="Close">&times;</button>';
					echo '<h3 class="yak-feedback-modal-header">Feedback Entry #' . $post_id . ' - ' . esc_html( $full_entry['date'] ) . '</h3>';
					echo '<hr>';
					echo '<ul>';
					foreach ( $full_entry['responses'] as $resp ) {
						echo '<li><strong>' . esc_html( $resp['question'] ?? '' ) . ':</strong> ' . esc_html( $resp['answer'] ?? '' ) . '</li>';
					}
					echo '</ul>';
					echo '</div>';
					echo '</div>';
				}
			}
		}
	}

	echo '</div>';

    yak_feedback_render_word_cloud( $text_answers );


} );

// Empty content area (optional)
add_action( 'genesis_entry_content', function() {
	echo ''; // or any page content if desired
} );

// Modal JS + CSS, hooked in footer
add_action( 'wp_footer', function() {
	?>
	<style>
		.yak-feedback-modal {
			position: fixed;
			top: 0; left: 0; right: 0; bottom: 0;
			background: rgba(0,0,0,0.4);
			display: none;
			justify-content: center;
			align-items: center;
			z-index: 9999;
		}
		.yak-feedback-modal-content {
			background: white;
			padding: 1.5rem;
			max-width: 600px;
			max-height: 80vh;
			overflow-y: auto;
			border-radius: 6px;
			position: relative;
		}
		.yak-feedback-modal-close {
			position: absolute;
			top: 0.5rem;
			right: 0.5rem;
			background: none;
			border: none;
			font-size: 1.5rem;
			cursor: pointer;
		}
	</style>
	<script>
	document.addEventListener('DOMContentLoaded', () => {
		document.querySelectorAll('.yak-feedback-view-entry').forEach(link => {
			link.addEventListener('click', e => {
				e.preventDefault();
				const modalId = link.getAttribute('data-modal-id');
				const modal = document.getElementById(modalId);
				if (modal) modal.style.display = 'flex';
			});
		});

		document.querySelectorAll('.yak-feedback-modal-close').forEach(btn => {
			btn.addEventListener('click', e => {
				const modal = btn.closest('.yak-feedback-modal');
				if (modal) modal.style.display = 'none';
			});
		});

		document.querySelectorAll('.yak-feedback-modal').forEach(modal => {
			modal.addEventListener('click', e => {
				if (e.target === modal) modal.style.display = 'none';
			});
		});
	});
	</script>
	<?php
} );



function yak_feedback_render_word_cloud( $text_answers ) {
	if ( empty( $text_answers ) ) return;

	// Stopwords to filter out
	$stopwords = [
		'the','and','a','an','in','on','of','to','is','it','that','this','with','for','as','at','by','from','be','has','was','are','were','i','you','they','we','he','she','them','or','but','not','so','if','then','than','into','out','about','up','down','over','under','all','some','any','my','your','their','our','its','also'
	];

	$all_words = [];

	// Gather all text content
	foreach ( $text_answers as $entries ) {
		foreach ( $entries as $entry ) {
			$words = str_word_count( strtolower( $entry['answer'] ), 1 );
			foreach ( $words as $word ) {
				$word = trim( preg_replace( '/[^\p{L}\p{N}]+/u', '', $word ) ); // strip punctuation
				if ( strlen( $word ) < 3 || in_array( $word, $stopwords ) ) continue;
				if ( ! isset( $all_words[ $word ] ) ) {
					$all_words[ $word ] = 0;
				}
				$all_words[ $word ]++;
			}
		}
	}

	if ( empty( $all_words ) ) return;

	arsort( $all_words );
	$top_words = array_slice( $all_words, 0, 50, true );
	$max_count = max( $top_words );

	echo '<h2>Top Words from Open-Ended Responses</h2>';
	echo '<div class="yak-feedback-wordcloud">';

	foreach ( $top_words as $word => $count ) {
		$scale = ( $count / $max_count );
		$font_size = 0.85 + $scale * 1.5; // ~0.85em to 2.35em
		echo '<span style="font-size:' . esc_attr( round( $font_size, 2 ) ) . 'em;" title="' . esc_attr( $count ) . '">' . esc_html( $word ) . '</span> ';
	}

	echo '</div>';

	echo '<style>
		.yak-feedback-wordcloud {
			padding: 1rem;
			border: 1px solid #ddd;
			background: #fefefe;
			border-radius: 6px;
			line-height: 2;
			max-width: 100%;
			font-family: sans-serif;
		}
		.yak-feedback-wordcloud span {
			display: inline-block;
			margin: 0.25rem;
			color: #333;
		}
	</style>';
}


// Run Genesis loop and output
genesis();
