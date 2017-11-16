<?
##############################################
# Bitrix: SiteManager                        #
# Copyright (c) 2002-2010 Bitrix             #
# http://www.bitrixsoft.com                  #
# mailto:sources@bitrixsoft.com              #
##############################################

global $DB;
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/mail/constants.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/mail/classes/".strtolower($DB->type)."/mail.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/mail/classes/general/smtp.php");
?>