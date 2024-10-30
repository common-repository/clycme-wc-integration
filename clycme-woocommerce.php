<?php
/**
 * Plugin Name: clycme - WC Integration
 * Plugin URI: https://developers.vercel.app/
 * Description: clycme Partners integration for WooCommerce.
 * Version: 1.0.0
 * Author: Joshua Ortiz
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package clycme-wc integration
 */

defined( 'ABSPATH' ) || exit;

if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

    add_action( 'woocommerce_order_status_on-hold', 'clycme_add_order_to_clyc', 3, 1);

    function clycme_add_order_to_clyc( $order_id ) {
        $order = wc_get_order( $order_id );
        $data = $order->get_data();
        $products = $order->get_items();

        $total = (int)$order->get_total();
        $sub_total = $order->get_total() - $order->get_total_shipping();
        $date_object = $order->get_date_completed();
        if (is_null($date_object)) {
            $date = date("Y-m-d H:i:s");
        } else {
            $date = $date_object->__toString();
        }
        
        $product_list = array();

        foreach($products as $order_item) {
            $product_id = $order_item->get_product_id();
            $product_detail = wc_get_product( $product_id );
            $price = $product_detail->get_price();
            $quantity = $order_item->get_quantity();
            $product_to_push = array('external_product_id' => $product_id, 'price' => $price, 'quantity' => $quantity);
            array_push($product_list, $product_to_push);
        }

        $meta = array('order' => $data, 'products' => $products);

        $url = 'https://api.clyc.me/v1/sales';

        $json_body = json_encode(array( 'external_order_id' => $order_id, 'sub_total' => $sub_total, 'total' => $total, 'date' => $date, 'details' => $product_list, 'meta' => $meta ));

        wp_remote_post( $url, array(
            'method' => 'POST',
            'timeout' => 85,
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking' => true,
            'headers' => array('Content-Type' => 'application/json; charset=utf-8', 'x-api-key' => get_option('clycme_api_key')),
            'body' => $json_body,
            'data_format' => 'body',
        ));
    }

    function clycme_wc_register_settings() {
        add_option( 'clycme_api_key', 'APIKEY');
        register_setting( 'clycme_options_group', 'clycme_api_key', 'clycme_callback' );
    }

    add_action( 'admin_init', 'clycme_wc_register_settings' );

    function clycme_wc_register_options_page() {
        add_options_page('clycme Settings', 'clyc.me Settings', 'manage_options', 'clycme-wc', 'clycme_options_page');
    }

    add_action('admin_menu', 'clycme_wc_register_options_page');

    function clycme_options_page() {
        ?>
            <div>
            <?php screen_icon(); ?>
            <h1 style="margin-bottom: 2rem;">clyc.me - WooCommerce Integration Settings</h1>
            <form method="post" action="options.php">
            <?php settings_fields( 'clycme_options_group' ); ?>
            <h3>Fill this form with the key provided by clycme.</h3>
            <h4>Api Key</h4>
            <input type="text" id="clycme_api_key" name="clycme_api_key" value="<?php echo get_option('clycme_api_key'); ?>" style="width: 95%;max-width: 25rem;" />

            <?php  submit_button(); ?>
            </form>
            </div>
        <?php
    }
}
