<?php
/*
 * Plugin Name: CTXPHC Membership Plugin
 * Plugin URI: http://kaosoft.com/membership-dashboard-plugin
 * Description: Replaces some default WP Dashboard meta boxes
 * with Membership related meta boxes and data.
 * Version: 1.0
 * Author: Kapt Kaos
 * Author URI: http://kaptkaos.com
 * License: GPL2
 */
require_once( ABSPATH . 'wp-content/plugins/membership-dashboard/functions.php' );

// CUSTOM ADMIN DASHBOARD HEADER LOGO
function custom_admin_logo() {
	// echo '<style type="text/css">#header-logo { background-image: url(' . get_bloginfo( 'template_directory' ) . '/images/logo_admin_dashboard.png) !important; }</style>';
}

add_action( 'admin_head', 'custom_admin_logo' );

// Admin footer modification
function remove_footer_admin() {
	echo '<span id="footer-thankyou">Developed by <a href="http://www.kaptkaos.com" target="_blank">Kapt Kaos</a></span>';
}

add_filter( 'admin_footer_text', 'remove_footer_admin' );

/**
 * Begin by removing unneeded dashboard widgets
 */
function ctxphc_remove_dashboard_widgets() {
	global $current_member;
	$user = wp_get_current_user();
	if ( ! $user->has_cap( 'manage_options' ) ) {
		// remove_meta_box( 'dashboard_right_now', 'dashboard', 'core' );
		remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'core' );
		remove_meta_box( 'dashboard_incoming_links', 'dashboard', 'core' );
		remove_meta_box( 'dashboard_plugins', 'dashboard', 'core' );
		remove_meta_box( 'dashboard_quick_press', 'dashboard', 'core' );
		// remove_meta_box( 'dashboard_recent_drafts', 'dashboard', 'core' );
		remove_meta_box( 'dashboard_primary', 'dashboard', 'core' );
		remove_meta_box( 'dashboard_secondary', 'dashboard', 'core' );
	}
}

add_action( 'wp_dashboard_setup', 'ctxphc_remove_dashboard_widgets' );
// Move the 'Right Now' dashboard widget to the right hand side
function ctxphc_move_dashboard_widget() {
	$user = wp_get_current_user();
	if ( ! $user->has_cap( 'manage_options' ) ) {
		global $wp_meta_boxes;
		$widget = $wp_meta_boxes['dashboard']['normal']['core']['dashboard_right_now'];
		unset( $wp_meta_boxes['dashboard']['normal']['core']['dashboard_right_now'] );
		$wp_meta_boxes['dashboard']['side']['core']['dashboard_right_now'] = $widget;
	}
}

add_action( 'wp_dashboard_setup', 'ctxphc_move_dashboard_widget' );

// add new dashboard widgets
function ctxphc_add_dashboard_widgets() {
	$date_format    = 'F';
	$safe_next_month = sanitize_text_field( get_next_months_name( $date_format ) );
	wp_add_dashboard_widget( 'ctxphc_dashboard_membership_status', 'Membership Status', 'ctxphc_add_membership_status_widget' );
	wp_add_dashboard_widget( 'ctxphc_dashboard_next_months_birthdays', 'Birthdays in ' . $safe_next_month, 'ctxphc_add_birthday_widget' );
	// wp_add_dashboard_widget( 'ctxphc_dashboard_membership_reports', 'Membership Reporting', 'ctxphc_add_membership_reports_widget' );
	wp_add_dashboard_widget( 'ctxphc_dashboard_newest_members', 'Newest Members', 'ctxphc_add_newest_members_widget' );
} // end Add Dashboard Widgets
add_action( 'wp_dashboard_setup', 'ctxphc_add_dashboard_widgets' );

function dashboard_report_lists() {
	$report_links = "<a href='admin.php?page=gf_update'>Active Members</a>";
	?>
	<div class='report_list' id='report_dashboard_links'><?php echo $report_links ?>
		<a href="javascript:void(0);" onclick="GFDismissUpgrade();"
		   style='float: right;'><?php _( "Dismiss", "gravityforms" ) ?></a>
	</div>
<?php
}

