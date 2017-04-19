<?php

namespace App\Form\Decorator;

/**
 * Abstract base class for decorators
 */
abstract class AbstractDecorator
{
	/**
	 * The decoarated object
	 * @var App\Form\Form|App\Form\Field\AbstractField
	 */
	protected $decorated;

	/**
	 * Options for the decorator
	 * @var array
	 */
	protected $options;

	/**
	 * Initializes the decorator
	 *
	 * @param object $decorated The decorated object
	 * @param array $options The decorator options
	 */
	public function __construct($decorated, $options)
	{
		$this->decorated = $decorated;
		$this->options = $options;
	}

	/**
	 * Passthrough for callng functions on the decorated object
	 *
	 * @param object $decorated The decorated object
	 * @param array $options The decorator options
	 */
	public function __call($name, $args)
	{
		return call_user_func_array([ $this->decorated, $name ], $args);
	}

	/**
	 * Creates a decorator and wraps it around this decorator
	 *
	 * @param string $decorator The decorator name
	 * @param array $options The options
	 */
	public function decorate($name, $options)
	{
		$classBase = str_replace('/', '\\', dirname(str_replace('\\', '/', get_class($this))));
		$decoClass = $classBase . '\\' . ucfirst($name);

		if (!class_exists($decoClass)) {
			throw new \Exception('No such decorator: \'' . $name . '\'');
		}

		$decorator = new $decoClass($this, $options);

		if (($this->decorated instanceof AbstractDecorator) || method_exists($this->decorated, 'decorateWithObject')) {
			$this->decorated->decorateWithObject($this);
		}
	}

	/**
	 * @return string The HTML for the decorated object
	 */
	abstract public function render();

	public function __toString()
	{
		return $this->render();
	}
}