<?php

CModule::AddAutoloadClasses('sender', array(
		"bitrix\\sender\\contacttable" => "lib/contact.php",
		"bitrix\\sender\\contactlisttable" => "lib/contact.php",
		"bitrix\\sender\\listtable" => "lib/contact.php",

		"bitrix\\sender\\grouptable" => "lib/group.php",
		"bitrix\\sender\\groupconnectortable" => "lib/group.php",

		"bitrix\\sender\\mailingtable" => "lib/mailing.php",
		"bitrix\\sender\\mailingchaintable" => "lib/mailing.php",
		"bitrix\\sender\\mailinggrouptable" => "lib/mailing.php",

		"bitrix\\sender\\postingtable" => "lib/posting.php",
		"bitrix\\sender\\postingrecipienttable" => "lib/posting.php",
		"bitrix\\sender\\postingreadtable" => "lib/posting.php",
		"bitrix\\sender\\postingclicktable" => "lib/posting.php",
		"bitrix\\sender\\postingunsubtable" => "lib/posting.php",

		"bitrix\\sender\\postingmanager" => "lib/postingmanager.php",
		"bitrix\\sender\\mailingmanager" => "lib/mailingmanager.php",
		"bitrix\\sender\\subscription" => "lib/subscription.php",

		"bitrix\\sender\\connector" => "lib/connector.php",
		"bitrix\\sender\\connectormanager" => "lib/connectormanager.php",

		"bitrix\\sender\\senderconnectorcontact" => "lib/senderconnectorcontact.php",
		"bitrix\\sender\\senderconnectorrecipient" => "lib/senderconnectorrecipient.php",

		"Bitrix\\Sender\\TemplateTable" => "lib/template.php",
		"Bitrix\\Sender\\Preset\\Template" => "lib/preset/template.php",
		"Bitrix\\Sender\\Preset\\MailBlock" => "lib/preset/mailblock.php",
		"Bitrix\\Sender\\Preset\\TemplateBase" => "lib/preset/template.php",
		"Bitrix\\Sender\\Preset\\MailBlockBase" => "lib/preset/mailblock.php",
));


\CJSCore::RegisterExt("sender_admin", Array(
	"js" =>    "/bitrix/js/sender/admin.js",
	"rel" =>   array()
));