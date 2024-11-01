<?php

/**
 * Fired during plugin activation
 *
 * @link       https://tapchat.mer/
 * @since      1.0.0
 *
 * @package    TapChat
 * @subpackage TapChat/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    TapChat
 * @subpackage TapChat/includes
 * @author     Phillip Dane <info@tapchat.me>
 */
class TapChat_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
        
        global $wpdb;
		$dbCustomerRequestTable = $wpdb->prefix.'tapchat_customer_request';
		$dbCustomerTable = $wpdb->prefix.'tapchat_customer';
		
		$charset_collate = $wpdb->get_charset_collate();

		$dbCustomer = "CREATE TABLE IF NOT EXISTS $dbCustomerRequestTable (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			customer_id varchar(20) NOT NULL,
			operator_id mediumint(9) NOT NULL,
			request_status varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
			created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id)
		  ) $charset_collate;";

		$dbGuest = "CREATE TABLE IF NOT EXISTS $dbCustomerTable (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			full_name varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
			email varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
			phone varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
			wp_id mediumint(9) COLLATE utf8mb4_unicode_ci NULL,
			created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $dbCustomer );
		dbDelta( $dbGuest );
        
        //flush_rewrite_rules();

	}

}
