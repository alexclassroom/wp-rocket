<?php
declare(strict_types=1);

namespace WP_Rocket\Tests\Unit\inc\Engine\Optimization\RUCSS\Admin\Subscriber;

use Mockery;
use Brain\Monkey\Functions;
use WP_Rocket\Engine\Optimization\RUCSS\Controller\Queue;
use WP_Rocket\Tests\Unit\FilesystemTestCase;
use WP_Rocket\Engine\Optimization\RUCSS\Admin\Database;
use WP_Rocket\Engine\Optimization\RUCSS\Admin\Settings;
use WP_Rocket\Engine\Optimization\RUCSS\Admin\Subscriber;
use WP_Rocket\Engine\Optimization\RUCSS\Controller\UsedCSS;

/**
 * @covers \WP_Rocket\Engine\Optimization\RUCSS\Admin\Subscriber::clean_used_css_and_cache
 *
 * @uses   ::rocket_clean_domain
 *
 * @group  RUCSS
 */
class Test_CleanUsedCssAndCache extends FilesystemTestCase {

	private $settings;
	private $database;
	private $used_css;
	private $subscriber;

	protected $path_to_test_data = '/inc/Engine/Optimization/RUCSS/Admin/Subscriber/cleanUsedCssAndCache.php';

	public function setUp() : void {
		parent::setUp();

		$this->settings    = Mockery::mock( Settings::class );
		$this->database    = Mockery::mock( Database::class );
		$this->used_css    = Mockery::mock( UsedCSS::class );
		$this->subscriber  = new Subscriber( $this->settings, $this->database, $this->used_css, Mockery::mock( Queue::class ) );
	}

	/**
	 * @dataProvider providerTestData
	 */
	public function testShouldDoExpected( $input ) {
		if (
			isset( $input['settings']['remove_unused_css_safelist'], $input['old_settings']['remove_unused_css_safelist'] )
			&&
			$input['settings']['remove_unused_css_safelist'] !== $input['old_settings']['remove_unused_css_safelist']
		 ) {
			$this->used_css->shouldReceive( 'delete_used_css_rows' )->once();


			Functions\expect( 'set_transient' )
				->once()
				->with(
					'rocket_rucss_processing',
					Mockery::type( 'int' ),
					90
				);

			Functions\expect( 'rocket_renew_box' )
				->once()
				->with( 'rucss_success_notice' );
		} else {
			$this->database
				->shouldReceive( 'truncate_used_css_table' )
				->never();

			Functions\expect( 'set_transient' )
				->never();
		}

		$this->subscriber->clean_used_css_and_cache( $input['settings'], $input['old_settings'] );
	}
}
