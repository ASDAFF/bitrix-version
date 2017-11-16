<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (
	!defined('PUBLIC_AJAX_MODE') 
	&& !IsModuleInstalled("im")
	&& $GLOBALS["USER"]->IsAuthorized()
	&& (
		!isset($_SESSION["USER_LAST_ONLINE"]) 
		|| intval($_SESSION["USER_LAST_ONLINE"])+60 <= time()
	)
)
{
	$_SESSION["USER_LAST_ONLINE"] = time();
	CUser::SetLastActivityDate($GLOBALS["USER"]->GetID());		
}
?>