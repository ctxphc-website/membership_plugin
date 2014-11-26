<?php

/**
 * Created by PhpStorm.
 * User: ken_kilgore1
 * Date: 10/27/2014R
 * Time: 7:21 PM
 */

/**
 * ************************************************
 * Get member records from old table
 * ************************************************
 */
function get_merge_memb_records()
{
    global $wpdb;
    return $wpdb->get_results("SELECT * FROM  ctxphc_ctxphc_members");
}

/**
 * *****************************************
 * Prepare database table variables
 * *****************************************
 */
function set_db_args()
{
    $dbargs = array(
        'old_members_table' => 'ctxphc_ctxphc_members',
        'old_spouse_table' => 'ctxphc_ctxphc_memb_spouses',
        'old_family_table' => 'ctxphc_ctxphc_family_members',
        'member_table' => 'ctxphc_members',
        'address_table' => 'ctxphc_member_addresses',
        'orderby' => 'memb_id',
        'order' => 'ASC'
    );

    return $dbargs;
} // end of preparing database variables.

/**
 *
 */
function merge_members()
{
    /**
     * ************************************
     * link to class dbMerge if not already loaded
     * ************************************
     */
    if (! class_exists('dbMerge')) {
        require_once (dirname(__FILE__) . '/class-dbmerge.php');
    }

    // Set database arguments for use in dbMerge class
    $dbargs = set_db_args();

    /**
    * Create new instance of dbMerge class.
    * Pass database arguments.
     */
    $mergeMembers = new dbMerge($dbargs);

    // get member records from previous tables
    // for use in the dbMerge class
    $merge_member_records = get_merge_memb_records();

    // Convert merge member records object to a single members
    // record object. Pass this object to the dbMerge class
    foreach ($merge_member_records as $merge_member_record) {
        $mergeMembers->prepare_members($merge_member_record);
        $mergeMembers->display();
    }

    //clear and close dbMerge class
    $mergeMembers->__destruct();
}
?>
<div class="wrap">
	<h2>
	<?php
    echo 'Database Merge';
    ?>
    	</h2>

	<form method="post">
		<input type="hidden" name="page" value="db_merge">
		<div>
			<ul>
			<?php
            merge_members();
            ?>
			</ul>
		</div>
	</form>
</div>
