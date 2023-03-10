jQuery( document ).ready( function() {

	var avadaMenu;

	// Hide the menu from Appearance.
	jQuery( '#menu-appearance a[href="themes.php?page=avada_options"]' ).css( 'display', 'none' );

	// Activate the Avada admin menu global option entry when global options are active.
	if ( jQuery( 'a[href="themes.php?page=avada_options"]' ).hasClass( 'current' ) ) {
		avadaMenu = jQuery( '#toplevel_page_avada' );

		avadaMenu.addClass( 'wp-has-current-submenu wp-menu-open' ).removeClass( 'wp-not-current-submenu' );
		avadaMenu.children( 'a' ).addClass( 'wp-has-current-submenu wp-menu-open' );
		avadaMenu.children( '.wp-submenu' ).find( 'a[href="themes.php?page=avada_options"]' ).parent().addClass( 'current' );
		avadaMenu.children( '.wp-submenu' ).find( 'a[href="themes.php?page=avada_options"]' ).addClass( 'current' );

		// Do not show the appearance menu as active
		jQuery( '#menu-appearance a[href="themes.php"]' ).removeClass( 'wp-has-current-submenu wp-menu-open' );
		jQuery( '#menu-appearance' ).removeClass( 'wp-has-current-submenu wp-menu-open' );
		jQuery( '#menu-appearance' ).addClass( 'wp-not-current-submenu' );
		jQuery( '#menu-appearance a[href="themes.php"]' ).addClass( 'wp-not-current-submenu' );
		jQuery( '#menu-appearance' ).children( '.wp-submenu' ).find( 'li' ).removeClass( 'current' );
	}

	// Maintenance
	jQuery( '.avada-db-menu-sub-item-maintenance a, a[href="themes.php?page=avada_options#heading_maintenance"]' ).on( 'click', function( e ) {
		e.preventDefault();

		goToMaintenanceModeTab();
	} );
} );

// Prevent anchor jumping on page load
if ( location.hash && '#heading_maintenance' === location.hash ) {
	jQuery( window ).on( 'load', function() {
		setTimeout( function() {
			goToMaintenanceModeTab();
		}, 200 );
	} );
}

function goToMaintenanceModeTab() {
	jQuery( 'a[data-css-id="heading_maintenance"]' ).trigger( 'click' );

	jQuery( 'html, body' ).animate( {
		scrollTop: jQuery( '#fusionredux-form-wrapper' ).offset().top - jQuery( '#wpadminbar' ).height()
	}, 500 );
}
