<?php
namespace Bitrix\Main\Text;

class XmlConverter
	extends Converter
{
	public function encode($text, $textType = "")
	{
		if (is_object($text))
			return $text;

		return String::htmlspecialchars($text);
	}

	public function decode($text, $textType = "")
	{
		if (is_object($text))
			return $text;

		return String::htmlspecialchars_decode($text);
	}
}
