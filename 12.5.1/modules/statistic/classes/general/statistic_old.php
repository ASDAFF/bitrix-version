<?
class CStat extends CTraffic {}
class CVisit extends CPage {}
class CStatCountry extends CCountry {}
class CAllStatistic extends CStatistics {}
class CStatistic extends CStatistics 
{
	function Stoplist($test="N") { return CStopList::Check($test); }
	function KeepStatistic($HANDLE_CALL=false) { return CStatistics::Keep($HANDLE_CALL); }
}
?>