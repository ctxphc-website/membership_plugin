<?php

class dbMerge {

	/**
	 * **********************************
	 * Initialize variables
	 * **********************************
	 *
	 */


	private $address_table, $member_table, $old_family_table, $old_spouse_table, $old_members_table, $orderby, $order;

	function __construct( $args ) {
		$arguments = func_get_args();
		if ( ! empty( $arguments ) ) {
			foreach ( $arguments[0] as $key => $property ) {
				if ( property_exists( $this, $key ) ) {
					$this->{$key} = $property;
				}
			}
		}
	}

	/**
	 * **********************************
	 * Make sure all Membership types
	 * are corrected for new membership
	 * types.
	 * **********************************
	 *
	 * @return string
	 */
	private function fix_membership_type( $existing_memb_type ) {
		switch ( $existing_memb_type ) {
			case 'Single':
				$new_memb_type = 1;
				break;
			case 'Couple':
				$new_memb_type = 3;
				break;
			case 'Family':
				$new_memb_type = 4;
				break;
			case '1':
				$new_memb_type = 1;
				break;
			case '2':
				$new_memb_type = 3;
				break;
			case '3':
				$new_memb_type = 4;
				break;
		}

		return $new_memb_type;
	}

	/**
	 * **********************************
	 * Make sure all Relationship types
	 * are corrected for new relationship
	 * types.
	 * **********************************
	 *
	 * @return string
	 */
	private function fix_relationship_type( $rel ) {
		switch ( $rel ) {
			case 1:
				$rel_id = 2;
				break;
			case 2:
				$rel_id = 3;
				break;
			case 3:
				$rel_id = 4;
				break;
			case 4:
				$rel_id = 5;
				break;
		}

		return $rel_id;
	}

	/**
	 * **********************************
	 * Make sure all birthdays
	 * include leading zeros for single digit
	 * days and months.
	 * **********************************
	 *
	 * @return string
	 */
	private function fix_birthdays( $bday_month, $bday_day ) {
		$bday = sprintf( '%02s/%02s', $bday_month, $bday_day );

		return $bday;
	}

	/* private function set_member_data_array( $rec_type ) {

		$this->memb_data_array = array(
			'first_name'     => $this->first_name,
			'last_name'      => $this->last_name,
			'email'          => $this->email,
			'phone'          => $this->phone,
			'bday'           => $this->fix_birthdays( $this->bday_month, $this->bday_day ),
			'hatch_date'     => $this->hatch_date,
			'tag_date'       => $this->tag_date,
			'initiated_date' => null,
			'renewal_date'   => $this->get_renewal_date( $this->hatch_date ),
			'status_id'      => 1,
			'membership_id'  => $this->membership_id,
			'address_id'     => $this->address_id,
		);

		if ( $rec_type == 'memb' ) {
			$this->memb_data_array['occupation'] = $this->occupation;
		} else {
			$this->memb_data_array['memb_id'] = $this->prev_memb_id;
		}

		if ( $this->memb_wp_user_id ) {
			$this->memb_data_array['wp_user_id'] = $this->memb_wp_user_id;
		}

		return $this->memb_data_array;
	} */


	/**
	 * ******************************************
	 * * Prepare member data from previous membership tables
	 * * for inserting into the new membership table.
	 * ******************************************
	 */
	private function set_member_data_args( $memb_data ) {
		unset( $this->username );
		foreach ( $memb_data as $mkey => $mvalue ) {
			switch ( $mkey ) {
				case 'memb_fname':
				case 'fam_fname':
					$mkey = 'first_name';
					break;
				case 'memb_lname':
				case 'fam_lname':
					$mkey = 'last_name';
					break;
				case 'memb_email':
				case 'fam_email':
					$mkey = 'email';
					break;
				case 'memb_phone':
				case 'fam_phone':
					$mkey = 'phone';
					break;
				case 'memb_bday_month':
				case 'fam_bday_month':
					$mkey = 'bday_month';
					break;
				case 'memb_bday_day':
				case 'fam_bday_day':
					$mkey = 'bday_day';
					break;
				case 'memb_occup':
					$mkey = 'occupation';
					break;
				case 'memb_addr':
					$mkey = 'addr1';
					break;
				case 'memb_city':
					$mkey = 'city';
					break;
				case 'memb_state':
					$mkey = 'state';
					break;
				case 'memb_zip':
					$mkey = 'zip';
					break;
				case 'memb_type':
					$mvalue = $this->fix_membership_type( $mvalue );
					$mkey   = 'membership_id';
					break;
				case 'fam_rel':
				case 'relationship':
					$mvalue = $this->fix_relationship_type( $mvalue );
				case 'memb_user':
					$mkey = 'username';
					break;
				case 'memb_pass':
					$mkey = 'password';
					break;
				case 'memb_tag':
				case 'fam_tag':
				case 'tag':
					$mkey = 'tag_date';
					break;
				case 'memb_hatch_date':
				case 'fam_hatch_date':
					$mkey = 'hatch_date';
					break;
				case 'memb_initiated':
				case 'initiated':
					$mkey = 'initiated_date';
					break;
			}
			$this->memb_data_array[ $mkey ] = $mvalue;
			$this->$mkey                    = $mvalue;
		}
		if ( empty( $this->username ) ) {
			if ( $this->email ) {
				$this->username = substr( $this->first_name, 0, 3 ) . substr( $this->last_name, 0, 4 );
				$this->password = wp_generate_password( $length = 12, $include_standard_special_chars = false );
			}
		}

		return $this->member_data = $memb_data;
	} // end of primary member preparations