function ctxphc_add_membership_status_widget() {
	$membership_types   = array(
		'ID',
		'IC',
		'CO',
		'HH'
	);
	$memb_text_singular = array(
		'ID' => 'Individual',
		'IC' => 'Individual and Child',
		'CO' => 'Couple',
		'HH' => 'Household'
	);
	$memb_text_plural   = array(
		'ID' => 'Individuals',
		'IC' => 'Individuals with Children',
		'CO' => 'Couples',
		'HH' => 'Households'
	);
	$total_members      = 0;
	?>
	<div class="main">
		<ul>
			<?php

			foreach ( $membership_types as $memb_type ) {
				$num_members   = count_members( $memb_type );

				if ( isset( $num_members->memb_count )){
					$mcount        = $num_members->memb_count;
				} else {
					$mcount = 0;
				}

				if ( $mcount > 1 ){
					$text          = sprintf( '%s ' . sanitize_text_field( $memb_text_plural[ $memb_type ] ), number_format_i18n( $mcount ) );
				} else {
					$text          = sprintf( '%s ' . sanitize_text_field( $memb_text_singular[ $memb_type ] ), number_format_i18n( $mcount ) );
				}

				$text          = sprintf( $text, number_format_i18n( $mcount ) );
				// printf( '<li class="%1$s-count"><a href="list-members.php?membership_type=%1$s">%2$s</a></li>', $memb_type, $text );
				printf( '<li class="%1$s-count" ><span>%2$s </span ></li >', $memb_type, $text );

				$total_members = $total_members + $mcount;
			}

			$text = '--------------------';
			printf( '<li class="%1$s"><span>%2$s</span></li>', 'total-line', $text );
			$total_members = number_format_i18n( $total_members );
			$text          = "$total_members  Total Members<br><br>";
			// printf( '<li class="%1$s-count"><a href="list-members.php?membership_type=%1$s">%2$s</a></li>', 'total-member', $text );
			printf( '<li class="%1$s-count"><span>%2$s </span></li>', 'total-members', $text );
			?>
		</ul>
	</div>
<?php
} // end Membership Status widget

function ctxphc_add_membership_reports_widget() {
	?>
	<div class="main">
		<ul>
			<li><a
					href="<?php bloginfo( 'wpurl' . 'wp-admin/index.php?page=active_members' ) ?>">Active
					Members</a></li>
			<li><a
					href="<?php plugins_url( 'includes/pending-members.php', __FILE__ ) ?>">Pending
					Members</a></li>
			<li><a
					href="<?php plugins_url( 'includes/archived-members.php', __FILE__ ) ?>">Archived
					Members</a></li>
			<li><a
					href="<?php plugins_url( 'includes/renewing-members.php', __FILE__ ) ?>">Renewing
					Members</a></li>
			<li><a
					href="<?php plugins_url( 'includes/parrot-points.php', __FILE__ ) ?>">Parrot
					Point Status</a></li>
		</ul>
	</div>
<?php
} // end Membership Report Link Widget


function ctxphc_add_newest_members_widget() {
	global $wpdb;
	$rpt_type        = 1;
	$order_by        = 'ID';
	$order           = 'desc';
	$current_members = get_ctxphc_members( $rpt_type, $order_by, $order );
	// echo '<br> This is where I will include the 10 newest members basic info<br>';
	?>
	<div class="main">
		<ul>
			<?php
			$ac = 0;
			while ( $ac <= 9 ) {
				$text = $current_members[ $ac ]->first_name;
				$text .= ' ' . $current_members[ $ac ]->last_name;
				$text .= ' ' . $current_members[ $ac ]->email;
				// $text = sprintf( $text, $current_members->email );
				// printf( '<li class="%1"><span>%2$s</span></li>', 'newest-members', $text );
				echo '<li class="newest-member"><span>' . $text . '</span></li>';
				$ac ++;
			}
			?>
		</ul>
	</div>
<?php
} // end add Newest Members widget

function ctxphc_add_birthday_widget() {
	$date_format = 'm';
	$next_month  = get_next_months_name( $date_format );
	$rpt_type    = 1;
	$order_by    = 'bday';
	$order       = 'asc';
	$members     = get_ctxphc_members( $rpt_type, $order_by, $order );
	?>
	<div class="main">
		<ul>
			<?php
			foreach ( $members as $member ) {
				$a_date = explode( '/', $member->bday );
				if ( $a_date[0] === $next_month ) {
					$text = sanitize_text_field( $member->first_name ) . ' ' . sanitize_text_field( $member->last_name );
					$text = $text . ' ' . sanitize_text_field( $member->bday );
					// printf( '<li class="%1$s"><a href="list-members.php?membership_type=%1$s">%2$s</a></li>', 'total-member', $text );
					echo '<li class="next-months-bdays"><span>' . $text . '</span></li>';
				}
			}
			?>
		</ul>
	</div>
<?php
} // end Next Months Birthdays widget

function add_membership_report_menu() {
	// add_dashboard_page( $page_title, $menu_title, $capability, $menu_slug, $function );
	$membership_reports_page = add_dashboard_page( 'membership_reports', 'Membership Reports', 'manage_options', 'membership_reports', 'membership_reports' );
	add_dashboard_page( 'migrate_members', 'Migrate Members', 'manage_options', 'migrate_members', 'migrate_members' );
	add_action( 'load-' . $membership_reports_page, 'ctxphc_add_list_members_options' );
}

add_action( 'admin_menu', 'add_membership_report_menu' );

function membership_reports() {
	include_once( 'includes/membership-reports.php' );
}

function migrate_members() {
	include_once( 'includes/db-merge.php' );
}

function ctxphc_add_list_members_options() {
	$option = 'per_page';
	$args   = array(
		'label'   => 'Members',
		'default' => 20,
		'option'  => 'members_per_page'
	);
	add_screen_option( $option, $args );
}

add_filter( 'set-screen-option', 'member_table_set_option', 10, 3 );

/**
 *
 * @param
 *            $value
 *
 * @return mixed
 */
function member_table_set_option( $value ) {
	return $value;
}