( function( $ ) {
    $( "#expiration_date_div" ).siblings( "a.edit-expiration_date" ).click( function () {
        if ( $( "#expiration_date_div" ).is( ":hidden" ) ) {
             $( "#expiration_date_div" ).slideDown( "normal" );
             $( this ).hide();
        }
        return false;
    });
    $( ".cancel-expiration_date", "#expiration_date_div" ).click( function () {
        $( "#expiration_date_div" ).slideUp( "normal" );
        $( "#expiration_date_div" ).siblings( "a.edit-expiration_date" ).show();
        return false;
    });
    $( ".save-expiration_date", "#expiration_date_div" ).click( function () {
        $( "#expiration_date_div" ).slideUp( "normal" );
        $( "#expiration_date_div" ).siblings( "a.edit-expiration_date" ).show();
        if ( $( "#exp_check" ).attr('checked') ) {
	        $( "#expiration_timestamp b" ).html(
					postL10n.dateFormat.replace( '%1$s', $('option[value="' + $( "#exp_month" ).val() + '"]', '#exp_month').text() )
						.replace( '%2$s', $( "#exp_day" ).val() )
						.replace( '%3$s', $( "#exp_year" ).val() )
						.replace( '%4$s', $( "#exp_hour" ).val() )
						.replace( '%5$s', $( "#exp_minute" ).val() )
				);
        }
        return false;
    });
} )( jQuery );



