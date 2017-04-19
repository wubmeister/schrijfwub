<?php

namespace App\Form\Decorator\Form;

use App\Form\Decorator\AbstractDecorator;

/**
 * Abstract base class for form decorators
 */
class AbstractFormDecorator extends AbstractDecorator
{
	public function render()
	{
		$html = $this->open() . PHP_EOL;
		foreach ($this->decorated->getFields() as $field) {
			$html .= '    ' . $field->render() . PHP_EOL;
		}
		$html .= $this->close() . PHP_EOL;

		return $html;
	}
}