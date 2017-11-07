<?php
global $STEMMING_RU_VOWELS;
$STEMMING_RU_VOWELS = "юехнсшщчъ";
global $STEMMING_RU_PERFECTIVE_GERUND;
$STEMMING_RU_PERFECTIVE_GERUND = "/(шбьхяэ|хбьхяэ|ъбьхяэ|юбьхяэ|шбьх|хбьх|ъбьх|юбьх|шб|хб|ъб|юб)$/".BX_UTF_PCRE_MODIFIER;

$STEMMING_RU_ADJECTIVE=array("ее"=>2, "хе"=>2, "ше"=>2, "не"=>2, "хлх"=>3, "шлх"=>3, "еи"=>2, "хи"=>2, "ши"=>2, "ни"=>2, "ел"=>2, "хл"=>2, "шл"=>2, "нл"=>2, "ецн"=>2, "нцн"=>3, "елс"=>3, "нлс"=>3, "ху"=>2, "шу"=>2, "сч"=>2, "чч"=>2, "юъ"=>2, "ъъ"=>2, "нч"=>2, "еч"=>2);
$STEMMING_RU_PARTICIPLE_GR1=array("ел"=>2, "мм"=>2, "бь"=>2, "чы"=>2, "ы"=>1);
$STEMMING_RU_PARTICIPLE_GR2=array("хбь"=>3, "шбь"=>3, "счы"=>3);
$STEMMING_RU_ADJECTIVAL_GR1=array();
$STEMMING_RU_ADJECTIVAL_GR2=array();
foreach($STEMMING_RU_ADJECTIVE as $i => $il)
{
	foreach($STEMMING_RU_PARTICIPLE_GR1 as $j => $jl) $STEMMING_RU_ADJECTIVAL_GR1[$j.$i]=$jl+$il;
	foreach($STEMMING_RU_PARTICIPLE_GR2 as $j => $jl) $STEMMING_RU_ADJECTIVAL_GR2[$j.$i]=$jl+$il;
}
global $STEMMING_RU_ADJECTIVAL1;
arsort($STEMMING_RU_ADJECTIVAL_GR1);
$STEMMING_RU_ADJECTIVAL1="/([юъ])(".implode("|", array_keys($STEMMING_RU_ADJECTIVAL_GR1)).")$/".BX_UTF_PCRE_MODIFIER;

global $STEMMING_RU_ADJECTIVAL2;
foreach($STEMMING_RU_ADJECTIVE as $i => $il)
	$STEMMING_RU_ADJECTIVAL_GR2[$i]=$il;
arsort($STEMMING_RU_ADJECTIVAL_GR2);
$STEMMING_RU_ADJECTIVAL2="/(".implode("|", array_keys($STEMMING_RU_ADJECTIVAL_GR2)).")$/".BX_UTF_PCRE_MODIFIER;

global $STEMMING_RU_VERB1;
$STEMMING_RU_VERB1="/([юъ])(ммн|ере|ире|еьэ|кю|мю|кх|ел|кн|мн|ер|чр|мш|рэ|и|к|м)$/".BX_UTF_PCRE_MODIFIER;

