<?
$MESS["MAIN_ADMIN_GROUP_NAME"] = "��������������";
$MESS["MAIN_ADMIN_GROUP_DESC"] = "������ ������ � ���������� ������.";
$MESS["MAIN_EVERYONE_GROUP_NAME"] = "��� ������������ (� ��� ����� ����������������)";
$MESS["MAIN_EVERYONE_GROUP_DESC"] = "��� ������������, ������� ����������������.";
$MESS["MAIN_VOTE_RATING_GROUP_NAME"] = "������������, ������� ����� ���������� �� �������";
$MESS["MAIN_VOTE_RATING_GROUP_DESC"] = "� ��� ������ ������������ ����������� �������������.";
$MESS["MAIN_VOTE_AUTHORITY_GROUP_NAME"] = "������������ ������� ����� ���������� �� ���������";
$MESS["MAIN_VOTE_AUTHORITY_GROUP_DESC"] = "� ��� ������ ������������ ����������� �������������.";
$MESS["MAIN_RULE_ADD_GROUP_AUTHORITY_NAME"] = "���������� � ������ �������������, ������� ����� ���������� �� ���������";
$MESS["MAIN_RULE_ADD_GROUP_RATING_NAME"] = "���������� � ������ �������������, ������� ����� ���������� �� �������";
$MESS["MAIN_RULE_REM_GROUP_AUTHORITY_NAME"] = "�������� �� ������ �������������, �� ������� ����� ���������� �� ���������";
$MESS["MAIN_RULE_REM_GROUP_RATING_NAME"] = "�������� �� ������ �������������, �� ������� ����� ���������� �� �������";
$MESS["MAIN_RULE_AUTO_AUTHORITY_VOTE_NAME"] = "�������������� ����������� �� ��������� ������������";
$MESS["MAIN_RATING_NAME"] = "�������";
$MESS["MAIN_RATING_AUTHORITY_NAME"] = "���������";
$MESS["MAIN_RATING_TEXT_LIKE_Y"] = "��� ��������";
$MESS["MAIN_RATING_TEXT_LIKE_N"] = "������ �� ��������";
$MESS["MAIN_RATING_TEXT_LIKE_D"] = "��� ��������";
$MESS["MAIN_DEFAULT_SITE_NAME"] = "���� �� ���������";
$MESS["MAIN_DEFAULT_LANGUAGE_NAME"] = "Russian";
$MESS["MAIN_DEFAULT_LANGUAGE_FORMAT_DATE"] = "DD.MM.YYYY";
$MESS["MAIN_DEFAULT_LANGUAGE_FORMAT_DATETIME"] = "DD.MM.YYYY HH:MI:SS";
$MESS["MAIN_DEFAULT_LANGUAGE_FORMAT_NAME"] = "#NAME# #LAST_NAME#";
$MESS["MAIN_DEFAULT_LANGUAGE_FORMAT_CHARSET"] = "windows-1251";
$MESS["MAIN_DEFAULT_SITE_FORMAT_DATE"] = "DD.MM.YYYY";
$MESS["MAIN_DEFAULT_SITE_FORMAT_DATETIME"] = "DD.MM.YYYY HH:MI:SS";
$MESS["MAIN_DEFAULT_SITE_FORMAT_NAME"] = "#NAME# #LAST_NAME#";
$MESS["MAIN_DEFAULT_SITE_FORMAT_CHARSET"] = "windows-1251";
$MESS["MAIN_MODULE_NAME"] = "������� ������";
$MESS["MAIN_MODULE_DESC"] = "���� �������";
$MESS["MAIN_INSTALL_DB_ERROR"] = "�� ���� ����������� � ����� ������. ��������� ������������ ��������� ����������";
$MESS["MAIN_NEW_USER_TYPE_NAME"] = "����������������� ����� ������������";
$MESS["MAIN_NEW_USER_TYPE_DESC"] = "

#USER_ID# - ID ������������
#LOGIN# - �����
#EMAIL# - EMail
#NAME# - ���
#LAST_NAME# - �������
#USER_IP# - IP ������������
#USER_HOST# - ���� ������������
";
$MESS["MAIN_USER_INFO_TYPE_NAME"] = "���������� � ������������";
$MESS["MAIN_USER_INFO_TYPE_DESC"] = "

