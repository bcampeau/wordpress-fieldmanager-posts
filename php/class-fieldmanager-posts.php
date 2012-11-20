<?php

class Fieldmanager_Posts {

	/** @type string Fieldmanager field name to augment with related post search */ 
	public $field_name;
	
	/** @type boolean Specify a minimum length under which content would not searched for terms */ 
	public $minimum_content_length;
	
	/** @type boolean Specify the maximum links to return */ 
	public $maximum_links = 5;
	
	/** @type string Label for related posts search button */ 
	public $button_label = "Find Related Posts";

	public function __construct( $options = array() ) {
		// Extract options
		foreach ( $options as $k => $v ) {
			try {
				$reflection = new ReflectionProperty( $this, $k ); // Would throw a ReflectionException if item doesn't exist (developer error)
				if ( $reflection->isPublic() ) $this->$k = $v;
				else throw new Exception; // If the property isn't public, don't set it (rare)
			} catch ( Exception $e ) {
				$message = sprintf(
					__( 'You attempted to set a property <em>%1$s</em> that is nonexistant or invalid for an instance of <em>%2$s</em> named <em>%3$s</em>.' ),
					$k, __CLASS__, !empty( $options['name'] ) ? $options['name'] : 'NULL'
				);
				$title = __( 'Nonexistant or invalid option' );
				wp_die( $message, $title );
			}
		}
		
		// Add the action hook for term extraction handling via AJAX
		add_action( 'wp_ajax_fm_posts_find_related', array( $this, 'ajax_find_related_posts' ) );
		
		// Add the filter required for handling addition of a suggest terms button
		add_filter( 'fm_element_markup_end', array( $this, 'modify_form_element' ), 10, 2 ); 
		
		// Add the Fieldmanager Terms javascript library
		fm_add_script( 'fm_posts_js', 'js/fieldmanager-posts.js', array(), false, false, 'fm_posts', array( 'nonce' => wp_create_nonce( 'fm_posts_find_related_nonce' ) ), fieldmanager_posts_get_baseurl() );

	}
	
	/**
	 * Handle the AJAX request for post matching
	 *
	 * @params string $post_type
	 * @return void
	 */
	public function ajax_find_related_posts() {
		// Check the nonce before we do anything
		check_ajax_referer( 'fm_posts_find_related_nonce', 'fm_posts_find_related_nonce' );
		error_log("1\n");
		// Create an array to hold the results.
		$result = array();
		error_log("2\n");
		// Pass the post title and content to term extraction if one if them is not empty. 
		// Otherwise return the empty array.
		error_log("3\n");
		if( !empty( $_POST['post_id'] ) && !empty( $_POST['post_terms'] ) ) {
			error_log("4\n");
			$result = $this->related_posts( $_POST['post_id'], $_POST['post_terms'] );
			error_log("5\n");
		}
		error_log("6\n");
		echo json_encode( $result );
		
		die();
	}

	/**
	 * Handle finding related posts
	 *
	 * @params string $post_type
	 * @return void
	 */
	public function related_posts( $post_id, $post_terms ) {
		error_log("a\n");		
		// Holds the return values
		$post_matches = array();
		error_log("b\n");
		// Convert terms to an object
		$terms = json_decode( stripslashes( $post_terms ) );
		error_log("c\n");
		$related_post_results = array();
		error_log("d\n");
		// Query each taxonomy and term individually because we need to know how many matches occurred for each to rank the final list
		error_log( print_r( get_object_vars( $terms ), true ) );
		foreach( get_object_vars( $terms ) as $taxonomy => $terms ) {
			error_log("e\n");
			$tax_name = json_decode($taxonomy);
			error_log("f\n");
			foreach( $terms as $term_id ) {
				$args = array(
					'post__not_in' => array( $post_id ), // exclude the post we are trying to find related content for
					'tax_query' => array(
						array( 
							'taxonomy' => $tax_name,
							'field' => 'id',
							'terms' => $term_id
						)
					),
					'orderby' => 'date', // always order by newest first
					'order' => 'DESC',
					'posts_per_page' => 50
				);
				$query = new WP_Query( $args );
				
				error_log( $taxonomy . ":" . $term_id );
				error_log( print_r( $query->request, true ) );
								
				while( $query->have_posts() ) {
					 $query->next_post();
					 // See if this post was found previously.
					 if( array_key_exists( $query->post->ID, $related_post_results ) ) {
					 	// Post already matched. Just increment the match count.
					 	$related_post_results[$query->post->ID]['count']++;
					 } else {
					 	// First match. Add to the array.
						$post_type = get_post_type_object( get_post_type( $query->post->ID ) );
					 	$related_post_results[$query->post->ID] = array(
					 		'count' => 1,
					 		'id' => $query->post->ID,
					 		'post_type' => $post_type->labels->singular_name,
					 		'post_date' => get_the_date( $query->post->ID ),
					 		'post_title' => get_the_title( $query->post->ID ), 
					 		'permalink' => get_permalink( $query->post->ID )
					 	);
					 }
				}
			}
		}
		
		// Perform a final sort on the data to favor posts with the most matches
		uasort( $related_post_results, function( $a, $b ) {
			 return ( $a['count'] > $b['count'] ) ? -1 : 1;
		} );
		
		// Return the slice of the array up to the max post limit
		error_log( print_r( $related_post_results, true ) );
		return array_slice( $related_post_results, 0, $this->maximum_links );			
	}
	
	/**
	 * Handles modifying the Fieldmanager field to add the "Find Related Posts" button
	 * @return string Modified Fieldmanager form element
	 */
	public function modify_form_element( $value, $field ) {
		// Verify the field name matches the one being modified and that it is the correct type of element to be used with this plugin.
		// This functionality is only enabled for Fieldmanager_Post.
		// If so, add the suggest button. Otherwise return the element unmodified.
		if ( $field->name == $this->field_name 
			&& ( get_class( $field ) == "Fieldmanager_Post" )
			&& isset( $field->post_types )
			&& !empty( $field->post_types ) ) $value .= $this->find_related( $field->get_element_id(), $field->post_types );
		
		return $value;
	}
	
	/**
	 * Generates HTML for the "Find Related Posts" button.
	 * @return string Button HTML.
	 */
	public function find_related( $related_field_id, $post_types ) {
		$classes = array( 'fm-posts-related', 'fm-posts-related-' . $this->field_name );
		$out = '<div class="fm-posts-related-wrapper">';
		$out .= sprintf(
			'<input type="button" class="%s" value="%s" name="%s" data-related-element="%s" data-post-types="%s" />',
			implode( ' ', $classes ),
			__( $this->button_label ),
			'fm_posts_related_' . $this->field_name,
			$related_field_id,
			( is_array( $post_types ) ) ? implode( ",", $post_types ) : $post_types
		);
		$out .= '</div>';
		return $out;
	}


}