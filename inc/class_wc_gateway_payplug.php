<?php

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
/**
* PayPlug Payment Gateway
*
* Provides a PayPlug Payment Gateway.
*
* @class       WC_Gateway_Payplug
* @extends     WC_Payment_Gateway
*/
if ( !class_exists( 'WC_Gateway_Payplug_Base' ) ) {
    class WC_Gateway_Payplug_Base extends WC_Payment_Gateway
    {
        public static  $log = false ;
        /**
         * Constructor for the gateway.
         */
        public function __construct()
        {
            $this->version = WCPAYPLUG_VERSION;
            $this->icon = WCPAYPLUG_BASE_URL . '/assets/images/cards_logo.png';
            $this->method_title = __( 'PayPlug', 'woocommerce-payplug' );
            $this->notify_url = add_query_arg( 'wc-api', 'WC_Gateway_Payplug', home_url( '/' ) );
            // Load the form fields.
            $this->init_form_fields();
            // Load the settings.
            $this->init_settings();
            $this->settings = get_option( $this->plugin_id . 'woocommerce-payplug' . '_settings', null );
            // Define user setting variables.
            $this->enabled = $this->get_option( 'enabled' );
            $this->title = $this->get_option( 'title' );
            $this->description = $this->get_option( 'description' );
            $this->payplug_login = $this->get_option( 'payplug_login' );
            $this->payplug_password = $this->get_option( 'payplug_password' );
            $this->set_completed = $this->get_option( 'set_completed' );
            $this->payplug_key = $this->get_option( 'payplug_key' );
            $this->cancel_url = $this->get_option( 'cancel_url' );
            $this->method_description = __( 'Payment with PayPlug', 'woocommerce-payplug' );
            $this->supports = array( 'products', 'refunds' );
            $this->view_transaction_url = 'https://portal.payplug.com/#/payments/%s';
            add_action( 'woocommerce_api_wc_gateway_payplug', array( $this, 'check_ipn_response' ) );
            add_action( 'valid_payplug_ipn_request', array( $this, 'update_order_with_ipn' ) );
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
            do_action( 'wc_payplug.loaded' );
        }
        
        /**
         * Checking if this gateway is enabled.
         */
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
        
        /**
         * Return whether or not this gateway still requires setup to function.
         *
         * When this gateway is toggled on via AJAX, if this returns true a
         * redirect will occur to the settings page instead.
         *
         * @since 3.4.0
         * @return bool
         */
        public function needs_setup()
        {
            if ( empty($this->payplug_key) ) {
                return true;
            }
        }
        
        /**
         * Admin page content
         */
        public function admin_options()
        {
            wp_enqueue_style(
                'wcpayplugcss',
                WCPAYPLUG_BASE_URL . 'assets/css/payplug.css',
                array(),
                $this->version
            );
            if ( empty($this->payplug_key) ) {
                echo  '<div class="wcpayplug_error">
                    <p>Veuillez indiquer votre login et votre mot de passe pour activer la passerelle de paiement</p>
                </div>' ;
            }
            ?>
            
            
            
            <div class="payplug_admin_hld">
                <?php 
            parent::admin_options();
            $wcpayplug_options = get_option( 'woocommerce_woocommerce-payplug_extras' );
            ?>
                    <div id="wc_get_started" class="payplug">
                    <a class="button button-primary get_free_account_bt" href="http://url.wba.fr/payplug" target="_blank"><?php 
            _e( 'Ouvrir mon compte' );
            ?></a>
                    
                    <fieldset class="get_pro_version">
                        <legend><?php 
            _e( 'WooCommerce PayPlug PRO', 'woocommerce-payplug' );
            ?></legend>
                        <a href="<?php 
            echo  add_query_arg( array(
                'page' => 'wcpayplug-admin#version-pro',
            ), admin_url( 'options-general.php' ) ) ;
            ?>" class="button button-primary pro_version_bt">Essayer gratuitement</a>
                        <h4>Améliorez votre boutique :</h4>
                        <p>
                            Proposez le paiement en plusieurs fois sur votre boutique et booster vos ventes !
                        </p>
                    </fieldset>
                    </div>
                    <?php 
            ?>
            </div>
            <?php 
        }
        
        /**
         * Start Gateway Settings Form Fields.
         */
        public function init_form_fields()
        {
            $this->form_fields = array(
                'enabled'          => array(
                'title'       => __( 'Enable/Disable', 'woocommerce-payplug' ),
                'type'        => 'checkbox',
                'label'       => __( 'Enable PayPlug', 'woocommerce-payplug' ),
                'description' => __( 'NB: This payment gateway can only be enabled if the currency used by the store is the euro. <a href="https://support.payplug.com/hc/fr/articles/360022396212-Acceptez-vous-les-devises-%C3%A9trang%C3%A8res-" target="_blank">learn more</a>', 'woocommerce-payplug' ),
                'default'     => 'yes',
            ),
                'test_mode'        => array(
                'title'       => __( 'Test Mode', 'woocommerce-payplug' ),
                'type'        => 'checkbox',
                'label'       => __( 'Use PayPlug in TEST (Sandbox) Mode', 'woocommerce-payplug' ),
                'default'     => 'no',
                'description' => __( 'Use test mode to test transactions with no real payment required <a href="https://support.payplug.com/hc/fr/articles/360021142492-Comment-tester-le-service-Qu-est-ce-que-le-mode-TEST-" target="_blank">learn more</a>', 'woocommerce-payplug' ),
            ),
                'title'            => array(
                'title'       => __( 'Title', 'woocommerce-payplug' ),
                'type'        => 'text',
                'description' => __( 'Payment method title seen by the user upon payment.', 'woocommerce-payplug' ),
                'default'     => __( 'Credit Card', 'woocommerce-payplug' ),
            ),
                'description'      => array(
                'title'       => __( 'Description', 'woocommerce-payplug' ),
                'type'        => 'textarea',
                'description' => __( 'Payment method description seen by the user upon payment.', 'woocommerce-payplug' ),
                'default'     => __( 'Make secure payments using your bank card with PayPlug.', 'woocommerce-payplug' ),
            ),
                'cancel_url'       => array(
                'title'       => __( 'Cancel URL', 'woocommerce-payplug' ),
                'type'        => 'text',
                'description' => __( 'URL to the page on your website that PayPlug redirects to if the buyer cancels', 'woocommerce-payplug' ),
                'default'     => wc_get_cart_url(),
            ),
                'payplug_login'    => array(
                'title'       => __( 'PayPlug login', 'woocommerce-payplug' ),
                'type'        => 'text',
                'description' => __( 'The email address used to log on to PayPlug.', 'woocommerce-payplug' ),
                'default'     => '',
            ),
                'payplug_password' => array(
                'title'       => __( 'PayPlug password', 'woocommerce-payplug' ),
                'type'        => 'password',
                'description' => __( 'The password used to log on to PayPlug. This value is not saved in database.', 'woocommerce-payplug' ),
                'default'     => '',
            ),
                'set_completed'    => array(
                'title'   => __( "Mark the order as 'completed'", 'woocommerce-payplug' ),
                'type'    => 'checkbox',
                'label'   => __( "Mark the order as 'completed' upon payment confirmation by PayPlug instead of 'in progress'", 'woocommerce-payplug' ),
                'default' => 'no',
            ),
                'payplug_key'      => array(
                'title'       => '',
                'type'        => 'hidden',
                'default'     => '',
                'description' => '',
            ),
            );
        }
        
        public function process_admin_options()
        {
            // set version
            $wcpayplug_options = get_option( $this->plugin_id . $this->id . '_extras' );
            $wcpayplug_options['version'] = $this->version;
            update_option( $this->plugin_id . $this->id . '_extras', $wcpayplug_options );
            // clean options
            delete_option( $this->plugin_id . $this->id . '_settings' );
            $post_data = $this->get_post_data();
            $login = wc_get_var( $post_data[$this->plugin_id . $this->id . '_payplug_login'], '' );
            $password = wc_get_var( $post_data[$this->plugin_id . $this->id . '_payplug_password'], '' );
            
            if ( !empty($password) ) {
                $response = $this->get_payplug_api_key( $login, $password );
                
                if ( is_wp_error( $response ) ) {
                    $this->log( 'Login error : ' . wc_print_r( $response, true ) );
                    WC_Admin_Settings::add_error( $response->get_error_message() );
                    unset( $post_data[$this->plugin_id . $this->id . '_enabled'] );
                } else {
                    // store API key
                    $post_data[$this->plugin_id . $this->id . '_payplug_key'] = ( isset( $post_data[$this->plugin_id . $this->id . '_test_mode'] ) ? $response->secret_keys->test : $response->secret_keys->live );
                    
                    if ( !isset( $post_data[$this->plugin_id . $this->id . '_test_mode'] ) && empty($response->secret_keys->live) ) {
                        // Check if payplug account is in test mode only
                        $post_data[$this->plugin_id . $this->id . '_test_mode'] = 'yes';
                        $post_data[$this->plugin_id . $this->id . '_payplug_key'] = $response->secret_keys->test;
                        WC_Admin_Settings::add_error( 'Votre compte PayPlug ne propose que le mode TEST' );
                    }
                
                }
                
                // don't store password
                $post_data[$this->plugin_id . $this->id . '_payplug_password'] = '';
            }
            
            $this->set_post_data( $post_data );
            parent::process_admin_options();
        }
        
        public function get_payplug_api_key( $login, $password )
        {
            $response = wp_remote_post( 'https://api.payplug.com/v1/keys', array(
                'headers' => array(
                'Content-Type' => 'application/json; charset=utf-8',
            ),
                'body'    => json_encode( array(
                'email'    => $login,
                'password' => $password,
            ) ),
                'method'  => 'POST',
            ) );
            if ( is_wp_error( $response ) ) {
                return $response;
            }
            
            if ( isset( $response['response']['code'] ) && $response['response']['code'] == 201 ) {
                return json_decode( $response['body'] );
            } else {
                return new WP_Error( 'loginError', __( 'Your credentials are invalid.', 'woocommerce-payplug' ) );
            }
        
        }
        
        public function wc_version_3()
        {
            
            if ( version_compare( WOOCOMMERCE_VERSION, '3.0.0', '>=' ) ) {
                return true;
            } else {
                return false;
            }
        
        }
        
        public function str_max( $str, $max = 100 )
        {
            // truncate string
            if ( !empty($str) ) {
                $str = ( strlen( $str ) > $max ? substr( $str, 0, $max ) : $str );
            }
            return $str;
        }
        
        public function set_payment_data( $order_id )
        {
            $order = new WC_Order( $order_id );
            $billing_first_name = ( !$this->wc_version_3() ? $order->billing_first_name : $order->get_billing_first_name() );
            $billing_last_name = ( !$this->wc_version_3() ? $order->billing_last_name : $order->get_billing_last_name() );
            $billing_email = ( !$this->wc_version_3() ? $order->billing_email : $order->get_billing_email() );
            $billing_address1 = ( !$this->wc_version_3() ? $order->billing_address_1 : $order->get_billing_address_1() );
            $billing_address2 = ( !$this->wc_version_3() ? $order->billing_address_2 : $order->get_billing_address_2() );
            $billing_postcode = ( !$this->wc_version_3() ? $order->billing_postcode : $order->get_billing_postcode() );
            $billing_city = ( !$this->wc_version_3() ? $order->billing_city : $order->get_billing_city() );
            $billing_country = ( !$this->wc_version_3() ? $order->billing_country : $order->get_billing_country() );
            $billing_phone = ( !$this->wc_version_3() ? $order->billing_phone : $order->get_billing_phone() );
            $billing_phone = str_replace( ' ', '', $billing_phone );
            if ( substr( $billing_phone, 0, 1 ) === '0' ) {
                $billing_phone = '+33' . substr( $billing_phone, 1 );
            }
            $company_name = ( !$this->wc_version_3() ? $order->billing_company : $order->get_billing_company() );
            $billing_datas = array(
                'first_name'          => $this->str_max( $billing_first_name ),
                'last_name'           => $this->str_max( $billing_last_name ),
                'email'               => $this->str_max( $billing_email, 255 ),
                'address1'            => $this->str_max( $billing_address1, 255 ),
                'postcode'            => $this->str_max( $billing_postcode, 16 ),
                'city'                => $this->str_max( $billing_city ),
                'country'             => $this->str_max( $billing_country, 2 ),
                'mobile_phone_number' => $this->str_max( $billing_phone ),
                'company_name'        => $this->str_max( $company_name ),
            );
            if ( !empty($billing_address2) ) {
                $billing_datas['address2'] = $billing_address2;
            }
            $billing_datas = array_filter( $billing_datas );
            $billing_datas = apply_filters( 'woocommerce-payplug_filter_billing_datas', $billing_datas );
            
            if ( !WC()->cart->needs_shipping() ) {
                $shipping_datas = $billing_datas;
                $shipping_datas['delivery_type'] = 'OTHER';
            } else {
                $has_shipping_address = ( !$this->wc_version_3() ? $order->shipping_address_1 : $order->get_shipping_address_1() );
                
                if ( $has_shipping_address && !wc_ship_to_billing_address_only() ) {
                    $shipping_first_name = ( !$this->wc_version_3() ? $order->shipping_first_name : $order->get_shipping_first_name() );
                    $shipping_last_name = ( !$this->wc_version_3() ? $order->shipping_last_name : $order->get_shipping_last_name() );
                    $shipping_email = $billing_email;
                    $shipping_company = ( !$this->wc_version_3() ? $order->billing_company : $order->get_billing_company() );
                    $shipping_address1 = ( !$this->wc_version_3() ? $order->shipping_address_1 : $order->get_shipping_address_1() );
                    $shipping_address2 = ( !$this->wc_version_3() ? $order->shipping_address_2 : $order->get_shipping_address_2() );
                    $shipping_postcode = ( !$this->wc_version_3() ? $order->shipping_postcode : $order->get_shipping_postcode() );
                    $shipping_city = ( !$this->wc_version_3() ? $order->shipping_city : $order->get_shipping_city() );
                    $shipping_country = ( !$this->wc_version_3() ? $order->shipping_country : $order->get_shipping_country() );
                    $shipping_datas = array(
                        'first_name'   => $this->str_max( $shipping_first_name ),
                        'last_name'    => $this->str_max( $shipping_last_name ),
                        'email'        => $this->str_max( $shipping_email, 255 ),
                        'address1'     => $this->str_max( $shipping_address1, 255 ),
                        'postcode'     => $this->str_max( $shipping_postcode, 16 ),
                        'city'         => $this->str_max( $shipping_city ),
                        'country'      => $this->str_max( $shipping_country, 2 ),
                        'company_name' => $this->str_max( $shipping_company ),
                    );
                    if ( !empty($shipping_address2) ) {
                        $shipping_datas['address2'] = $shipping_address2;
                    }
                    $shipping_datas['delivery_type'] = 'BILLING';
                } else {
                    $shipping_datas = $billing_datas;
                    $shipping_datas['delivery_type'] = 'BILLING';
                }
            
            }
            
            $shipping_datas = array_filter( $shipping_datas );
            $shipping_datas = apply_filters( 'woocommerce-payplug_filter_shipping_datas', $shipping_datas );
            return array(
                'order'          => $order,
                'billing_datas'  => $billing_datas,
                'shipping_datas' => $shipping_datas,
            );
        }
        
        public function process_payplug_payment( $order_id )
        {
            $payment_data = $this->set_payment_data( $order_id );
            $billing_datas = $payment_data['billing_datas'];
            $shipping_datas = $payment_data['shipping_datas'];
            $order = $payment_data['order'];
            try {
                require_once WCPAYPLUG_BASE_PATH . '/inc/lib/init.php';
                \Payplug\Payplug::init( array(
                    'secretKey'  => $this->payplug_key,
                    'apiVersion' => '2019-08-06',
                ) );
                $payment = \Payplug\Payment::create( array(
                    'amount'           => intval( floatval( number_format(
                    $order->get_total(),
                    2,
                    '.',
                    ''
                ) ) * 100 ),
                    'currency'         => get_woocommerce_currency(),
                    'billing'          => $billing_datas,
                    'shipping'         => $shipping_datas,
                    'hosted_payment'   => array(
                    'return_url' => $this->get_return_url( $order ),
                    'cancel_url' => $this->cancel_url,
                ),
                    'notification_url' => $this->notify_url,
                    'metadata'         => array(
                    'order_id' => $order_id,
                ),
                ) );
                return array(
                    'payment_url' => $payment->hosted_payment->payment_url,
                    '_payplug_id' => $payment->id,
                );
            } catch ( Exception $e ) {
                $return_errors = new WP_Error();
                $errors = $e->getErrorObject();
                foreach ( $errors['details'] as $key_errors_group => $errors_group ) {
                    
                    if ( is_array( $errors_group ) ) {
                        foreach ( $errors_group as $key => $error ) {
                            $return_errors->add( 'woocommerce-payplug', $key_errors_group . ' : ' . $key . ' => ' . $error );
                        }
                    } else {
                        $return_errors->add( 'woocommerce-payplug', $key_errors_group . ' : ' . $errors_group );
                    }
                
                }
                $this->log( 'error on process_payplug_payment() : ' . wc_print_r( $e->getErrorObject(), true ) );
                return $return_errors;
            }
        }
        
        public function translate_errors( $str )
        {
            // Traduction simplissime des erreurs
            $translations = array(
                'mobile_phone_number'                 => 'Numéro de téléphone mobile',
                'company_name'                        => 'Nom de l\'entreprise',
                'You must provide a non-empty string' => 'Vous devez remplir ce champ',
                'The phone number format is invalid'  => 'Le format du numéro de téléphone n\'est pas valide (+33 x xx xx xx xx attendu)',
                'billing'                             => 'Facturation',
                'shipping'                            => 'Expédition',
                ' is required for Oney payments'      => ' est nécessaire pour les paiement avec Oney',
            );
            foreach ( $translations as $key => $value ) {
                $str = str_replace( $key, $value, $str );
            }
            return $str;
        }
        
        /**
         * Process the payment and return the result.
         */
        public function process_payment( $order_id )
        {
            $result = $this->process_payplug_payment( $order_id );
            
            if ( is_wp_error( $result ) ) {
                foreach ( $result->errors as $key => $values ) {
                    foreach ( $values as $index => $value ) {
                        wc_add_notice( $this->translate_errors( $value ), 'error' );
                    }
                }
                return;
            }
            
            update_post_meta( $order_id, '_payplug_id', $result['_payplug_id'] );
            $order = new WC_Order( $order_id );
            $order->add_order_note( 'create payment | PayPlug ID : ' . $result['_payplug_id'] );
            return array(
                'result'   => 'success',
                'redirect' => $result['payment_url'],
            );
        }
        
        public function process_refund( $order_id, $amount = null, $reason = '' )
        {
            $payment_id = get_post_meta( $order_id, '_transaction_id', true );
            $data = array(
                'amount'   => round( $amount * 100 ),
                'metadata' => array(
                'order_id' => $order_id,
                'reason'   => $reason,
            ),
            );
            try {
                require_once WCPAYPLUG_BASE_PATH . '/inc/lib/init.php';
                \Payplug\Payplug::init( array(
                    'secretKey'  => $this->payplug_key,
                    'apiVersion' => '2019-08-06',
                ) );
                $refund = \Payplug\Refund::create( $payment_id, $data );
                return new WP_Error( 'PayPlugRefundSystem', __( 'The refund request has been sent to PayPlug. Please reload this page.', 'woocommerce-payplug' ) );
            } catch ( Exception $e ) {
                $this->log( 'error on process_refund() : ' . wc_print_r( $e->getErrorObject(), true ) );
                return false;
            }
        }
        
        public function check_ipn_response()
        {
            try {
                require_once WCPAYPLUG_BASE_PATH . '/inc/lib/init.php';
                \Payplug\Payplug::init( array(
                    'secretKey'  => $this->payplug_key,
                    'apiVersion' => '2019-08-06',
                ) );
            } catch ( Exception $e ) {
                $this->log( 'error on check_ipn_response() login : ' . wc_print_r( $e->getErrorObject(), true ) );
                return false;
            }
            $input = file_get_contents( 'php://input' );
            try {
                $resource = \Payplug\Notification::treat( $input );
                
                if ( $resource instanceof \Payplug\Resource\Payment && $resource->is_paid ) {
                    @ob_clean();
                    header( 'HTTP/1.1 200 OK' );
                    do_action( 'valid_payplug_ipn_request', $resource );
                } else {
                    if ( $resource instanceof \Payplug\Resource\Refund ) {
                        // Process the refund.
                        
                        if ( isset( $resource->payment_id ) ) {
                            
                            if ( isset( $resource->metadata['order_id'] ) ) {
                                $order_id = $resource->metadata['order_id'];
                            } else {
                                $args = array(
                                    'post_type'      => 'shop_order',
                                    'meta_query'     => array( array(
                                    'key'   => '_transaction_id',
                                    'value' => $resource->payment_id,
                                ) ),
                                    'posts_per_page' => 1,
                                    'post_status'    => 'any',
                                );
                                $my_query = new WP_Query( $args );
                                $order_id = $my_query->post->ID;
                                wp_reset_postdata();
                            }
                            
                            wc_create_refund( array(
                                'amount'   => $resource->amount / 100,
                                'reason'   => ( isset( $resource->metadata['reason'] ) ? $resource->metadata['reason'] : 'Refund from PayPlug Portal' ),
                                'order_id' => $order_id,
                            ) );
                        }
                    
                    }
                }
            
            } catch ( Exception $e ) {
                $this->log( 'error on check_ipn_response() process : ' . wc_print_r( $e->getErrorObject(), true ) );
                return false;
            }
        }
        
        /**
         * Successful Payment.
         */
        public function update_order_with_ipn( $IPN_data )
        {
            try {
                $order_id = $IPN_data->metadata['order_id'];
            } catch ( Exception $e ) {
                $this->log( 'error on update_order_with_ipn() process : ' . wc_print_r( $e->getErrorObject(), true ) );
                exit;
            }
            $order = new WC_Order( $order_id );
            // Check order not already completed
            
            if ( $this->wc_version_3() ) {
                if ( $order->get_status() == 'completed' ) {
                    exit;
                }
            } else {
                if ( $order->status == 'completed' ) {
                    exit;
                }
            }
            
            // Payment completed
            // Reduce stock levels for old versions
            if ( !$this->wc_version_3() ) {
                $order->reduce_order_stock();
            }
            $order->add_order_note( 'IPN ok | PayPlug ID : ' . $IPN_data->id );
            $order->payment_complete( $IPN_data->id );
            if ( $this->set_completed == 'yes' ) {
                $order->update_status( 'completed' );
            }
            exit;
        }
        
        /**
         * Logging method.
         *
         * @param string $message Log message.
         * @param string $level   Optional. Default 'alert'.
         *     emergency|alert|critical|error|warning|notice|info|debug
         */
        public static function log( $message, $level = 'alert' )
        {
            if ( empty(self::$log) ) {
                self::$log = wc_get_logger();
            }
            self::$log->log( $level, $message, array(
                'source' => 'payplug',
            ) );
        }
    
    }
}