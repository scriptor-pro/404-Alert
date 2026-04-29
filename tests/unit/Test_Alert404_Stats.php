<?php
/**
 * Tests for Alert404_Stats class.
 *
 * @package Alert404
 */

class Test_Alert404_Stats extends WP_UnitTestCase {

	public function setUp(): void {
		parent::setUp();
		Alert404_Stats::clear();
	}

	public function tearDown(): void {
		Alert404_Stats::clear();
		parent::tearDown();
	}

	public function test_record_with_valid_event() {
		$event = array(
			'url' => 'http://example.com/missing',
			'ip' => '192.168.1.1',
			'referrer' => 'http://google.com',
			'user_agent' => 'Mozilla/5.0',
			'user_readable' => 'Chrome 120',
		);

		$result = Alert404_Stats::record( $event );
		$this->assertTrue( $result );

		$count = Alert404_Stats::get_total_count();
		$this->assertEquals( 1, $count );
	}

	public function test_record_with_missing_url() {
		$event = array(
			'ip' => '192.168.1.1',
		);

		$result = Alert404_Stats::record( $event );
		$this->assertFalse( $result );
	}

	public function test_record_with_missing_ip() {
		$event = array(
			'url' => 'http://example.com/missing',
		);

		$result = Alert404_Stats::record( $event );
		$this->assertFalse( $result );
	}

	public function test_record_with_full_url_fallback() {
		$event = array(
			'full_url' => 'http://example.com/missing',
			'ip' => '192.168.1.1',
		);

		$result = Alert404_Stats::record( $event );
		$this->assertTrue( $result );

		$count = Alert404_Stats::get_total_count();
		$this->assertEquals( 1, $count );
	}

	public function test_get_total_count() {
		$this->assertEquals( 0, Alert404_Stats::get_total_count() );

		Alert404_Stats::record( array(
			'url' => 'http://example.com/1',
			'ip' => '192.168.1.1',
		) );

		Alert404_Stats::record( array(
			'url' => 'http://example.com/2',
			'ip' => '192.168.1.2',
		) );

		$count = Alert404_Stats::get_total_count();
		$this->assertEquals( 2, $count );
	}

	public function test_get_unique_urls_count() {
		Alert404_Stats::record( array(
			'url' => 'http://example.com/page1',
			'ip' => '192.168.1.1',
		) );

		Alert404_Stats::record( array(
			'url' => 'http://example.com/page1',
			'ip' => '192.168.1.2',
		) );

		Alert404_Stats::record( array(
			'url' => 'http://example.com/page2',
			'ip' => '192.168.1.3',
		) );

		$unique = Alert404_Stats::get_unique_urls_count();
		$this->assertEquals( 2, $unique );
	}

	public function test_get_recent() {
		Alert404_Stats::record( array(
			'url' => 'http://example.com/1',
			'ip' => '192.168.1.1',
		) );

		Alert404_Stats::record( array(
			'url' => 'http://example.com/2',
			'ip' => '192.168.1.2',
		) );

		$records = Alert404_Stats::get_recent( 10 );
		$this->assertCount( 2, $records );
		$this->assertEquals( 'http://example.com/2', $records[0]['url'] );
		$this->assertEquals( 'http://example.com/1', $records[1]['url'] );
	}

	public function test_get_recent_with_limit() {
		for ( $i = 0; $i < 5; $i++ ) {
			Alert404_Stats::record( array(
				'url' => 'http://example.com/' . $i,
				'ip' => '192.168.1.' . $i,
			) );
		}

		$records = Alert404_Stats::get_recent( 2 );
		$this->assertCount( 2, $records );
	}

	public function test_get_top_urls() {
		Alert404_Stats::record( array(
			'url' => 'http://example.com/page1',
			'ip' => '192.168.1.1',
		) );

		Alert404_Stats::record( array(
			'url' => 'http://example.com/page1',
			'ip' => '192.168.1.2',
		) );

		Alert404_Stats::record( array(
			'url' => 'http://example.com/page2',
			'ip' => '192.168.1.3',
		) );

		$top = Alert404_Stats::get_top_urls( 10 );
		$this->assertCount( 2, $top );
		$this->assertEquals( 2, $top['http://example.com/page1'] );
		$this->assertEquals( 1, $top['http://example.com/page2'] );
	}