#USER_ID# - ID ������������
#STATUS# - ������ ������
#MESSAGE# - ��������� ������������
#LOGIN# - �����
#CHECKWORD# - ����������� ������ ��� ����� ������
#NAME# - ���
#LAST_NAME# - �������
#EMAIL# - E-Mail ������������
";
$MESS["MAIN_NEW_USER_CONFIRM_TYPE_NAME"] = "������������� ����������� ������ ������������";
$MESS["MAIN_NEW_USER_CONFIRM_TYPE_DESC"] = "


#USER_ID# - ID ������������
#LOGIN# - �����
#EMAIL# - EMail
#NAME# - ���
#LAST_NAME# - �������
#USER_IP# - IP ������������
#USER_HOST# - ���� ������������
#CONFIRM_CODE# - ��� �������������
";
$MESS["MAIN_USER_INVITE_TYPE_NAME"] = "����������� �� ���� ������ ������������";
$MESS["MAIN_USER_INVITE_TYPE_DESC"] = "#ID# - ID ������������
#LOGIN# - �����
#URL_LOGIN# - �����, �������������� ��� ������������� � URL
#EMAIL# - EMail
#NAME# - ���
#LAST_NAME# - �������
#PASSWORD# - ������ ������������ 
#CHECKWORD# - ����������� ������ ��� ����� ������
#XML_ID# - ID ������������ ��� ����� � �������� �����������
";
$MESS["MAIN_NEW_USER_EVENT_NAME"] = "#SITE_NAME#: ����������������� ����� ������������";
$MESS["MAIN_NEW_USER_EVENT_DESC"] = "�������������� ��������� ����� #SITE_NAME#
------------------------------------------

�� ����� #SERVER_NAME# ������� ��������������� ����� ������������.

������ ������������:
ID ������������: #USER_ID#

���: #NAME#
�������: #LAST_NAME#
E-Mail: #EMAIL#

Login: #LOGIN#

������ ������������� �������������.";
$MESS["MAIN_USER_INFO_EVENT_NAME"] = "#SITE_NAME#: ��������������� ����������";
$MESS["MAIN_USER_INFO_EVENT_DESC"] = "�������������� ��������� ����� #SITE_NAME#
------------------------------------------
#NAME# #LAST_NAME#,

#MESSAGE#

���� ��������������� ����������:

ID ������������: #USER_ID#
������ �������: #STATUS#
Login: #LOGIN#

�� ������ �������� ������, ������� �� ��������� ������:
http://#SERVER_NAME#/auth/index.php?change_password=yes&lang=ru&USER_CHECKWORD=#CHECKWORD#&USER_LOGIN=#LOGIN#

��������� ������������� �������������.";
$MESS["MAIN_USER_PASS_REQUEST_EVENT_DESC"] = "�������������� ��������� ����� #SITE_NAME#
------------------------------------------
#NAME# #LAST_NAME#,

#MESSAGE#

��� ����� ������ ��������� �� ��������� ������:
http://#SERVER_NAME#/auth/index.php?change_password=yes&lang=ru&USER_CHECKWORD=#CHECKWORD#&USER_LOGIN=#LOGIN#

���� ��������������� ����������:

ID ������������: #USER_ID#
������ �������: #STATUS#
Login: #LOGIN#

��������� ������������� �������������.";
$MESS["MAIN_USER_PASS_CHANGED_EVENT_DESC"] = "�������������� ��������� ����� #SITE_NAME#
------------------------------------------
#NAME# #LAST_NAME#,

#MESSAGE#

���� ��������������� ����������:

ID ������������: #USER_ID#
������ �������: #STATUS#
Login: #LOGIN#

��������� ������������� �������������.";
$MESS["MAIN_NEW_USER_CONFIRM_EVENT_NAME"] = "#SITE_NAME#: ������������� ����������� ������ ������������";
$MESS["MAIN_NEW_USER_CONFIRM_EVENT_DESC"] = "�������������� ��������� ����� #SITE_NAME#
------------------------------------------

������������,

�� �������� ��� ���������, ��� ��� ��� ����� ��� ����������� ��� ����������� ������ ������������ �� ������� #SERVER_NAME#.

