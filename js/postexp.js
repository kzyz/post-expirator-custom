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
	        $( "#expiration_timestamp b" ).html( $( "option[value=" + $( "#exp_month" ).val() + "]", "#exp_month" ).text() + " " + $( "#exp_day" ).val() + ", " + $( "#exp_year" ).val() + " @ " + $( "#exp_hour" ).val() + ":" + $( "#exp_minute" ).val() );
        }
        return false;
    });
} )( jQuery );