<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return apply_filters( 'wc_xem_settings',
	array(
		'enabled' => array(
			'title'       => __( 'Enable/Disable', 'woocommerce-gateway-xem' ),
			'label'       => __( 'Enable XEM payments', 'woocommerce-gateway-xem' ),
			'type'        => 'checkbox',
			'description' => '',
			'default'     => 'no'
		),
		'title' => array(
			'title'       => __( 'Title', 'woocommerce-gateway-xem' ),
			'type'        => 'text',
			'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce-gateway-xem' ),
			'default'     => __( 'XEM (Digital currency)', 'woocommerce-gateway-xem' ),
			'desc_tip'    => true,
		),
		'description' => array(
			'title'       => __( 'Description', 'woocommerce-gateway-xem' ),
			'type'        => 'text',
			'description' => __( 'This controls the description which the user sees during checkout. Leave it empty and it will not show.', 'woocommerce-gateway-xem' ),
			'default'     => __( 'Pay with XEM.', 'woocommerce-gateway-xem'),
			'desc_tip'    => true,
		),
		'xem_address' => array(
			'title'       => __( 'XEM address', 'woocommerce-gateway-xem' ),
			'type'        => 'text',
			'description' => __( 'Input the XEM address where you want customers to pay XEM too.', 'woocommerce-gateway-xem' ),
			'default'     => '',
			'placeholder' => 'NASLLJ-7P5TAU-2SBHNV-UJJ66Q-CQNZY2-IVRPTP-QOB2',
			'desc_tip'    => true,
		),
		'match_amount' => array(
			'title'       => __( 'Match amount', 'woocommerce-gateway-xem' ),
			'label'       => __( 'Match transactions on Amount', 'woocommerce-gateway-xem' ),
			'type'        => 'checkbox',
			'description' => __( 'When customers paid in checkout and the system tried to match a transfer to your account, it will also try to match it on amount.', 'woocommerce-gateway-xem' ),
			'default'     => 'no',
			'desc_tip'    => true,
		),
        'prices_in_xem' => array(
            'title'       => __( 'Show prices in XEM', 'woocommerce-gateway-xem' ),
            'type'        => 'select',
            'class'       => 'wc-enhanced-select',
            'description' => __( 'Show prices on store pages in XEM', 'woocommerce-gateway-xem' ),
            'default'     => 'no',
            'desc_tip'    => true,
            'options'     => array(
                    'no'    => __( 'Default prices', 'woocommerce-gateway-xem' ),
                    'only'    => __( 'Only XEM price', 'woocommerce-gateway-xem' ),
                    'both'    => __( 'Default and XEM prices', 'woocommerce-gateway-xem' ),
            ),
        ),
		'logging' => array(
			'title'       => __( 'Logging', 'woocommerce-gateway-xem' ),
			'label'       => __( 'Log debug messages', 'woocommerce-gateway-xem' ),
			'type'        => 'checkbox',
			'description' => __( 'Save debug messages to the WooCommerce System Status log.', 'woocommerce-gateway-xem' ),
			'default'     => 'no',
			'desc_tip'    => true,
		),
        'test' => array(
            'title'       => __( 'Test', 'woocommerce-gateway-xem' ),
            'label'       => __( 'Run in TESTNET', 'woocommerce-gateway-xem' ),
            'type'        => 'checkbox',
            'description' => __( 'Will run payments on the testnet', 'woocommerce-gateway-xem' ),
            'default'     => 'no',
            'desc_tip'    => true,
        ),
        'test_xem_address' => array(
            'title'       => __( 'TESTNET XEM address', 'woocommerce-gateway-xem' ),
            'type'        => 'text',
            'description' => __( 'TESTNET XEM address. Should always start with T', 'woocommerce-gateway-xem' ),
            'default'     => '',
            'placeholder' => 'TBFLJ2-LTBOIF-KRMYHS-I3TEKK-6ISQOB-JILNTP-AJ2Q',
            'desc_tip'    => true,
        ),
	)
);