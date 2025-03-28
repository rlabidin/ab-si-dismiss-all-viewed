<?php 
/*
Plugin Name:    ___Sliced Invoices Dismiss All Viewed Invoices/Quotes  Extension
Plugin URI:     http://theabidins.com/plugins/sliced-invoice/
Description:    Extension for Sliced Invoices Plugin - Provides a Dismiss-All function to clear all notices of Invoice & Quotes Viewed.
Version:        0.6.0
Tested:		    6.7.2
Sliced Invoice  3.9.3
Author:         Randy Abidin
Author URI:     https://theabidins.com
License:        GPLv2 or later
License URI:    http://www.gnu.org/licenses/gpl-2.0.html
*/
defined( 'ABSPATH' ) or die( 'Nope, not accessing this' );

class AbDismissAllViewedDefinitions {
   
    function __construct() {  
        add_action('admin_notices', array( $this, 'process_viewed_items'));
    }

    function check_if_sliced_admin_notices_exists() {
        global $wpdb;
        
        $viewed_prefixes = array( 'sliced_admin_notice_invoice_viewed_', 'sliced_admin_notice_quote_viewed_' );
        $viewed_count_array = array();
        $cache_group = 'my_plugin_admin_notices'; // Unique cache group
        $cache_duration = 3600; // Cache for 1 hour

        foreach ( $viewed_prefixes as $prefix ) {
            $like_pattern = $prefix . '%';
            $cache_key = 'viewed_count_' . md5($like_pattern);

            // Try to get the count from the cache
            $count = wp_cache_get( $cache_key, $cache_group );

            if ( false === $count ) {
                // Cache miss, perform the database query
                $prepared_sql = $wpdb->prepare(
                    "SELECT COUNT(*)
                    FROM {$wpdb->options}
                    WHERE option_name LIKE %s",
                    $like_pattern
                );
                $count = $wpdb->get_var( $prepared_sql );

                if ( null !== $count ) {
                    // Store the count in the cache
                    wp_cache_set( $cache_key, $count, $cache_group, $cache_duration );
                } else {
                    $count = 0; // Default to 0 if query fails
                }
            }

            $viewed_count_array[] = $count;
        }

        if ($viewed_count_array[0] > 0 || $viewed_count_array[1] > 0) {
            return $viewed_count_array; //return the count of each types
        } else {
            return false; // No matching option names found
        }
    }

    function process_viewed_items() {
        global $pagenow, $item_viewed_counts;

        // remove selected viewed messages
        if( isset( $_POST['nonce_viewed_items']) && isset( $_POST['remove_SI_viewed_items3']) ) {  
            $nonce_value = wp_unslash( $_POST['nonce_viewed_items'] );
            if( wp_verify_nonce( $nonce_value, 'viewed-nonce' ) ) {

                if( isset( $_POST['quote'])) {
                    $this->remove_notice_action('quote');
                }
                if( isset( $_POST['invoice'])) {
                    $this->remove_notice_action('invoice');
                }
            }
            else{
                die( esc_html('<p> &nbsp; </p>This link has expired. Try reloading page.</p>') );
            }
        }

        $item_viewed_counts = $this->check_if_sliced_admin_notices_exists(); // raNDY PUT THIS IN TEST BELOW

        // Are you sure you want to remove them?
        $action_underway = 0;

                
 
        if( isset( $_POST['nonce_viewed_items']) && isset( $_POST['remove_SI_viewed_items2']) ) {
            $nonce_value = wp_unslash( $_POST['nonce_viewed_items'] );
            
            if( wp_verify_nonce( $nonce_value, 'viewed-nonce' ) ) {

                $message = "You have not selected any viewed messages to remove!";
                $remove_count = 0;
                $invoice_checked = '';
                if( isset( $_POST['invoice'])) {
                    $invoice_checked = 'CHECKED';
                    $remove_count++;
                    $message = "This will remove ALL invoice-viewed messages";
                }

                $quote_checked = '';
                if( isset( $_POST['quote'])) {
                    $quote_checked = 'CHECKED';
                    if( $invoice_checked === 'CHECKED' ) {
                        $message .= " and remove ALL quote-viewed messages";
                    }
                    else {
                        $message = "This will remove ALL quote-viewed messages";
                    }
                }

                $item_viewed_counts = $this->check_if_sliced_admin_notices_exists(); // raNDY PUT THIS IN TEST BELOW

    
                echo "<div class='notice notice-warning is-dismissible'>
                        <form action='' method='POST'><span style='line-height:3.4em;'>Are you sure?</span>\n";

                        if( $item_viewed_counts[0]) echo "
                            <label><input type='checkbox' name='invoice' " . esc_attr($invoice_checked) . ">Invoices</label> &nbsp; \n";
                        if( $item_viewed_counts[1]) echo "    
                            <label><input type='checkbox' name='quote' " . esc_attr($quote_checked) . ">Quotes</label>\n";
                        echo "
                            <input type='hidden' name='page_name' value='" . esc_attr($pagenow) . "'>\n
                            <input type='hidden' name='nonce_viewed_items' value='" .  esc_attr( wp_create_nonce('viewed-nonce') ) . "'>\n
                            <input type='submit' name='remove_SI_viewed_items3' value='Yes'>\n" . 
                            esc_attr($message) . "\n
                        </form>\n
                    </div>\n";
                $action_underway = 1; 

            
            }
            else{
                die( esc_html('<p> &nbsp; </p>This link has expired. Try reloading page.</p>') );
            }
        }


        // select invoice and or quotes viewed notices to be removed.
        if( isset( $_POST['nonce_viewed_items']) && isset( $_POST['remove_SI_viewed_items']) ) {
            $nonce_value = wp_unslash( $_POST['nonce_viewed_items'] );          
            if( wp_verify_nonce( $nonce_value, 'viewed-nonce' ) ) {

            echo "<div class='notice notice-warning is-dismissible'>\n
                <form action='' method='POST'><span style='line-height:3.4em;'>Select notices to remove:</span> &nbsp\n";
                if( $item_viewed_counts[0]) echo "
                    <label><input type='checkbox' name='invoice'>Invoices</label> &nbsp; \n";
                if( $item_viewed_counts[1]) echo "
                    <label><input type='checkbox' name='quote'>Quotes</label>\n";
                    echo "
                    <input type='hidden' name='page_name' value='" . esc_attr($pagenow) . "'>
                    <input type='hidden' name='nonce_viewed_items' value='" .  esc_attr( wp_create_nonce('viewed-nonce') ) . "'>\n
                    <input type='submit' name='remove_SI_viewed_items2' value='remove'>
                </form>
            </div>\n";
            $action_underway = 1;   
            }
            else {
                die( esc_html('<p> &nbsp; </p>This link has expired. Try reloading page.</p>') );              
            }         
        }


        if( $action_underway === 0 ) {


            if ($item_viewed_counts){ 
                // drop to form to check for any viewed invoices or quotes
                echo "<div class='notice notice-warning is-dismissible'>
                #Invoices: " . esc_attr($item_viewed_counts[0]) . "   #Quotes: " . esc_attr($item_viewed_counts[1]) . "
                <form action='' method='POST'><span style='line-height:3.4em;'>TP141 Remove All Viewed Invoices/Quotes?</span>
                    <input type='hidden' name='page_name' value='" . esc_attr($pagenow) . "'>
                    <input type='submit' name='remove_SI_viewed_items' value='Yes'>
                     <input type='hidden' name='nonce_viewed_items' value='" .  esc_attr( wp_create_nonce('viewed-nonce') ) . "'>\n
                </form>
                </div>\n";
            }
        }
    }

