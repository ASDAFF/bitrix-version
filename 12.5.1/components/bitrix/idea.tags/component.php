<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule("blog"))
{
	ShowError(GetMessage("BLOG_MODULE_NOT_INSTALL"));
	return;
}

$arParams["BLOG_URL"] = preg_replace("/[^a-zA-Z0-9_-]/is", "", Trim($arParams["BLOG_URL"]));

if(!array_key_exists("PATH_TO_BLOG_CATEGORY", $arParams) || !is_string($arParams["PATH_TO_BLOG_CATEGORY"]))
    $arParams["PATH_TO_BLOG_CATEGORY"] = "";

//0 no limit
$arParams["TAGS_COUNT"] = intval($arParams["TAGS_COUNT"]);
$arResult["CATEGORY"] = Array();

if ($arBlog = CBlog::GetByUrl($arParams["BLOG_URL"]))
{
	if($arBlog["ACTIVE"] == "Y")
	{
		$arGroup = CBlogGroup::GetByID($arBlog["GROUP_ID"]);
		if($arGroup["SITE_ID"] == SITE_ID)
		{
			$arResult["BLOG"] = $arBlog;
                        $toCnt = array();

                        $res = CBlogCategory::GetList(Array("NAME" => "ASC"), Array("BLOG_ID" => $arBlog["ID"]));
                        while ($arCategory=$res->GetNext())
                        {
                            $arSumCat[$arCategory["ID"]] = Array(
                                "ID" => $arCategory["ID"],
                                "NAME" => $arCategory["NAME"],
                            );
                            $toCnt[] = $arCategory['ID'];
                        }

                        $resCnt = CBlogPostCategory::GetList(Array(), Array("BLOG_ID" => $arBlog["ID"], "CATEGORY_ID"=> $toCnt), Array("CATEGORY_ID"), false, array("ID", "BLOG_ID", "CATEGORY_ID", "NAME"));
                        while($arCategoryCount = $resCnt->Fetch())
                        {
                            if(IntVal($arSumCat[$arCategoryCount["CATEGORY_ID"]]["ID"])>0)
                            {
                                $arSumCat[$arCategoryCount["CATEGORY_ID"]]["CNT"] = $arCategoryCount['CNT'];
                                $arSumCat[$arCategoryCount["CATEGORY_ID"]]["SIZE"] = round(log(log($arCategoryCount['CNT']+1)+1)*100); //53% minimal size
                                $arSumCat[$arCategoryCount["CATEGORY_ID"]]["URL"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_BLOG_CATEGORY"], array("category_id" => $arCategoryCount["CATEGORY_ID"]));
                            }
                        }
                        if(!empty($arSumCat))
                        {
                            //Remove empty tags
                            foreach($arSumCat as $id => $arTag)
                                if(!array_key_exists("CNT", $arTag))
                                    unset($arSumCat[$id]);
                                
                            $arResult["CATEGORY"] = $arSumCat;

                            if($arParams["TAGS_COUNT"]>0)
                            {
                                //Sort by CNT
                                usort($arResult["CATEGORY"], create_function('$a,$b', 'if ($a["CNT"] == $b["CNT"]) return 0; return ($a["CNT"] > $b["CNT"]) ? -1 : 1;'));
                                $arResult["CATEGORY"] = array_slice($arResult["CATEGORY"], 0, $arParams["TAGS_COUNT"]);
                            }
                            //Re-Sort by NAME
                            usort($arResult["CATEGORY"], create_function('$a,$b', 'if ($a["NAME"] == $b["NAME"]) return 0; return ($a["NAME"] < $b["NAME"]) ? -1 : 1;'));
                        }
		}
		else
			$arResult["FATAL_ERROR_MESSAGE"] = GetMessage("BLOG_ERR_NO_BLOG");
	}
	else
		$arResult["FATAL_ERROR_MESSAGE"] = GetMessage("BLOG_ERR_NO_BLOG");
}
else
	$arResult["FATAL_ERROR_MESSAGE"] = GetMessage("BLOG_ERR_NO_BLOG");

$this->IncludeComponentTemplate();
?>