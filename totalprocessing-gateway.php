<?php
/*
 * Plugin Name: Total Processing Gateway for Woocommerce
 * Plugin URI: https://www.totalprocessing.com
 * Description: Take credit card payments on your store.
 * Author: Total Processing
 * Author URI: https://www.totalprocessing.com
 * Version: 2.1.1
 *
 */

/*
 * This action hook registers our PHP class as a WooCommerce payment gateway
 */
add_filter('woocommerce_payment_gateways', 'totalproc_add_gateway_class');
function totalproc_add_gateway_class($gateways)
{
    $gateways[] = 'WC_TotalProcessing_Gateway'; // your class name is here
    return $gateways;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'totalproc_add_plugin_action_links');
/**
 * Add plugin action links
 * @param array $links
 * @return array
 */
function totalproc_add_plugin_action_links(array $links)
{
    $action_links = array(
        '<a href="' . esc_url(admin_url('admin.php?page=wc-settings&tab=checkout&section=totalprocessing')) . '">Settings</a>',
    );

    return array_merge($action_links, $links);
}

// display the extra data in the order admin panel
function totalproc_display_order_data_in_admin($order)
{  ?>
    <div class="order_data_column">
        <h4><?php _e('TP Gateway Details', 'woocommerce'); ?></h4>
        <?php
        echo '<p><strong>' . __('Transaction ID') . ':</strong>' . get_post_meta($order->get_id(), '_opp_id', true) . '</p>';
        echo '<p><strong>' . __('Payment Type') . ':</strong>' . get_post_meta($order->get_id(), '_opp_paymentType', true) . '</p>';
        echo '<p><strong>' . __('Currency') . ':</strong>' . get_post_meta($order->get_id(), '_opp_currency', true) . '</p>';
        echo '<p><strong>' . __('Entity ID') . ':</strong>' . get_post_meta($order->get_id(), '_opp_entityId', true) . '</p>'; ?>
    </div>
    <?php }

add_action('woocommerce_admin_order_data_after_order_details', 'totalproc_display_order_data_in_admin');

/*
 * The class itself, please note that it is inside plugins_loaded action hook
 */
