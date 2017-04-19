<?php

namespace App\Form\Decorator\Form;

class Semantic extends AbstractFormDecorator
{
	public function open()
	{
		$html = $this->decorated->open([ 'class' => 'ui form' ]);
		return $html;
	}
}