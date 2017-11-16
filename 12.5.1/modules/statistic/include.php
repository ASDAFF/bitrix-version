<?
global $DBType;
IncludeModuleLangFile(__FILE__);

require_once ($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/filter_tools.php");
require_once ($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/statistic/stat_tools.php");
require_once ($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/statistic/ip_tools.php");
/*patchlimitationmutatormark1*/
CModule::AddAutoloadClasses(
	"statistic",
	array(
		"CKeepStatistics" => "classes/general/keepstatistic.php",
		"CAllStatistics" => "classes/general/statistic.php",
		"CStatistics" => "classes/".$DBType."/statistic.php",
		"CAdv" => "classes/".$DBType."/adv.php",
		"CGuest" => "classes/".$DBType."/guest.php",
		"CTraffic" => "classes/".$DBType."/traffic.php",
		"CUserOnline" => "classes/".$DBType."/useronline.php",
		"CStoplist" => "classes/".$DBType."/stoplist.php",
		"CHit" => "classes/general/hit.php",
		"CSession" => "classes/".$DBType."/session.php",
		"CReferer" => "classes/general/referer.php",
		"CPhrase" => "classes/general/phrase.php",
		"CSearcher" => "classes/".$DBType."/searcher.php",
		"CSearcherHit" => "classes/general/searcherhit.php",
		"CPage" => "classes/".$DBType."/page.php",
		"CStatEvent" => "classes/".$DBType."/statevent.php",
		"CStatEventType" => "classes/".$DBType."/stateventtype.php",
		"CAutoDetect" => "classes/".$DBType."/autodetect.php",
		"CCountry" => "classes/general/country.php",
		"CCity" => "classes/general/city.php",
		"CStatRegion" => "classes/general/city.php",
		"CCityLookup" => "classes/general/city.php",
		"CCityLookup_geoip_mod" => "tools/geoip_mod.php",
		"CCityLookup_geoip_extension" => "tools/geoip_extension.php",
		"CCityLookup_geoip_pure" => "tools/geoip_pure.php",
		"CCityLookup_stat_table" => "tools/stat_table.php",
		"CPath" => "classes/general/path.php",

		"CStat" => "classes/general/statistic_old.php",
		"CVisit" => "classes/general/statistic_old.php",
		"CStatCountry" => "classes/general/statistic_old.php",
		"CAllStatistic" => "classes/general/statistic_old.php",
		"CStatistic" => "classes/general/statistic_old.php",
		"CStatResult" => "classes/general/statresult.php",
		"statistic" => "install/index.php",
	)
);

$DB_test = CDatabase::GetModuleConnection("statistic", true);
if(!is_object($DB_test))
	return false;
?>