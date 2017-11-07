<?
function Number2Word_Rus($source, $IS_MONEY = "Y", $currency = "")
{
	$result = "";

	if (strlen($currency) <= 0 || $currency == "RUR")
		$currency = "RUB";

	$arNumericLang = array(
		"RUB" => array(
			"1c" => "сто ",
			"2c" => "двести ",
			"3c" => "триста ",
			"4c" => "четыреста ",
			"5c" => "п€тьсот ",
			"6c" => "шестьсот ",
			"7c" => "семьсот ",
			"8c" => "восемьсот ",
			"9c" => "дев€тьсот ",
			"1d0e" => "дес€ть ",
			"1d1e" => "одиннадцать ",
			"1d2e" => "двенадцать ",
			"1d3e" => "тринадцать ",
			"1d4e" => "четырнадцать ",
			"1d5e" => "п€тнадцать ",
			"1d6e" => "шестнадцать ",
			"1d7e" => "семнадцать ",
			"1d8e" => "восемнадцать ",
			"1d9e" => "дев€тнадцать ",
			"2d" => "двадцать ",
			"3d" => "тридцать ",
			"4d" => "сорок ",
			"5d" => "п€тьдес€т ",
			"6d" => "шестьдес€т ",
			"7d" => "семьдес€т ",
			"8d" => "восемьдес€т ",
			"9d" => "дев€носто ",
			"5e" => "п€ть ",
			"6e" => "шесть ",
			"7e" => "семь ",
			"8e" => "восемь ",
			"9e" => "дев€ть ",
			"1et" => "одна тыс€ча ",
			"2et" => "две тыс€чи ",
			"3et" => "три тыс€чи ",
			"4et" => "четыре тыс€чи ",
			"1em" => "один миллион ",
			"2em" => "два миллиона ",
			"3em" => "три миллиона ",
			"4em" => "четыре миллиона ",
			"1eb" => "один миллиард ",
			"2eb" => "два миллиарда ",
			"3eb" => "три миллиарда ",
			"4eb" => "четыре миллиарда ",
			"1e." => "один рубль ",
			"2e." => "два рубл€ ",
			"3e." => "три рубл€ ",
			"4e." => "четыре рубл€ ",
			"1e" => "один ",
			"2e" => "два ",
			"3e" => "три ",
			"4e" => "четыре ",
			"11k" => "11 копеек",
			"12k" => "12 копеек",
			"13k" => "13 копеек",
			"14k" => "14 копеек",
			"1k" => "1 копейка",
			"2k" => "2 копейки",
			"3k" => "3 копейки",
			"4k" => "4 копейки",
			"." => "рублей ",
			"t" => "тыс€ч ",
			"m" => "миллионов ",
			"b" => "миллиардов ",
			"k" => " копеек",
		),
		"UAH" => array(
			"1c" => "сто ",
			"2c" => "дв≥ст≥ ",
			"3c" => "триста ",
			"4c" => "чотириста ",
			"5c" => "п'€тсот ",
			"6c" => "ш≥стсот ",
			"7c" => "с≥мсот ",
			"8c" => "в≥с≥мсот ",
			"9c" => "дев'€тьсот ",
			"1d0e" => "дес€ть ",
			"1d1e" => "одинадц€ть ",
			"1d2e" => "дванадц€ть ",
			"1d3e" => "тринадц€ть ",
			"1d4e" => "чотирнадц€ть ",
			"1d5e" => "п'€тнадц€ть ",
			"1d6e" => "ш≥стнадц€ть ",
			"1d7e" => "с≥мнадц€ть ",
			"1d8e" => "в≥с≥мнадц€ть ",
			"1d9e" => "дев'€тнадц€ть ",
			"2d" => "двадц€ть ",
			"3d" => "тридц€ть ",
			"4d" => "сорок ",
			"5d" => "п'€тдес€т ",
			"6d" => "ш≥стдес€т ",
			"7d" => "с≥мдес€т ",
			"8d" => "в≥с≥мдес€т ",
			"9d" => "дев'€носто ",
			"5e" => "п'€ть ",
			"6e" => "ш≥сть ",
			"7e" => "с≥м ",
			"8e" => "в≥с≥м ",
			"9e" => "дев'€ть ",
			"1e." => "один гривн€ ",
			"2e." => "два гривн≥ ",
			"3e." => "три гривн≥ ",
			"4e." => "чотири гривн≥ ",
			"1e" => "один ",
			"2e" => "два ",
			"3e" => "три ",
			"4e" => "чотири ",
			"1et" => "одна тис€ча ",
			"2et" => "дв≥ тис€ч≥ ",
			"3et" => "три тис€ч≥ ",
			"4et" => "чотири тис€ч≥ ",
			"1em" => "один м≥льйон ",
			"2em" => "два м≥льйона ",
			"3em" => "три м≥льйона ",
			"4em" => "чотири м≥льйона ",
			"1eb" => "один м≥ль€рд ",
			"2eb" => "два м≥ль€рда ",
			"3eb" => "три м≥ль€рда ",
			"4eb" => "чотири м≥ль€рда ",
			"11k" => "11 коп≥йок",
			"12k" => "12 коп≥йок",
			"13k" => "13 коп≥йок",
			"14k" => "14 коп≥йок",
			"1k" => "1 коп≥йка",
			"2k" => "2 коп≥йки",
			"3k" => "3 коп≥йки",
			"4k" => "4 коп≥йки",
			"." => "гривень ",
			"t" => "тис€ч ",
			"m" => "м≥льйон≥в ",
			"b" => "м≥ль€рд≥в ",
			"k" => " коп≥йок",
		)
	);


	// k - копейки
	if ($IS_MONEY == "Y")
	{
		$source = DoubleVal($source);

		$dotpos = strpos($source, ".");
		if ($dotpos === false)
		{
			$ipart = $source;
			$fpart = "";
		}
		else
		{
			$ipart = substr($source, 0, $dotpos);
			$fpart = substr($source, $dotpos + 1);
		}

		$fpart = substr($fpart, 0, 2);
		while (strlen($fpart)<2) $fpart .= "0";
	}
	else
	{
		$source = IntVal($source);
		$ipart = $source;
		$fpart = "";
	}

	while ($ipart[0]=="0") $ipart = substr($ipart, 1);

	$ipart1 = StrRev($ipart);
	$ipart = "";
	$i = 0;
	while ($i<strlen($ipart1))
	{
		$ipart_tmp = $ipart1[$i];
		// t - тыс€чи; m - милионы; b - миллиарды;
		// e - единицы; d - дес€тки; c - сотни;
		if ($i % 3 == 0)
		{
			if ($i==0) $ipart_tmp .= "e";
			elseif ($i==3) $ipart_tmp .= "et";
			elseif ($i==6) $ipart_tmp .= "em";
			elseif ($i==9) $ipart_tmp .= "eb";
			else $ipart_tmp .= "x";
		}
		elseif ($i % 3 == 1) $ipart_tmp .= "d";
		elseif ($i % 3 == 2) $ipart_tmp .= "c";
		$ipart = $ipart_tmp.$ipart;
		$i++;
	}

	if ($IS_MONEY == "Y")
	{
		$result = $ipart.".".$fpart."k";
	}
	else
	{
		$result = $ipart;
	}

	if ($result[0] == ".")
		$result = "ноль ".$result;

	$result = str_replace("0c0d0et", "", $result);
	$result = str_replace("0c0d0em", "", $result);
	$result = str_replace("0c0d0eb", "", $result);

	$result = str_replace("0c", "", $result);
	$result = str_replace("1c", $arNumericLang[$currency]["1c"], $result);
	$result = str_replace("2c", $arNumericLang[$currency]["2c"], $result);
	$result = str_replace("3c", $arNumericLang[$currency]["3c"], $result);
	$result = str_replace("4c", $arNumericLang[$currency]["4c"], $result);
	$result = str_replace("5c", $arNumericLang[$currency]["5c"], $result);
	$result = str_replace("6c", $arNumericLang[$currency]["6c"], $result);
	$result = str_replace("7c", $arNumericLang[$currency]["7c"], $result);
	$result = str_replace("8c", $arNumericLang[$currency]["8c"], $result);
	$result = str_replace("9c", $arNumericLang[$currency]["9c"], $result);

	$result = str_replace("1d0e", $arNumericLang[$currency]["1d0e"], $result);
	$result = str_replace("1d1e", $arNumericLang[$currency]["1d1e"], $result);
	$result = str_replace("1d2e", $arNumericLang[$currency]["1d2e"], $result);
	$result = str_replace("1d3e", $arNumericLang[$currency]["1d3e"], $result);
	$result = str_replace("1d4e", $arNumericLang[$currency]["1d4e"], $result);
	$result = str_replace("1d5e", $arNumericLang[$currency]["1d5e"], $result);
	$result = str_replace("1d6e", $arNumericLang[$currency]["1d6e"], $result);
	$result = str_replace("1d7e", $arNumericLang[$currency]["1d7e"], $result);
	$result = str_replace("1d8e", $arNumericLang[$currency]["1d8e"], $result);
	$result = str_replace("1d9e", $arNumericLang[$currency]["1d9e"], $result);

	$result = str_replace("0d", "", $result);
	$result = str_replace("2d", $arNumericLang[$currency]["2d"], $result);
	$result = str_replace("3d", $arNumericLang[$currency]["3d"], $result);
	$result = str_replace("4d", $arNumericLang[$currency]["4d"], $result);
	$result = str_replace("5d", $arNumericLang[$currency]["5d"], $result);
	$result = str_replace("6d", $arNumericLang[$currency]["6d"], $result);
	$result = str_replace("7d", $arNumericLang[$currency]["7d"], $result);
	$result = str_replace("8d", $arNumericLang[$currency]["8d"], $result);
	$result = str_replace("9d", $arNumericLang[$currency]["9d"], $result);

	$result = str_replace("0e", "", $result);
	$result = str_replace("5e", $arNumericLang[$currency]["5e"], $result);
	$result = str_replace("6e", $arNumericLang[$currency]["6e"], $result);
	$result = str_replace("7e", $arNumericLang[$currency]["7e"], $result);
	$result = str_replace("8e", $arNumericLang[$currency]["8e"], $result);
	$result = str_replace("9e", $arNumericLang[$currency]["9e"], $result);

	$result = str_replace("1et", $arNumericLang[$currency]["1et"], $result);
	$result = str_replace("2et", $arNumericLang[$currency]["2et"], $result);
	$result = str_replace("3et", $arNumericLang[$currency]["3et"], $result);
	$result = str_replace("4et", $arNumericLang[$currency]["4et"], $result);
	$result = str_replace("1em", $arNumericLang[$currency]["1em"], $result);
	$result = str_replace("2em", $arNumericLang[$currency]["2em"], $result);
	$result = str_replace("3em", $arNumericLang[$currency]["3em"], $result);
	$result = str_replace("4em", $arNumericLang[$currency]["4em"], $result);
	$result = str_replace("1eb", $arNumericLang[$currency]["1eb"], $result);
	$result = str_replace("2eb", $arNumericLang[$currency]["2eb"], $result);
	$result = str_replace("3eb", $arNumericLang[$currency]["3eb"], $result);
	$result = str_replace("4eb", $arNumericLang[$currency]["4eb"], $result);


	if ($IS_MONEY == "Y")
	{
		$result = str_replace("1e.", $arNumericLang[$currency]["1e."], $result);
		$result = str_replace("2e.", $arNumericLang[$currency]["2e."], $result);
		$result = str_replace("3e.", $arNumericLang[$currency]["3e."], $result);
		$result = str_replace("4e.", $arNumericLang[$currency]["4e."], $result);
	}
	else
	{
		$result = str_replace("1e", $arNumericLang[$currency]["1e"], $result);
		$result = str_replace("2e", $arNumericLang[$currency]["2e"], $result);
		$result = str_replace("3e", $arNumericLang[$currency]["3e"], $result);
		$result = str_replace("4e", $arNumericLang[$currency]["4e"], $result);
	}

	if ($IS_MONEY == "Y")
	{
		$result = str_replace("11k", $arNumericLang[$currency]["11k"], $result);
		$result = str_replace("12k", $arNumericLang[$currency]["12k"], $result);
		$result = str_replace("13k", $arNumericLang[$currency]["13k"], $result);
		$result = str_replace("14k", $arNumericLang[$currency]["14k"], $result);
		$result = str_replace("1k", $arNumericLang[$currency]["1k"], $result);
		$result = str_replace("2k", $arNumericLang[$currency]["2k"], $result);
		$result = str_replace("3k", $arNumericLang[$currency]["3k"], $result);
		$result = str_replace("4k", $arNumericLang[$currency]["4k"], $result);
	}

	if ($IS_MONEY == "Y")
		$result = str_replace(".", $arNumericLang[$currency]["."], $result);

	$result = str_replace("t", $arNumericLang[$currency]["t"], $result);
	$result = str_replace("m", $arNumericLang[$currency]["m"], $result);
	$result = str_replace("b", $arNumericLang[$currency]["b"], $result);

	if ($IS_MONEY == "Y")
		$result = str_replace("k", $arNumericLang[$currency]["k"], $result);

	return (ToUpper(substr($result, 0, 1)) . substr($result, 1));
}
?>
