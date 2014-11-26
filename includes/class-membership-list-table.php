<?php
/**
 * Created by PhpStorm.
 * User: Ken Kilgore
 * Date: 10/22/2014
 * Time: 7:16 AM
 */
/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __FILE__ ) . '/' );
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * @property array found_data
 * @property array _column_headers
 */
class Membership_List_Table extends WP_List_Table {
	public $report_type;
	public $data;
	private $args;

	public  function __construct( $args ) {
		$arguments = func_get_args();
		if ( ! empty( $arguments ) ) {
			foreach ( $arguments[0] as $key => $property ) {
				if ( property_exists( $this, $key ) ) {
					$this->{$key} = $property;
				}
			}
		}

		parent::__construct( array(
			//singular name of the listed records
			'singular' => __( 'member', 'members_list_table' ),
			//plural name of the listed records
			'plural'   => __( 'members', 'members_list_table' ),
			//does this table support ajax?
			'ajax'     => false,
		) );
		add_action( 'admin_head', array(
			&$this,
			'admin_header',
		) );
		/****** Used for Testing Only
		 * echo '<div>showing $this->data:<br><pre>';
		 * print_r( $this->data );
		 * echo '</pre></div>';
		 * echo '<div>showing $data:<br><pre>';
		 * print_r( $data );
		 * echo '</pre></div>';
		 ******************************/
	}

	function admin_header() {
		$page = ( isset( $_GET['page'] ) ) ? esc_attr( $_GET['page'] ) : false;

		if ( 'active_members_listing' != $page ) {
			return;
		}
		echo '<style type="text/css">';
		echo '.wp-list-table .column-cb { width: 5%; }';
		echo '.wp-list-table .column-first_name { width: 15%; }';
		echo '.wp-list-table .column-last_name { width: 15%; }';
		echo '.wp-list-table .column-phone { width: 10%;}';
		echo '.wp-list-table .column-email { width: 10%;}';
		echo '.wp-list-table .column-bday { width: 10%;}';
		echo '.wp-list-table .column-hatch_date { width: 15% }';
		echo '</style>';
	}

	function no_items() {
		_e( 'No Parrot Head Members Found, Dude!!!' );
	}

	/**
	 * @param $item
	 *
	 * @return string
	 */
	function column_first_name( $item ) {
		switch( $this->report_type ){
			case 1;
				$actions = array(
					'edit'    => sprintf( '<a href="?page=%s&action=%s&member=%s">Edit</a>', $_REQUEST['page'], 'edit', $item->ID ),
					'archive' => sprintf( '<a href="?page=%s&action=%s&member=%s">Archive</a>', $_REQUEST['page'], 'archive', $item->ID ),
				);
				break;
			case 0;
				$actions = array(
					'activate'    => sprintf( '<a href="?page=%s&action=%s&member=%s">Activate</a>', $_REQUEST['page'], 'activate', $item->ID ),
					'contact' => sprintf( '<a href="?page=%s&action=%s&member=%s">Contact</a>', $_REQUEST['page'], 'contact', $item->ID ),
					'delete' => sprintf( '<a href="?page=%s&action=%s&member=%s">Delete</a>', $_REQUEST['page'], 'delete', $item->ID ),
				);
				break;
			case 2;
				$actions = array(
					'contact'    => sprintf( '<a href="?page=%s&action=%s&member=%s">Contact</a>', $_REQUEST['page'], 'contact', $item->ID ),
					'un-archive' => sprintf( '<a href="?page=%s&action=%s&member=%s">Un-Archive</a>', $_REQUEST['page'], 'un-archive', $item->ID ),
				);
				break;
		}

		return sprintf( '%1$s %2$s', $item->first_name, $this->row_actions( $actions ) );
	}