global $STEMMING_RU_VERB2;
$STEMMING_RU_VERB2="/(еире|сире|хкю|шкю|емю|хре|хкх|шкх|хкн|шкн|емн|сер|счр|емш|хрэ|шрэ|хьэ|еи|си|хк|шк|хл|шл|ем|ър|хр|шр|сч|ч)$/".BX_UTF_PCRE_MODIFIER;
global $STEMMING_RU_NOUN;
$STEMMING_RU_NOUN="/(хълх|хъу|хел|хъл|юлх|ълх|эъ|хъ|эч|хч|ъу|юу|нл|юл|ел|ъл|хи|ни|еи|хеи|хх|ех|эе|хе|нб|еб|ч|э|ш|с|н|и|х|е|ъ|ю)$/".BX_UTF_PCRE_MODIFIER;
function stemming_letter_ru()
{
	return "╦ИЖСЙЕМЦЬЫГУЗТШБЮОПНКДФЩЪВЯЛХРЭАЧ╗ижсйемцьыгузтшбюопнкдфщъвялхрэач";
}
function stemming_ru_sort($a, $b)
{
	$al = strlen($a);
	$bl = strlen($b);
	if($al == $bl)
		return 0;
	elseif($al < $bl)
		return 1;
	else
		return -1;
}
function stemming_stop_ru($sWord)
{
	if(strlen($sWord) < 2)
		return false;
	static $stop_list = false;
	if(!$stop_list)
	{
		$stop_list = array (
			"QUOTE"=>0,"HTTP"=>0,"WWW"=>0,"RU"=>0,"IMG"=>0,"GIF"=>0,"аег"=>0,"аш"=>0,"ашк"=>0,
			"ашр"=>0,"бюл"=>0,"бюь"=>0,"бн"=>0,"бнр"=>0,"бяе"=>0,"бш"=>0,"цде"=>0,"дю"=>0,
			"дюф"=>0,"дкъ"=>0,"дн"=>0,"ец"=>0,"еяк"=>0,"еяр"=>0,"еы"=>0,"фе"=>0,"гю"=>0,
			"хг"=>0,"хкх"=>0,"хл"=>0,"ху"=>0,"йюй"=>0,"йнцд"=>0,"йрн"=>0,"кх"=>0,"кха"=>0,
			"лем"=>0,"лме"=>0,"лн"=>0,"лш"=>0,"мю"=>0,"мюд"=>0,"ме"=>0,"мер"=>0,"мх"=>0,
			"мн"=>0,"мс"=>0,"на"=>0,"нм"=>0,"нр"=>0,"нвем"=>0,"он"=>0,"онд"=>0,"опх"=>0,
			"опн"=>0,"яюл"=>0,"яеа"=>0,"ябн"=>0,"рюй"=>0,"рюл"=>0,"реа"=>0,"рн"=>0,"рнф"=>0,
			"рнкэй"=>0,"рср"=>0,"рш"=>0,"сф"=>0,"унр"=>0,"вец"=>0,"вел"=>0,"врн"=>0,"врна"=>0,
			"щр"=>0,"щрнр"=>0,
		);
		if(defined("STEMMING_STOP_RU"))
		{
			foreach(explode(",", STEMMING_STOP_RU) as $word)
			{
				$word = trim($word);
				if(strlen($word)>0)
					$stop_list[$word]=0;
			}
		}
	}
	return !array_key_exists($sWord, $stop_list);
}

function stemming_upper_ru($sText)
{
	return str_replace(array("╗"), array("е"), ToUpper($sText, "ru"));
}

