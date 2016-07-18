<?php

namespace Fh\QueryBuilder;

use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Artisan;

class Base extends TestCase {

    public function setUp()
    {
        parent::setUp();
        Artisan::call('migrate',[
        	'--path' => __DIR__
        ]);
    }

	public function tearDown()
	{
		Artisan::call('migrate:reset');
		\Mockery::close();
		parent::tearDown();
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

		$app['config']->set('database.default', 'sqlite_testing');
		$app['config']->set('database.connections.sqlite_testing', array(
			'driver'    => 'sqlite',
			'database'  => ':memory:',
			'prefix'    => ''
		));
		$app['config']->set('database.log', true);
		$app['config']->set('fh-laravel-api-query-builder.defaultLimit', 10);
		$app['config']->set('fh-laravel-api-query-builder.baseUri', '/api/v1/');
		$app['config']->set('fh-laravel-api-query-builder.modelNamespace', 'Fh\QueryBuilder');
		$app['config']->set('fh-laravel-api-query-builder.pagingStyle', 'limit/offset');
		$app['config']->set('fh-laravel-api-query-builder.offsetParameterName', 'offset');
		$app['config']->set('fh-laravel-api-query-builder.limitParameterName', 'limit');
		$app['config']->set('fh-laravel-api-query-builder.pageParameterName', 'page');
	}

}
