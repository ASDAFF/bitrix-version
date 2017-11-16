<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule("blog"))
{
	ShowError(GetMessage("BLOG_MODULE_NOT_INSTALL"));
	return;
}

if (!CModule::IncludeModule("idea"))
{
	ShowError(GetMessage("IDEA_MODULE_NOT_INSTALL"));
	return;
}

//ACTION PROCESSING
if($_REQUEST["ACTION"])
{
    switch ($_REQUEST["ACTION"] && check_bitrix_sessid()) 
    {
        case "DELETE":
            if($_REQUEST["ID"])
                CIdeaManagment::getInstance()->Notification()->getEmailNotify()->Delete($_REQUEST["ID"]);
            LocalRedirect($APPLICATION->GetCurPageParam("", array("ACTION", "ID", "sessid")));
        break;
    }
}

$arResult = array(
    "USER_ID" => $USER->GetID(),
    "IDEA" => array(),
    "SUBSCRIBE" => array(),
    "IDEA_STATUS" => array(),
    "GRID" => array()
);

//Get Idea subscribtion
if($arResult["USER_ID"]>0)
{
    //InitGrid
    $GridOptions = new CGridOptions($arResult["GRID_ID"]);

    //Grid Sort
    $arSort = $GridOptions->GetSorting(
        array(
            "sort" => array("DATE_PUBLISH" => "DESC"), 
            "vars" => array("by" => "by", "order" => "order")
        )
    );
    $arResult["GRID"]["SORT"] = $arSort["sort"];
    $arResult["GRID"]["SORT_VARS"] = $arSort["vars"];
    
    $arNav = $GridOptions->GetNavParams(array("nPageSize"=>25));
    
    //Select Subscribe
    $arBlogPostId = array();
    $oIdeaSubscribe = CIdeaManagment::getInstance()->Notification()->getEmailNotify()->GetList(
        array(), 
        array("USER_ID" => $arResult["USER_ID"]), 
        false, 
        false, 
        array("ID")
    );
    
    $oIdeaSubscribe->NavStart($arNav["nPageSize"], false);
    
    $arExtraSubscribe = array();
    while($r = $oIdeaSubscribe->Fetch())
    {
        $arResult["SUBSCRIBE"][] = $r["ID"];
        if(in_array($r["ID"], array(CIdeaManagmentEmailNotify::SUBSCRIBE_ALL, CIdeaManagmentEmailNotify::SUBSCRIBE_ALL_IDEA)))
            $arExtraSubscribe[$r["ID"]] = $r["ID"];
        elseif($r["ID"][0] == CIdeaManagmentEmailNotify::SUBSCRIBE_IDEA_COMMENT)
            $arBlogPostId[] = ltrim($r["ID"], CIdeaManagmentEmailNotify::SUBSCRIBE_IDEA_COMMENT);
    }

    //Grid Nav
    $arResult["GRID"]["NAVIGATION"] = $oIdeaSubscribe;
    
    if(array_key_exists(CIdeaManagmentEmailNotify::SUBSCRIBE_ALL, $arExtraSubscribe))
        $arResult["IDEA"][CIdeaManagmentEmailNotify::SUBSCRIBE_ALL] = array(
            "TITLE" => GetMessage("IDEA_SUBSCRIBE_ALL_SUBSCRIBED"),
            "ID" => CIdeaManagmentEmailNotify::SUBSCRIBE_ALL,
        );
    
    if(array_key_exists(CIdeaManagmentEmailNotify::SUBSCRIBE_ALL_IDEA, $arExtraSubscribe))
        $arResult["IDEA"][CIdeaManagmentEmailNotify::SUBSCRIBE_ALL_IDEA] = array(
            "TITLE" => GetMessage("IDEA_SUBSCRIBE_ALL_IDEA_SUBSCRIBED"),
            "ID" => CIdeaManagmentEmailNotify::SUBSCRIBE_ALL_IDEA,
        );
    
    if(!empty($arBlogPostId))
    {
        $arSortArgument = each($arResult["GRID"]["SORT"]);
        
        $oIdeaPost = CBlogPost::GetList(
            array($arSortArgument["key"] => $arSortArgument["value"]),
            array("ID" => $arBlogPostId),
            false,
            false,
            array("ID", "TITLE", "PATH", "DATE_PUBLISH", /*"AUTHOR_ID",*/ CIdeaManagment::UFStatusField, "AUTHOR_LOGIN", "AUTHOR_NAME", "AUTHOR_LAST_NAME")
        );

        while($r = $oIdeaPost->Fetch())
            $arResult["IDEA"][$r["ID"]] = $r;
    }
    
    $arResult["IDEA_STATUS"] = CIdeaManagment::getInstance()->Idea()->GetStatusList();
    
    //Make Grid
    $arResult["GRID"]["ID"] = "idea_subscribe_".$arResult["USER_ID"];
    
    foreach($arResult["IDEA"] as $key => $Idea)
    {
        $arActions = array(
            array(
                "ICONCLASS" => "delete",
                "TEXT" => GetMessage("IDEA_POST_UNSUBSCRIBE"),
                "ONCLICK" => "window.location.href='?ACTION=DELETE&ID=".$Idea["ID"]."&".bitrix_sessid_get()."';"
            ),
        );
        
        if(!in_array($key, array(CIdeaManagmentEmailNotify::SUBSCRIBE_ALL, CIdeaManagmentEmailNotify::SUBSCRIBE_ALL_IDEA)))
            $arColumns = array(
                "NAME" => "<a href='".CComponentEngine::MakePathFromTemplate(htmlspecialcharsBack($Idea["PATH"]), array("post_id" => $Idea["ID"]))."'>".$Idea["TITLE"]."</a>",
            );
        
        $AuthorName = $Idea["AUTHOR_LOGIN"];
        if(strlen($Idea["AUTHOR_NAME"])>0 || strlen($Idea["AUTHOR_LAST_NAME"])>0)
            $AuthorName = trim($Idea["AUTHOR_NAME"].' '.$Idea["AUTHOR_LAST_NAME"]);

        $arResult["GRID"]["DATA"][] = array(
            "data" => array(
                "NAME" => $Idea["TITLE"],
                "STATUS" => $arResult["IDEA_STATUS"][$Idea[CIdeaManagment::UFStatusField]]["VALUE"],
                "PUBLISHED" => $Idea["DATE_PUBLISH"],
                "AUTHOR" => $AuthorName,
            ),
            "actions" => $arActions,
            "columns" => $arColumns,
            "editable" => false,
        );
    }
    //END -> Make Grid
}
//END -> Get Idea subscribtion

$this->IncludeComponentTemplate();
?>