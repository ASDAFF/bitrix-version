<?
IncludeModuleLangFile(__FILE__);
/** @global CUser $USER */
if ($USER->IsAdmin())
{
	$menu = array(
		"parent_menu" => "global_menu_settings",
		"section" => "bitrixcloud",
		"sort" => 1645,
		"text" => GetMessage("BCL_MENU_ITEM"),
		"icon" => "bitrixcloud_menu_icon",
		"page_icon" => "bitrixcloud_page_icon",
		"items_id" => "menu_bitrixcloud",
		"items" => array(),
	);
	if (!IsModuleInstalled('intranet'))
	{
		$menu["items"][] = array(
			"text" => GetMessage("BCL_MENU_CONTROL_ITEM"),
			"url" => "bitrixcloud_cdn.php?lang=".LANGUAGE_ID,
		);
	}
	$menu["items"][] = array(
		"text" => GetMessage("BCL_MENU_BACKUP_ITEM"),
		"url" => "bitrixcloud_backup.php?lang=".LANGUAGE_ID,
	);
	return $menu;
}
else
{
	return false;
}
?>