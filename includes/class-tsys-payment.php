<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://www.weavers-web.com/
 * @since      1.0.0
 *
 * @package    Tsys_Payment
 * @subpackage Tsys_Payment/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Tsys_Payment
 * @subpackage Tsys_Payment/includes
 * @author     Weavers Web Solution Private Limited <simanta.karmakar@weavers-web.com>
 */
add_action('plugins_loaded', 'init_custom_gateway_class');
function init_custom_gateway_class(){

    class Tsys_Payment extends WC_Payment_Gateway {

        public $domain;
        protected $version;
        /**
         * Constructor for the gateway.
         */
        public function __construct() {
            if ( defined( 'TSYS_PAYMENT_VERSION' ) ) {
                $this->version = TSYS_PAYMENT_VERSION;
            } else {
                $this->version = '1.0.0';
            }
            $this->plugin_name = 'tsys-payment';
            $this->load_dependencies();
            $this->define_admin_hooks();
            $this->define_public_hooks();
            $this->domain = 'tsys_payment';

            $this->supports = array( 'subscriptions', 'products' );

            $this->id                 = 'tsys';
            $this->icon               = apply_filters('woocommerce_custom_gateway_icon', '');
            $this->has_fields         = false;
            $this->method_title       = __( 'TSYS Payment', $this->domain );
            $this->method_description = __( 'Allows payments with tsys payment gateway.', $this->domain );

            // Load the settings.
            $this->init_form_fields();
            $this->init_settings();

            // Define user set variables
            $this->title        = $this->get_option( 'title' );
            $this->device_id        = $this->get_option( 'device_id' );
            $this->transaction_key        = $this->get_option( 'transaction_key' );
            $this->description  = $this->get_option( 'description' );
            $this->instructions = $this->get_option( 'instructions', $this->description );
            $this->order_status = $this->get_option( 'order_status', 'completed' );

            // Actions
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
            add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );

            // Customer Emails
            add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
        }

        private function load_dependencies() {

            /**
             * The class responsible for orchestrating the actions and filters of the
             * core plugin.
             */
            require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-tsys-payment-loader.php';

            /**
             * The class responsible for defining internationalization functionality
             * of the plugin.
             */
            require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-tsys-payment-i18n.php';

            /**
             * The class responsible for defining all actions that occur in the admin area.
             */
            require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-tsys-payment-admin.php';

            /**
             * The class responsible for defining all actions that occur in the public-facing
             * side of the site.
             */
            require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-tsys-payment-public.php';

            $this->loader = new Tsys_Payment_Loader();

        }

        /**
         * Register all of the hooks related to the admin area functionality
         * of the plugin.
         *
         * @since    1.0.0
         * @access   private
         */
        private function define_admin_hooks() {

            $plugin_admin = new Tsys_Payment_Admin( $this->get_plugin_name(), $this->get_version() );

            $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
            $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

        }

        /**
         * Register all of the hooks related to the public-facing functionality
         * of the plugin.
         *
         * @since    1.0.0
         * @access   private
         */
        private function define_public_hooks() {

            $plugin_public = new Tsys_Payment_Public( $this->get_plugin_name(), $this->get_version() );

            $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
            $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

        }

        public function run() {
            $this->loader->run();
        }

        public function get_plugin_name() {
            return $this->plugin_name;
        }

        public function get_version() {
            return $this->version;
        }
        /**
         * Initialise Gateway Settings Form Fields.
         */
        public function init_form_fields() {

            $this->form_fields = array(
                'enabled' => array(
                    'title'   => __( 'Enable/Disable', $this->domain ),
                    'type'    => 'checkbox',
                    'label'   => __( 'Enable TSYS Payment', $this->domain ),
                    'default' => 'yes'
                ),
                'title' => array(
                    'title'       => __( 'Title', $this->domain ),
                    'type'        => 'text',
                    'description' => __( 'This controls the title which the user sees during checkout.', $this->domain ),
                    'default'     => __( 'TSYS Payment', $this->domain ),
                    'desc_tip'    => true,
                ),
                'device_id' => array(
                    'title'       => __( 'Device ID', $this->domain ),
                    'type'        => 'text',
                    'description' => __( 'Please enter device id for payment.', $this->domain ),
                    'desc_tip'    => true,
                ),
                'transaction_key' => array(
                    'title'       => __( 'Transaction Key', $this->domain ),
                    'type'        => 'text',
                    'description' => __( 'Please enter transaction key for payment.', $this->domain ),
                    'desc_tip'    => true,
                ),
                'operating_userid' => array(
                    'title'       => __( 'Operating UserID', $this->domain ),
                    'type'        => 'text',
                    'description' => __( 'Please enter operating user ID for payment.', $this->domain ),
                    'desc_tip'    => true,
                ),
                'order_status' => array(
                    'title'       => __( 'Order Status', $this->domain ),
                    'type'        => 'select',
                    'class'       => 'wc-enhanced-select',
                    'description' => __( 'Choose whether status you wish after checkout.', $this->domain ),
                    'default'     => 'wc-completed',
                    'desc_tip'    => true,
                    'options'     => wc_get_order_statuses()
                ),
                'description' => array(
                    'title'       => __( 'Description', $this->domain ),
                    'type'        => 'textarea',
                    'description' => __( 'Payment method description that the customer will see on your checkout.', $this->domain ),
                    'default'     => __('Payment Information', $this->domain),
                    'desc_tip'    => true,
                ),
                'instructions' => array(
                    'title'       => __( 'Instructions', $this->domain ),
                    'type'        => 'textarea',
                    'description' => __( 'Instructions that will be added to the thank you page and emails.', $this->domain ),
                    'default'     => '',
                    'desc_tip'    => true,
                ),
            );
        }

        /**
         * Output for the order received page.
         */
        public function thankyou_page() {
            if ( $this->instructions )
                echo wpautop( wptexturize( $this->instructions ) );
        }

        /**
         * Add content to the WC emails.
         *
         * @access public
         * @param WC_Order $order
         * @param bool $sent_to_admin
         * @param bool $plain_text
         */
        public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
            if ( $this->instructions && ! $sent_to_admin && 'custom' === $order->payment_method && $order->has_status( 'on-hold' ) ) {
                echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
            }
        }

        public function payment_fields(){

            if ( $description = $this->get_description() ) {
                echo wpautop( wptexturize( $description ) );
            }

            ?>
            <div id="custom_input">
                <p class="form-row form-row-wide">
                    <label for="mobile" class=""><?php _e('Card Data Source *', $this->domain); ?></label>
                    <input id="WC_Gateway_Globalpay-card-internet" class="input-text js-sv-wc-payment-gateway-credit-card-form-input js-sv-wc-payment-gateway-credit-card-form-account-datasource" type="radio" name="WC_Gateway_Globalpay-card-datasource" value="INTERNET" checked> INTERNET
                    <input id="WC_Gateway_Globalpay-card-phone" class="input-text js-sv-wc-payment-gateway-credit-card-form-input js-sv-wc-payment-gateway-credit-card-form-account-datasource" type="radio" name="WC_Gateway_Globalpay-card-datasource" value="PHONE"> PHONE
                     <input id="WC_Gateway_Globalpay-card-mail" class="input-text js-sv-wc-payment-gateway-credit-card-form-input js-sv-wc-payment-gateway-credit-card-form-account-datasource" type="radio" name="WC_Gateway_Globalpay-card-datasource" value="MAIL"> MAIL
                </p>
                <p class="form-row form-row-wide">
                    <label for="mobile" class=""><?php _e('Card Number *', $this->domain); ?></label>
                    <input id="WC_Gateway_Globalpay-card-number" class="input-text js-sv-wc-payment-gateway-credit-card-form-input js-sv-wc-payment-gateway-credit-card-form-account-number" inputmode="numeric" autocomplete="cc-number" autocorrect="no" autocapitalize="no" spellcheck="no" type="tel" placeholder="•••• •••• •••• ••••" name="WC_Gateway_Globalpay-card-number">
                </p>
                <p class="form-row form-row-wide">
                    <label for="transaction" class=""><?php _e('Expiry (MM/YY) *', $this->domain); ?></label>
                    <input id="WC_Gateway_Globalpay-card-expiry" class="input-text js-sv-wc-payment-gateway-credit-card-form-input js-sv-wc-payment-gateway-credit-card-form-expiry" inputmode="numeric" autocomplete="cc-exp" autocorrect="no" autocapitalize="no" spellcheck="no" type="tel" placeholder="MM / YY" name="WC_Gateway_Globalpay-card-expiry">
                </p>
                <p class="form-row form-row-wide">
                    <label for="transaction" class=""><?php _e('Card code *', $this->domain); ?></label>
                    <input id="WC_Gateway_Globalpay-card-cvc" class="input-text js-sv-wc-payment-gateway-credit-card-form-input js-sv-wc-payment-gateway-credit-card-form-csc" inputmode="numeric" autocomplete="off" autocorrect="no" autocapitalize="no" spellcheck="no" type="tel" maxlength="4" placeholder="CVC" name="WC_Gateway_Globalpay-card-cvc" style="width:100px">
                </p>
                <div class="error-msg"><?php echo $msg; ?></div>
            </div>
            <?php
        }

        /**
         * Process the payment and return the result.
         *
         * @param int $order_id
         * @return array
         */
        public function process_payment( $order_id ) {

            $order = wc_get_order( $order_id );
            // $item_data = $order->get_data();

            $status = 'wc-' === substr( $this->order_status, 0, 3 ) ? substr( $this->order_status, 3 ) : $this->order_status;

            // Set order status
            $order->update_status( $status, __( 'Checkout with TSYS payment. ', $this->domain ) );
            
            // or call the Payment complete
            // $order->payment_complete();

            // Reduce stock levels
            $order->reduce_order_stock();

            // Remove cart
            WC()->cart->empty_cart();

            // Return thankyou redirect
            return array(
                'result'    => 'success',
                'redirect'  => $this->get_return_url( $order )
            );
        }
    }
}

add_filter( 'woocommerce_payment_gateways', 'add_custom_gateway_class' );
function add_custom_gateway_class( $methods ) {
    $methods[] = 'Tsys_Payment'; 
    return $methods;
}