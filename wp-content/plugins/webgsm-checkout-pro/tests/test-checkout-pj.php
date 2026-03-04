<?php
/**
 * Tests for WebGSM Checkout PJ flow
 *
 * @package WebGSM_Checkout_Pro
 */

class WebGSM_Checkout_PJ_Test extends WP_UnitTestCase {

    public function setUp(): void {
        parent::setUp();

        if ( ! class_exists( 'WooCommerce' ) ) {
            $this->markTestSkipped( 'WooCommerce is not active' );
        }

        // Ensure class is loaded (plugin bootstrap in test env usually loads plugin, but be safe)
        if ( ! class_exists( 'WebGSM_Checkout_Save' ) ) {
            include_once WP_PLUGIN_DIR . '/webgsm-checkout-pro/includes/class-checkout-save.php';
        }
    }

    public function tearDown(): void {
        parent::tearDown();
        // Clean global POST state
        $_POST = [];
    }

    public function test_pj_fields_saved_to_order() {
        // Create a simple order
        $order = wc_create_order();
        $order_id = is_int( $order ) ? $order : $order->get_id();

        // Simulate checkout POST for PJ
        $_POST['billing_customer_type'] = 'pj';
        $_POST['billing_company'] = 'ACME SRL';
        $_POST['billing_cui'] = '12345678';
        $_POST['billing_j'] = 'J40/1234/2020';
        $_POST['billing_phone'] = '0712345678';
        $_POST['billing_email'] = 'test@example.com';
        $_POST['billing_address_1'] = 'Strada Principală 1';
        $_POST['billing_city'] = 'București';
        $_POST['billing_state'] = 'B';
        $_POST['billing_postcode'] = '010101';

        // Call the save hook directly
        $save = new WebGSM_Checkout_Save();
        $save->webgsm_save_order_meta( $order_id, [] );

        // Reload order
        $order = wc_get_order( $order_id );

        // Assertions
        $this->assertEquals( 'pj', $order->get_meta( '_customer_type' ) );
        $this->assertEquals( 'ACME SRL', $order->get_billing_company() );

        $company = $order->get_meta( '_company_data' );
        $this->assertNotEmpty( $company );
        $this->assertEquals( '12345678', $company['cui'] );
        $this->assertEquals( '0712345678', $company['phone'] );
        $this->assertEquals( 'test@example.com', $company['email'] );
        $this->assertEquals( 'Strada Principală 1', $company['address'] );
    }
}