add_action('plugins_loaded', 'totalproc_init_gateway_class');
function totalproc_init_gateway_class()
{

    class WC_TotalProcessing_Gateway extends WC_Payment_Gateway
    {

        public $version = '2.1.1';

        public function __construct()
        {

            // Don't load for change payment method page.
            if (isset($_GET['change_payment_method'])) {
                return;
            }


            $this->id = 'totalprocessing'; // payment gateway plugin ID
            $this->icon = ''; // URL of the icon that will be displayed on checkout page near your gateway name
            $this->has_fields = true; // in case you need a custom credit card form
            $this->method_title   = __('Total Processing Gateway', 'total-processing-for-woocommerce');
            $this->method_description = __('Take payments over the Total Processing payment gateway.'); // will be displayed on the options page
            // gateways can support subscriptions, refunds, saved payment methods,
            // but in this tutorial we begin with simple payments
            $this->supports = array(
                'products',
                'refunds',
                'tokenization',
                'add_payment_method',
                'subscriptions',
                'subscription_cancellation',
                'subscription_suspension',
                'subscription_reactivation',
                'subscription_amount_changes',
                'subscription_date_changes',
                'subscription_payment_method_change',
                'subscription_payment_method_change_customer',
                'subscription_payment_method_change_admin',
                'multiple_subscriptions',
                'pre-orders',
            );
            // Method with all the options fields
            $this->init_form_fields();
            // Load the settings.
            $this->init_settings();
            $this->title = $this->get_option('title');
            $this->description = $this->get_option('description');
            $this->enabled = $this->get_option('enabled');
            $this->custom_tp_css = $this->get_option('custom_tp_css');
            $this->OPP_debug = false;
            $this->pay_btn = $this->get_option('pay_btn');
            $this->processing_btn = $this->get_option('processing_btn');
            $this->action_btn = $this->get_option('action_btn');
            $this->completion_text = $this->get_option('completion_text');
            $this->fail_text = $this->get_option('fail_text');
            $this->fail_message_body = $this->get_option('fail_message_body');
            $this->success_message_body = $this->get_option('success_message_body');
            $this->hide_text_box = $this->get_option('hide_text_box');
            $this->OPP_endPoint = $this->get_option('OPP_mode');
            $this->OPP_successCodes = $this->get_option('OPP_successCodes');
            $this->totalproc_gateway_success_codes();
            $this->OPP_holdCodes = $this->get_option('OPP_holdCodes');
            $this->totalproc_gateway_hold_codes();
            $this->OPP_risk = $this->get_option('OPP_risk');
            $this->OPP_holdScore = $this->get_option('OPP_holdScore');
            $this->OPP_accessToken = $this->get_option('OPP_accessToken');
            $this->OPP_entityId = $this->get_option('OPP_entityId');
            $this->OPP_schemes = $this->get_option('OPP_schemes');
            $this->OPP_schemeCheckout = implode(' ', $this->get_option('OPP_schemes'));
            $this->OPP_cvv = $this->get_option('OPP_cvv');
            $this->OPP_3d = $this->get_option('OPP_3d');
            $this->OPP_entity3d = $this->get_option('OPP_entity3d');
            $this->OPP_rg = $this->get_option('OPP_rg');
            $this->OPP_googlePay = $this->get_option('OPP_googlePay');
            $this->OPP_gpEntityId = $this->get_option('OPP_gpEntityId');
            $this->OPP_orderButtonText = $this->get_option('OPP_orderButtonText');
            $this->OPP_pleasePayMessage = $this->get_option('OPP_pleasePayMessage');
            $this->OPP_sendConfirmationNote = $this->get_option('OPP_sendConfirmationNote');
            $this->OPP_paymentFormStyling = $this->get_option('OPP_paymentFormStyling');
            $this->OPP_iosInputFix = $this->get_option('OPP_iosInputFix');
            $this->OPP_useCustomIframeStyling = $this->get_option('OPP_useCustomIframeStyling');
            $this->OPP_iframeStyling = $this->get_option('OPP_iframeStyling');
            // $this->OPP_subscriptions = $this->get_option('OPP_subscriptions');
            // $this->OPP_entityScheduler = $this->get_option('OPP_entityScheduler');
            $this->totalproc_gateway_decline_codes();
            $this->OPP_targetEntityId = $this->OPP_entityId;
            $this->renderGooglePay = false;

            if ($this->OPP_3d === 'yes') {
                $this->OPP_targetEntityId = $this->OPP_entity3d;
            }

            if ($this->OPP_googlePay === 'yes' && $this->OPP_gpEntityId) {
                $this->OPP_schemeCheckout .= ' GOOGLEPAY';
                $this->renderGooglePay = true;
            }

            // This action hook saves the settings
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
            // We need custom JavaScript to obtain a token
            add_action('wp_enqueue_scripts', array($this, 'totalproc_payment_scripts'));
            // register a webhook here
            add_filter('woocommerce_available_payment_gateways', array($this, 'totalproc_prepare_order_pay_page'));
        }

        /**
         * Log messages to WooCommerce log
         *
         * @param mixed $message
         */
        public static function totalproc_debug($message)
        {
            // Convert message to string
            if (!is_string($message)) {
                $message = (version_compare(WC_VERSION, '3.0', '<')) ? print_r($message, true) : wc_print_r($message, true);
            }

            if (version_compare(WC_VERSION, '3.0', '<')) {

                static $logger;

                if (empty($logger)) {
                    $logger = new WC_Logger();
                }

                $logger->add(date('Y-m-d'), $message);
            } else {

                $logger = wc_get_logger();

                $context = array('source' => date('Y-m-d'));

                $logger->debug($message, $context);
            }
        }

        public function init_form_fields()
        {

            $defaultStyling = <<<EOT
            {
                "card-number-placeholder" : {
                    "color" : "#ff0000",
                    "font-size" : "16px",
                    "font-family" : "monospace"
                },
                "cvv-placeholder" : {
                    "color" : "#0000ff",
                    "font-size" : "16px",
                    "font-family" : "Arial"
                }
            }
EOT;

            $this->form_fields = array(
                'enabled' => array(
                    'title'       => 'Enable/Disable',
                    'label'       => 'Enable Total Processing Gateway',
                    'type'        => 'checkbox',
                    'description' => '',
                    'default'     => 'no'
                ),
                'title' => array(
                    'title'       => 'Title',
                    'type'        => 'text',
                    'description' => 'This controls the title which the user sees during checkout.',
                    'default'     => 'Credit/Debit Card',
                    'desc_tip'    => true,
                ),
                'description' => array(
                    'title'       => 'Description',
                    'type'        => 'textarea',
                    'description' => 'This controls the description which the user sees during checkout.',
                    'default'     => 'Pay securely with your Credit/Debit card',
                ),
                'OPP_mode' => array(
                    'title'         => 'Endpoint Mode',
                    'type'             => 'select',
                    'default'         => 'Live',
                    'options' => array(
                        'https://oppwa.com' => 'Live',
                        'https://test.oppwa.com' => 'Test'
                    )
                ),
                'OPP_successCodes' => array(
                    'title'         => 'Success Codes',
                    'type'             => 'text',
                    'description'     => 'Positive Response Codes (these will trigger completed. Separate with comma)',
                    'default'        => '000.000.000,000.100.110',
                    'desc_tip'        => true
                ),
                'OPP_accessToken' => array(
                    'title'         => 'Access Token',
                    'type'             => 'text',
                    'description'     => 'Access Token from BIP (Merchant level)',
                    'default'        => '',
                    'desc_tip'        => true
                ),
                'OPP_entityId' => array(
                    'title'         => 'Entity Id',
                    'type'             => 'text',
                    'description'     => 'Entity Id (Channel level)',
                    'default'        => '',
                    'desc_tip'        => true
                ),
                'OPP_schemes' => array(
                    'title'  => 'Card Schemes Enabled',
                    'type' => 'multiselect',
                    'class' => 'wc-enhanced-select',
                    'default' => array(
                        'VISA',
                        'MASTER'
                    ),
                    'options' => array(
                        'AMEX' => 'AMEX',
                        'MASTER' => 'MASTER',
                        'VISA' => 'VISA'
                    )
                ),
                'OPP_3d' => array(
                    'title'         => '3d Secured?',
                    'type'             => 'checkbox',
                    'label'         => '3d Secure Enabled',
                    'default'         => 'no',
                    'description'     => '3d secure processing for card schemes.'
                ),
                'OPP_entity3d' => array(
                    'title'         => '3d Entity Id',
                    'type'             => 'text',
                    'description'     => '3d Entity Id (Channel level)',
                    'default'        => '',
                    'desc_tip'        => true
                ),
                'OPP_rg' => array(
                    'title'         => 'Create Registration',
                    'type'             => 'checkbox',
                    'label'         => 'Create RG',
                    'default'         => 'yes',
                    'description'     => 'Tokenise the card upon successful payment and enable one click checkout.'
                ),
                'OPP_googlePay' => array(
                    'title'         => 'Allow Google Pay',
                    'type'             => 'checkbox',
                    'label'         => 'Google Pay',
                    'default'         => 'no',
                    'description'     => 'Allow payments via Google Pay'
                ),
                'OPP_gpEntityId' => array(
                    'title'         => 'Google Pay Entity ID',
                    'type'             => 'text',
                    'default'         => '',
                    'description'     => 'Google Pay Entity ID provided to you by Total Processing'
                ),
                'OPP_orderButtonText' => array(
                    'title'         => 'Custom "Place order" button text',
                    'type'             => 'text',
                    'default'         => 'Proceed to payment',
                    'description'     => 'Changes the "Place order" button text'
                ),
                'OPP_pleasePayMessage' => array(
                    'title'         => 'Please Pay Message',
                    'type'             => 'textarea',
                    'default'         => 'Your order has been created, please make your payment below.',
                    'description'     => 'The message that displays above the payment form'
                ),
                'OPP_sendConfirmationNote' => array(
                    'title'         => 'Send payment confirmation note to customer',
                    'type'             => 'checkbox',
                    'default'         => 'yes',
                    'description'     => 'When payment is confirmed this decides whether the note should be a customer note (checked) or a private one (unchecked)',
                    'desc_tip'        => true
                ),
                'OPP_paymentFormStyling' => array(
                    'title'         => 'Payment form styling',
                    'type'             => 'select',
                    'default'         => 'Card',
                    'description'     => 'Please refer to https://totalprocessing.docs.oppwa.com/tutorials/integration-guide/customisation',
                    'options' => array(
                        'card' => 'Card',
                        'plain' => 'Plain'
                    )
                ),
                'OPP_iosInputFix' => array(
                    'title'         => 'Apply iOS input styling fix',
                    'type'             => 'checkbox',
                    'default'         => 'yes',
                    'description'     => 'Fixes a known issue in which the card number and CVV get cut off on iOS devices, you may want to disable this if using your own custom styling',
                ),
                'OPP_useCustomIframeStyling' => array(
                    'title'         => 'Use custom iFrame styling',
                    'type'             => 'checkbox',
                    'default'         => 'no',
                    'description'     => 'Activates the below custom iFrame styling',
                ),
                'OPP_iframeStyling' => array(
                    'title'         => 'Custom iFrame styling',
                    'type'             => 'textarea',
                    'default'         => $defaultStyling,
                    'description'     => 'Example provided, please refer to https://totalprocessing.docs.oppwa.com/tutorials/integration-guide/customisation and remember to use double quotes only',
                )
            );
        }

        public function totalproc_gateway_success_codes()
        {

            return explode(',', $this->OPP_successCodes);
        }

        public function totalproc_gateway_hold_codes()
        {

            return explode(',', $this->OPP_holdCodes);
        }

        public function totalproc_gateway_decline_codes()
        {

            $array = [
                "800.100.151" => "Card number invalid, please check and try again",
                "800.100.153" => "Card Security code CVV is incorrect",
                "800.100.157" => "Card expiry date is inaccurate",
                "600.200.500" => "Unspported payment method, Visa and Mastercard is accepted",
                "100.100.101" => "Card number incorrect",
                "100.100.200" => "Card Expiry month is required",
                "100.100.201" => "Invalid expiry month",
                "100.100.300" => "Please supply card expiry year",
                "100.100.301" => "Invalid expiry year",
                "100.100.303" => "Card has expired",
                "100.100.304" => "Card not yet valid",
                "100.100.305" => "Invalid expiry date format",
                "800.400.100" => "Billing address check failed, please use correct card billing address",
                "800.400.101" => "Mismatch of street, please use correct card billing address",
                "800.400.102" => "Mismatch of street, please use correct card billing address",
                "800.400.103" => "Mismatch of PO box, please use correct card billing address",
                "800.400.104" => "Mismatch of zip code, please use correct card billing address",
                "800.400.110" => "Address verification check failed, please use correct card billing address",
                "800.400.150" => "Order geo-location risk are you using a proxy to connect",
                "800.400.151" => "Order geo-location risk are you using a proxy to connect"
            ];

            return $array;
        }

        public function get_icon()
        {

            $icons_str = '';

            if (in_array('AMEX', $this->OPP_schemes)) {
                $icons_str .= '<img src="' . esc_url(plugins_url('assets/images', __FILE__)) . '/amex.svg' . '" style="padding-right:0.75rem;" alt="American Express" />';
            }
            if (in_array('MASTER', $this->OPP_schemes)) {
                $icons_str .= '<img src="' . esc_url(plugins_url('assets/images', __FILE__)) . '/mastercard.svg' . '" style="padding-left:0.75rem; max-height:20px;" alt="Mastercard" />';
            }
            if (in_array('VISA', $this->OPP_schemes)) {
                $icons_str .= '<img src="' . esc_url(plugins_url('assets/images', __FILE__)) . '/visa.svg' . '" style="padding-left:0.75rem; max-width:52px; margin-top:1px;" alt="Visa" />';
            }

            return apply_filters('woocommerce_gateway_icon', $icons_str, $this->id);
        }

        public function totalproc_payment_scripts()
        {

            // we need JavaScript to process a token only on cart/checkout pages, right?

            if (!is_cart() && !is_checkout()) {
                if (!is_account_page()) {
                    return;
                }
            }

            // if our payment gateway is disabled, we do not have to enqueue JS too
            if ('no' === $this->enabled) {
                return;
            }

            wp_enqueue_script('jquery');
        }

        public function process_payment($order_id)
        {

            global $woocommerce;

            $order = wc_get_order($order_id);

            $redirect_url = add_query_arg('wc-tp-confirmation', 1, $order->get_checkout_payment_url(false));

            return array(
                'result'   => 'success',
                'redirect' => $redirect_url,
            );
        }

        public function process_refund($order_id, $amount = null, $reason = '')
        {

            global $woocommerce;

            $RFArray = ["DB", "CP", "RB"];
            $RVArray = ["PA"];

            $order = wc_get_order($order_id);
            $order_data = $order->get_data();

            if (
                !$order->get_meta('_opp_entityId') &&
                !$order->get_meta('_opp_currency') &&
                !$order->get_meta('_opp_paymentType') &&
                !$order->get_meta('_opp_id')
            ) {
                return new WP_Error('Error', 'Transaction data load error.');
            }
            $fullAmount = number_format($order->get_total(), 2, '.', '');

            $payload = [
                "authentication.userId" => $this->OPP_userId,
                "authentication.password" => $this->OPP_password,
                "authentication.entityId" => $order->get_meta('_opp_entityId'),
                "currency" => $order->get_meta('_opp_currency')
            ];

            if (in_array($order->get_meta('_opp_paymentType'), $RFArray)) {
                if ((float) $amount <= 0) {
                    return new WP_Error('Error', 'Refund requires an amount.');
                } else if ((float) $amount > (float) $fullAmount) {
                    return new WP_Error('Error', 'Refund amount is higher than the original transaction total.');
                } else {
                    $reverseAction = [
                        "amount" => $amount,
                        "paymentType" => "RF"
                    ];
                }
            } else if ($order->get_meta('_opp_paymentType') == 'PA') {
                $reverseAction = [
                    "paymentType" => "RV"
                ];
            } else {

                return new WP_Error('Error', 'Workflow is not supported. Original transaction must be of paymentType PA,DB,CP,RB.');
            }

            $payload = array_merge($payload, $reverseAction);

            $data = http_build_query($payload);

            $parameters = [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->OPP_accessToken
                ],
                'body' => $data
            ];

            $json = wp_remote_post($this->OPP_endPoint . '/v1/payments/' . $order->get_meta('_opp_id'), $parameters);

            $order->add_order_note($json['body'], false);

            if (!is_wp_error($json)) {

                $responseData = json_decode($json['body'], true);

                if (in_array($responseData['result']['code'], $this->totalproc_gateway_success_codes())) {

                    $order->add_order_note('Refund/Reversal completed ref(' . $responseData['id'] . '): ' . $responseData['result']['code'], false);

                    return true;
                } else {

                    $order->add_order_note('Refund/Reversal failed with ' . $responseData['result']['code'] . ': ' . $responseData['result']['description'], false);

                    return new WP_Error('Error', $responseData['result']['code'] . ': ' . $responseData['result']['description']);
                }
            }

            return new WP_Error('Error', 'Communication error.');
        }

        /**
         * Adds in the Total Processing code, ready to process the customers order
         */
        public function totalproc_prepare_order_pay_page($gateways)
        {
            if (is_checkout()) {
                $gateways['totalprocessing']->order_button_text = __(
                    $this->OPP_orderButtonText ? $this->OPP_orderButtonText : 'Proceed to payment',
                    'woocommerce'
                );
            }

            if (!is_wc_endpoint_url('order-pay') || !isset($_GET['wc-tp-confirmation'])) {
                return $gateways;
            }

            if (isset($_GET['wc-tp-check'])) {
                add_action('woocommerce_pay_order_after_submit', array($this, 'totalproc_check_tp_transaction'));
            } else {
                add_action('woocommerce_pay_order_after_submit', array($this, 'totalproc_render_copyandpay_form'));
            }

            add_filter('woocommerce_checkout_show_terms', '__return_false');
            add_filter('woocommerce_pay_order_button_html', '__return_false');
            add_filter('woocommerce_available_payment_gateways', '__return_empty_array');
            add_filter('woocommerce_no_available_payment_methods_message', array($this, 'totalproc_change_no_available_methods_message'));

            return array();
        }

        public function totalproc_change_no_available_methods_message()
        {
            return wpautop(__($this->OPP_pleasePayMessage, 'total-processing-for-woocommerce'));
        }


        public function totalproc_render_copyandpay_form()
        {
            $order = wc_get_order(absint(get_query_var('order-pay')));

            $order_data = $order->get_data();

            $url = "$this->OPP_endPoint/v1/checkouts";

            $tokens = null;

            $registration = false;

            if ($this->OPP_rg === "yes" && is_user_logged_in()) {
                $registration = true;
                $tokens = WC_Payment_Tokens::get_customer_tokens(get_current_user_id(), $this->id);
            }

            $order_data['shipping']['OPP_status'] = false;
            if (!empty($order_data['shipping']['first_name'])) {
                $order_data['shipping']['OPP_status'] = true;
            }
            if (!empty($order_data['shipping']['last_name'])) {
                $order_data['shipping']['OPP_status'] = true;
            }
            if (!empty($order_data['shipping']['company'])) {
                $order_data['shipping']['OPP_status'] = true;
            }
            if (!empty($order_data['shipping']['address_1'])) {
                $order_data['shipping']['OPP_status'] = true;
            }
            if (!empty($order_data['shipping']['address_2'])) {
                $order_data['shipping']['OPP_status'] = true;
            }
            if (!empty($order_data['shipping']['city'])) {
                $order_data['shipping']['OPP_status'] = true;
            }
            if (!empty($order_data['shipping']['state'])) {
                $order_data['shipping']['OPP_status'] = true;
            }
            if (!empty($order_data['shipping']['postcode'])) {
                $order_data['shipping']['OPP_status'] = true;
            }
            if (!empty($order_data['shipping']['country'])) {
                $order_data['shipping']['OPP_status'] = true;
            }

            $payload = [
                "entityId" => $this->OPP_targetEntityId,
                "amount" => $order->get_total(),
                "currency" => $order_data['currency'],
                "paymentType" => 'DB',
                "merchantTransactionId" => $order_data['id'],
                "customer.merchantCustomerId" => $order_data['customer_id'],
                "customParameters[SHOPPER_order_key]" => $order_data['order_key'],
                "customParameters[SHOPPER_cart_hash]" => $order_data['cart_hash'],
                "customer.givenName" => $order_data['billing']['first_name'],
                "customer.surname" => $order_data['billing']['last_name'],
                "customer.mobile" => $order_data['billing']['phone'],
                "customer.email" => $order_data['billing']['email'],
                "customer.ip" => $order_data['customer_ip_address'],
                "customer.browserFingerprint.value" => $order_data['customer_user_agent'],
                "billing.street1" => $order_data['billing']['address_1'],
                "billing.street2" => $order_data['billing']['address_2'],
                "billing.city" => $order_data['billing']['city'],
                "billing.state" => $order_data['billing']['state'],
                "billing.postcode" => $order_data['billing']['postcode'],
                "billing.country" => $order_data['billing']['country']
            ];

            if (is_array($tokens)) {
                $tokens = array_values($tokens);

                foreach ($tokens as $key => $token) {
                    $payload['registrations[' . $key . '].id'] = $token->get_token();
                }
            }

            $order_items = array_values($order->get_items());

            foreach ($order_items as $key => $item) {
                $product = wc_get_product($item['product_id']);

                $payload['cart.items[' . $key . '].name'] = $item['name'];
                $payload['cart.items[' . $key . '].merchantItemId'] = $item['product_id'];
                $payload['cart.items[' . $key . '].quantity'] = $item['quantity'];
                $payload['cart.items[' . $key . '].type'] = $product->get_type();
                $payload['cart.items[' . $key . '].sku'] = $product->get_sku();
                $payload['cart.items[' . $key . '].price'] = number_format($item['total'] / $item['quantity'], 2, '.', '');
                $payload['cart.items[' . $key . '].currency'] = $order_data['currency'];
                $payload['cart.items[' . $key . '].totalAmount'] = number_format($item['total'], 2, '.', '');
                $payload['cart.items[' . $key . '].totalTaxAmount'] = number_format($item['total_tax'], 2, '.', '');
                $payload['cart.items[' . $key . '].shipping'] = number_format($order_data['shipping_total'], 2, '.', '');
                $payload['cart.items[' . $key . '].originalPrice'] = number_format($product->get_price(), 2, '.', '');
                if ($item['subtotal'] != '0' && $item['subtotal'] != '0.00') {
                    $payload['cart.items[' . $key . '].discount'] = number_format(1 - ($item['total'] / $item['subtotal']), 2, '.', '');
                }
            }

            if ($order_data['shipping']['OPP_status'] == true) {

                $shipping = [
                    "shipping.street1" => $order_data['shipping']['address_1'],
                    "shipping.street2" => $order_data['shipping']['address_2'],
                    "shipping.city" => $order_data['shipping']['city'],
                    "shipping.state" => $order_data['shipping']['state'],
                    "shipping.postcode" => $order_data['shipping']['postcode'],
                    "shipping.country" => $order_data['shipping']['country']
                ];
            } else {

                $shipping = [
                    "shipping.street1" => $order_data['billing']['address_1'],
                    "shipping.street2" => $order_data['billing']['address_2'],
                    "shipping.city" => $order_data['billing']['city'],
                    "shipping.state" => $order_data['billing']['state'],
                    "shipping.postcode" => $order_data['billing']['postcode'],
                    "shipping.country" => $order_data['billing']['country']
                ];
            }

            $headers = [
                'Authorization' => 'Bearer ' . $this->OPP_accessToken
            ];

            $args = [
                'headers' => $headers,
                'body' => array_merge($payload, $shipping)
            ];

            $response = wp_remote_post($url, $args);

            $response_body = wp_remote_retrieve_body($response);

            $response_array = json_decode($response_body, true);

            $checkoutId = $response_array['id'];

            $response_code = wp_remote_retrieve_response_code($response);

            if ($response_code !== 200 || $response_array['result']['code'] !== '000.200.100') {
    ?>
                <h1>Failed to render checkout</h1>
                <h1>Error code: <?php esc_html_e($response_array['result']['code']); ?> Message: <?php esc_html_e($response_array['result']['description']); ?></h1>
                <h1>Please check the plugin settings</h1>
            <?php
                return new WP_Error('Error', $response_array['result']['code'] . ': ' . esc_html_e($response_array['result']['description']));
            }

            $redirect_url = add_query_arg('wc-tp-confirmation', 1, $order->get_checkout_payment_url(false));

            $redirect_url = $redirect_url . '&wc-tp-check=1';

            wp_register_script('totalp_woo', plugins_url('/assets/js/totalp-woo.js', __FILE__), array('jquery'), $this->version);

            $data_array = [
                'registration' => $registration,
                'google_pay' => $this->renderGooglePay,
                'gp_entity_id' => $this->OPP_gpEntityId,
                'redirect_url' => esc_url($redirect_url),
                'scheme_checkout' => esc_attr($this->OPP_schemeCheckout),
                'style' => strtolower($this->OPP_paymentFormStyling)
            ];

            if ($this->OPP_useCustomIframeStyling == 'yes') {
                $data_array['custom_iframe_styling'] = $this->OPP_iframeStyling;
            }

            if ($this->OPP_iosInputFix == 'yes') {
                wp_register_style('totalp_ios_fix', plugins_url('/assets/css/totalp-ios-fix.css', __FILE__), [], $this->version);
                wp_enqueue_style('totalp_ios_fix');
            }

            wp_localize_script('totalp_woo', 'tp_data', $data_array);
            wp_enqueue_script('totalp_woo');
            wp_enqueue_script('opp-payment-widgets', $this->OPP_endPoint . '/v1/paymentWidgets.js?checkoutId=' . $checkoutId, [], null, false);
        }

        public function totalproc_check_tp_transaction()
        {
            global $woocommerce;

            $resource_path = sanitize_text_field($_GET['resourcePath']);

            $order = wc_get_order(absint(get_query_var('order-pay')));

            $order_data = $order->get_data();

            $url = $this->OPP_endPoint . $resource_path . "?entityId=$this->OPP_targetEntityId";

            $headers = [
                'Authorization' => 'Bearer ' . $this->OPP_accessToken
            ];

            $args = [
                'headers' => $headers
            ];

            $response = wp_remote_get($url, $args);

            $response_body = wp_remote_retrieve_body($response);

            $response_data = json_decode($response_body, true);

            if (in_array($response_data['result']['code'], $this->totalproc_gateway_success_codes())) {

                if (isset($response_data['registrationId'])) {

                    $tokens = WC_Payment_Tokens::get_customer_tokens(get_current_user_id(), $this->id);

                    foreach ($tokens as $token) {
                        $tokens_array[] = $token->get_token();
                    }

                    if (!$tokens_array || !in_array($response_data['registrationId'], $tokens_array)) {

                        if ($response_data['paymentBrand'] == 'VISA') {
                            $card_type = 'Visa';
                        } else if ($response_data['paymentBrand'] == 'AMEX') {
                            $card_type = 'American Express';
                        } else {
                            $card_type = 'Mastercard';
                        }

                        $token = new WC_Payment_Token_CC();

                        $token->set_token($response_data['registrationId']);
                        $token->set_gateway_id($this->id);
                        $token->set_card_type($card_type);
                        $token->set_last4((string) $response_data['card']['last4Digits']);
                        $token->set_expiry_month(trim($response_data['card']['expiryMonth']));
                        $token->set_expiry_year(trim($response_data['card']['expiryYear']));
                        $token->set_user_id(get_current_user_id());

                        $token->save();
                    }
                }

                $order->payment_complete();
                wc_reduce_stock_levels($order->get_id());

                // some notes to customer (replace true with false to make it private)
                $send_confirmation = false;

                if ($this->OPP_sendConfirmationNote == 'yes') {
                    $send_confirmation = true;
                }
                $order->add_order_note('Confirmation of payment! Thank you!', $send_confirmation);

                $order->add_meta_data('_opp_id', $response_data['id']);
                $order->add_meta_data('_opp_entityId', $this->OPP_targetEntityId);
                $order->add_meta_data('_opp_paymentType', 'DB');
                $order->add_meta_data('_opp_currency', $order_data['currency']);

                //save order update.
                $order->save();

                $woocommerce->cart->empty_cart();

                // Redirect to the thank you page
                wp_redirect($this->get_return_url($order));
            } else {

                $redirect_url = add_query_arg('wc-tp-confirmation', 1, $order->get_checkout_payment_url(false));

                $this->totalproc_debug(
                    'TotalProcessing Payment Failure, Order: ' .
                        $order->get_id() . ' code: ' .
                        $response_data['result']['code'] .
                        ' transaction ID: ' . ($response_data['id'] ?? null) .
                        PHP_EOL .
                        ' Full dump: ' . json_encode($response_data)
                );

                $order->add_order_note('Payment attempt failed, code: ' . $response_data['result']['code'] . ' Message: ' . $response_data['result']['description'], false);
            ?>
                <h1> Error code: <?php esc_html_e($response_data['result']['code']); ?> Message: <?php esc_html_e($response_data['result']['description']); ?> </h1>
                <h1> <a href="<?php esc_html_e($redirect_url); ?>">Click here to reload the form and try again</a></h1>
<?php
                return new WP_Error('Error', $response_data['result']['code'] . ': ' . $response_data['result']['description']);
            }
        }

        public function add_payment_method()
        {
            return true;
        }
    }
}
