<?
IncludeModuleLangFile(__FILE__);

global $MESS, $DOCUMENT_ROOT;

CModule::AddAutoloadClasses(
    'av.ibprops',
    array(
        'C_AV_IBlock_Manage' => 'classes/general/av_ibprops.php'
   )
);

Class C_AV_ibprops_service
{
	function OnBuildGlobalMenu(&$aGlobalMenu, &$aModuleMenu)
	{
		foreach($aModuleMenu as $k=>$v) {
			if($v["section"]=="iblock") {
				$MODULE_ID = basename(dirname(__FILE__));
				$aMenu = array(
					"sort" => 50,
					"text" => GetMessage("AV_IBPROPS_NAME"),
					"title" => '',
					"url" => "ibprops_manage.php",
					"icon" => "",
					"page_icon" => "",
					"items_id" => $MODULE_ID."_items",
					"more_url" => array(),
					"items" => array()
				);
				array_unshift($aModuleMenu[$k]["items"],$aMenu);
			}
		}
	}
}
?>