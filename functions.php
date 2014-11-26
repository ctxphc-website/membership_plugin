<?php
/**
 * Created by PhpStorm.
 * User: ken_kilgore1
 * Date: 10/24/2014
 * Time: 9:15 AM
 *
 * @param $rpt_type
 * @param $order_by
 * @param $order
 *
 * @return
 */
function get_ctxphc_members( $rpt_type, $order_by, $order ) {
    global $wpdb;
    $wpdb->show_errors();
    $members = $wpdb->get_results( "SELECT * FROM ctxphc_members WHERE status_id = $rpt_type ORDER BY $order_by $order" );
    //print_r( $members );
    //$wpdb->print_error();

    return $members;
}

function count_members( $type ) {
    global $wpdb;
    $rows = new stdClass;

    $query = 'SELECT mtype, COUNT(*) AS memb_count FROM
		(SELECT b.memb_type AS mtype FROM ctxphc_members a JOIN ctxphc_membership_types b WHERE a.membership_type = b.ID)
		AS mt WHERE mtype = %s GROUP BY mtype';
    $results = $wpdb->get_results( $wpdb->prepare( $query, $type ) );
    foreach ( $results as $row ) {
        foreach ( $row as $key => $value ) {
            $rows->$key = $value;
        }
    }

    return $rows;
}

/**
 * Gets next months name for use in birthday metabox.
 *
 * @param $date_format
 *
 * @return string
 */
function get_next_months_name( $date_format ) {
    $now = new datetime();
    $now->modify( 'first day of next month' );

    return $now->format( $date_format );
}

function md_archive_member( $table, $data, $where ) {
    global $wpdb;

    $wpdb->show_errors();
    $arc_result = $wpdb->update( $table, $data, $where );

    //$wpdb->print_error();
    return $arc_result;

}

/**
 * Resets global variables based on $_GET and $_POST
 *
 * This function resets global variables based on the names passed
 * in the $vars array to the value of $_POST[$var] or $_GET[$var] or ''
 * if neither is defined.
 *
 * @since 1.0.0
 *
 * @param array $vars An array of globals to reset.
 */
function md_reset_vars( $vars ) {
    foreach ( $vars as $var ) {
        if ( empty( $_POST[ $var ] ) ) {
            if ( empty( $_GET[ $var ] ) ) {
                $GLOBALS[ $var ] = '';
            } else {
                $GLOBALS[ $var ] = $_GET[ $var ];
            }
        } else {
            $GLOBALS[ $var ] = $_POST[ $var ];
        }
    }
}