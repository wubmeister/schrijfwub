<?php

namespace App\Form\Field;

use App\HtmlHelper;

/**
 * Class to represent a text field
 */
class Text extends AbstractField
{
	/**
	 * Renders the field and returns the HTML
	 *
	 * @param array $attrs Additional attributes for the field
	 * @return string The HTML for the form field
	 */
	public function render($attrs = [])
	{
		if (isset($this->options['attrs'])) {
			$attrs = array_merge($this->options['attrs'], $attrs);
		}
		$attrs['type'] = isset($this->options['dataType']) ? $this->options['dataType'] : 'text';
		$attrs['name'] = $this->name;
		$attrs['id'] = $this->getId();

		return HtmlHelper::createTag('input', $attrs, true);
	}
}
