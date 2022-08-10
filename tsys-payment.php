<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://www.weavers-web.com/
 * @since             1.0.0
 * @package           Tsys_Payment
 *
 * @wordpress-plugin
 * Plugin Name:       TSYS Payment (BETA)
 * Plugin URI:        https://www.weavers-web.com/
 * Description:       This is a short description of what the plugin does. It's displayed in the WordPress admin area.
 * Version:           1.0.0-rs-1.1
 * Author:            Weavers Web Solution Private Limited
 * Author URI:        https://www.weavers-web.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       tsys-payment
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'TSYS_PAYMENT_VERSION', '1.0.0-rs-1.1' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-tsys-payment-activator.php
 */
function activate_tsys_payment() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-tsys-payment-activator.php';
	Tsys_Payment_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-tsys-payment-deactivator.php
 */
function deactivate_tsys_payment() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-tsys-payment-deactivator.php';
	Tsys_Payment_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_tsys_payment' );
register_deactivation_hook( __FILE__, 'deactivate_tsys_payment' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-tsys-payment.php';

// exit;
add_action('woocommerce_checkout_process', 'process_custom_payment');
function process_custom_payment(){
    session_start();
    if($_POST['payment_method'] != 'tsys')
        return;
    if( !isset($_POST['billing_email']) || empty($_POST['billing_email'])){     
        return false;
    }elseif( !isset($_POST['billing_first_name']) || empty($_POST['billing_first_name'])){
        return false;
    }elseif( !isset($_POST['billing_last_name']) || empty($_POST['billing_last_name'])){
        return false;
    }elseif( !isset($_POST['billing_country']) || empty($_POST['billing_country'])){
        return false;
    }elseif( !isset($_POST['billing_address_1']) || empty($_POST['billing_address_1'])){
        return false;
    }elseif( !isset($_POST['billing_city']) || empty($_POST['billing_city'])){
        return false;
    }elseif( !isset($_POST['billing_state']) || empty($_POST['billing_state'])){
        return false;
    }elseif( !isset($_POST['billing_postcode']) || empty($_POST['billing_postcode'])){
        return false;
    }elseif( !isset($_POST['billing_phone']) || empty($_POST['billing_phone'])){
        return false;
    }elseif( !isset($_POST['WC_Gateway_Globalpay-card-number']) || empty($_POST['WC_Gateway_Globalpay-card-number']) ){
        wc_add_notice( __( 'Please enter your card number' ), 'error' );
    }elseif( !isset($_POST['WC_Gateway_Globalpay-card-expiry']) || empty($_POST['WC_Gateway_Globalpay-card-expiry']) ){
        wc_add_notice( __( 'Please enter your card expiry' ), 'error' );
    }elseif( !isset($_POST['WC_Gateway_Globalpay-card-cvc']) || empty($_POST['WC_Gateway_Globalpay-card-cvc']) ){
        wc_add_notice( __( 'Please enter your card cvc' ), 'error' );
    }elseif( !isset($_POST['WC_Gateway_Globalpay-card-datasource']) || empty($_POST['WC_Gateway_Globalpay-card-datasource']) ){
        wc_add_notice( __( 'Please select your card data source' ), 'error' );
    }else{
        //$get_details = get_option('woocommerce_custom_settings');
        $get_details = get_option('woocommerce_tsys_settings');
                
        $device_id = $get_details['device_id'];
        $transaction_key = $get_details['transaction_key'];

        $card_auth_array = array (
          'CardAuthentication' => 
          array (
            'deviceID' => $device_id,
            'transactionKey' => $transaction_key,
            'cardDataSource' => str_replace(' ', '', $_POST['WC_Gateway_Globalpay-card-datasource']),
            'cardNumber' => str_replace(' ', '', $_POST['WC_Gateway_Globalpay-card-number']),
            'expirationDate' => str_replace(' ', '', $_POST['WC_Gateway_Globalpay-card-expiry']),
            'addressLine1' => $_POST['billing_address_1'],
            'zip' => $_POST['billing_postcode'],
            'tokenRequired' => 'Y',
            'developerID' => '003386G001',
            'terminalCapability' => 'KEYED_ENTRY_ONLY',
            'terminalOperatingEnvironment' => 'OFF_MERCHANT_PREMISES_UNATTENDED',
            'cardholderAuthenticationMethod' => 'NOT_AUTHENTICATED',
            'terminalAuthenticationCapability' => 'NO_CAPABILITY',
            'terminalOutputCapability' => 'DISPLAY_ONLY',
            'maxPinLength' => 'NOT_SUPPORTED',
            'terminalCardCaptureCapability' => 'NO_CAPABILITY',
            'cardholderPresentDetail' => 'CARDHOLDER_NOT_PRESENT_ELECTRONIC_COMMERCE',
            'cardPresentDetail' => 'CARD_NOT_PRESENT',
            'cardDataInputMode' => 'ELECTRONIC_COMMERCE_NO_SECURITY_CHANNEL_ENCRYPTED_SET_WITHOUT_CARDHOLDER_CERTIFICATE',
            'cardholderAuthenticationEntity' => 'NOT_AUTHENTICATED',
            'cardDataOutputCapability' => 'NONE',
          ),
        );

        $card_curl = curl_init();

        curl_setopt_array($card_curl, array(
          CURLOPT_URL => "https://stagegw.transnox.com/servlets/TransNox_API_Server",
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "POST",
          CURLOPT_POSTFIELDS => json_encode($card_auth_array),
          CURLOPT_HTTPHEADER => array(
            "cache-control: no-cache",
            "content-type: application/json",
            "postman-token: 26729e68-049e-e8aa-af42-6bd1363b33eb"
          ),
        ));

        $card_response = curl_exec($card_curl);
        $card_err = curl_error($card_curl);

        curl_close($card_curl);

        if ($card_err) {
          echo "cURL Error #:" . $card_err;
        } else {
          echo $card_response;
        }
        $card_response_arr = json_decode($card_response, true);

        // $parr = array(
        //   'get_details' => $get_details,
        //   'endpoint' => 'https://stagegw.transnox.com/servlets/TransNox_API_Server',
        //   'request' => $card_auth_array,
        //   'response' =>$card_response_arr
        // );
        //print_r($parr);die;

        $cart = WC()->cart->get_cart();
        if($card_response_arr['CardAuthenticationResponse']['responseMessage'] == "Success"){
            foreach( $cart as $cart_item_key => $cart_item ){  
                $product = $cart_item['data'];  
                // echo $product->get_type().'-'.$product->get_name().'<br>'; 
                if($product->get_type() == 'subscription'){

                    $recurring_total = intval($product->get_price()*100);               
                    $TOE = ($card_response_arr['CardAuthenticationResponse']['cardType'] == "M")?'NO_TERMINAL':'OFF_MERCHANT_PREMISES_UNATTENDED';
    
                    $recurring_array = array (
                      'Sale' => 
                      array (
                        'deviceID' => $device_id,
                        'transactionKey' => $transaction_key,
                        'cardDataSource' => 'PHONE',
                        'transactionAmount' => $recurring_total,
                        'cardNumber' => $card_response_arr['CardAuthenticationResponse']['token'],
                        'expirationDate' => str_replace(' ', '', $_POST['WC_Gateway_Globalpay-card-expiry']),
                        'cardOnFileTransactionIdentifier' => $card_response_arr['CardAuthenticationResponse']['cardTransactionIdentifier'],
                        'externalReferenceID' => rand ( 1000 , 9999 ),
                        'cardOnFile' => 'Y',
                        'tokenRequired' => 'Y',
                        'terminalCapability' => 'KEYED_ENTRY_ONLY',
                        'terminalOperatingEnvironment' => 'OFF_MERCHANT_PREMISES_UNATTENDED',
                        'cardholderAuthenticationMethod' => 'NOT_AUTHENTICATED',
                        'terminalAuthenticationCapability' => 'NO_CAPABILITY',
                        'terminalOutputCapability' => 'DISPLAY_ONLY',
                        'maxPinLength' => 'NOT_SUPPORTED',
                        'terminalCardCaptureCapability' => 'NO_CAPABILITY',
                        'cardholderPresentDetail' => 'CARDHOLDER_NOT_PRESENT_RECURRING_TRANSACTION',
                        'cardPresentDetail' => 'CARD_NOT_PRESENT',
                        'cardDataInputMode' => 'MERCHANT_INITIATED_TRANSACTION_CARD_CREDENTIAL_STORED_ON_FILE',
                        'cardholderAuthenticationEntity' => 'NOT_AUTHENTICATED',
                        'cardDataOutputCapability' => 'NONE',
                        'developerID' => '003386G001',
                        'isRecurring' => 'Y',
                      ),
                    );

                    // echo "<pre>";
                    // print_r($recurring_array);
                    // echo "</pre>";
                    // exit();

                    $recurring_curl = curl_init();

                    curl_setopt_array($recurring_curl, array(
                      CURLOPT_URL => "https://stagegw.transnox.com/servlets/TransNox_API_Server",
                      CURLOPT_RETURNTRANSFER => true,
                      CURLOPT_ENCODING => "",
                      CURLOPT_MAXREDIRS => 10,
                      CURLOPT_TIMEOUT => 30,
                      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                      CURLOPT_CUSTOMREQUEST => "POST",
                      CURLOPT_POSTFIELDS => json_encode($recurring_array, true),
                      CURLOPT_HTTPHEADER => array(
                        "cache-control: no-cache",
                        "content-type: application/json",
                        "postman-token: d72932d1-818c-e43d-e511-d0e31d9f2b62"
                      ),
                    ));

                    $recurring_response = curl_exec($recurring_curl);
                    $recurring_err = curl_error($recurring_curl);

                    curl_close($recurring_curl);

                    if ($recurring_err) {
                      echo "cURL Error #:" . $recurring_err;
                    } else {                
                        echo $recurring_response;
                    }

                    $recurring_response_arr = json_decode($recurring_response, true);
                    // echo "<pre>";
                    // print_r($recurring_response_arr);
                    // echo "</pre>";
                    // exit();
                    $transactionid = $recurring_response_arr['SaleResponse']['transactionID']; 
                    $_SESSION['transactionID'] = $transactionid;

                    if($card_response_arr['CardAuthenticationResponse']['cardType'] == "X" && $recurring_total == '35.00'){
                        $recurring_full_void = array (
                          'Void' => 
                          array (
                            'deviceID' => $device_id,
                            'transactionKey' => $transaction_key,
                            'transactionID' => $transactionid,
                            'developerID' => '003386G001',
                            'voidReason' => 'POST_AUTH_USER_DECLINE',
                          ),
                        );

                        $recurring_fullvoid_curl = curl_init();

                        curl_setopt_array($recurring_fullvoid_curl, array(
                          CURLOPT_URL => "https://stagegw.transnox.com/servlets/TransNox_API_Server",
                          CURLOPT_RETURNTRANSFER => true,
                          CURLOPT_ENCODING => "",
                          CURLOPT_MAXREDIRS => 10,
                          CURLOPT_TIMEOUT => 30,
                          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                          CURLOPT_CUSTOMREQUEST => "POST",
                          CURLOPT_POSTFIELDS => json_encode($recurring_full_void),
                          CURLOPT_HTTPHEADER => array(
                            "cache-control: no-cache",
                            "content-type: application/json",
                            "postman-token: 3e4f4986-526c-cffa-17cd-4d74e7de7aaf"
                          ),
                        ));

                        $recurring_fullvoid_response = curl_exec($recurring_fullvoid_curl);
                        $recurring_fullvoid_err = curl_error($recurring_fullvoid_curl);

                        curl_close($recurring_fullvoid_curl);

                        if ($recurring_fullvoid_err) {
                          echo "cURL Error #:" . $recurring_fullvoid_err;
                        } else {
                          echo $recurring_fullvoid_response;
                        }
                    }
                }else{
                    // $order = wc_get_order( $order_id );
                    // if($product->get_price() == '17.35' || $product->get_price() == '34.13' || $product->get_price() == '39.45'){
                    //     $item_total = $product->get_price();
                    // }else{
                        $item_total = intval($product->get_price()*100);
                    // }

                    $sale_array = array (
                      'Sale' => 
                      array (
                        'deviceID' => $device_id,
                        'transactionKey' => $transaction_key,
                        'cardDataSource' => str_replace(' ', '', $_POST['WC_Gateway_Globalpay-card-datasource']),
                        'transactionAmount' => $item_total,
                        'cardNumber' => $card_response_arr['CardAuthenticationResponse']['token'],
                        'expirationDate' => str_replace(' ', '', $_POST['WC_Gateway_Globalpay-card-expiry']),
                        'cvv2' => $_POST['WC_Gateway_Globalpay-card-cvc'],
                        'externalReferenceID' => rand ( 1000 , 9999 ),
                        'tokenRequired' => 'Y',
                        'terminalCapability' => 'KEYED_ENTRY_ONLY',
                        'terminalOperatingEnvironment' => 'OFF_MERCHANT_PREMISES_UNATTENDED',
                        'cardholderAuthenticationMethod' => 'NOT_AUTHENTICATED',
                        'terminalAuthenticationCapability' => 'NO_CAPABILITY',
                        'terminalOutputCapability' => 'DISPLAY_ONLY',
                        'maxPinLength' => 'NOT_SUPPORTED',
                        'terminalCardCaptureCapability' => 'NO_CAPABILITY',
                        'cardholderPresentDetail' => 'CARDHOLDER_NOT_PRESENT_ELECTRONIC_COMMERCE',
                        'cardPresentDetail' => 'CARD_NOT_PRESENT',
                        'cardDataInputMode' => 'ELECTRONIC_COMMERCE_NO_SECURITY_CHANNEL_ENCRYPTED_SET_WITHOUT_CARDHOLDER_CERTIFICATE',
                        'cardholderAuthenticationEntity' => 'NOT_AUTHENTICATED',
                        'cardDataOutputCapability' => 'NONE',
                        'developerID' => '003386G001',
                      ),
                    );

                    // echo "<pre>";
                    // print_r($sale_array);
                    // echo "</pre>";
                    // die;

                    $curl = curl_init();

                    curl_setopt_array($curl, array(
                      CURLOPT_URL => "https://stagegw.transnox.com/servlets/TransNox_API_Server",
                      CURLOPT_RETURNTRANSFER => true,
                      CURLOPT_ENCODING => "",
                      CURLOPT_MAXREDIRS => 10,
                      CURLOPT_TIMEOUT => 30,
                      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                      CURLOPT_CUSTOMREQUEST => "POST",
                      CURLOPT_POSTFIELDS => json_encode($sale_array),
                      CURLOPT_HTTPHEADER => array(
                        "cache-control: no-cache",
                        "content-type: application/json",
                        "postman-token: d72932d1-818c-e43d-e511-d0e31d9f2b62"
                      ),
                    ));

                    $response = curl_exec($curl);
                    $err = curl_error($curl);

                    curl_close($curl);

                    if ($err) {
                      echo "cURL Error #:" . $err;
                    } else {                
                        echo $response;
                    }

                    $response_arr = json_decode($response, true);
                    // echo "<pre>";
                    // print_r($response_arr);
                    // echo "</pre>";
                    // exit();
                    $transactionid = $response_arr['SaleResponse']['transactionID']; 
                    $_SESSION['transactionID'] = $transactionid;

                    if($card_response_arr['CardAuthenticationResponse']['cardType'] == "X" && $item_total == '400'){
                        $full_void = array (
                          'Void' => 
                          array (
                            'deviceID' => $device_id,
                            'transactionKey' => $transaction_key,
                            'transactionID' => $transactionid,
                            'developerID' => '003386G001',
                            'voidReason' => 'POST_AUTH_USER_DECLINE',
                          ),
                        );

                        $fullvoid_curl = curl_init();

                        curl_setopt_array($fullvoid_curl, array(
                          CURLOPT_URL => "https://stagegw.transnox.com/servlets/TransNox_API_Server",
                          CURLOPT_RETURNTRANSFER => true,
                          CURLOPT_ENCODING => "",
                          CURLOPT_MAXREDIRS => 10,
                          CURLOPT_TIMEOUT => 30,
                          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                          CURLOPT_CUSTOMREQUEST => "POST",
                          CURLOPT_POSTFIELDS => json_encode($full_void),
                          CURLOPT_HTTPHEADER => array(
                            "cache-control: no-cache",
                            "content-type: application/json",
                            "postman-token: 3e4f4986-526c-cffa-17cd-4d74e7de7aaf"
                          ),
                        ));

                        $fullvoid_response = curl_exec($fullvoid_curl);
                        $fullvoid_err = curl_error($fullvoid_curl);

                        curl_close($fullvoid_curl);

                        if ($fullvoid_err) {
                          echo "cURL Error #:" . $fullvoid_err;
                        } else {
                          echo $fullvoid_response;
                        }
                    }elseif($card_response_arr['CardAuthenticationResponse']['cardType'] == "M" && $item_total == '1500'){
                        $pvoid_array = array (
                          'Void' => 
                          array (
                            'deviceID' => $device_id,
                            'transactionKey' => $transaction_key,
                            'transactionAmount' => '5.00',
                            'transactionID' => $transactionid,
                            'operatorID' => 'OP1',
                            'tokenRequired' => 'Y',
                            'developerID' => '003386G001',
                            'voidReason' => 'POST_AUTH_USER_DECLINE',
                            'laneID' => '00000000',
                          ),
                        );

                        $pvoid_curl = curl_init();

                        curl_setopt_array($pvoid_curl, array(
                          CURLOPT_URL => "https://stagegw.transnox.com/servlets/TransNox_API_Server",
                          CURLOPT_RETURNTRANSFER => true,
                          CURLOPT_ENCODING => "",
                          CURLOPT_MAXREDIRS => 10,
                          CURLOPT_TIMEOUT => 30,
                          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                          CURLOPT_CUSTOMREQUEST => "POST",
                          CURLOPT_POSTFIELDS => json_encode($pvoid_array),
                          CURLOPT_HTTPHEADER => array(
                            "cache-control: no-cache",
                            "content-type: application/json",
                            "postman-token: 9d511432-9744-6d89-84a5-a34ec732b4cd"
                          ),
                        ));

                        $pvoid_response = curl_exec($pvoid_curl);
                        $pvoid_err = curl_error($pvoid_curl);

                        curl_close($pvoid_curl);
                    }
                }
            }
        }else{
            wc_add_notice( __( 'Card authentication failed. Please check your card details. Try again!' ), 'error' );
        }
    }
}

/**
 * Update the order meta with field value
 */
add_action( 'woocommerce_checkout_update_order_meta', 'custom_payment_update_order_meta' );
function custom_payment_update_order_meta( $order_id ) {
    session_start();
    if($_POST['payment_method'] != 'tsys')
        return;

    // echo "<pre>";
    // print_r($_POST);
    // echo "</pre>";
    // exit();
    if(!empty($_SESSION['transactionID'])){
        $order = wc_get_order( $order_id );
        $order->payment_complete();
    }
    update_post_meta( $order_id, 'globalpay_transactionid', $_SESSION['transactionID'] );
    update_post_meta( $order_id, 'globalpay_card_datasource', str_replace(' ', '', $_POST['WC_Gateway_Globalpay-card-datasource']) );
    update_post_meta( $order_id, 'globalpay_card_number', str_replace(' ', '', $_POST['WC_Gateway_Globalpay-card-number']) );
    update_post_meta( $order_id, 'globalpay_card_expiry', str_replace(' ', '', $_POST['WC_Gateway_Globalpay-card-expiry']) );
    update_post_meta( $order_id, 'globalpay_card_cvc', $_POST['WC_Gateway_Globalpay-card-cvc'] );
}

/**
 * Display field value on the order edit page
 */
add_action( 'woocommerce_admin_order_data_after_billing_address', 'custom_checkout_field_display_admin_order_meta', 10, 1 );
function custom_checkout_field_display_admin_order_meta($order){
    $method = get_post_meta( $order->id, '_payment_method', true );
    if($method != 'tsys')
        return;

    $card_data_source = get_post_meta( $order->id, 'globalpay_card_datasource', true );
    $trn_id = get_post_meta( $order->id, 'globalpay_transactionid', true );
    $card_number = get_post_meta( $order->id, 'globalpay_card_number', true );
    $card_expiry = get_post_meta( $order->id, 'globalpay_card_expiry', true );
    $card_cvc = get_post_meta( $order->id, 'globalpay_card_cvc', true );

    echo '<p><strong>'.__( 'Card Data Source' ).':</strong> ' . $card_data_source . '</p>';
    echo '<p><strong>'.__( 'TransactionID' ).':</strong> ' . $trn_id . '</p>';
    echo '<p><strong>'.__( 'Card Number' ).':</strong> ' . $card_number . '</p>';
    echo '<p><strong>'.__( 'Card Expiry').':</strong> ' . $card_expiry . '</p>';
    echo '<p><strong>'.__( 'Card cvc').':</strong> ' . $card_cvc . '</p>';
}


add_action( 'woocommerce_order_partially_refunded', 'action_function_name_refunded', 10, 2 );
function action_function_name_refunded( $order_id, $refund_id ){
    $get_details = get_option('woocommerce_custom_settings');
    
    $device_id = $get_details['device_id'];
    $transaction_key = $get_details['transaction_key'];
    $card_data_source = get_post_meta( $order_id, 'globalpay_card_datasource', true );
    $trn_id = get_post_meta( $order_id, 'globalpay_transactionid', true );
    $card_number = get_post_meta( $order_id, 'globalpay_card_number', true );
    $card_expiry = get_post_meta( $order_id, 'globalpay_card_expiry', true );
    $card_cvc = get_post_meta( $order_id, 'globalpay_card_cvc', true );

    $order = wc_get_order( $order_id );
    $order_refunds = $order->get_refunds();
    $refund_amount = $order_refunds[0]->get_amount();
    // echo "<pre>";
    // print_r($order_refunds[0]->get_amount());
    // echo "</pre>";
    // exit();

    if(!empty($trn_id)){
        // $return_array = array (
        //   'Return' => 
        //   array (
        //     'deviceID' => $device_id,
        //     'transactionKey' => $transaction_key,
        //     'transactionID' => $trn_id
        //   ),
        // );

        $return_array = array (
          'Return' => 
          array (
            'deviceID' => $device_id,
            'transactionKey' => $transaction_key,
            'cardDataSource' => 'MANUAL',
            'transactionAmount' => ''.$refund_amount.'',
            'currencyCode' => 'USD',
            'cardNumber' => $card_number,
            'expirationDate' => $card_expiry,
            'cvv2' => $card_cvc,
            'terminalCapability' => 'MAGSTRIPE_CONTACTLESS_ONLY',
            'terminalOperatingEnvironment' => 'OFF_MERCHANT_PREMISES_MPOS',
            'cardholderAuthenticationMethod' => 'MANUAL_SIGNATURE',
            'terminalAuthenticationCapability' => 'SIGNATURE_ANALYSIS',
            'terminalOutputCapability' => 'DISPLAY_ONLY',
            'maxPinLength' => 'UNKNOWN',
            'terminalCardCaptureCapability' => 'CARD_CAPTURE_CAPABILITY',
            'cardholderPresentDetail' => 'CARDHOLDER_NOT_PRESENT_PHONE_TRANSACTION',
            'cardPresentDetail' => 'TRANSPONDER_AMEX',
            'cardDataInputMode' => 'KEY_ENTERED_INPUT',
            'cardholderAuthenticationEntity' => 'MERCHANT_CARD_ACCEPTOR_SIGNATURE',
            'cardDataOutputCapability' => 'MAGNETIC_STRIPE_WRITE',
            'tokenRequired' => 'Y',
          ),
        );

        $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => "https://stagegw.transnox.com/servlets/TransNox_API_Server",
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "POST",
          CURLOPT_POSTFIELDS => json_encode($return_array),
          CURLOPT_HTTPHEADER => array(
            "cache-control: no-cache",
            "content-type: application/json",
            "postman-token: 9d511432-9744-6d89-84a5-a34ec732b4cd"
          ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
          echo "cURL Error #:" . $err;
        } else {
            $order = wc_get_order( $order_id );
            $order->update_status( 'wc-partial-payment' );
        }
    }
}

add_action( 'woocommerce_order_fully_refunded', 'action_function_name_fullyrefunded', 10, 2 );
function action_function_name_fullyrefunded( $order_id, $refund_id ){
    $get_details = get_option('woocommerce_custom_settings');
    
    $device_id = $get_details['device_id'];
    $transaction_key = $get_details['transaction_key'];
    $card_data_source = get_post_meta( $order_id, 'globalpay_card_datasource', true );
    $trn_id = get_post_meta( $order_id, 'globalpay_transactionid', true );
    $card_number = get_post_meta( $order_id, 'globalpay_card_number', true );
    $card_expiry = get_post_meta( $order_id, 'globalpay_card_expiry', true );
    $card_cvc = get_post_meta( $order_id, 'globalpay_card_cvc', true );

    $order = wc_get_order( $order_id );
    $order_refunds = $order->get_refunds();
    $refund_amount = $order_refunds[0]->get_amount();
    // echo "<pre>";
    // print_r($order_refunds[0]->get_amount());
    // echo "</pre>";
    // exit();

    if(!empty($trn_id)){
        $return_array = array (
          'Return' => 
          array (
            'deviceID' => $device_id,
            'transactionKey' => $transaction_key,
            'transactionID' => $trn_id
          ),
        );

        $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => "https://stagegw.transnox.com/servlets/TransNox_API_Server",
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "POST",
          CURLOPT_POSTFIELDS => json_encode($return_array),
          CURLOPT_HTTPHEADER => array(
            "cache-control: no-cache",
            "content-type: application/json",
            "postman-token: 9d511432-9744-6d89-84a5-a34ec732b4cd"
          ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
          echo "cURL Error #:" . $err;
        } else {
            $order = wc_get_order( $order_id );
            $order->update_status( 'wc-refunded' );
        }
    }
}

add_filter( 'woocommerce_valid_order_statuses_for_cancel', 'custom_valid_order_statuses_for_cancel', 10, 2 );
function custom_valid_order_statuses_for_cancel( $statuses, $order ){

    // Set HERE the order statuses where you want the cancel button to appear
    $custom_statuses    = array( 'completed', 'pending', 'processing', 'on-hold', 'failed' );

    // Set HERE the delay (in days)
    $duration = 3; // 3 days

    // UPDATE: Get the order ID and the WC_Order object
    if( isset($_GET['order_id']))
        $order = wc_get_order( absint( $_GET['order_id'] ) );

    $delay = $duration*24*60*60; // (duration in seconds)
    $date_created_time  = strtotime($order->get_date_created()); // Creation date time stamp
    $date_modified_time = strtotime($order->get_date_modified()); // Modified date time stamp
    $now = strtotime("now"); // Now  time stamp

    // Using Creation date time stamp
    if ( ( $date_created_time + $delay ) >= $now ) return $custom_statuses;
    else return $statuses;
}

function action_woocommerce_cancelled_order( $order_id ) { 
    $get_details = get_option('woocommerce_custom_settings');
    
    $device_id = $get_details['device_id'];
    $transaction_key = $get_details['transaction_key'];
    $trn_id = get_post_meta( $order_id, 'globalpay_transactionid', true );
    // $card_number = get_post_meta( $order_id, 'globalpay_card_number', true );
    // $card_expiry = get_post_meta( $order_id, 'globalpay_card_expiry', true );
    // $card_cvc = get_post_meta( $order_id, 'globalpay_card_cvc', true );

    // $order = wc_get_order( $order_id );
    // $order_refunds = $order->get_refunds();
    // $refund_amount = $order_refunds[0]->get_amount();
    // echo "<pre>";
    // print_r($order_refunds[0]->get_amount());
    // echo "</pre>";
    // exit();

    if(!empty($trn_id)){
        $return_array = array (
          'Return' => 
          array (
            'deviceID' => $device_id,
            'transactionKey' => $transaction_key,
            'transactionID' => $trn_id
          ),
        );

        $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => "https://stagegw.transnox.com/servlets/TransNox_API_Server",
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "POST",
          CURLOPT_POSTFIELDS => json_encode($return_array),
          CURLOPT_HTTPHEADER => array(
            "cache-control: no-cache",
            "content-type: application/json",
            "postman-token: 9d511432-9744-6d89-84a5-a34ec732b4cd"
          ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
          echo "cURL Error #:" . $err;
        } else {
            $order = wc_get_order( $order_id );
            $order->update_status( 'wc-cancelled' );
        }
    }
}; 
         
// add the action 
add_action( 'woocommerce_cancelled_order', 'action_woocommerce_cancelled_order', 10, 1 );

add_filter( 'cron_schedules', 'weavers_add_every_sixty_minutes' );
function weavers_add_every_sixty_minutes( $schedules ) {
    $schedules['every_one_hour'] = array(
            'interval'  => 3600,
            'display'   => __( 'Every 1 hour', 'textdomain' )
    );
    return $schedules;
}

// Schedule an action if it's not already scheduled
if ( ! wp_next_scheduled( 'weavers_add_every_sixty_minutes' ) ) {
    wp_schedule_event( time(), 'every_one_hour', 'weavers_add_every_sixty_minutes' );
}

// Hook into that action that'll fire every three minutes
add_action( 'weavers_add_every_sixty_minutes', 'every_one_hour_event_func' );
function every_one_hour_event_func() {
    /*Batch Close*/
    $get_details = get_option('woocommerce_custom_settings');
    $device_id = $get_details['device_id'];
    $transaction_key = $get_details['transaction_key'];
    $operating_userid = $get_details['operating_userid'];
    $batch_array = array (
      'BatchClose' => 
      array (
        'deviceID' => $device_id,
        'transactionKey' => $transaction_key,
        'operatingUserID' => $operating_userid,
        'batchCloseParameter' => 
        array (
          'deviceID' => $device_id
        ),
      ),
    );
    // echo "<pre>";
    // print_r($batch_array);
    // echo "</pre>";
    // exit();
    $batch_curl = curl_init();

    curl_setopt_array($batch_curl, array(
      CURLOPT_URL => "https://stagegw.transnox.com/servlets/TransNox_API_Server",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS => json_encode($batch_array),
      CURLOPT_HTTPHEADER => array(
        "cache-control: no-cache",
        "content-type: application/json",
        "postman-token: 02344c15-c060-b37e-e0b4-3d8318d13742"
      ),
    ));

    $batch_response = curl_exec($batch_curl);
    $batch_err = curl_error($batch_curl);

    curl_close($batch_curl);

    if ($batch_err) {
      echo "cURL Error #:" . $batch_err;
    } else {
      echo $batch_response;
    }
}

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
// function run_tsys_payment() {

// 	$plugin = new Tsys_Payment();
// 	$plugin->run();

// }
// run_tsys_payment();
