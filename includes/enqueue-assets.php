<?php

add_action( 'wp_enqueue_scripts', function() {
	if ( is_singular( 'post' ) ) {

        $plugin_url = plugin_dir_url( dirname( __FILE__ ) );
        wp_enqueue_script(
            'yak-feedback',
            $plugin_url . 'assets/yak-feedback.js',
            [ 'jquery' ],
            '1.0',
            true
        );

		// wp_enqueue_script(
		// 	'yak-feedback',
		// 	plugins_url( 'assets/yak-feedback.js', __FILE__ ),
		// 	[ 'jquery' ],
		// 	'1.0',
		// 	true
		// );

		wp_localize_script( 'yak-feedback', 'YakFeedback', [
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'yak_feedback_nonce' ),
			'post_id'  => get_the_ID(),
		] );
	}
} );


wp_enqueue_style(
	'yak-feedback-style',
	plugins_url( 'assets/yak-feedback.css', dirname( __FILE__ ) ),
	[],
	'1.0'
);

