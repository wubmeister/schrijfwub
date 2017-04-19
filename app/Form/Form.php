<?php

namespace App\Form;

use App\HtmlHelper;

/**
 * Class to represent a HTML form
 */
class Form
{
	/**
	 * The form method
	 * @var string
	 */
	protected $method;

	/**
	 * The form action
	 * @var string
	 */
	protected $action;

	/**
	 * The form encoding type
	 * @var string
	 */
	protected $enctype;

	/**
	 * The form element attributes
	 * @var array
	 */
	protected $attributes;

	/**
	 * The form values
	 * @var array
	 */
	protected $values;

	/**
	 * The form fields
	 * @var array
	 */
	protected $fields = [];

	/**
	 * Initializes a form with name and options
	 *
	 * @param string $name The name of the form
	 * @param array $options Options for the form. Recognized keys are: method, action, enctype, attrs, defaultValues, startValues ans postValues
	 */
	public function __construct($name, $options = [])
	{
		$this->name = $name;
		$this->method = isset($options['method']) ? strtolower($options['method']) : 'post';
		$this->action = isset($options['action']) ? $options['action'] : '';
		$this->enctype = isset($options['enctype']) ? $options['enctype'] : 'application/x-www-form-urlencoded';
		$this->attributes = isset($options['attrs']) ? $options['attrs'] : [];

		$this->values = isset($options['defaultValues']) ? $options['defaultValues'] : [];
		if (isset($options['startValues'])) $this->values = array_merge($this->values, $options['startValues']);
		if (isset($options['postValues'])) $this->values = array_merge($this->values, $options['postValues']);
	}

	/**
	 * Creates a form with name and options
	 *
	 * @param string $name The name of the form
	 * @param array $options Options for the form. Recognized keys are: method, action, enctype, attrs, defaultValues, startValues ans postValues
	 */
	public static function factory($name, $options = [])
	{
		return new static($name, $options);
	}

	/**
	 * Gets the name of the form
	 *
	 * @return string The name of the form
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Creates a new decorator, wraps it around the form and returns it
	 *
	 * @param string $decorator The decorator name
	 * @param array $options Additional options for the decorator
	 */
	public function decorate($decorator, $options = [])
	{
		$decoClass = 'App\\Form\\Decorator\\Form\\' . ucfirst($decorator);

		if (!class_exists($decoClass)) {
			throw new \Exception('No such decorator: \'' . $decorator . '\'');
		}

		return new $decoClass($this, $options);
	}

	/**
	 * Catches calls to xxxField methods to add a field
	 *
	 * @param string $name The method name
	 * @param array $args The arguments. For xxxField methods, the first argument should be the name of the field. The second argument is optional and contains an array with options
	 */
	public function __call($name, $args)
	{
		if (substr($name, -5) == 'Field') {
			$type = substr($name, 0, -5);
			$typeClass = 'App\\Form\\Field\\' . ucfirst($type);

			if (!class_exists($typeClass)) {
				throw new \Exception('No such field type: \'' . $type . '\'');
			}

			if (count($args) == 0) {
				throw new \Exception('Method ' . $name . ' expects at least one argument: string $name');
			}

			$fieldName = $args[0];
			$label = ucfirst($fieldName);
			$options = null;
			$selectOptions = null;

			foreach ($args as $i => $arg) {
				if ($i > 0) {
					if (is_string($arg)) {
						$label = $arg;
					} else if (is_array($arg)) {
						if (!$options) {
							$options = $arg;
						} else {
							$selectOptions = $arg;
						}
					}
				}
			}

			if (!$options) $options = [];
			if (!$selectOptions) $selectOptions = [];

			$options['value'] = isset($this->values[$fieldName]) ? $this->values[$fieldName] : null;

			$field = new $typeClass($this, $fieldName, $label, $options, $selectOptions);
			$this->fields[$fieldName] = $field;

			return $field;
		}

		throw new \Exception('Undefined method: \'' . $name . '\'');
	}

	/**
	 * Renders the form and returns the HTML
	 *
	 * @return string The HTML
	 */
	public function render($attrs = [])
	{
		$html = $this->open($attrs) . PHP_EOL;
		foreach ($this->fields as $field) {
			$html .= '    ' . (string)$field . PHP_EOL;
		}
		$html .= $this->close() . PHP_EOL;

		return $html;
	}

	/**
	 * Gets the opening tag
	 *
	 * @return string The opening tag
	 */
	public function open($attrs = [])
	{
		return HtmlHelper::createTag('form', array_merge([
			'method' => $this->method,
			'action' => $this->action,
			'enctype' => $this->enctype
		], $this->attributes, $attrs));
	}

	/**
	 * Gets the closing tag
	 *
	 * @return string The closing tag
	 */
	public function close()
	{
		return '</form>';
	}

	/**
	 * Replaces a field with a decorator
	 *
	 * @param string $name The field name
	 * @param Decorator $decorator The decorator
	 */
	public function replaceField($name, $decorator)
	{
		$this->fields[$name] = $decorator;
	}

	public function __toString()
	{
		return $this->render();
	}
}