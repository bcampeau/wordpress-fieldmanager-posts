( function( $ ) {

var fm_posts_element;

$( document ).ready( function () {
	$( '.fm-posts-related' ).live( 'click', function( e ) {
		// If the post ID is empty, do nothing
		if ( $("#post_ID").val() != "" ) {
			// Store the element we are working with
			fm_posts_element = $(this).data('relatedElement');
			var fm_taxonomy_terms = {};
			
			// Get all the currently selected taxonomy terms in single taxonomy fields for use in finding related content
			// This must be done using Javascript since terms may have changed in the interface before being committed to the database
			// First check all fieldmanager taxonomy fields
			$(".fm-options").each(function () {
				// Only use option fields where a taxonomy is specified
				var fm_taxonomy = JSON.parse($(this).data("taxonomy"));
				if( fm_taxonomy != undefined ) {
					var fm_terms = new Array();
					$("option:selected", this).each(function () {
						fm_terms.push( $(this).val() );
					});
					if ( fm_terms.length > 0 ) fm_taxonomy_terms[$(this).data( "taxonomy" )] = fm_terms;
				}
			});
			
			// Next check all built-in WordPress fields
			$(".categorychecklist input:checked").each(function () {
				// Split the ID to get the taxonomy and field ID
				var term_parts = $(this).attr('id').split("-");
				
				// See if the taxonomy array already exists
				if ( $.isArray( fm_taxonomy_terms[term_parts[1]] ) == false ) fm_taxonomy_terms[term_parts[1]] = new Array();

				// Add this term to the array if it doesn't already exist
				if ( $.inArray( term_parts[2], fm_taxonomy_terms[term_parts[1]] ) == -1 ) fm_taxonomy_terms[term_parts[1]].push(term_parts[2]);
			});
			$(".tagchecklist span").each(function () {
				// Strip the X
				var tag = $(this).text().substr(2);
			
				// See if the taxonomy array already exists
				if ( $.isArray( fm_taxonomy_terms['post_tag'] ) == false ) fm_taxonomy_terms['post_tag'] = new Array();

				// Add this term to the array if it doesn't already exist
				if ( $.inArray( tag, fm_taxonomy_terms['post_tag'] ) == -1 ) fm_taxonomy_terms['post_tag'].push(tag);
			});
		
			//console.log(fm_taxonomy_terms);
			var fm_taxonomy_terms_json = JSON.stringify(fm_taxonomy_terms);
			//console.log(fm_taxonomy_terms_json);

			// Query for matching terms
			$.post( ajaxurl, { action: 'fm_posts_find_related', post_id: $("#post_ID").val(), post_terms: fm_taxonomy_terms_json, post_types: $(this).data("postTypes"), fm_posts_find_related_nonce: fm_posts.nonce }, function ( result ) {
				console.log( result );
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
					$( "#" + fm_posts_element ).trigger( 'fm_posts_related' );

				}
				
				// Clear the terms element since this was used solely for this request
				fm_posts_element = "";
			});
		}
		
	} );
} );

} )( jQuery );