	/**
	 * @param $item
	 *
	 * @return string
	 */
	function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="member[]" value="%s" />', $item->ID );
	}

	function column_archive( $item ){
		if ( $item->status_id == 2 ){
			return sprintf( '<input type="checkbox" name="member[]" value="%b" %s />', $item->status_id , 'checked');

		} else {
			return sprintf( '<input type="checkbox" name="member[]" value="%b" />', $item->status_id );
		}
	}

	/**
	 * @return array
	 */
	function get_bulk_actions() {
		switch ( $this->report_type ) {
			case 0: //PENDING Members
				$actions = array(
					'active'  => 'Activate',
					'delete'  => 'Delete',
					'contact' => 'Contact',
				);
				break;
			case 1:  //Active Members
				$actions = array(
					'archive' => 'Archive',
					'edit'    => 'Edit',
				);
				break;
			case 2:  //Archived Members
				$actions = array(
					'edit'       => 'Edit',
					'un-archive' => 'Un-archive',
				);
				break;
		}

		return $actions;
	}


	function get_views() {
		//Retrieve $mdview for use in query to get items.
		//$mdview = ( isset( $_REQUEST['mdview'] ) ? $_REQUEST['mdview'] : 'active' );
		/**
		 * $active_url = admin_url() . 'index.php?page=active_members';
		 * $pending_url = admin_url() . 'index.php?page=pending_members';
		 * $archived_url = admin_url() . 'index.php?page=archived_members';
		 * $new_members_url = admin_url() . 'index.php?page=add_members';
		 */

		$views   = array();
		$current = ( ! empty( $_REQUEST['mdview'] ) ? $_REQUEST['mdview'] : 'active' );

		//Active Members link (default)
		$url_array       = array( 'page' => 'membership_reports', 'mdview' => 'active' );
		$active_url      = add_query_arg( $url_array, admin_url() );
		$class           = ( $current == 'active' ? ' class="current"' : '' );
		$views['active'] = "<a href='{$active_url}' {$class} >Active Members</a>";

		//Pending Members link
		$url_array        = array( 'page' => 'membership_reports', 'mdview' => 'pending' );
		$pending_url      = add_query_arg( $url_array, admin_url() );
		$class            = ( $current == 'pending' ? ' class="current"' : '' );
		$views['pending'] = "<a href='{$pending_url}' {$class} >Pending Members</a>";

		//Archived Members link
		$url_array         = array( 'page' => 'membership_reports', 'mdview' => 'archived' );
		$archived_url      = add_query_arg( $url_array, admin_url() );
		$class             = ( $current == 'archived' ? ' class="current"' : '' );
		$views['archived'] = "<a href='{$archived_url}' {$class} >Archived Members</a>";

		return $views;
	}

	function months_dropdown( $table, $birthday ) {
	    global $wpdb, $wp_locale;

	    $months = $wpdb->get_results( $wpdb->prepare( "
	        SELECT DISTINCT YEAR( bday ) AS year, MONTH( bday ) AS month
	        FROM %s
	        ORDER BY %s DESC
	        ", $table, 'birthdate' ) );

	    /**
	    * Filter the 'Months' drop-down results.
	    *
	    * @since 1.1.0
	    *
	    * @param object $months    The months drop-down query results.
	    * @param string $post_type The post type.
	    */
	    $months = apply_filters( 'months_dropdown_results', $months, $post_type );

	    $month_count = count( $months );

	    if ( ! $month_count || ( 1 == $month_count && 0 == $months[0]->month ) ) {
	       return;
        }

	$m = isset( $_GET['m'] ) ? (int)$_GET['m'] : 0;
	    ?>
	        <label for="filter-by-date" class="screen-reader-text"><?php _e( 'Filter by date' ); ?></label>
	        <select name="m" id="filter-by-date">
	            <option<?php selected( $m, 0 ); ?> value="0"><?php _e( 'All dates' ); ?></option>
	            <?php
	            foreach ( $months as $arc_row ) {
	                if ( 0 == $arc_row->year ) {
	                    continue;
	                }

	                $month = zeroise( $arc_row->month, 2 );
	                $year = $arc_row->year;

	                printf( "<option %s value='%s'>%s</option>\n", selected( $m, $year . $month, false ), esc_attr( $arc_row->year . $month ), /* translators: 1: month name, 2: 4-digit year */
	                    sprintf( __( '%1$s %2$d' ), $wp_locale->get_month( $month ), $year ) );
	            }
	            ?>
	        </select>
	    <?php
	    }

	/**
	 *
	 */
	public function prepare_items() {
		global $membersearch;

		$membersearch = isset( $_REQUEST['s'] ) ? wp_unslash( trim( $_REQUEST['s'] ) ) : '';

		$columns  = $this->get_columns();
		$hidden   = $this->get_hidden_columns();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array(
			$columns,
			$hidden,
			$sortable
		);

		$this->items = $this->data;
		usort( $this->items, array(
			&$this,
			'usort_reorder'
		) );

		$members_per_page = $this->get_items_per_page( 'members_per_page', 20 );
		$paged            = $this->get_pagenum();
		$total_items      = count( $this->items );

		if ( is_array( $this->items ) ) {
			$this->found_data = array_slice( $this->items, ( ( $paged - 1 ) * $members_per_page ), $members_per_page );
			$this->set_pagination_args( array(
				'total_items' => $total_items,
				'per_page'    => $members_per_page,
			) );
			$this->items = $this->found_data;
		}
	}

	/**
	 * Override the parent columns method. Defines the columns to use in your listing table
	 * @type ARRAY columns
	 * @return Array
	 */
	function get_columns() {
		switch ( $this->report_type ) {
			case '0':
				$columns = array(
					'cb'         => '<input type="checkbox" />',
					'first_name' => 'First Name',
					'last_name'  => 'Last Name',
					'bday'       => 'Birthday',
					'email'      => 'Email',
					'phone'      => 'Phone',
				);
				break;
			case '2':
				$columns = array(
					'cb'         => '<input type="checkbox" />',
					'first_name' => 'First Name',
					'last_name'  => 'Last Name',
					'bday'       => 'Birthday',
					'email'      => 'Email',
					'phone'      => 'Phone',
				);
				break;
			default;
				$columns = array(
					'cb'         => '<input type="checkbox" />',
					'first_name' => 'First Name',
					'last_name'  => 'Last Name',
					'bday'       => 'Birthday',
					'email'      => 'Email',
					'phone'      => 'Phone',
					'hatch_date' => 'Hatch Date',
				);
				break;
		}

		return $columns;
	}

	/**
	 * Define which columns are hidden
	 *
	 *
	 * @return Array
	 */
	function get_hidden_columns() {
		switch ( $this->report_type ) {
			case '0':
				return array(
					'address',
					'bday',
					'hatch_date',
				);
				break;
			case '2':
				return array(
					'address',
					'bday',
				);
				break;
			default;
				return array(
					'address',
				);
				break;
		}
	}

	/**
	 * Define the sortable columns
	 *
	 *
	 * @return Array
	 */
	function get_sortable_columns() {
		switch ( $this->report_type ) {
			case '0':
				$sortable_columns = array(
					'first_name' => array(
						'first_name',
						false,
					),
					'last_name'  => array(
						'last_name',
						false,
					),
				);
				break;
			case '2':
				$sortable_columns = array(
					'first_name' => array(
						'first_name',
						false,
					),
					'last_name'  => array(
						'last_name',
						false,
					),
					'hatch_date' => array(
						'Hatch Date',
						false,
					),
				);
				break;
			default;
				$sortable_columns = array(
					'first_name' => array(
						'first_name',
						false,
					),
					'last_name'  => array(
						'last_name',
						false,
					),
					'bday'       => array(
						'bday',
						false,
					),
					'hatch_date' => array(
						'Hatch Date',
						false,
					),
				);
				Break;
		} //end Switch
		return $sortable_columns;
	}

	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'cb':
			case 'first_name':
			case 'last_name':
			case 'email':
			case 'phone':
			case 'bday':
			case 'address':
			case 'hatch_date':
				return $item->$column_name;
			default:
				return print_r( $item, true );
		}
	}

	function usort_reorder( $a, $b ) {
		// If no sort, default to first_name
		$orderby = ( ! empty( $_GET['orderby'] ) ) ? $_GET['orderby'] : 'first_name';
		// If no order, default to asc
		$order = ( ! empty( $_GET['order'] ) ) ? $_GET['order'] : 'asc';
		// Determine sort order
		$result = strcmp( $a->$orderby, $b->$orderby );

		// Send final sort direction to usort
		return ( $order === 'asc' ) ? $result : - $result;
	}
} //class

