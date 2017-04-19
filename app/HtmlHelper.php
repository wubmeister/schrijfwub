<?php

namespace App;

/**
 * Helper class to easilly create HTML
 */
class HtmlHelper
{
	public static function buildAttrs($attrs)
	{
		$html = '';
		foreach ($attrs as $key => $value) {
			$html .= ' ' . $key . '="' . $value . '"';
		}

		return substr($html, 1);
	}

	public static function createTag($tagName, $attrs, $selfClosing = false)
	{
		$html = '<' . $tagName . ' ' . self::buildAttrs($attrs) . ($selfClosing ? ' />' : '>');
		return $html;
	}
}