	/**
	 * ************************************
	 * If membership type is Couple or Family
	 * Retrieve record(s) to be inserted into
	 * new membership table.
	 * ************************************
	 *
	 * @param string $prev_memb_id
	 */
	function get_merge_member_data( $prev_table ) {
		global $wpdb;
		$this->merge_member_records = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM %s WHERE memb_id = %d", $prev_table, $this->prev_memb_id ) );

		if ( is_null( $this->merge_member_records ) ) {
			$wpdb->print_error();
			//wp_die( "Failed to insert locate any spouse record for merge member $this->sp_first_name $this->sp_last_name record id:$this-prev_memb_id>" );
		} else {
			return $this->merge_member_records;
		}
	}

	/**
	 * **********************************
	 * Check if member to be merged already
	 * exists in the new member table
	 * **********************************
	 *
	 * @return mixed boolean,integer
	 */
	function member_exists() {
		global $wpdb;
		$existing = $wpdb->get_row( "SELECT  * FROM ctxphc_members WHERE email = '{$this->email}'" );
		if ( $existing != null ) {
			return $existing->ID;
		} else {
			return false;
		}
	}

	/**
	 * **********************************
	 * Add merged member to wordpress users
	 * **********************************
	 *
	 * @param unknown $this
	 *
	 * @return Ambigous <NULL, number>
	 */
	function add_memb_to_wp_users( $email, $username, $password ) {
		$user_id = username_exists( $username );
		if ( ! $user_id and email_exists( $email ) == false ) {
			if ( $password != 'Password' || empty( $password ) ) {
				wp_create_user( $username, $password, $email );
			} else {
				$random_password = wp_generate_password( $length = 12, $include_standard_special_chars = false );
				$user_id        = wp_create_user( $username, $random_password, $email );
			}
		}

		return $user_id;
	}

	/**
	 * *********************************
	 * Determine Renewal Date based on Hatch Date
	 * if Hatch Date year is the same as current year
	 * and Hatch Date month is 9 or higher membership
	 * is good through to the next years renewal time.
	 * ********************************
	 */
	function get_renewal_date() {
		$curr_year    = date( 'Y' );
		$renewal_year = $curr_year + 1;
		$hatchdate    = date_create( $this->hatch_date );
		$hatch_month  = date_format( $hatchdate, 'm' );
		$hatch_year   = date_format( $hatchdate, "Y" );

		if ( $hatch_month > 8 && $hatch_year == $curr_year ) {
			$this->renewal_date = sprintf( '%02s/%02s/%04s', 01, 01, $renewal_year + 1 );
		} else {
			$this->renewal_date = sprintf( '%02s/%02s/%04s', 01, 01, $renewal_year );
		}

		return $this->renewal_date;
	}

