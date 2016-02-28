<?php
namespace Fh\QueryBuilder;
use Illuminate\Support\ServiceProvider;
class FhApiQueryBuilderServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__.'/config.php' => config_path('fh-api-query-builder.php'),
        ]);
    }
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/config.php', 'fh-api-query-builder'
        );
    }
}