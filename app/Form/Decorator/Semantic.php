<?php

namespace App\Form\Decorator;

class Semantic extends AbstractDecorator
{
	public function render()
	{
		$html = $this->decorated->render();
		$html = '<div class="field"><label for="' . $this->decorated->getId() . '">' . $this->decorated->getLabel() . '</label>' . $html . '</div>';
		return $html;
	}
}