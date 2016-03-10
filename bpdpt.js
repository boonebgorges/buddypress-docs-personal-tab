( function( $ ) {
	$( document ).ready( function() {
		$( 'label[for="new-folder-type"]' ).hide();
		$( 'input[name="new-folder-type"]' ).hide();
		$( 'select[name="new-folder-type"]' ).hide();

		$( '#doc-folders' ).on( 'bp_docs:toggle_folder_metabox', function() {
			$( this ).show();
		} );

		// Blargh
		$( '#doc-folders' ).trigger( 'bp_docs:toggle_folder_metabox' );
	} );
} )( jQuery )
