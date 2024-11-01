<?php

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
if ( !class_exists( 'WC_Gateway_Payplug' ) ) {
    class WC_Gateway_Payplug extends WC_Gateway_Payplug_Base
    {
        public  $oney_id ;
        public function __construct()
        {
            $this->id = 'woocommerce-payplug';
            $this->title = 'Payer par carte bancaire';
            parent::__construct();
        }
        
        public function is_available()
        {
            if ( 'yes' !== $this->enabled ) {
                return false;
            }
            if ( !in_array( get_woocommerce_currency(), array( 'EUR' ) ) ) {
                return false;
            }
            if ( empty($this->payplug_key) ) {
                return false;
            }
            return true;
        }
    
    }
}