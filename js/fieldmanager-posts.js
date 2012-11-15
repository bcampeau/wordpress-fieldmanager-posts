( function( $ ) {

var fm_posts_element;

$( document ).ready( function () {
	$( '.fm-posts-related' ).live( 'click', function( e ) {
		// If the post ID is empty, do nothing
		if ( $("#post_ID").val() != "" ) {
			// Store the element we are working with
			fm_posts_element = $(this).data('relatedElement');
		
			// Query for matching terms
			$.post( ajaxurl, { action: 'fm_posts_find_related', post_id: $("#post_ID").val(), post_types: $(this).data("postTypes"), fm_posts_find_related_nonce: fm_posts.nonce }, function ( result ) {
				resultObj = JSON.parse( result );
				// Check if there were results
				if( !$.isEmptyObject( resultObj ) ) {
					// Iterate over the matches
					$.each( resultObj, function( index, post ) {
						post.id;
						post.title;
						post.url;
					});
					
					// Also trigger a jQuery event other custom theme scripts can bind to if needed
					$( "#" + fm_terms_element ).trigger( 'fm_posts_related' );

				}
				
				// Clear the terms element since this was used solely for this request
				fm_posts_element = "";
			});
		}
		
	} );
} );

} )( jQuery );