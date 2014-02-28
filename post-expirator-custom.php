<?php
/*
Plugin Name: Post Expirator Custom
Plugin URI: 
Description: This Plugin is Custom Post Expirator Plugin.
Author: Kazunori Yamazaki
Version: 0.2
Author URI: 
Text Domain: post-expirator-custom
*/

/*
This program is based on the Post Expirator plugin written by Aaron Axelsen.
I appreciate your efforts, Aaron.
*/

/******************************************************************************
 * PostExp
 * 
 * @author		Kazunori Yamazaki
 * @version		0.2
 * 
 *****************************************************************************/

class PostExp {
	
	var $version;
	var $text_domain;
	var $plugin_base_name;
	var $plugin_dir;
	
	/**
	 * the constructor
	 * 
	 * @param none
	 * @return none
	 */
	function PostExp() {
		global $wpdb;

		$this -> version = '0.2';
		$this -> text_domain = 'post-expirator-custom';
		$this -> plugin_base_name = plugin_basename( __FILE__ );
		$this -> plugin_dir = get_option( 'siteurl' ) . '/wp-content/plugins/' . dirname( $this -> plugin_base_name );
		load_plugin_textdomain( $this -> text_domain, false, 'post-expirator-custom/languages' );
	}
	
	/**
	 * addCronMinutes
	 * 
	 * @param $array
	 * @return $array
	 */
	function addCronMinutes( $array ) {
		$array['postexpiratorminute'] = array(
			'interval' => 60,
			'display' => __( 'Once a Minute', $this -> text_domain )
		);
		
		return $array;
	}
	
	/**
	 * deleteExpiredPosts
	 * 
	 * @param none
	 * @return none
	 */
	function deleteExpiredPosts() {
		global $wpdb;
		
		$time_adj = current_time( 'mysql', 1 );
		
		$results = $wpdb -> get_results( 
								$wpdb -> prepare( 
									"SELECT post_id, meta_value " .
									"FROM " . $wpdb -> postmeta . " as postmeta, " . $wpdb -> posts ." as posts " .
									"WHERE postmeta.post_id = posts.ID " .
									"AND posts.post_status = %s " .
									"AND postmeta.meta_key = %s " .
									"AND postmeta.meta_value <= %s ",
									'publish',
									'exp_date_gmt',
									$time_adj
								)
							);
	  	if ( !empty( $results ) ) {
	  		foreach ( $results as $row ) {
				wp_update_post( array( 'ID' => $row -> post_id, 'post_status' => 'draft' ) );
	  		}
		}
	}
	
	/**
	 * activate
	 * 
	 * @param none
	 * @return none
	 */
	function activate() {
		global $current_blog;
		
		$time = time();
		
		if ( is_multisite() )
			wp_schedule_event( $time, 'postexpiratorminute', 'expirationdate_delete_'. $current_blog -> blog_id );
		else
			wp_schedule_event( $time, 'postexpiratorminute', 'expirationdate_delete' );
	}
	
	/**
	 * deactivate
	 * 
	 * @param none
	 * @return none
	 */
	function deactivate() {
		global $current_blog;
		
		if ( is_multisite() )
			wp_clear_scheduled_hook( 'expirationdate_delete_' . $current_blog -> blog_id );
		else
			wp_clear_scheduled_hook( 'expirationdate_delete' );
	}
	
	/**
	 * addColumns
	 * 
	 * @param $columns
	 * @return $columns
	 */
	function addColumns( $columns ) {
	  	$columns['expires'] = __( 'Expires', $this -> text_domain );
	  	return $columns;
	}

	/**
	 * showValue
	 * 
	 * @param $column_name
	 * @return none
	 */
	function showValue( $column_name ) {
		if ( $column_name == 'expires' ) { 
			global $wpdb, $post, $count;
			
			$id = $post -> ID;
			
			$utc = get_post_meta( $id, 'exp_date', true );
			$gmt = get_post_meta( $id, 'exp_date_gmt', true );
						
			if ( empty( $utc ) ) {
				$t_time = $h_time = __( 'None' );
				$time_diff = 0;
			} else {
				$t_time = date_i18n( __( 'Y/m/d g:i:s A' ), strtotime( $utc ) );
				$m_time = $utc;
				$time = mysql2date( 'G', $gmt );
	
				$time_diff = time() - $time;
	
				if ( $time_diff > 0 && $time_diff < 24 * 60 * 60 )
					$h_time = sprintf( __( '%s ago' ), human_time_diff( $time ) );
				else
					$h_time = mysql2date( __( 'Y/m/d' ), $m_time );
			}
			
			echo '<abbr title="' . $t_time . '">' . $h_time . '</abbr>';
			echo '<br />';
			if ( 'publish' == $post -> post_status && !empty( $utc ) ) {
				if ( $time_diff > 0 )
					echo '<strong class="attention">' . __( 'Missed schedule' ). '</strong>';
				else
					_e( 'Scheduled' );
			}
		}
	}
	
