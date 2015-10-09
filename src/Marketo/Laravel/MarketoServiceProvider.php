<?php namespace Marketo\Laravel;

use Illuminate\Support\ServiceProvider;
use Marketo\Marketo;

class MarketoServiceProvider extends ServiceProvider
{

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $config = __DIR__ . '/config/config.php';
        $this->mergeConfigFrom($config, 'marketo');
        $this->publishes([$config => config_path('marketo.php')]);

    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {

        $this->app->singleton('marketo', function ($app) {

            return new Marketo(config('marketo'));

        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array('marketo');
    }

}