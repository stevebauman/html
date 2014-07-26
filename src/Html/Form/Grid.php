<?php namespace Orchestra\Html\Form;

use Closure;
use InvalidArgumentException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Fluent;
use Orchestra\Support\Collection;
use Orchestra\Support\Str;

class Grid extends \Orchestra\Html\Abstractable\Grid
{
    /**
     * Enable CSRF token.
     *
     * @var boolean
     */
    public $token = false;

    /**
     * Hidden fields.
     *
     * @var array
     */
    protected $hiddens = array();

    /**
     * List of row in array.
     *
     * @var array
     */
    protected $row = null;

    /**
     * All the fieldsets.
     *
     * @var \Orchestra\Support\Collection
     */
    protected $fieldsets;

    /**
     * Set submit button message.
     *
     * @var string
     */
    public $submit = null;

    /**
     * Set the no record message.
     *
     * @var string
     */
    public $format = null;

    /**
     * Selected view path for form layout.
     *
     * @var array
     */
    protected $view = null;

    /**
     * {@inheritdoc}
     */
    protected $definition = array(
        'name'    => null,
        '__call'  => array('fieldsets', 'view', 'hiddens'),
        '__get'   => array('attributes', 'row', 'view', 'hiddens'),
        '__set'   => array('attributes'),
        '__isset' => array('attributes', 'row', 'view', 'hiddens'),
    );

    /**
     * {@inheritdoc}
     */
    protected function initiate()
    {
        $this->fieldsets = new Collection;

        $config = $this->app['config']->get('orchestra/html::form', array());

        foreach ($config as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }

        $this->row = array();
    }

    /**
     * Set fieldset layout (view).
     *
     * <code>
     *      // use default horizontal layout
     *      $fieldset->layout('horizontal');
     *
     *      // use default vertical layout
     *      $fieldset->layout('vertical');
     *
     *      // define fieldset using custom view
     *      $fieldset->layout('path.to.view');
     * </code>
     *
     * @param  string   $name
     * @return void
     */
    public function layout($name)
    {
        if (in_array($name, array('horizontal', 'vertical'))) {
            $this->view = "orchestra/html::form.{$name}";
        } else {
            $this->view = $name;
        }
    }

    /**
     * Attach rows data instead of assigning a model.
     *
     * <code>
     *      // assign a data
     *      $table->with(DB::table('users')->get());
     * </code>
     *
     * @param  array|stdClass|\Illuminate\Database\Eloquent\Model   $row
     * @return mixed
     */
    public function with($row = null)
    {
        if (is_null($row)) {
            return $this->row;
        }

        is_array($row) && $row = new Fluent($row);

        $this->row = $row;
    }

    /**
     * Attach rows data instead of assigning a model.
     *
     * @param  array    $rows
     * @return mixed
     * @see    Grid::with()
     */
    public function row($row = null)
    {
        return $this->with($row);
    }

    /**
     * Create a new Fieldset instance.
     *
     * @param  string   $name
     * @param  \Closure $callback
     * @return Fieldset
     */
    public function fieldset($name, Closure $callback = null)
    {
        $fieldset = new Fieldset($this->app, $name, $callback);

        if (is_null($name = $fieldset->getName())) {
            $name = sprintf('fieldset-%d', $this->fieldsets->count());
        } else {
            $name = Str::slug($name);
        }

        $this->keyMap[$name] = $fieldset;

        return $this->fieldsets->push($fieldset);
    }

    /**
     * Add hidden field.
     *
     * @param  string   $name
     * @param  \Closure $callback
     * @return void
     */
    public function hidden($name, $callback = null)
    {
        $value = data_get($this->row, $name);

        $field = new Fluent(array(
            'name'       => $name,
            'value'      => $value ?: '',
            'attributes' => array(),
        ));

        if ($callback instanceof Closure) {
            call_user_func($callback, $field);
        }

        $this->hiddens[$name] = $this->app['form']->hidden($name, $field->value, $field->attributes);
    }

    /**
     * Find control that match the given id.
     *
     * @param  string   $name
     * @return Field|null
     */
    public function find($name)
    {
        if (Str::contains($name, '.')) {
            list($fieldset, $control) = explode('.', $name, 2);
        } else {
            $fieldset = 'fieldset-0';
            $control = $name;
        }

        if (! array_key_exists($fieldset, $this->keyMap)) {
            throw new InvalidArgumentException("Name [{$name}] is not available.");
        }

        return $this->keyMap[$fieldset]->of($control);
    }

    /**
     * Setup form configuration.
     *
     * @param  PresenterInterface                   $listener
     * @param  string                               $url
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  array                                $attributes
     * @return array
     */
    public function resource(PresenterInterface $listener, $url, Model $model, array $attributes = array())
    {
        $method = 'POST';

        if ($model->exists) {
            $url = "{$url}/{$model->getKey()}";
            $method = 'PUT';
        }

        $attributes['method'] = $method;

        return $this->setup($listener, $url, $model, $attributes);
    }

    /**
     * Setup simple form configuration.
     *
     * @param  PresenterInterface                   $listener
     * @param  string                               $url
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  array                                $attributes
     * @return array
     */
    public function setup(PresenterInterface $listener, $url, $model, array $attributes = array())
    {
        $method = array_get($attributes, 'method', 'POST');
        $url    = $listener->handles($url);

        $attributes = array_merge($attributes, array(
            'url'    => $url,
            'method' => $method,
        ));

        $this->with($model);
        $this->attributes($attributes);
        $listener->setupForm($this);

        return $this;
    }
}