function stemming_ru($word, $flags = 0)
{
	global $STEMMING_RU_VOWELS;
	global $STEMMING_RU_PERFECTIVE_GERUND;
	global $STEMMING_RU_ADJECTIVAL1;
	global $STEMMING_RU_ADJECTIVAL2;
	global $STEMMING_RU_VERB1;
	global $STEMMING_RU_VERB2;
	global $STEMMING_RU_NOUN;
	//There is a 33rd letter, ╦ (?), but it is rarely used, and we assume it is mapped into Е (e).
	$word=str_replace("╗", "е", $word);
	//Exceptions
	static $STEMMING_RU_EX = array(
		"аеге"=>true,
		"ашкэ"=>true,
		"лемч"=>true,
		"цпюмюр"=>true,
		"цпюмхр"=>true,
		"реплхмюк"=>true,
		"хкх"=>true,
		"псйюб"=>true,
		"опхел"=>true,
	);
	if(isset($STEMMING_RU_EX[$word]))
		return $word;

	//HERE IS AN ATTEMPT TO STEM RUSSIAN SECOND NAMES BEGINS
	//http://www.gramma.ru/SPR/?id=2.8
	if($flags & 1)
	{
		if(preg_match("/(нб|еб)$/", $word))
		{
			return array(
				stemming_ru($word."ю"),
				stemming_ru($word),
			);
		}
		if(preg_match("/(нб|еб)(ю|с|шл|е)$/", $word, $found))
		{
			return array(
				stemming_ru($word),
				stemming_ru(substr($word, 0, -strlen($found[2]))),
			);
		}
	}
	//HERE IS AN ATTEMPT TO STEM RUSSIAN SECOND NAMES ENDS

	//In any word, RV is the region after the first vowel, or the end of the word if it contains no vowel.
	//All tests take place in the the RV part of the word.
	$found=array();
	if(preg_match("/^(.*?[$STEMMING_RU_VOWELS])(.+)$/".BX_UTF_PCRE_MODIFIER, $word, $found))
	{
		$rv = $found[2];
		$word = $found[1];
	}
	else
	{
		return $word;
	}

	//Do each of steps 1, 2, 3 and 4.
	//Step 1: Search for a PERFECTIVE GERUND ending. If one is found remove it, and that is then the end of step 1.


	if(preg_match($STEMMING_RU_PERFECTIVE_GERUND, $rv, $found))
	{
		switch($found[0]) {
			case "юб":
			case "юбьх":
			case "юбьхяэ":
			case "ъб":
			case "ъбьх":
			case "ъбьхяэ":
				$rv = substr($rv, 0, 1-strlen($found[0]));
				break;
			default:
				$rv = substr($rv, 0, -strlen($found[0]));
		}
	}
	//Otherwise try and remove a REFLEXIVE ending, and then search in turn for
	// (1) an ADJECTIVE,
	// (2) a VERB or (3)
	// a NOUN ending.
	// As soon as one of the endings (1) to (3) is found remove it, and terminate step 1.
	else
	{
		$rv = preg_replace("/(яъ|яэ)$/".BX_UTF_PCRE_MODIFIER, "", $rv);
		//ADJECTIVAL
		if(preg_match($STEMMING_RU_ADJECTIVAL1, $rv, $found))
			$rv = substr($rv, 0, -strlen($found[2]));
		elseif(preg_match($STEMMING_RU_ADJECTIVAL2, $rv, $found))
			$rv = substr($rv, 0, -strlen($found[0]));
		elseif(preg_match($STEMMING_RU_VERB1, $rv, $found))
			$rv = substr($rv, 0, -strlen($found[2]));
		elseif(preg_match($STEMMING_RU_VERB2, $rv, $found))
			$rv = substr($rv, 0, -strlen($found[0]));
		else
			$rv = preg_replace($STEMMING_RU_NOUN, "", $rv);
	}

	//Step 2: If the word ends with Х (i), remove it.
	if(substr($rv, -1) == "х")
		$rv = substr($rv, 0, -1);
	//Step 3: Search for a DERIVATIONAL ending in R2 (i.e. the entire ending must lie in R2), and if one is found, remove it.
	//R1 is the region after the first non-vowel following a vowel, or the end of the word if there is no such non-vowel.
	if(preg_match("/(нярэ|няр)$/".BX_UTF_PCRE_MODIFIER, $rv))
	{
		$R1=0;
		$rv_len = strlen($rv);
		while( ($R1<$rv_len) && (strpos($STEMMING_RU_VOWELS, substr($rv,$R1,1))!==false) )
			$R1++;
		if($R1 < $rv_len)
			$R1++;
		//R2 is the region after the first non-vowel following a vowel in R1, or the end of the word if there is no such non-vowel.
		$R2 = $R1;
		while( ($R2<$rv_len) && (strpos($STEMMING_RU_VOWELS, substr($rv,$R2,1))===false) )
			$R2++;
		while( ($R2<$rv_len) && (strpos($STEMMING_RU_VOWELS, substr($rv,$R2,1))!==false) )
			$R2++;
		if($R2 < $rv_len)
			$R2++;
		//"нярэ", "няр"
		if((substr($rv, -4) == "нярэ") && ($rv_len >= ($R2+4)))
			$rv = substr($rv, 0, $rv_len - 4);
		elseif((substr($rv, -3) == "няр") && ($rv_len >= ($R2+3)))
			$rv = substr($rv, 0, $rv_len - 3);
	}
	//Step 4: (1) Undouble М (n), or, (2) if the word ends with a SUPERLATIVE ending, remove it and undouble М (n), or (3) if the word ends Э (') (soft sign) remove it.
	$rv = preg_replace("/(еиье|еиь)$/".BX_UTF_PCRE_MODIFIER, "", $rv);
	$r = preg_replace("/мм$/".BX_UTF_PCRE_MODIFIER, "м", $rv);
	if($r == $rv)
		$rv = preg_replace("/э$/".BX_UTF_PCRE_MODIFIER, "", $rv);
	else
		$rv = $r;

	return $word.$rv;
}
?>
