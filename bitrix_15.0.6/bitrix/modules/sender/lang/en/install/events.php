<?
$MESS["SUBSCRIBE_CONFIRM_NAME"] = "Confirm subscription";
$MESS["SUBSCRIBE_CONFIRM_DESC"] = "#ID# - subscription ID
#EMAIL# - subscription e-mail
#CONFIRM_CODE# - confirmation code
#SUBSCR_SECTION# - section containing the (specified in settings)
#USER_NAME# - subscriber name (can be empty)
#DATE_SUBSCR# - the date the e-mail was added or modified
";
$MESS["SUBSCRIBE_CONFIRM_SUBJECT"] = "#SITE_NAME#: Confirm subscription";
$MESS["SUBSCRIBE_CONFIRM_MESSAGE"] = "This message was sent from #SITE_NAME#.
------------------------------------------

Hello,

You are receiving this message because your e-mail was subscribed to #SERVER_NAME# newsletter.

Subscription details:

Address (email) .............. #EMAIL#
Date added or updated .... #DATE_SUBSCR#

Your confirmation code: #CONFIRM_CODE#

Click this link to confirm your subscription:
http://#SERVER_NAME##SUBSCR_SECTION#subscr_edit.php?ID=#ID#&CONFIRM_CODE=#CONFIRM_CODE#

Alternatively, you can enter the confirmation code manually here:
http://#SERVER_NAME##SUBSCR_SECTION#subscr_edit.php?ID=#ID#

Attention! No newsletter messages will be sent to you until you have confirmed your subscription.

---------------------------------------------------------------------
Keep this message; it contains authentication details.
You can use your subscription code to change subscription preferences or unsubscribe.

Edit subscription:
http://#SERVER_NAME##SUBSCR_SECTION#subscr_edit.php?ID=#ID#&CONFIRM_CODE=#CONFIRM_CODE#

Unsubscribe:
http://#SERVER_NAME##SUBSCR_SECTION#subscr_edit.php?ID=#ID#&CONFIRM_CODE=#CONFIRM_CODE#&action=unsubscribe
---------------------------------------------------------------------

This message was sent by robot.
";
?>