	public function test_get_top_ips() {
		Alert404_Stats::record( array(
			'url' => 'http://example.com/1',
			'ip' => '192.168.1.1',
		) );

		Alert404_Stats::record( array(
			'url' => 'http://example.com/2',
			'ip' => '192.168.1.1',
		) );

		Alert404_Stats::record( array(
			'url' => 'http://example.com/3',
			'ip' => '192.168.1.2',
		) );

		$top = Alert404_Stats::get_top_ips( 10 );
		$this->assertCount( 2, $top );
		$this->assertEquals( 2, $top['192.168.1.1'] );
		$this->assertEquals( 1, $top['192.168.1.2'] );
	}

	public function test_get_count_for_date() {
		// Record events (they'll be recorded with current timestamp).
		Alert404_Stats::record( array(
			'url' => 'http://example.com/1',
			'ip' => '192.168.1.1',
		) );

		$today = gmdate( 'Y-m-d' );
		$count = Alert404_Stats::get_count_for_date( $today );
		$this->assertEquals( 1, $count );
	}

	public function test_get_count_by_referrer() {
		Alert404_Stats::record( array(
			'url' => 'http://example.com/1',
			'ip' => '192.168.1.1',
			'referrer' => 'http://google.com',
		) );

		Alert404_Stats::record( array(
			'url' => 'http://example.com/2',
			'ip' => '192.168.1.2',
			'referrer' => 'http://google.com',
		) );

		Alert404_Stats::record( array(
			'url' => 'http://example.com/3',
			'ip' => '192.168.1.3',
			'referrer' => 'http://bing.com',
		) );

		$by_referrer = Alert404_Stats::get_count_by_referrer( 10 );
		$this->assertCount( 2, $by_referrer );
		$this->assertEquals( 2, $by_referrer['http://google.com'] );
		$this->assertEquals( 1, $by_referrer['http://bing.com'] );
	}

	public function test_clear() {
		Alert404_Stats::record( array(
			'url' => 'http://example.com/1',
			'ip' => '192.168.1.1',
		) );

		$this->assertEquals( 1, Alert404_Stats::get_total_count() );

		$result = Alert404_Stats::clear();
		$this->assertTrue( $result );

		$this->assertEquals( 0, Alert404_Stats::get_total_count() );
	}

	public function test_record_returns_correct_data_types() {
		Alert404_Stats::record( array(
			'url' => 'http://example.com/test',
			'ip' => '192.168.1.1',
			'referrer' => 'http://google.com',
			'user_agent' => 'Mozilla/5.0',
		) );

		$records = Alert404_Stats::get_recent( 1 );
		$record = $records[0];

		$this->assertIsInt( $record['id'] );
		$this->assertIsString( $record['url'] );
		$this->assertIsString( $record['ip'] );
		$this->assertIsString( $record['referrer'] );
		$this->assertIsString( $record['user_agent'] );
		$this->assertIsString( $record['created_at'] );
	}

	public function test_caching() {
		Alert404_Stats::record( array(
			'url' => 'http://example.com/1',
			'ip' => '192.168.1.1',
		) );

		// First call should hit database.
		$count1 = Alert404_Stats::get_total_count();

		// Second call should hit cache.
		$count2 = Alert404_Stats::get_total_count();

		$this->assertEquals( $count1, $count2 );
	}

	public function test_validate_date_format() {
		// Using reflection to test private method.
		$reflection = new ReflectionClass( 'Alert404_Stats' );
		$method = $reflection->getMethod( 'validate_date_format' );
		$method->setAccessible( true );

		$this->assertTrue( $method->invoke( null, '2024-01-01' ) );
		$this->assertFalse( $method->invoke( null, '2024-1-1' ) );
		$this->assertFalse( $method->invoke( null, 'invalid' ) );
	}
}