    function remove_notice_action( $post_type) {
        global $pagenow, $wpdb;

            if( get_option('sliced_admin_notices')) {
                $notice_array = get_option('sliced_admin_notices');

                $y = 1;
                foreach ($notice_array as $notice_value) {

                    if (strpos($notice_value, $post_type . '_viewed_') !== false) {
                        $item_id = str_replace( $post_type . '_viewed_', '', $notice_value );

                        if( get_option("sliced_admin_notice_" . $post_type . "_viewed_$item_id")) {
                            $hide_notice = sanitize_text_field( $post_type . "_viewed_" . $item_id );
                            Sliced_Admin_Notices::remove_notice( $hide_notice );

                            delete_option( "sliced_admin_notice_" . $post_type ."_viewed_" . $item_id );

                        }
                        else {
                            // saved that notice and put is back after all are collected.
                            // echo "<h2 align='center'> Keep *** " . $notice_value . "</h2>\n";
                            // $keep_notices_array[$y] = $notice_value;
                            // $y++;                              
                        }
                    }
                    else {
                        // echo "<h2 align='center'>TP77 Really Keep *** " . $notice_value . "</h2>\n";
                        // $keep_notices_array[$y] = $notice_value;
                        // $y++;                              
                    }
                }
                $action_underway = 0;
                
                update_option( 'sliced_admin_notices', Sliced_Admin_Notices::get_notices() );
            }
    }
}
$AbDismissAllViewedDefinitions = new AbDismissAllViewedDefinitions();

/*
sliced_admin_notices
a:2:{i:0;s:19:"invoice_viewed_2350";i:1;s:17:"quote_viewed_1947";}

sliced_admin_notice_invoice_viewed_2350
a:3:{s:5:"class";s:14:"notice-success";s:7:"content";s:142:"<p>Invoice <a class="sliced-number" href="http://localhost/abidinsapps/wp-admin/post.php?post=1403&action=edit">INV-AA-2350</a> was viewed</p>";s:11:"dismissable";b:1;}

sliced_admin_notice_quote_viewed_1947
a:3:{s:5:"class";s:14:"notice-success";s:7:"content";s:140:"<p>Quote <a class="sliced-number" href="http://localhost/abidinsapps/wp-admin/post.php?post=1403&action=edit">QUO-AA-1947</a> was viewed</p>";s:11:"dismissable";b:1;}


http://localhost/abidinsapps/wp-admin/?sliced-hide-notice=quote_viewed_1947&_sliced_notice_nonce=6233168f0c
*/