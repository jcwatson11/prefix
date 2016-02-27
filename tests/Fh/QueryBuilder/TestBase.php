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
			'Fh\Data\Gbo\GboServiceProvider'
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
		// $this->logListenToDbQueries();
	}

/**
 * Turns on query logging for the purposes of testing.
	public function logListenToDbQueries()
	{
		// Set up logging
		if (Config::get('database.log', false))
		{
			Event::listen('illuminate.query', function($query, $bindings, $time, $name)
			{
				$data = compact('bindings', 'time', 'name');

				// Format binding data for sql insertion
				foreach ($bindings as $i => $binding)
				{
					if ($binding instanceof \DateTime)
					{
						$bindings[$i] = $binding->format('\'Y-m-d H:i:s\'');
					}
					else if (is_string($binding))
					{
						$bindings[$i] = "'$binding'";
					}
				}

				// Insert bindings into query
				$query = str_replace(array('%', '?'), array('%%', '%s'), $query);
				$query = vsprintf($query, $bindings);

				Log::info($query, $data);
			});
		}
	}
*/


}