	/**
	 * updatePostMeta
	 * 
	 * @param $id
	 * @return none
	 */
	function updatePostMeta( $id ) {
		if ( isset( $_POST['exp_check'] ) ) {
		    $month	= $_POST['exp_month'];
		    $day	= $_POST['exp_day'];
		    $year	= $_POST['exp_year'];
		    $hour	= $_POST['exp_hour'];
		    $minute	= $_POST['exp_minute'];
		    $second	= $_POST['exp_second'];
		    
			$utc = sprintf( "%04d-%02d-%02d %02d:%02d:%02d", $year, $month, $day, $hour, $minute, $second );
			$gmt = get_gmt_from_date( $utc );
			
			update_post_meta( $id, 'exp_date', $utc );
			update_post_meta( $id, 'exp_date_gmt', $gmt );
		} else {
			delete_post_meta( $id, 'exp_date' );
			delete_post_meta( $id, 'exp_date_gmt' );
		}
	}
	
	/**
	 * submitbox
	 * 
	 * @param $id
	 * @return none
	 */
	function submitbox() {
		global $post, $action;

		$datef = __( 'M j, Y @ G:i' );
		$expirationdatets = strtotime( get_post_meta( $post -> ID, 'exp_date' , true ) );
		
		if ( empty( $expirationdatets ) ) {
			$exp_date = __( 'None' );
		} else {
			$exp_date = date_i18n( $datef, $expirationdatets );
		}
		
		echo '<div class="misc-pub-section curtime misc-pub-section-last" style="border-top:1px solid #EEE">';
		echo '	<span id="expiration_timestamp"> ' . __( 'Expired on', $this -> text_domain ) . ': <b>' . $exp_date . '</b></span>';
		echo '	<a href="#edit_expiration_date" class="edit-expiration_date hide-if-no-js" tabindex="4">' . __( 'Edit' ) . '</a>';
		echo '	<div id="expiration_date_div" class="hide-if-js">';
		$this -> touchTime( ( $action == 'edit' ), 1, 4 );
		echo '	</div>';
		echo '</div>';
	}
	
