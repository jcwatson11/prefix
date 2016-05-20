<?php
namespace Fh\QueryBuilder;
use Illuminate\Support\ServiceProvider;
class FhApiQueryBuilderServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__.'/config.php' => config_path('fh-laravel-api-query-builder.php'),
        ]);
        $this->app->bindShared('Fh\QueryBuilder\RestMapperInterface',function() {
            return new RestMapper();
        });
    }
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/config.php', 'fh-laravel-api-query-builder'
        );
    }
}