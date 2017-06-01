<?php

/**
 * Xem gateway main class based on woocommerce
 *
 * @since 1.0.0
 *
 */
class WC_Gateway_Xem extends WC_Payment_Gateway {

    /**
     * Logging enabled?
     *
     * @var bool
     */
    public $logging;


    function __construct() {
        $this->id = 'xem';
        $this->method_title = __('XEM', 'woocommerce-gateway-xem');
        $this->method_description = __('XEM works by showing a QR code and let customers pay XEM to your XEM wallet for orders in you shop.', 'woocommerce-gateway-xem');
        $this->has_fields = true;
        $this->icon = WC_XEM_PLUGIN_URL . ('/assets/img/pay_with_xem.png');
        $this->order_button_text = "Waiting for payment";


        // Load the form fields.
        $this->init_form_fields();

        // Load the settings.
        $this->init_settings();

        // Get setting values.
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->enabled = $this->get_option('enabled');
        $this->xem_address = $this->get_option('xem_address');
        $this->match_amount = 'yes' === $this->get_option('match_amount');
        $this->logging = 'yes' === $this->get_option('logging');
        $this->test = 'yes' === $this->get_option('test');
        $this->prices_in_xem = $this->get_option('prices_in_xem');
        if ( $this->test ) {
            $this->xem_address = $this->get_option('test_xem_address');
        }




        // Hooks.
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(
            $this,
            'process_admin_options'
        ));
        add_action('wp_enqueue_scripts', array( $this, 'payment_scripts' ));



    }



    /**
     * Payment form on checkout page
     */
    public function payment_fields() {
        $user = wp_get_current_user();
        $xem_amount = Xem_Currency::get_xem_amount($this->get_order_total(), strtoupper(get_woocommerce_currency()));

        //Todo: Lock amount for 5 minutes
        WC()->session->set('xem_amount', $xem_amount);

        if ( $user->ID ) {
            $user_email = get_user_meta($user->ID, 'billing_email', true);
            $user_email = $user_email ? $user_email : $user->user_email;
        } else {
            $user_email = '';
        }

        $xem_ref = wp_create_nonce("3h62h6u26h42h6i2462h6u4h624");

        //Start wrapper
        echo '<div id="xem-form"
			data-email="' . esc_attr($user_email) . '"
			data-amount="' . esc_attr($this->get_order_total()) . '"
			data-currency="' . esc_attr(strtolower(get_woocommerce_currency())) . '"
			data-xem-address="' . esc_attr($this->xem_address) . '"
			data-xem-amount="' . esc_attr($xem_amount) . '"
			data-xem-ref="' . esc_attr($xem_ref) . '"
			">';

        //Info box
        echo '<div id="xem-description">';
        if ( $this->description ) {
            echo apply_filters( 'wc_sxem_description', wpautop( wp_kses_post( $this->description ) ) );
        }
        echo '</div>';


        //QRcode
        echo '<div id="xem-qr"></div>';


        echo '<div id="xem-payment-desc">';

        echo '<div>';

        echo '<div class="xem-payment-desc-row">';
        echo '<label class="xem-label-for">' . __('Amount XEM:', 'woocommerce-gateway-xem') . '</label>';
        echo '<label id="xem-amount-wrapper" class="xem-label xem-amount" data-clipboard-text="' . esc_attr($xem_amount) . '">' . esc_attr($xem_amount) . '</label>';
        echo '</div>';

        echo '<div class="xem-payment-desc-row">';
        echo '<label class="xem-label-for">' . __('Address:', 'woocommerce-gateway-xem') . '</label>';
        echo '<label id="xem-address-wrapper" class="xem-label xem-address" data-clipboard-text="' . esc_attr($this->xem_address) . '">' . esc_attr($this->xem_address) . '</label>';
        echo '</div>';

        echo '<div class="xem-payment-desc-row">';
        echo '<label class="xem-label-for">' . __('Reference:', 'woocommerce-gateway-xem') . '</label>';
        echo '<label id="xem-ref-wrapper" class="xem-label xem-ref" data-clipboard-text="' . esc_attr($xem_ref) . '">' . esc_attr($xem_ref) . '</label>';
        echo '</div>';

        echo '</div>';

        echo '</div>';


        //XemProcess
        echo '<div id="xem-process"></div>';

    }

    /**
     * payment_scripts function.
     *
     * Outputs scripts used for stripe payment
     *
     * @access public
     */
    public function payment_scripts() {

        wp_enqueue_script('woocommerce_xem_qrcode', plugins_url('assets/js/qrcode.js', WC_XEM_MAIN_FILE), array( 'jquery' ), WC_XEM_VERSION, true);
        wp_enqueue_script('jquery-initialize', plugins_url('assets/js/jquery.initialize.js', WC_XEM_MAIN_FILE), array( 'jquery' ), WC_XEM_VERSION, true);
        wp_enqueue_script('clipboard', plugins_url('assets/js/clipboard.js', WC_XEM_MAIN_FILE), array( 'jquery' ), WC_XEM_VERSION, true);
        wp_enqueue_script('nanobar', plugins_url('assets/js/nanobar.js', WC_XEM_MAIN_FILE), array( 'jquery' ), WC_XEM_VERSION, true);
        wp_enqueue_script('woocommerce_xem_js', plugins_url('assets/js/xem-checkout.js', WC_XEM_MAIN_FILE), array(
            'jquery',
            'woocommerce_xem_qrcode',
            'jquery-initialize',
            'clipboard',
            'nanobar'
        ), WC_XEM_VERSION, true);
        wp_enqueue_style('woocommerce_xem_css', plugins_url('assets/css/xem-checkout.css', WC_XEM_MAIN_FILE), array(), WC_XEM_VERSION);


        //Add js variables
        $xem_params = array(
            'wc_ajax_url' => WC()->ajax_url(),
            'nounce' => wp_create_nonce("woocommerce-xem"),
            'testnet' => $this->test,
            'store' => get_bloginfo()
        );

        wp_localize_script('woocommerce_xem_js', 'wc_xem_params', apply_filters('wc_xem_params', $xem_params));

    }

    public function validate_fields() {
        $xem_payment = json_decode(WC()->session->get('xem_payment'));
        if ( empty($xem_payment) ) {
            wc_add_notice(__('A XEM payment has not been registered to this checkout. Please contact our support department.', 'woocommerce-gateway-xem'), 'error');
            return false;
        }
        return true;
    }

    /**
     * Process Payment.
     *
     *
     * @param int $order_id
     *
     * @return array
     */
    public function process_payment( $order_id ) {

        global $woocommerce;
        $order = new WC_Order($order_id);

        //Get the XEM transaction
        $xem_payment = json_decode(WC()->session->get('xem_payment'));


        // Mark as on-hold (we're awaiting the cheque)
        $order->update_status('on-hold', __('Awaiting XEM payment', 'woocommerce'));
        update_post_meta($order_id, 'xem_payment_hash', $xem_payment->meta->hash->data);
        update_post_meta($order_id, 'xem_payment_ref', $this->hex2str($xem_payment->transaction->message->payload));
        update_post_meta($order_id, 'xem_payment_amount', $xem_payment->transaction->amount);
        update_post_meta($order_id, 'xem_payment_fee', $xem_payment->transaction->fee);
        update_post_meta($order_id, 'xem_payment_height', $xem_payment->meta->height);
        update_post_meta($order_id, 'xem_payment_recipient', $xem_payment->transaction->recipient);

        // Reduce stock levels
        $order->reduce_order_stock();

        //Mark as paid
        $order->payment_complete();

        // Remove cart
        $woocommerce->cart->empty_cart();
        WC()->session->set('xem_payment', false);
        //Lock amount for 5 minutes
        WC()->session->set('xem_amount', false);


        return array(
            'result' => 'success',
            'redirect' => $this->get_return_url()
        );
    }

    public function hex2str( $hex ) {
        $str = '';
        for ( $i = 0; $i < strlen($hex); $i += 2 ) $str .= chr(hexdec(substr($hex, $i, 2)));
        return $str;
    }

    /**
     * Get Xem amount to pay
     *
     * @param float $total Amount due.
     * @param string $currency Accepted currency.
     *
     * @return float|int
     */
    public function get_xem_amount( $total, $currency = '' ) {
        if ( !$currency ) {
            $currency = get_woocommerce_currency();
        }
        /*Todo: Add filter for supported currencys. Also, could add to tri-exchange if currency outside polo currency*/
        $supported_currencys = array();

        switch ( strtoupper($currency) ) {
            // Zero decimal currencies.
            case 'BIF' :
            case 'CLP' :
            case 'DJF' :
            case 'GNF' :
            case 'JPY' :
            case 'KMF' :
            case 'KRW' :
            case 'MGA' :
            case 'PYG' :
            case 'RWF' :
            case 'VND' :
            case 'VUV' :
            case 'XAF' :
            case 'XOF' :
            case 'XPF' :
                $total = absint($total);
                break;
            default :
                $total = round($total, 2) * 100; // In cents.
                break;
        }

        return $total;
    }

    /**
     * Init settings for gateways.
     */
    public function init_settings() {
        parent::init_settings();
        $this->enabled = !empty($this->settings['enabled']) && 'yes' === $this->settings['enabled'] ? 'yes' : 'no';
    }

    /**
     * Initialise Gateway Settings Form Fields
     */
    public function  init_form_fields() {
        $this->form_fields = include('wc-gateway-xem-settings.php');

        wc_enqueue_js("
			jQuery( function( $ ) {
				
			});
		");
    }

    /**
     * Check if this gateway is enabled
     */
    public function is_available() {
        if ( 'yes' === $this->enabled && $this->xem_address ) {
            return true;
        }
        return false;
    }

}