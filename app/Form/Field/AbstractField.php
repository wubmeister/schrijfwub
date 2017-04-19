<?php

namespace App\Form\Field;

/**
 * Abstract base class to represent a form field
 */
abstract class AbstractField
{
	/**
	 * The owner form
	 * @var App\Form\Form
	 */
	protected $form;

	/**
	 * The name of the field
	 * @var string
	 */
	protected $name;

	/**
	 * The ID attribute of the field
	 * @var string
	 */
	protected $id;

	/**
	 * The label of the field
	 * @var string
	 */
	protected $label;

	/**
	 * The options for the field
	 * @var array
	 */
	protected $options;

	/**
	 * The selection options for the field
	 * @var string
	 */
	protected $selectOptions;

	/**
	 * Initializes the field instance
	 *
	 * @param App\Form\Form $form The owner form
	 * @param string $name The name of the field
	 * @param string $label The label for the field
	 * @param array $options Options for the field
	 * @param array $selectOptions Selection options for the field
	 */
	public function __construct($form, $name, $label = null, $options = [], $selectOptions = [])
	{
		$this->form = $form;
		$this->name = $name;
		$this->label = $label ? $label : ucfirst($name);
		$this->options = $options;
		$this->selectOptions = $selectOptions;
	}

	/**
	 * Gets a valid ID for the field
	 *
	 * @return string The ID attribute
	 */
	public function getId()
	{
		if (!$this->id) {
			$this->id = $this->form->getName() . '_' . $this->name;
			$this->id = str_replace('[]', '', $this->id);
			$this->id = preg_replace('/\[([^\]]+)\]/', '_$1', $this->id);
		}
		return $this->id;
	}

	/**
	 * Gets the label
	 *
	 * @return string The label
	 */
	public function getLabel()
	{
		return $this->label;
	}

	/**
	 * Renders the field and returns the HTML
	 *
	 * @param array $attrs Additional attributes for the field
	 * @return string The HTML for the form field
	 */
	abstract public function render($attrs = []);

	/**
	 * Alias for 'render', allows one to output the field as a string
	 *
	 * @return string The HTML for the form field
	 */
	public function __toString()
	{
		return $this->render();
	}

	/**
	 * Creates a decorator and wraps it around the form field
	 *
	 * @param string $decorator The decrator name
	 * @param array $options Options for the decorator
	 * @return Decorator The decorator
	 */
	public function decorate($name, $options = [])
	{
		$decoClass = 'App\\Form\\Decorator\\' . ucfirst($name);

		if (!class_exists($decoClass)) {
			throw new \Exception('No such decorator: \'' . $name . '\'');
		}

		$decorator = new $decoClass($this, $options);
		$this->form->replaceField($this->name, $decorator);

		return $decorator;
	}

	/**
	 * Updates references from the form to the outer decorator
	 *
	 * @param App\Form\Decorator $decorator The decorator
	 */
	public function decorateWithObject($decorator)
	{
		$this->form->replaceField($this->name, $decorator);
	}
}