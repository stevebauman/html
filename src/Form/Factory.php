<?php namespace Orchestra\Html\Form;

use Closure;
use Orchestra\Html\Factory as BaseFactory;
use Orchestra\Contracts\Html\Form\Factory as FactoryContract;

class Factory extends BaseFactory implements FactoryContract
{
    /**
     * {@inheritdoc}
     */
    public function make(Closure $callback = null)
    {
        $builder = new FormBuilder(
            $this->app->make('request'),
            $this->app->make('translator'),
            $this->app->make('view'),
            new Grid($this->app)
        );

        return $builder->extend($callback);
    }

    /**
     * Allow to access `form` service location method using magic method.
     *
     * @param  string  $method
     * @param  array   $parameters
     *
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return call_user_func_array([$this->app->make('form'), $method], $parameters);
    }
}
