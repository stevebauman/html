<?php namespace Orchestra\Html;

use Orchestra\Html\Form\Control;
use Orchestra\Html\Form\Factory as FormFactory;
use Illuminate\Contracts\Foundation\Application;
use Orchestra\Html\Form\BootstrapThreePresenter;
use Orchestra\Support\Providers\ServiceProvider;
use Orchestra\Html\Table\Factory as TableFactory;
use Orchestra\Contracts\Html\Form\Template as TemplateContract;
use Orchestra\Contracts\Html\Form\Control as FormControlContract;

class HtmlServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerHtmlBuilder();

        $this->registerFormBuilder();

        $this->registerOrchestraFormBuilder();

        $this->registerOrchestraTableBuilder();

        $this->app->alias('html', HtmlBuilder::class);
        $this->app->alias('form', FormBuilder::class);
    }

    /**
     * Register the HTML builder instance.
     *
     * @return void
     */
    protected function registerHtmlBuilder()
    {
        $this->app->singleton('html', function (Application $app) {
            return new HtmlBuilder($app->make('url'));
        });
    }

    /**
     * Register the form builder instance.
     *
     * @return void
     */
    protected function registerFormBuilder()
    {
        $this->app->singleton('form', function (Application $app) {
            $form = new FormBuilder($app->make('html'), $app->make('url'));

            return $form->setSessionStore($app->make('session.store'));
        });
    }

    /**
     * Register the Orchestra\Form builder instance.
     *
     * @return void
     */
    protected function registerOrchestraFormBuilder()
    {
        $this->app->singleton(FormControlContract::class, Control::class);

        $this->app->singleton(TemplateContract::class, function (Application $app) {
            $class = $app->make('config')->get('orchestra/html::form.presenter', BootstrapThreePresenter::class);

            return $app->make($class);
        });

        $this->app->singleton('orchestra.form', function (Application $app) {
            return new FormFactory($app);
        });
    }

    /**
     * Register the Orchestra\Table builder instance.
     *
     * @return void
     */
    protected function registerOrchestraTableBuilder()
    {
        $this->app->singleton('orchestra.table', function ($app) {
            return new TableFactory($app);
        });
    }

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $path = realpath(__DIR__.'/../resources');

        $this->addConfigComponent('orchestra/html', 'orchestra/html', "{$path}/config");
        $this->addViewComponent('orchestra/html', 'orchestra/html', "{$path}/views");
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['html', 'form', 'orchestra.form', 'orchestra.form.control', 'orchestra.table'];
    }
}