	/**
	 * **********************************
	 * CURRENTLY NOT USED
	 * *********************************
	 * Compare old member data with
	 * existing member data.
	 * **********************************
	 *
	 * @param unknown $field1
	 * @param unknown $field2
	 *
	 * @return boolean
	 */
	function compare_fields( $field1, $field2 ) {
		if ( $field1 == $field2 ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * **********************************
	 * Insert Address Record
	 * **********************************
	 */
	function insert_address_rec() {
		$this->addr_data = array(
			'addr1' => $this->addr1,
			'city'  => $this->city,
			'state' => $this->state,
			'zip'   => $this->zip
		);

		global $wpdb;
		$insert_address_result = $wpdb->insert( $this->address_table, $this->addr_data );

		if ( empty( $insert_address_result ) ) {
			$wpdb->print_error();
			wp_die( "<li><h4>Insert Address Results:</h4>Failed to insert address record for merge member $this->first_name $this->last_name</li>" );
		} else {
			echo "<li><h4>Insert Address Results:</h4>Successfully inserted address record for $this->first_name $this->last_name!</li>";
			return $wpdb->insert_id;
		}
	}  //Address insert end

	/**
	 * **********************************
	 * Insert Member Record
	 * **********************************
	 *
	 * @param ARRAY $data
	 * @param VAR   $table
	 *
	 * @return boolean|number
	 */
	function insert_member_record( $merge_data, $merge_table ) {
		global $wpdb;
		$insert_member_result = $wpdb->insert( $merge_table, $merge_data );

		if ( empty( $insert_member_result ) ) {
			$wpdb->print_error();
			wp_die( "<li><h4>Insert Member Results:</h4>Failed to insert member data for merge member $this->first_name $this->last_name</li>" );
		} else {
			echo "<li><h4>Insert Member Results:</h4>Successfully inserted member data for $this->first_name $this->last_name!</li>";
			return $wpdb->insert_id;
		}
	}  //Insert Member End

	/**
	 * **********************************
	 * Process member records for insertion into
	 * production database.
	 * **********************************
	 */
	function prepare_members( $member_record ) {
		//start processing member record with no left over address information.
		unset( $this->address_id );

		// Set prev_memb_id using the primary members previous table record id.
		$this->prev_memb_id = $member_record['memb_id'];

		// Convert merge member data to match keys of new table.
		$merge_member_record = $this->set_member_data_args( $member_record );

		// Check if merge member already exists in new table.
		$existing_member_id = $this->member_exists( $this->email );

		if ( isset( $existing_member_id ) && ! empty( $existing_member_id ) ) {
			if ( isset( $this->wp_user_id ) && ! emtpy( $this->wp_user_id ) ) {
				echo "<li><h3>Existing Member</h3>$this->first_name $this->last_name already exists.  Need to look into comparing fields to make sure data is the same.</li>";
				unset( $this->existing_member_id );
			} else {
				// Add member to wordpress users and insert wordpress user id into existing member record.
				$memb_wp_user_id = $this->add_memb_to_wp_users( $this->email, $this->username, $this->password );
				if ( isset( $memb_wp_user_id ) && ! empty( $memb_wp_user_id ) ) {
					$update_result = $wpdb->update( $this->member_table, array( 'wp_user_id' => $memb_wp_user_id ), array( 'ID' => $existing_member_id ) );
				}
			}
		} else {
			/**
			 * Process new member
			 */
			// Create address record for primary member.
			$this->address_id = $this->insert_address_rec();

			// Set the renewal date for member
			$this->renewal_date = $this->get_renewal_date( $this->hatch_date );

			// Build the array of data for inserting into the membership table.
			//$memb_data = $this->set_member_data_array( 'memb' );

			// Insert the previous member data into the new membership table.
			$this->member_id = $this->insert_member_record( $this->memb_data_array, $this->member_table );
		}  // End Existing Member Processing

		// Get spouse and/or child records if Member is not Single
		if ( $this->membership_id >= 3 ) {
			//Locating previous Spouse/Partner record
			$sp_records = $this->get_merge_member_data( $this->old_spouse_table );
			foreach ( $sp_records as $sp_record ) {
				$sp_data = $this->set_member_data_args( $sp_record );

				// Check if previous spouse/partner already exists in new table.
				$existing_member_id = $this->member_exists( $this->email );
				if ( isset( $existing_member_id ) && ! empty( $existing_member_id ) ) {
					if ( isset( $this->wp_user_id ) && ! emtpy( $this->wp_user_id ) ) {
						echo "<li><h3>Existing Member</h3>$this->first_name $this->last_name already exists.  Need to look into comparing fields to make sure data is the same.</li>";
					} else {
						$memb_wp_user_id = $this->add_memb_to_wp_users( $this->email, $this->username, $this->password );
					}
				} else {
					$memb_wp_user_id    = $this->add_memb_to_wp_users( $this->email, $this->username, $this->password );
					$this->sp_member_id = $this->insert_member_record( $this->member_data_array, $this->member_table );
				}
			}

			// Begin Child/Other record processing
			if ( $this->membership_id == 4 ) {
				//Locating previous Child/Other records
				$fam_records = $this->get_merge_member_data( $this->old_family_table );
				foreach ( $fam_records as $fam_record ) {
					$fam_data = $this->set_member_data_args( $fam_record );
					// Check if previous child/other member already exists in new table.
					$existing_member_id = $this->member_exists( $this->email );

					if ( isset( $existing_member_id ) && ! empty( $existing_member_id ) ) {
						if ( isset( $this->wp_user_id ) && ! emtpy( $this->wp_user_id ) ) {
							echo "<li><h3>Existing Member</h3>$this->first_name $this->last_name already exists.  Need to look into comparing fields to make sure data is the same.</li>";
						} else {
							$memb_wp_user_id = $this->add_memb_to_wp_users( $this->email, $this->username, $this->password );
						}
					} else {
						$memb_wp_user_id     = $this->add_memb_to_wp_users( $this->email, $this->username, $this->password );
						$this->fam_member_id = $this->insert_member_record( $this->member_data_array, $this->member_table );
					}
				}
			}
		}
	}

	/**
	 * **********************************
	 * Display the results of the merged members data.
	 * **********************************
	 */
	function display() {
		echo "<li><h3>Completed</h3> processing record for: $this->first_name $this->last_name, $this->email</li>";
	}

	function __destruct() { }

}
