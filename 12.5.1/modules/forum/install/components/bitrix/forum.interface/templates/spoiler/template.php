<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
// ************************* Input params***************************************************************
// ************************* BASE **********************************************************************
$arParams["TEXT"] = trim($arParams["~TEXT"]);
if (empty($arParams["TEXT"]))
	return ""; 
// *************************/BASE **********************************************************************
// ************************* ADDITIONAL ****************************************************************
$arParams["TITLE"] = trim($arParams["~TITLE"]);
$arParams["TITLE"] = (empty($arParams["TITLE"]) ? GetMessage("F_HIDDEN_TEXT") : $arParams["TITLE"]); 
$arParams["RETURN"] = ($arParams["RETURN"] == "Y" ? "Y" : "N");
// *************************/ADDITIONAL ****************************************************************
// *************************/Input params***************************************************************
$str = '<table class=\'forum-spoiler\'>'.
	'<thead onclick=\'ForumInitSpoiler(this)\'><tr><th><div>'.htmlspecialcharsEx($arParams["TITLE"]).'</div></th></tr></thead>'.
	'<tbody class=\'forum-spoiler\' style=\'display:none;\'><tr><td>'.
		$arParams["TEXT"].
	'</td></tr></tbody>'.
'</table>';
if ($arParams["RETURN"] == "Y")
	$this->__component->arParams["RETURN_DATA"] = $str; 
else
	echo $str; 
?>