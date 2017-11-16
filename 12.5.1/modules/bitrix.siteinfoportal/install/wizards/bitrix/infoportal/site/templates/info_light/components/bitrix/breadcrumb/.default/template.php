<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

//delayed function must return a string

__IncludeLang(dirname(__FILE__).'/lang/'.LANGUAGE_ID.'/'.basename(__FILE__));

$curPage = $GLOBALS['APPLICATION']->GetCurPage($get_index_page=false);

if ($curPage != SITE_DIR)
{
	if (empty($arResult) || $curPage != $arResult[count($arResult)-1]['LINK'])
		$arResult[] = array('TITLE' =>  htmlspecialcharsback($GLOBALS['APPLICATION']->GetTitle(false, true)), 'LINK' => $curPage);
}

if(empty($arResult))
	return "";
	
$strReturn = '<div id="breadcrumb">';

for($index = 0, $itemSize = count($arResult); $index < $itemSize; $index++)
{
	$strReturn .= '<i>&ndash;</i>';

	$title = htmlspecialcharsex($arResult[$index]["TITLE"]);
	
	if($arResult[$index]["LINK"] <> "")
		$strReturn .= '<a href="'.$arResult[$index]["LINK"].'" title="'.$title.'">'.$title.'</a>';
	else
		$strReturn .= '<span>'.$title.'</span>';
}

$strReturn .= '</div>';

return $strReturn;
?>