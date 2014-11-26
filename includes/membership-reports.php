<?php
/**
 * Created by PhpStorm.
 * User: ken_kilgore1
 * Date: 10/24/2014
 * Time: 2:23 PM
 */

include_once 'class-membership-list-table.php';
global $memberReport, $wpdb;

if ( isset( $_GET['action'] ) ) {
	$member_action = esc_attr( $_GET['action'] );
	md_process_action( $member_action );

} else {
	if ( isset( $_POST['s'] ) ) {
		$membersearch = esc_attr( $_POST['s'] );
		md_member_search( $membersearch );
	} else {
		if ( isset( $_REQUEST['mdview'] ) ) {
			$memb_rpt = sanitize_text_field( $_REQUEST['mdview'] );
			switch ( $memb_rpt ) {
				case 'active':
					render_members_report( 'active' );
					break;
				case 'pending':

			}
		}
		$rpt_type = ( ! empty( $_REQUEST['mdview'] ) ? $_REQUEST['mdview'] : 'active' );
		render_members_report( $rpt_type );
	}
}

function md_process_action( $member_action ) {
	global $memberarchived, $memberedit, $wpdb;

	switch( $member_action ){
		case 'archive';
			$arc_id    = esc_attr( $_GET['member'] );
			$arc_table = 'ctxphc_members';
			$arc_data  = array(
				'status_id' => 2,
			);
			$arc_where = array(
				'ID' => $arc_id,
			);

			$agrs = array(
				'data'  => $arc_data,
				'table' => $arc_table,
				'where' => $arc_where,
			);

			$result = md_archive_member( $arc_table, $arc_data, $arc_where );
			if ( $result ) {
				$arc_name = $wpdb->get_row( "SELECT * FROM ctxphc_members WHERE ID = $arc_id", object );

				$memberarchived = $arc_name->first_name . ' ' . $arc_name->last_name;
			} else {
				$memberarchived = $result;
			}
			break;
		case 'edit';
			//create member edit action.
			$memberedit = 'someones name';
			break;
		case 'un-archive';
			//create member un-archive process.
			$memberunarchive = 'someones name';
			break;
		case 'delete';
			//create member delete action.
			$memberdelete = 'someones name';
			break;
		case 'activate';
			//create member activate action.
			$memberactivate = 'someones name';
			break;
		case 'contact';
			//create member contact action.
			$membercontact = 'someones name';
			break;
	}

	$rpt_type = ( ! empty( $_REQUEST['mdview'] ) ? $_REQUEST['mdview'] : 'active' );
	render_members_report( $rpt_type );
}

function md_member_search( $search ) {
	//figure out how to deal with the search parameters.
	$rpt_type = ( ! empty( $_REQUEST['mdview'] ) ? $_REQUEST['mdview'] : 'active' );
	render_members_report( $rpt_type );
}

function get_members_report_args( $rpt_type ) {
	switch ( $rpt_type ) {
		case 'active';
			$memb_args = array(
				'title'       => 'Active Members',
				'report_type' => 1,
				'orderby'     => 'first_name',
				'order_dir'   => 'asc',
			);
			break;
		case 'pending';
			$memb_args = array(
				'title'       => 'Pending Members',
				'report_type' => 0,
				'orderby'     => 'first_name',
				'order_dir'   => 'asc',
			);
			break;
		case 'archived';
			$memb_args = array(
				'title'       => 'Archived Members',
				'report_type' => 2,
				'orderby'     => 'first_name',
				'order_dir'   => 'asc',
			);
	}

	return $memb_args;
}

function render_members_report( $rpt ) {
	global $memberarchived, $memberedit, $membersearch, $wpdb;

	$args = get_members_report_args( $rpt );

	$rpt_title   = $args['title'];
	$rpt_type    = $args['report_type'];
	$rpt_orderby = $args['orderby'];
	$rpt_order   = $args['order_dir'];

	$results = $wpdb->get_results( "SELECT * FROM ctxphc_members WHERE status_id = $rpt_type ORDER BY $rpt_orderby $rpt_order", object );

	$args = array(
		'report_type' => $rpt_type,
		'data'        => $results,
	);

	$memberReport = new Membership_List_Table( $args );
	$memberReport->prepare_items();
	?>
	<div class="wrap">
	<h2>
		<?php
		echo esc_html( $rpt_title );
		if ( current_user_can( 'create_users' ) ) {
			?>
			<a href="?page=member-new.php" class="add-new-h2"><?php echo esc_html_x( 'Add New', 'member' ); ?></a>
		<?php
		}

		if ( $membersearch ) {
			printf( '<span class="subtitle">' . __( 'Search results for &#8220;%s&#8221;' ) . '</span>', esc_html( $membersearch ) );
		}

		if ( $memberarchived ) {
			printf( '<span class="subtitle">' . __( '&#8220;%s&#8221; has been archived.;' ) . '</span>', esc_html( $memberarchived ) );
		}

		if ( $memberedit ) {
			printf( '<span class="subtitle">' . __( '&#8220;%s&#8221; has been saved.;' ) . '</span>', esc_html( $memberedit ) );
		}
		?>
	</h2>

	<form method="post"><input type="hidden" name="page" value="member_report">
	<?php
	$memberReport->views();
	$memberReport->search_box( __( 'Search Members' ), 'member' );
	$memberReport->display();
	?>
	</form>
	</div>
	<?php
}
//todo:  create function to handle 'EDIT' and 'ARCHIVE' links on first_name