	/**
	 * touchTime
	 *
	 * @param unknown_type $edit
	 * @param unknown_type $for_post
	 * @param unknown_type $tab_index
	 * @param unknown_type $multi
	 */
	function touchTime( $edit = 1, $for_post = 1, $tab_index = 0, $multi = 0 ) {
		global $wp_locale;
		$post = get_post();

		$exp_date = get_post_meta( $post->ID, 'exp_date', true );
		$exp_date_gmt = get_post_meta( $post->ID, 'exp_date_gmt', true );

		if ( $for_post )
			$edit = ! ( !$exp_date_gmt || '0000-00-00 00:00:00' == $exp_date_gmt ) ;

		$tab_index_attribute = '';
		if ( (int) $tab_index > 0 )
			$tab_index_attribute = " tabindex=\"$tab_index\"";

		// echo '<label for="timestamp" style="display: block;"><input type="checkbox" class="checkbox" name="edit_date" value="1" id="timestamp"'.$tab_index_attribute.' /> '.__( 'Edit timestamp' ).'</label><br />';
	
		$time_adj = current_time( 'timestamp' );
		$jj = ( $edit ) ? mysql2date( 'd', $exp_date, false ) : gmdate( 'd', $time_adj );
		$mm = ( $edit ) ? mysql2date( 'm', $exp_date, false ) : gmdate( 'm', $time_adj );
		$aa = ( $edit ) ? mysql2date( 'Y', $exp_date, false ) : gmdate( 'Y', $time_adj );
		$hh = ( $edit ) ? mysql2date( 'H', $exp_date, false ) : gmdate( 'H', $time_adj );
		$mn = ( $edit ) ? mysql2date( 'i', $exp_date, false ) : gmdate( 'i', $time_adj );
		$ss = ( $edit ) ? mysql2date( 's', $exp_date, false ) : gmdate( 's', $time_adj );

		$month = "<select " . ( $multi ? '' : 'id="exp_month" ' ) . "name=\"exp_month\"$tab_index_attribute>\n";
		for ( $i = 1; $i < 13; $i = $i +1 ) {
			$monthnum = zeroise($i, 2);
			$month .= "\t\t\t" . '<option value="' . $monthnum . '"';
			if ( $i == $mm )
				$month .= ' selected="selected"';
			/* translators: 1: month number (01, 02, etc.), 2: month abbreviation */
			$month .= '>' . sprintf( __( '%1$s-%2$s' ), $monthnum, $wp_locale->get_month_abbrev( $wp_locale->get_month( $i ) ) ) . "</option>\n";
		}
		$month .= '</select>';


		$day = '<input type="text" ' . ( $multi ? '' : 'id="exp_day" ' ) . 'name="exp_day" value="' . $jj . '" size="2" maxlength="2"' . $tab_index_attribute . ' autocomplete="off" />';
		$year = '<input type="text" ' . ( $multi ? '' : 'id="exp_year" ' ) . 'name="exp_year" value="' . $aa . '" size="4" maxlength="4"' . $tab_index_attribute . ' autocomplete="off" />';
		$hour = '<input type="text" ' . ( $multi ? '' : 'id="exp_hour" ' ) . 'name="exp_hour" value="' . $hh . '" size="2" maxlength="2"' . $tab_index_attribute . ' autocomplete="off" />';
		$minute = '<input type="text" ' . ( $multi ? '' : 'id="exp_minute" ' ) . 'name="exp_minute" value="' . $mn . '" size="2" maxlength="2"' . $tab_index_attribute . ' autocomplete="off" />';
		

		echo '<div class="expiration_date-wrap">';
		echo '<p class="expiration_date-check"><input type="checkbox" name="exp_check" id="exp_check"' . ( ( !$exp_date_gmt ) ? '' : ' checked="checked"' ) . ' /> ' . __( 'Enable Post Expiration', $this -> text_domain ) . '</p>';
		/* translators: 1: month input, 2: day input, 3: year input, 4: hour input, 5: minute input */
		printf(__('%1$s %2$s, %3$s @ %4$s : %5$s'), $month, $day, $year, $hour, $minute);

		echo '</div><input type="hidden" id="exp_second" name="exp_second" value="' . $ss . '" />';
	
		if ( $multi ) return;

		echo "\n";

		echo '<p>';
		echo '<a href="#edit_expiration_date" class="save-expiration_date hide-if-no-js button">' . __('OK') . '</a>&nbsp;';
		echo '<a href="#edit_expiration_date" class="cancel-expiration_date hide-if-no-js button-cancel">' . __('Cancel') . '</a>';
		echo '</p>';
	
	}

	/**
	 * addAdminCss
	 * 
	 * @param none
	 * @return none
	 */
	function addAdminCss() {
		echo '<link rel="stylesheet" href="' . $this -> plugin_dir . '/css/admin.css" type="text/css" media="all" />' . "\n";
	}
	
	/**
	 * addAdminScripts
	 * 
	 * @param none
	 * @return none
	 */
	function addAdminScripts() {
		wp_enqueue_script( 'postexp', $this -> plugin_dir . '/js/postexp.js', array( 'jquery-ui-core' ), false, true );
	}
}


/******************************************************************************
 * PostExp
 *****************************************************************************/
if ( class_exists( 'PostExp' ) ) {
	$postexp = & new PostExp();
	register_activation_hook( __FILE__, array( &$postexp, 'activate' ) );
	register_deactivation_hook( __FILE__, array( &$postexp, 'deactivate' ) );
	
	add_filter( 'cron_schedules', array( &$postexp, 'addCronMinutes' ) );
	add_filter( 'manage_posts_columns', array( &$postexp, 'addColumns' ) );
	add_filter( 'manage_pages_columns', array( &$postexp, 'addColumns' ) );
	
	if ( is_multisite() )
		add_action ('expirationdate_delete_' . $current_blog -> blog_id, array( &$postexp, 'deleteExpiredPosts' ) );
	else
		add_action ('expirationdate_delete', array( &$postexp, 'deleteExpiredPosts' ) );
	add_action( 'manage_posts_custom_column', array( &$postexp, 'showValue' ) );
	add_action( 'manage_pages_custom_column', array( &$postexp, 'showValue' ) );
	add_action( 'save_post', array( &$postexp, 'updatePostMeta' ) );
	add_action( 'post_submitbox_misc_actions', array( &$postexp, 'submitbox' ) );
	add_action( 'admin_print_styles', array( &$postexp, 'addAdminCss' ), 30 );
	add_action( 'admin_print_scripts', array( &$postexp, 'addAdminScripts' ) );
}
?>