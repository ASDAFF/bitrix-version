<?
IncludeModuleLangFile(__FILE__);

if($APPLICATION->GetGroupRight("sender")!="D")
{
	$aMenu = array(
		"parent_menu" => "global_menu_services",
		"section" => "sender",
		"sort" => 200,
		"text" => GetMessage("mnu_sender_sect"),
		"title" => GetMessage("mnu_sender_sect_title"),
		"icon" => "sender_menu_icon",
		"page_icon" => "sender_page_icon",
		"items_id" => "menu_sender",
		"items" => array(
			array(
				"text" => GetMessage("mnu_sender_mailing_admin"),
				"url" => "sender_mailing_admin.php?lang=".LANGUAGE_ID,
				"title" => GetMessage("mnu_sender_mailing_admin_alt"),
				"more_url" => array("sender_mailing_edit.php", "sender_mailing_wizard.php"),
			),
			array(
				"text" => GetMessage("mnu_sender_group"),
				"url" => "sender_group_admin.php?lang=".LANGUAGE_ID,
				"more_url" => array("sender_group_edit.php"),
				"title" => GetMessage("mnu_sender_group_alt")
			),
			array(
				"text" => GetMessage("mnu_sender_contact_admin"),
				"url" => "sender_contact_admin.php?lang=".LANGUAGE_ID,
				"more_url" => array("sender_contact_import.php"),
				"title" => GetMessage("mnu_sender_contact_admin_alt")
			),
			array(
				"text" => GetMessage("mnu_sender_template_admin"),
				"url" => "sender_template_admin.php?lang=".LANGUAGE_ID,
				"more_url" => array("sender_template_edit.php"),
				"title" => GetMessage("mnu_sender_template_admin_alt")
			),
		)
	);

	$arSiteMailing = array();
	if(CModule::IncludeModule('sender'))
	{
		$mailingListDb = \Bitrix\Sender\MailingTable::getList(array('filter' => array()));
		while ($mailing = $mailingListDb->fetch())
		{
			$arSiteMailing[] = array(
				"text" => htmlspecialcharsbx($mailing['NAME']),
				"title" => GetMessage("mnu_sender_site_mailing_one_alt"),
				"items_id" => "menu_sender_mailing_" . $mailing['ID'],
				"items" => array(
					array(
						"text" => GetMessage("mnu_sender_site_mailing_chain"),
						"url" => "sender_mailing_chain_admin.php?MAILING_ID=" . $mailing['ID'] . "&lang=" . LANGUAGE_ID,
						"title" => GetMessage("mnu_sender_site_mailing_chain_alt"),
						"more_url" => array("sender_mailing_chain_edit.php?MAILING_ID=" . $mailing['ID']),
					),
					array(
						"text" => GetMessage("mnu_sender_site_mailing_stat"),
						"url" => "sender_statistics.php?MAILING_ID=" . $mailing['ID'] . "&lang=" . LANGUAGE_ID,
						"title" => GetMessage("mnu_sender_site_mailing_stat_alt")
					),
					array(
						"text" => GetMessage("mnu_sender_site_mailing_addr"),
						"url" => "sender_mailing_recipient_admin.php?MAILING_ID=" . $mailing['ID'] . "&lang=" . LANGUAGE_ID,
						"title" => GetMessage("mnu_sender_site_mailing_addr_alt")
					),
				)
			);
		}
	}

	$aMenu['items'][] = array(
		"text" => GetMessage("mnu_sender_site_mailing"),
		"title" => GetMessage("mnu_sender_site_mailing_alt"),
		"dynamic" => true,
		"module_id" => "sender",
		"items_id" => "menu_sender_mailing_list",
		'items' => $arSiteMailing
	);

	return $aMenu;
}
return false;
?>