��� ��� ��� ������������� �����������: #CONFIRM_CODE#

��� ������������� ����������� ��������� �� ��������� ������:
http://#SERVER_NAME#/auth/index.php?confirm_registration=yes&confirm_user_id=#USER_ID#&confirm_code=#CONFIRM_CODE#

�� ����� ������ ������ ��� ��� ������������� ����������� �� ��������:
http://#SERVER_NAME#/auth/index.php?confirm_registration=yes&confirm_user_id=#USER_ID#

��������! ��� ������� �� ����� ��������, ���� �� �� ����������� ���� �����������.

---------------------------------------------------------------------

��������� ������������� �������������.";
$MESS["MAIN_USER_INVITE_EVENT_NAME"] = "#SITE_NAME#: ����������� �� ����";
$MESS["MAIN_USER_INVITE_EVENT_DESC"] = "�������������� ��������� ����� #SITE_NAME#
------------------------------------------
������������, #NAME# #LAST_NAME#!

��������������� ����� �� ��������� � ����� ������������������ �������������.

���������� ��� �� ��� ����.

���� ��������������� ����������:

ID ������������: #ID#
Login: #LOGIN#

����������� ��� ������� ������������� ������������� ������.

��� ����� ������ ��������� �� ��������� ������:
http://#SERVER_NAME#/auth.php?change_password=yes&USER_LOGIN=#URL_LOGIN#&USER_CHECKWORD=#CHECKWORD#
";
$MESS["MF_EVENT_NAME"] = "�������� ��������� ����� ����� �������� �����";
$MESS["MF_EVENT_DESCRIPTION"] = "#AUTHOR# - ����� ���������
#AUTHOR_EMAIL# - Email ������ ���������
#TEXT# - ����� ���������
#EMAIL_FROM# - Email ����������� ������
#EMAIL_TO# - Email ���������� ������";
$MESS["MF_EVENT_SUBJECT"] = "#SITE_NAME#: ��������� �� ����� �������� �����";
$MESS["MF_EVENT_MESSAGE"] = "�������������� ��������� ����� #SITE_NAME#
------------------------------------------

��� ���� ���������� ��������� ����� ����� �������� �����

�����: #AUTHOR#
E-mail ������: #AUTHOR_EMAIL#

����� ���������:
#TEXT#

��������� ������������� �������������.";
$MESS["MAIN_USER_PASS_REQUEST_TYPE_NAME"] = "������ �� ����� ������";
$MESS["MAIN_USER_PASS_CHANGED_TYPE_NAME"] = "������������� ����� ������";
$MESS["MAIN_USER_PASS_REQUEST_EVENT_NAME"] = "#SITE_NAME#: ������ �� ����� ������";
$MESS["MAIN_USER_PASS_CHANGED_EVENT_NAME"] = "#SITE_NAME#: ������������� ����� ������";
$MESS["MAIN_DESKTOP_CREATEDBY_KEY"] = "��������� �����";
$MESS["MAIN_DESKTOP_CREATEDBY_VALUE"] = "������ �������� &laquo;1�-�������&raquo;.";
$MESS["MAIN_DESKTOP_URL_KEY"] = "����� �����";
$MESS["MAIN_DESKTOP_URL_VALUE"] = "<a href=\"http://www.1c-bitrix.ru\">www.1c-bitrix.ru</a>";
$MESS["MAIN_DESKTOP_PRODUCTION_KEY"] = "���� ����";
$MESS["MAIN_DESKTOP_PRODUCTION_VALUE"] = "12 ������� 2010 �.";
$MESS["MAIN_DESKTOP_RESPONSIBLE_KEY"] = "������������� ����";
$MESS["MAIN_DESKTOP_RESPONSIBLE_VALUE"] = "���� ������";
$MESS["MAIN_DESKTOP_EMAIL_KEY"] = "E-mail";
$MESS["MAIN_DESKTOP_EMAIL_VALUE"] = "<a href=\"mailto:info@1c-bitrix.ru\">info@1c-bitrix.ru</a>";
$MESS["MAIN_DESKTOP_INFO_TITLE"] = "���������� � �����";
$MESS["MAIN_DESKTOP_RSS_TITLE"] = "������� 1�-�������";
?>