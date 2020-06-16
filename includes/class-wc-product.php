<?php
/**
 * WooCommerce Product Conversion Tracking
 *
 * @version 1.0.0
 * @package SM_WCPCT
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'SM_WCPCT_Product' ) ) :

	/**
	 * SM_WCPCT_Product Class
	 */
	class SM_WCPCT_Product {

        const TRACKING_CODE_META_KEY = 'sm_wcpct_tracking_code';
        const TRACKING_CODE_CONV_META_KEY = 'sm_wcpct_tracking_code_conv';
        const CONV_PRODUCTS_COOKIE_KEY = 'sm_wcpct_cp';

		/**
		 * Single instance of the class
		 *
		 * @since 1.0.0
		 */
		protected static $_instance;

		/**
		 * Constructor.
		 */
        public function __construct() {}

        /**
         * Save conversion tracking fields
         */
        public function save_fields( $id, $post ) {

            if ( !isset($_POST[self::TRACKING_CODE_META_KEY]) || !isset($_POST[self::TRACKING_CODE_CONV_META_KEY]) ) {
                return;
            }

            $tracking_code = $_POST[self::TRACKING_CODE_META_KEY];
            $tracking_code_conv = $_POST[self::TRACKING_CODE_CONV_META_KEY];

            update_post_meta( $id, self::TRACKING_CODE_META_KEY, $tracking_code );
            update_post_meta( $id, self::TRACKING_CODE_CONV_META_KEY, $tracking_code_conv );
        }

        /**
         * Display conversion tracking metabox
         */
        public function display_option_group() {
            echo '<div class="option_group">';

            woocommerce_wp_textarea_input( array(
                'id'      => self::TRACKING_CODE_META_KEY,
                'value'   => get_post_meta( get_the_ID(), self::TRACKING_CODE_META_KEY, true ),
                'label'   => 'Tracking Code',
                'desc_tip' => true,
                'description' => 'Tracking code to add to this specific product',
            ) );

            woocommerce_wp_textarea_input( array(
                'id'      => self::TRACKING_CODE_CONV_META_KEY,
                'value'   => get_post_meta( get_the_ID(), self::TRACKING_CODE_CONV_META_KEY, true ),
                'label'   => 'Conversion Trackign Code',
                'desc_tip' => true,
                'description' => 'Conversion tracking code to add to the checkout success page then this order is successfully purchased',
            ) );

            echo '</div>';
        }

        /**
         * Add tracking code to product page
         */
        public function display_tracking_codes() {
            global $post;

            /**
             * Display tracking code only in woocommerce product pages if tracking code is set
             */
            if ( $post->post_type == 'product' && $tracking_code = get_post_meta( get_the_ID(), self::TRACKING_CODE_META_KEY, true ) ) {
                echo $tracking_code;
            }

            /**
             * Display conversion tracking code if the user has successfully purchased the product
             * This is done with a conversion cookie set during checkout process
             */
            if ( isset($_COOKIE[self::CONV_PRODUCTS_COOKIE_KEY]) && !empty($_COOKIE[self::CONV_PRODUCTS_COOKIE_KEY]) ) {
                $converted_products = json_decode($_COOKIE[self::CONV_PRODUCTS_COOKIE_KEY], true);

                /* For each converted product display the conversion code if set */
                foreach ($converted_products as $product_id) {

                    $tracking_code = get_post_meta($product_id, self::TRACKING_CODE_META_KEY, true);
                    if ( $tracking_code ) {
                        echo $tracking_code;
                    }

                    $conv_tracking_code = get_post_meta($product_id, self::TRACKING_CODE_CONV_META_KEY, true);
                    if ( $conv_tracking_code ) {
                        echo $conv_tracking_code;
                    }
                }

                echo '<script>jQuery.ajax({ type: "POST", url: "' . admin_url( 'admin-ajax.php' ) . '", data: { action: "sm_wcpct_complete_convertion"}});</script>';
            }
        }

        /**
         * Handle conversion complete ajax call
         */
        public function handle_ajax_conversion_completed() {

            /* Delete the conversion cookie */
            setcookie( self::CONV_PRODUCTS_COOKIE_KEY, '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN );
        }

        /**
         * Checkout order processed
         * Save a cookie used to track the user conversion tracking on success page
         */
        public function checkout_order_processed( $order_id, $posted_data, $order ) {
            $order = new WC_Order( $order_id );
            $items = $order->get_items();

            $converted_products = array();

            /* For each order product, add the product id in a conversion product array to be saved in cookie */
            foreach ( $items as $item ) {
                $product_id = $item['product_id'];

                /* If the product has a conversion tracking code, add the product id to the array */
                if ( get_post_meta($product_id, self::TRACKING_CODE_CONV_META_KEY, true) ) {
                    $converted_products[] = $product_id;
                }
            }

            /* Save a conversion cookie with the product ids */
            if ($converted_products) {
                setcookie( self::CONV_PRODUCTS_COOKIE_KEY, json_encode($converted_products), time() + MONTH_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN );
            }
        }

		/**
		 * Return SM_WCPCT_Product Instance
		 *
		 * @static
		 * @return SM_WCPCT_Product
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

	}

endif;

function SM_WCPCT_Product() {
	return SM_WCPCT_Product::instance();
}
