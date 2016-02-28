<?php

namespace Fh\QueryBuilder;

use Orchestra\Testbench\TestCase;
use \Mockery as m;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Application;

class TestBase extends TestCase {

	static $pdo = null;

	public function tearDown()
	{
		parent::tearDown();
		\Mockery::close();
	}

	protected function getApplicationProviders($app)
	{
		$providers = [
			'Fh\QueryBuilder\FhApiQueryBuilderServiceProvider'
		];
		return array_merge($providers,parent::getApplicationProviders($app));
	}

	/**
	 * Define environment setup.
	 *
	 * @param  Application    $app
	 */
	protected function getEnvironmentSetUp($app)
	{
		// reset base path to point to our package's src directory
		$app['path.base'] = __DIR__ . '/../../../src';

		$app['config']->set('database.default', 'enterprisedb');
		$app['config']->set('database.connections.enterprisedb', array(
			'driver'    => getenv('ENTERPRISE_DRIVER'),
			'host'      => getenv('ENTERPRISE_HOST'),
			'database'  => getenv('ENTERPRISE_DBNAME'),
			'username'  => getenv('ENTERPRISE_USER'),
			'password'  => getenv('ENTERPRISE_PASS'),
			'charset'   => getenv('ENTERPRISE_CHARSET'),
			'collation' => getenv('ENTERPRISE_COLLATION'),
			'prefix'    => getenv('ENTERPRISE_PREFIX'),
		));
		$app['config']->set('database.log', true);
		$app['config']->set('fh-api-query-builder.limit', 10);
		$app['config']->set('fh-api-query-builder.baseuri', '/api/v1/');
		// $this->logListenToDbQueries();
	}

}
