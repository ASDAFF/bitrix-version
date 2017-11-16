<?
IncludeModuleLangFile(__FILE__);

if(class_exists("idea")) 
    return;

Class idea extends CModule
{
	var $MODULE_ID = "idea";
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;
	var $MODULE_CSS;
        var $errors;

	function idea()
	{
		$arModuleVersion = array();

		$path = str_replace("\\", "/", __FILE__);
		$path = substr($path, 0, strlen($path) - strlen("/index.php"));
		include($path."/version.php");

                if (is_array($arModuleVersion) && array_key_exists("VERSION", $arModuleVersion))
                {
                    $this->MODULE_VERSION = $arModuleVersion["VERSION"];
                    $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
                }
                elseif (defined('IDEA_VERSION') && defined('IDEA_VERSION_DATE'))
		{
			$this->MODULE_VERSION = IDEA_VERSION;
			$this->MODULE_VERSION_DATE = IDEA_VERSION_DATE;
		}

		$this->MODULE_NAME = GetMessage("IDEA_MODULE_NAME");
		$this->MODULE_DESCRIPTION = GetMessage("IDEA_MODULE_DESCRIPTION");
	}
        
        function GetIdeaUserFields()
        {
                //UF_CATEGORY_CODE - Idea category, depends of Iblock section tree
                //UF_ANSWER_ID - Offical answer in idea post
                //UF_ORIGINAL_ID - Original Idea ID, uses for dublicate collecting
                //UF_STATUS - Current status of Idea
                $ImportantUserFields = array(
                    "UF_CATEGORY_CODE" => false,
                    "UF_ANSWER_ID" => false,
                    "UF_ORIGINAL_ID" => false,
                    "UF_STATUS" => false,
                );
                $keysUserFields = array_keys($ImportantUserFields);
                
                global $USER_FIELD_MANAGER;
                $oUserFields = $USER_FIELD_MANAGER->GetUserFields("BLOG_POST");
                foreach($oUserFields as $UserFieldName => $arUserField)
                    if(in_array($UserFieldName, $keysUserFields))
                        $ImportantUserFields[$UserFieldName] = true;
                
                return $ImportantUserFields;
        }

	function InstallDB()
	{
            global $DB, $DBType, $APPLICATION;
            $this->errors = false;

            if(!$DB->Query("SELECT 'x' FROM b_idea_email_subscribe", true))
                $this->errors = $DB->RunSQLBatch($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/".$this->MODULE_ID."/install/db/".ToLower($DBType)."/install.sql");

            if($this->errors !== false)
            {
                $APPLICATION->ThrowException(implode("", $this->errors));
                return false;
            }

            //Install User Fields
            $this->InstallDBUserFields(); 

            RegisterModule($this->MODULE_ID);
            CModule::IncludeModule($this->MODULE_ID);
            RegisterModuleDependences('socialnetwork', 'OnFillSocNetLogEvents', $this->MODULE_ID, 'CIdeaManagmentSonetNotify', 'AddLogEvent');

            return true;
	}
        
        function InstallDBUserFields()
        {
            $ImportantUserFields = $this->GetIdeaUserFields();
            foreach($ImportantUserFields as $UserFieldName => $Exists)
            {
                if(!$Exists)
                {
                    $UserType = new CUserTypeEntity();
                    switch ($UserFieldName)
                    {
                        case "UF_CATEGORY_CODE":
                                $UserType->Add(array(
                                    "ENTITY_ID" => "BLOG_POST",
                                    "FIELD_NAME" => $UserFieldName,
                                    "USER_TYPE_ID" => "string",
                                    "IS_SEARCHABLE" => "N",
                                    "EDIT_FORM_LABEL" => array(
                                        LANGUAGE_ID => GetMessage("IDEA_UF_CATEGORY_CODE_DESCRIPTION"),
                                    ),
                                ));
                            break;
                        case "UF_ANSWER_ID":
                                $UserType->Add(array(
                                    "ENTITY_ID" => "BLOG_POST",
                                    "FIELD_NAME" => $UserFieldName,
                                    "USER_TYPE_ID" => "integer",
                                    "IS_SEARCHABLE" => "N",
                                    "MULTIPLE" => "Y",
                                    "EDIT_FORM_LABEL" => array(
                                        LANGUAGE_ID => GetMessage("IDEA_UF_ANSWER_ID_DESCRIPTION"),
                                    ),
                                ));
                            break;
                        case "UF_ORIGINAL_ID":
                                $UserType->Add(array(
                                    "ENTITY_ID" => "BLOG_POST",
                                    "FIELD_NAME" => $UserFieldName,
                                    "USER_TYPE_ID" => "string",
                                    "IS_SEARCHABLE" => "N",
                                    "EDIT_FORM_LABEL" => array(
                                        LANGUAGE_ID => GetMessage("IDEA_UF_ORIGINAL_ID_DESCRIPTION"),
                                    ),
                                ));
                            break;
                        case "UF_STATUS":
                                $ID = $UserType->Add(array(
                                    "ENTITY_ID" => "BLOG_POST",
                                    "FIELD_NAME" => $UserFieldName,
                                    "USER_TYPE_ID" => "enumeration",
                                    "IS_SEARCHABLE" => "N",
                                    "EDIT_FORM_LABEL" => array(
                                        LANGUAGE_ID => GetMessage("IDEA_UF_STATUS_DESCRIPTION"),
                                    ),
                                ));

                                if(intval($ID)>0)
                                {
                                    $UserTypeEnum = new CUserFieldEnum();
                                    $UserTypeEnum->SetEnumValues($ID, array(
                                        "n0" => array(
                                            "SORT" => 100,
                                            "XML_ID" => "NEW",
                                            "VALUE" => GetMessage("IDEA_UF_STATUS_NEW_TITLE"),
                                            "DEF" => "Y",
                                        ),
                                        "n1" => array(
                                            "SORT" => 200,
                                            "XML_ID" => "PROCESSING",
                                            "VALUE" => GetMessage("IDEA_UF_STATUS_PROCESSING_TITLE"),
                                            "DEF" => "N",
                                        ),
                                        "n2" => array(
                                            "SORT" => 300,
                                            "XML_ID" => "COMPLETED",
                                            "VALUE" => GetMessage("IDEA_UF_STATUS_COMPLETED_TITLE"),
                                            "DEF" => "N",
                                        ),
                                    ));
                                }
                            break;
                    }
                }
            }
        }

	function UnInstallDB()
	{
            global $DB, $DBType, $APPLICATION;
            $this->errors = false;
            
            $this->errors = $DB->RunSQLBatch($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/".$this->MODULE_ID."/install/db/".ToLower($DBType)."/uninstall.sql");

            if(!empty($this->errors))
            {
                $APPLICATION->ThrowException(implode("", $this->errors));
                return false;
            }
            
            UnRegisterModule($this->MODULE_ID);
            UnRegisterModuleDependences('socialnetwork', 'OnFillSocNetLogEvents', $this->MODULE_ID, 'CIdeaManagmentSonetNotify', 'AddLogEvent');

            return true;
	}

	function InstallEvents()
        {
            include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/events.php");
            return true;
        }

	function UnInstallEvents()
        {
            //Comment
            $EM = new CEventMessage;
            $oEventMessgae = $EM->GetList($by = "", $order = "", array("EVENT_NAME" => "ADD_IDEA_COMMENT"));
            while($arEvent = $oEventMessgae->Fetch())
                $EM->Delete($arEvent["ID"]);
            
            $ET = new CEventType;
            $ET->Delete("ADD_IDEA_COMMENT");
            
            //Idea
            $oEventMessgae = $EM->GetList($by = "", $order = "", array("EVENT_NAME" => "ADD_IDEA"));
            while($arEvent = $oEventMessgae->Fetch())
                $EM->Delete($arEvent["ID"]);
            
            $ET->Delete("ADD_IDEA");
            
            return true;
        }

	function InstallFiles()
	{
                CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/components", $_SERVER["DOCUMENT_ROOT"]."/bitrix/components", true, true);

		return true;
	}

	function UnInstallFiles(){return true;}

	function DoInstall()
	{
                if (!check_bitrix_sessid() || !IsModuleInstalled("iblock") || !IsModuleInstalled("blog"))
                    return false;
            
		global $APPLICATION, $step, $obModule;
                
                $step = IntVal($step);
                $obModule = $this;
                if($step<2)
                {
                    $this->InstallDBUserFields(); //Install User Fields
                    $APPLICATION->IncludeAdminFile(GetMessage("IDEA_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/step1.php");
                }
                elseif($step == 2)
                {
                    if($this->InstallFiles())
                    {
                        $this->InstallDB();
                        $this->InstallEvents();
                    }
                    $APPLICATION->IncludeAdminFile(GetMessage("IDEA_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/step2.php");
                }
	}

	function DoUninstall()
	{
                global $APPLICATION;
                
                $this->UnInstallDB();
                $this->UnInstallFiles();
                $this->UnInstallEvents();
                
                $APPLICATION->IncludeAdminFile(GetMessage("IDEA_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/unstep.php");
	}
}
?>