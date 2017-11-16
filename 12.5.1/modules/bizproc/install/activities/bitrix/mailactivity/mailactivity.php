<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

class CBPMailActivity
	extends CBPActivity
{
	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = array(
			"Title" => "",
			"MailUserFrom" => "",
			"MailUserFromArray" => "",
			"MailUserTo" => "",
			"MailUserToArray" => "",
			"MailSubject" => "",
			"MailText" => "",
			"MailMessageType" => "plain",
			"MailCharset" => "windows-1251",
			"DirrectMail" => "Y",
			"MailSite" => null,
		);
	}

	public function Execute()
	{
		$rootActivity = $this->GetRootActivity();
		$documentId = $rootActivity->GetDocumentId();

		$documentService = $this->workflow->GetService("DocumentService");

		$strMailUserFrom = "";

		$arMailUserFromArray = CBPHelper::ExtractUsers($this->MailUserFromArray, $documentId, false);
		foreach ($arMailUserFromArray as $user)
		{
			$dbUser = CUser::GetList(($b = ""), ($o = ""), array("ID_EQUAL_EXACT" => $user));
			if ($arUser = $dbUser->Fetch())
			{
				if (strlen($strMailUserFrom) > 0)
					$strMailUserFrom .= ", ";
				if (!defined("BX_MS_SMTP") || BX_MS_SMTP!==true)
				{
					if (strlen($arUser["NAME"]) > 0 || strlen($arUser["LAST_NAME"]) > 0)
						$strMailUserFrom .= "'".preg_replace("#['\r\n]+#", "", CUser::FormatName(COption::GetOptionString("bizproc", "name_template", CSite::GetNameFormat(false), SITE_ID), $arUser))."' <";
				}
				$strMailUserFrom .= preg_replace("#[\r\n]+#", "", $arUser["EMAIL"]);
				if (!defined("BX_MS_SMTP") || BX_MS_SMTP!==true)
				{
					if (strlen($arUser["NAME"]) > 0 || strlen($arUser["LAST_NAME"]) > 0)
						$strMailUserFrom .= ">";
				}
			}
		}

		$mailUserFromTmp = $this->MailUserFrom;
		if (strlen($mailUserFromTmp) > 0)
		{
			if (strlen($strMailUserFrom) > 0)
				$strMailUserFrom .= ", ";
			$strMailUserFrom .= preg_replace("#[\r\n]+#", "", $mailUserFromTmp);
		}

		$strMailUserTo = "";

		$arMailUserToArray = CBPHelper::ExtractUsers($this->MailUserToArray, $documentId, false);
		foreach ($arMailUserToArray as $user)
		{
			$dbUser = CUser::GetList(($b = ""), ($o = ""), array("ID_EQUAL_EXACT" => $user));
			if ($arUser = $dbUser->Fetch())
			{
				if (strlen($strMailUserTo) > 0)
					$strMailUserTo .= ", ";
				$strMailUserTo .= preg_replace("#[\r\n]+#", "", $arUser["EMAIL"]);
			}
		}

		$mailUserToTmp = $this->MailUserTo;
		if (strlen($mailUserToTmp) > 0)
		{
			if (strlen($strMailUserTo) > 0)
				$strMailUserTo .= ", ";
			$strMailUserTo .= preg_replace("#[\r\n]+#", "", $mailUserToTmp);
		}

		$charset = $this->MailCharset;

		if (!$this->IsPropertyExists("DirrectMail") || $this->DirrectMail == "Y")
		{
			global $APPLICATION;

			$strMailUserFrom = $APPLICATION->ConvertCharset($strMailUserFrom, SITE_CHARSET, $charset);
			$strMailUserFrom = CBPMailActivity::EncodeHeaderFrom($strMailUserFrom, $charset);

			$strMailUserTo = $APPLICATION->ConvertCharset($strMailUserTo, SITE_CHARSET, $charset);
			$strMailUserTo = CBPMailActivity::EncodeMimeString($strMailUserTo, $charset);

			$mailSubject = $APPLICATION->ConvertCharset($this->MailSubject, SITE_CHARSET, $charset);
			$mailSubject = CBPMailActivity::EncodeSubject($mailSubject, $charset);

			$mailText = $APPLICATION->ConvertCharset(CBPHelper::ConvertTextForMail($this->MailText), SITE_CHARSET, $charset);

			$eol = CAllEvent::GetMailEOL();

			mail(
				$strMailUserTo,
				$mailSubject,
				$mailText,
				"From: ".$strMailUserFrom.$eol.
				"Reply-To: ".$strMailUserFrom.$eol.
				"X-Priority: 3 (Normal)".$eol.
				"Content-Type: text/".($this->MailMessageType == "html" ? "html" : "plain")."; charset=".$charset.$eol.
				"X-Mailer: PHP/".phpversion()
			);
		}
		else
		{
			$siteId = null;
			if ($this->IsPropertyExists("MailSite"))
				$siteId = $this->MailSite;
			if (strlen($siteId) <= 0)
				$siteId = SITE_ID;

			$arFields = array(
				"SENDER" => $strMailUserFrom,
				"RECEIVER" => $strMailUserTo,
				"TITLE" => $this->MailSubject,
				"MESSAGE" => CBPHelper::ConvertTextForMail($this->MailText),
			);

			$event = new CEvent;
			$event->Send("BIZPROC_MAIL_TEMPLATE", $siteId, $arFields, "N");
		}

		return CBPActivityExecutionStatus::Closed;
	}

	function EncodeMimeString($text, $charset)
	{
		if(!CEvent::Is8Bit($text))
			return $text;

		$res = "";
		$maxl = 40;
		$eol = CEvent::GetMailEOL();
		$len = strlen($text);
		for($i=0; $i<$len; $i=$i+$maxl)
		{
			if($i>0)
				$res .= $eol."\t";
			$res .= "=?".$charset."?B?".base64_encode(substr($text, $i, $maxl))."?=";
		}
		return $res;
	}

	function EncodeSubject($text, $charset)
	{
		return "=?".$charset."?B?".base64_encode($text)."?=";
	}

	function EncodeHeaderFrom($text, $charset)
	{
		$i = strlen($text);
		while($i > 0)
		{
			if(ord(substr($text, $i-1, 1))>>7)
				break;
			$i--;
		}
		if($i==0)
			return $text;
		else
			return "=?".$charset."?B?".base64_encode(substr($text, 0, $i))."?=".substr($text, $i);
	}

	public static function ValidateProperties($arTestProperties = array(), CBPWorkflowTemplateUser $user = null)
	{
		$arErrors = array();

		if ((!array_key_exists("MailUserFrom", $arTestProperties) || strlen($arTestProperties["MailUserFrom"]) <= 0)
			&& (!array_key_exists("MailUserFromArray", $arTestProperties) || count($arTestProperties["MailUserFromArray"]) <= 0))
			$arErrors[] = array("code" => "NotExist", "parameter" => "MailUserFrom", "message" => GetMessage("BPMA_EMPTY_PROP1"));

		if ((!array_key_exists("MailUserTo", $arTestProperties) || strlen($arTestProperties["MailUserTo"]) <= 0)
			&& (!array_key_exists("MailUserToArray", $arTestProperties) || count($arTestProperties["MailUserToArray"]) <= 0))
			$arErrors[] = array("code" => "NotExist", "parameter" => "MailUserTo", "message" => GetMessage("BPMA_EMPTY_PROP2"));

		if (!array_key_exists("MailSubject", $arTestProperties) || strlen($arTestProperties["MailSubject"]) <= 0)
			$arErrors[] = array("code" => "NotExist", "parameter" => "MailSubject", "message" => GetMessage("BPMA_EMPTY_PROP3"));
		if (!array_key_exists("MailCharset", $arTestProperties) || strlen($arTestProperties["MailCharset"]) <= 0)
			$arErrors[] = array("code" => "NotExist", "parameter" => "MailCharset", "message" => GetMessage("BPMA_EMPTY_PROP4"));
		if (!array_key_exists("MailMessageType", $arTestProperties))
			$arErrors[] = array("code" => "NotExist", "parameter" => "MailMessageType", "message" => GetMessage("BPMA_EMPTY_PROP5"));
		elseif (!in_array($arTestProperties["MailMessageType"], array("plain", "html")))
			$arErrors[] = array("code" => "NotInRange", "parameter" => "MailMessageType", "message" => GetMessage("BPMA_EMPTY_PROP6"));
		if (!array_key_exists("MailText", $arTestProperties) || strlen($arTestProperties["MailText"]) <= 0)
			$arErrors[] = array("code" => "NotExist", "parameter" => "MailText", "message" => GetMessage("BPMA_EMPTY_PROP7"));

		return array_merge($arErrors, parent::ValidateProperties($arTestProperties, $user));
	}

	public static function GetPropertiesDialog($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $arCurrentValues = null, $formName = "")
	{
		$runtime = CBPRuntime::GetRuntime();

		$arMap = array(
			"MailUserFrom" => "mail_user_from",
			"MailUserTo" => "mail_user_to",
			"MailSubject" => "mail_subject",
			"MailText" => "mail_text",
			"MailMessageType" => "mail_message_type",
			"MailCharset" => "mail_charset",
			"DirrectMail" => "dirrect_mail",
			"MailSite" => "mail_site",
		);

		if (!is_array($arWorkflowParameters))
			$arWorkflowParameters = array();
		if (!is_array($arWorkflowVariables))
			$arWorkflowVariables = array();

		if (!is_array($arCurrentValues))
		{
			$arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
			if (is_array($arCurrentActivity["Properties"]))
			{
				foreach ($arMap as $k => $v)
				{
					if (array_key_exists($k, $arCurrentActivity["Properties"]))
					{
						if ($k == "MailUserFrom" || $k == "MailUserTo")
						{
							$arCurrentValues[$arMap[$k]] = CBPHelper::UsersArrayToString($arCurrentActivity["Properties"][$k."Array"], $arWorkflowTemplate, $documentType);
							if (strlen($arCurrentValues[$arMap[$k]]) > 0 && strlen($arCurrentActivity["Properties"][$k]) > 0)
								$arCurrentValues[$arMap[$k]] .= ", ";
							if (strlen($arCurrentActivity["Properties"][$k]) > 0)
								$arCurrentValues[$arMap[$k]] .= $arCurrentActivity["Properties"][$k];
						}
						else
						{
							$arCurrentValues[$arMap[$k]] = $arCurrentActivity["Properties"][$k];
						}
					}
					else
					{
						$arCurrentValues[$arMap[$k]] = "";
					}
				}
			}
			else
			{
				foreach ($arMap as $k => $v)
					$arCurrentValues[$arMap[$k]] = "";
			}
		}

		if (($arCurrentValues['dirrect_mail'] != "Y") && ($arCurrentValues['dirrect_mail'] != "N"))
			$arCurrentValues['dirrect_mail'] = "Y";

		return $runtime->ExecuteResourceFile(
			__FILE__,
			"properties_dialog.php",
			array(
				"arCurrentValues" => $arCurrentValues,
				"formName" => $formName,
			)
		);
	}

	public static function GetPropertiesDialogValues($documentType, $activityName, &$arWorkflowTemplate, &$arWorkflowParameters, &$arWorkflowVariables, $arCurrentValues, &$arErrors)
	{
		$arErrors = array();

		$runtime = CBPRuntime::GetRuntime();

		$arMap = array(
			"mail_user_from" => "MailUserFrom",
			"mail_user_to" => "MailUserTo",
			"mail_subject" => "MailSubject",
			"mail_text" => "MailText",
			"mail_message_type" => "MailMessageType",
			"mail_charset" => "MailCharset",
			"dirrect_mail" => "DirrectMail",
			"mail_site" => "MailSite",
		);

		$arProperties = array();
		foreach ($arMap as $key => $value)
		{
			if ($key == "mail_user_from" || $key == "mail_user_to")
				continue;
			$arProperties[$value] = $arCurrentValues[$key];
		}

		if (strlen($arProperties["MailSite"]) <= 0)
			$arProperties["MailSite"] = $arCurrentValues["mail_site_x"];

		list($mailUserFromArray, $mailUserFrom) = CBPHelper::UsersStringToArray($arCurrentValues["mail_user_from"], $documentType, $arErrors, array(__CLASS__, "CheckEmailUserValue"));
		if (count($arErrors) > 0)
			return false;
		$arProperties["MailUserFrom"] = implode(", ", $mailUserFrom);
		$arProperties["MailUserFromArray"] = $mailUserFromArray;

		list($mailUserToArray, $mailUserTo) = CBPHelper::UsersStringToArray($arCurrentValues["mail_user_to"], $documentType, $arErrors, array(__CLASS__, "CheckEmailUserValue"));
		if (count($arErrors) > 0)
			return false;
		$arProperties["MailUserTo"] = implode(", ", $mailUserTo);
		$arProperties["MailUserToArray"] = $mailUserToArray;

		$arErrors = self::ValidateProperties($arProperties, new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser));
		if (count($arErrors) > 0)
			return false;

		$arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
		$arCurrentActivity["Properties"] = $arProperties;

		return true;
	}

	public static function CheckEmailUserValue($user)
	{
		if (check_email($user))
			return $user;

		return null;
	}
}
?>