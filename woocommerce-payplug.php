<?php

/**
 * Plugin Name: WooCommerce PayPlug (Premium)
 * Plugin URI: https://wba.fr/woocommerce-payplug/
 * Description: La passerelle de paiement PayPlug pour WooCommerce
 * Author: Boris Colombier
 * Author URI: https://wba.fr
 * Version: 3.5.3
 * Text Domain: woocommerce-payplug
 * Domain Path: /languages/
 * WC requires at least: 2.6.0
 * WC tested up to: 4.3.0
 */

if ( !defined( 'ABSPATH' ) ) {
    exit;
    // Exit if accessed directly
}

defined( 'WCPAYPLUG_VERSION' ) || define( 'WCPAYPLUG_VERSION', '3.5.3' );
defined( 'WCPAYPLUG_BASE_PATH' ) || define( 'WCPAYPLUG_BASE_PATH', realpath( dirname( __FILE__ ) ) );
defined( 'WCPAYPLUG_BASE_URL' ) || define( 'WCPAYPLUG_BASE_URL', plugin_dir_url( __FILE__ ) );
defined( 'WCPAYPLUG_BASE_NAME' ) || define( 'WCPAYPLUG_BASE_NAME', basename( dirname( __FILE__ ) ) );
if ( !class_exists( 'WC_PayPlug' ) ) {
    class WC_PayPlug
    {
        private static  $instance ;
        public static function get_instance()
        {
            if ( !isset( self::$instance ) && !self::$instance instanceof WC_PayPlug ) {
                self::$instance = new WC_PayPlug();
            }
            return self::$instance;
        }
        
        public function __construct()
        {
            // Check WooCommerce is active
            
            if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) || in_array( 'woocommerce/woocommerce.php', array_keys( get_site_option( 'active_sitewide_plugins' ) ) ) ) {
                // load Freemius
                include WCPAYPLUG_BASE_PATH . '/inc/load_freemius.php';
                // uninstallation
                woocommercepayplug_fs()->add_action( 'after_uninstall', 'woocommercepayplug_fs_uninstall_cleanup' );
                function woocommercepayplug_fs_uninstall_cleanup()
                {
                    delete_option( 'woocommerce_woocommerce-payplug_settings' );
                    delete_option( 'woocommerce_woocommerce-payplug_extras' );
                }
                
                add_action( 'plugins_loaded', array( $this, 'wcpayplug_gateway_load' ), 0 );
            }
        
        }
        
        public function wcpayplug_woocommerce_fallback_notice()
        {
            $html = '<div class="error">';
            $html .= '<p>' . __( 'The WooCommerce PayPlug Gateway requires the latest version of <a href="http://wordpress.org/extend/plugins/woocommerce/" target="_blank">WooCommerce</a> to work!', 'woocommerce-payplug' ) . '</p>';
            $html .= '</div>';
            echo  $html ;
        }
        
        public function wcpayplug_gateway_load()
        {
            /**
             * Load textdomain.
             */
            load_plugin_textdomain( 'woocommerce-payplug', false, WCPAYPLUG_BASE_NAME . '/languages/' );
            
            if ( !class_exists( 'WC_Payment_Gateway' ) ) {
                add_action( 'admin_notices', array( $this, 'wcpayplug_woocommerce_fallback_notice' ) );
                return;
            }
            
            add_filter( 'woocommerce_payment_gateways', array( $this, 'woocommerce_payment_gateways' ) );
        }
        
        public function woocommerce_payment_gateways( $methods )
        {
            include WCPAYPLUG_BASE_PATH . '/inc/class_wc_gateway_payplug.php';
            include WCPAYPLUG_BASE_PATH . '/inc/class_wc_gateway_payplug-cc.php';
            $methods[] = 'WC_Gateway_Payplug';
            return $methods;
        }
    
    }
}
WC_PayPlug::get_instance();