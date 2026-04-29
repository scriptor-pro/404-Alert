<?php
/**
 * Tests unitaires pour Alert404_SMTP_Wizard
 */

class Test_Alert404_SMTP_Wizard extends Alert404_UnitTestCase {

	/**
	 * Teste que get_initial_state_returns_empty_when_no_transient
	 */
	public function test_get_initial_state_returns_empty_when_no_transient() {
		delete_transient( '404_alert_smtp_wizard_state' );
		$state = Alert404_SMTP_Wizard::get_wizard_state();

		$this->assertIsArray( $state );
		$this->assertFalse( isset( $state['step'] ) );
	}

	/**
	 * Teste que save_step_stores_state_in_transient
	 */
	public function test_save_step_stores_state_in_transient() {
		$data = array(
			'step'        => 1,
			'provider_id' => 'gmail',
		);
		Alert404_SMTP_Wizard::save_step( $data );

		$transient = get_transient( '404_alert_smtp_wizard_state' );
		$this->assertEquals( 1, $transient['step'] );
		$this->assertEquals( 'gmail', $transient['provider_id'] );
	}

	/**
	 * Teste que clear_wizard_state_deletes_transient
	 */
	public function test_clear_wizard_state_deletes_transient() {
		set_transient( '404_alert_smtp_wizard_state', array( 'step' => 1 ), HOUR_IN_SECONDS );
		Alert404_SMTP_Wizard::clear_wizard_state();

		$this->assertFalse( get_transient( '404_alert_smtp_wizard_state' ) );
	}
}
