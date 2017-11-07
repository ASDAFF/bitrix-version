<?
define("ADMIN_MODULE_NAME", "security");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/security/include.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/security/prolog.php");
IncludeModuleLangFile(__FILE__);

/**
 * @global CMain $APPLICATION
 * @global CUser $USER
 */
if(!$USER->IsAdmin())
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

if($_SERVER['REQUEST_METHOD'] == "POST" && check_bitrix_sessid() && $_POST["action"].$_POST["save"].$_POST["apply"] != "")
{
	if($_POST["save"].$_POST["apply"] != "")
	{
		if(isset($_POST["neededTests"]) && is_array($_POST["neededTests"]))
		{
			$neededTestPackages = array();
			foreach($_POST["neededTests"] as $packageKey => $active)
			{
				if($active == "Y")
				{
					$neededTestPackages[] = $packageKey;
				}
			}
			COption::SetOptionString("security", "needed_tests_packages", serialize($neededTestPackages));
		}
	}
	else
	{
		$result = "error";

		switch($_POST["action"])
		{
			case "save":
				CUtil::JSPostUnescape();
				if(isset($_POST["results"]) && is_array($_POST["results"]))
				{
					$resultsForSave = $_POST["results"];
				}
				else
				{
					$resultsForSave = array();
				}
				if(CSecuritySiteChecker::addResults($resultsForSave))
				{
					$result = "ok";
				}
				break;
			case "check":
				if(isset($_POST["first_start"]) && $_POST["first_start"] == "Y")
				{
					$isFirstStart = true;
				}
				else
				{
					$isFirstStart = false;
				}
				$neededTestPackages = "";
				$result = CSecuritySiteChecker::runTestPackage($neededTestPackages, $isFirstStart);
				break;
			default:
				$result = "Shit Happens!";
		}

		$APPLICATION->RestartBuffer();
		header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
		die(CUtil::PhpToJsObject($result));
	}
}

CSecuritySiteChecker::clearTemporaryData();

$aTabs = array(
	array(
		"DIV" => "main",
		"TAB" => GetMessage("SEC_SCANNER_MAIN_TAB"),
		"TITLE"=>GetMessage("SEC_SCANNER_TITLE"),
	),
);
$tabControl = new CAdminTabControl("tabControl", $aTabs, false, true);

$lastTestingInfo = CSecuritySiteChecker::getLastTestingInfo();
if(isset($lastTestingInfo["results"]))
{
	$lastResults = $lastTestingInfo["results"];
}
else
{
	$lastResults = array();
}

if(!empty($lastResults))
{
	$criticalResultsCount = CSecuritySiteChecker::calculateCriticalResults($lastResults);
}
else
{
	$criticalResultsCount = 0;
}

if(isset($lastTestingInfo["test_date"]))
{
	$lastDate = $lastTestingInfo["test_date"];
}
else
{
	$lastDate = "";
}

$APPLICATION->SetTitle(GetMessage("SEC_SCANNER_TITLE"));
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
?>
<div id="error_container" class="adm-security-error-container" style="display:none;">
	<?
	CAdminMessage::ShowMessage(array(
		"MESSAGE" => GetMessage("SEC_SCANNER_CRITICAL_ERRORS_TITLE"),
		"TYPE" => "ERROR",
		"DETAILS" => "",
		"HTML"=>true
	));
	?>
</div>
<form method="POST" action="security_scanner.php?lang=<?=LANG?><?=$_GET["return_url"]? "&amp;return_url=".urlencode($_GET["return_url"]): ""?>" name="settings_form">
<?$tabControl->Begin();?>
<?$tabControl->BeginNextTab();?>
<div class="adm-security-wrap">
	<div id="start_container" class="adm-security-first-step">
		<div id="first_start" class="adm-security-text-block" <?=(!CSecuritySiteChecker::isNewTestNeeded())? "style=\"display:none;\"" : ""?>>
		<?=GetMessage("SEC_SCANNER_CRITICAL_FIRST_START")?>
		</div>
		<span id="start_button" class="adm-btn adm-btn-green" onclick="startStopChecking()"><?=GetMessage("SEC_SCANNER_START_BUTTON")?></span>
	</div>
	<div id="status_bar" class="adm-security-status-bar" style="display:none;">
		<div id="progress_bar" style="width: 500px;" class="adm-progress-bar-outer">
			<div id="progress_bar_inner" style="width: 0px;" class="adm-progress-bar-inner"></div>
			<div id="progress_text" style="width: 500px;" class="adm-progress-bar-inner-text">0%</div>
		</div>
		<div id="current_test"></div>
		<span id="stop_button" class="adm-btn stop-button" onclick="startStopChecking()"><?=GetMessage("SEC_SCANNER_STOP_BUTTON")?></span>
	</div>
	<div id="results_info" class="adm-security-results-info adm-security-title" <?=(empty($lastResults) && empty($lastDate))? "style=\"display:none;\"" : ""?>>
		<div id="problems_count" style="width: 500px; float: left;"><?=(!empty($lastResults))? (GetMessage("SEC_SCANNER_PROBLEMS_COUNT").count($lastResults).GetMessage("SEC_SCANNER_CRITICAL_PROBLEMS_COUNT").$criticalResultsCount): ""?></div>
		<div id="last_activity" style="width: 100%; text-align: right;"><?=($lastDate != "")? GetMessage("SEC_SCANNER_TEST_DATE", array("#DATE#" => $lastDate)): ""?></div>
		<div style="clear:both;"></div>
	</div>
	<div id="results" class="adm-security-third-step" <?=(empty($lastResults))? "style=\"display:none;\"" : ""?>></div>
</div>
<?$tabControl->End();?>
</form>

<script>

	var results = <?=Cutil::PhpToJsObject($lastResults)?>;
	var problemsCount = 0;
	var actionUrl = "/bitrix/admin/security_scanner.php?lang=<?=LANGUAGE_ID?>";
	var criticalLevels = {
		"LOW" : "<?=GetMessageJS("SEC_SCANNER_CRITICAL_LOW")?>",
		"MIDDLE" : "<?=GetMessageJS("SEC_SCANNER_CRITICAL_MIDDLE")?>",
		"HIGHT" : "<?=GetMessageJS("SEC_SCANNER_CRITICAL_HIGHT")?>"
	};
	var started = false;
	var errorPopup = null;

	BX.ready(function(){
		onTestingComplete();
	});

	function getCriticalErrorsContainer() {
		var errorsContainerParent = BX.findChild(
			BX("error_container"), {
				tagName: 'div',
				className: 'adm-info-message'
			},
			true
		);
		var errorsContainer = BX.findChild(
			errorsContainerParent, {
				tagName: 'div',
				className: 'adm-info-message-errors'
			}
		);
		if(!errorsContainer) {
			errorsContainer = BX.create('div', {
				'props': {
					'className': 'adm-info-message-errors'
				}
			});
			errorsContainerParent.appendChild(errorsContainer);
		}
		return errorsContainer;
	}
	function showCriticalError(pTestName, pMessage) {
		var testName = pTestName || '';
		if(testName)
			testName += ": ";

		BX.show(BX("error_container"));
		var newError = BX.create('div', {
			'html': testName + pMessage
		});
		getCriticalErrorsContainer().appendChild(newError);
	}

	function setProblemCount(pCount) {
		BX("problems_count").innerHTML = "<?=GetMessageJS("SEC_SCANNER_PROBLEMS_COUNT")?> " + pCount + "<?=GetMessageJS("SEC_SCANNER_CRITICAL_PROBLEMS_COUNT")?>" + getCriticalErrorsCount();
	}

	function getCriticalErrorsCount() {
		var count = 0;

		for (var i = 0; i < results.length; i++) {
			if(results[i]["critical"] && results[i]["critical"] == "HIGHT") {
				count++;
			}
		}
		return count;
	}

	function isStarted() {
		return started;
	}

	function initializeTesting() {
		results = [];
		problemsCount = 0;
		started = true;
		setProgress(0);
		setProblemCount(0);
	}

	function onTestingStart() {
		BX.show(BX("results_info"));
		BX.show(BX("status_bar"));
		BX("current_test").innerHTML = "<?=GetMessageJS("SEC_SCANNER_INIT")?>";
		BX.hide(BX("last_activity"));
		BX.hide(BX("error_container"));
		BX.hide(BX("start_container"));
		BX.hide(BX("results"));
		BX.hide(BX("first_start"));
		
		BX.cleanNode(BX("results"));
		BX.cleanNode(getCriticalErrorsContainer());
	}

	function onTestingComplete() {
		BX.show(BX("start_container"));
		BX.show(BX("results"));
		showTestingResults();
		BX.hide(BX("status_bar"));
	}

	function getSortValue(pKey) {
		if(pKey == "LOW") {
			return 3;
		} else if(pKey == "MIDDLE") {
			return 2;
		} else {
			return 1;
		}
	}

	function sortResults() {
		results.sort(function(a,b) {
			return getSortValue(a.critical) - getSortValue(b.critical);
		});
	}

	function showTestResult(pResult, pIndex) {
		var uniqId = Math.random();
		var container = BX.create('div', {
			'props': {
				'className': pResult["critical"] == 'HIGHT' ? 'adm-security-block adm-security-block-important' : 'adm-security-block'
			}
		});

		container.appendChild(BX.create('div', {
			'props': {
				'className': 'adm-security-block-title'
			},
			'children': [
				BX.create('span', {
					'props': {
						'className': 'adm-security-block-num'
					},
					'text': pIndex + "."
				}),
				BX.create('span', {
					'props': {
						'className': 'adm-security-block-title-name'
					},
					'text': pResult["title"]
				}),
				BX.create('span', {
					'props': {
						'className': 'adm-security-block-status'
					},
					'text': "<?=GetMessageJS("SEC_SCANNER_CRITICAL_ERROR")?>"
				})
			]
		}));

		container.appendChild(BX.create('div', {
			'props': {
				'className': 'adm-security-block-text'},
			'html': pResult["detail"]
		}));

		container.appendChild(BX.create('div', {
			'props': {
				'id': "tip_arrow_" + uniqId,
				'className': 'adm-security-tip'
			},
			'events': {
				'click': function() {
					BX.toggleClass(BX("tip_arrow_" + uniqId), "adm-security-tip-open");
					if(BX.hasClass(this, "adm-security-tip-open")) {
						BX("tip_text_" + uniqId).innerHTML = "<?=GetMessageJS("SEC_SCANNER_TIP_BUTTON_ON")?>";
					} else {
						BX("tip_text_" + uniqId).innerHTML = "<?=GetMessageJS("SEC_SCANNER_TIP_BUTTON_OFF")?>";
					}
				}
			},
			'children': [
				BX.create('div', {
					'props': {
						'className': 'adm-security-tip-text'
					},
					'html': pResult["recommendation"]
				}),
				BX.create('span', {
					'props': {
						'id': "tip_text_" + uniqId,
						'className': 'adm-security-tip-link'
					},
					'text': "<?=GetMessageJS("SEC_SCANNER_TIP_BUTTON_OFF")?>"
				}),
				BX.create('div', {
					'props': {
						'className': 'adm-security-tip-arrow'
					}
				})
			]
		}));

		BX("results").appendChild(container);
	}

	function setProgress(pProgress) {
		BX("progress_text").innerHTML = pProgress + "%";
		BX("progress_bar_inner").style.width = 500 * pProgress / 100 + "px";
	}

	function setCurrentTest(pTestName) {
		BX("current_test").innerHTML = "<?=GetMessageJS("SEC_SCANNER_CURRENT_TEST")?>" + pTestName;
	}

	function showTestingResults() {
		sortResults();
		for (var i = 0; i < results.length; i++) {
			showTestResult(results[i], i + 1);
		}
	}

	function sendCheckRequest(pAction, pData, pCallback) {
		var action = pAction || "check";
		var data = pData || {};
		var callback = pCallback || processCheckingResults;
		data["action"] = action;
		data["sessid"] = BX.bitrix_sessid();
		BX.ajax.post(actionUrl, data, callback);
	}

	function startStopChecking() {
		if(isStarted()) {
			started = false;
			onTestingComplete();
		} else {
			initializeTesting();
			sendCheckRequest("check", {"first_start": "Y"});
			onTestingStart();
		}
	}

	function retrieveResults(pResults) {
		if(pResults["problem_count"]) {
			problemsCount += parseInt(pResults["problem_count"]);
			setProblemCount(problemsCount);
		}

		if(pResults["errors"]) {
			for (var i = 0; i < pResults["errors"].length; i++) {
				results.push(pResults["errors"][i]);
			}
		}
	}

	function completeTesting() {
		onTestingComplete();
		started = false;
		sendCheckRequest("save", {"results" : results});
	}

	function processCheckingResults(pResponce) {
		if(!isStarted())
			return;

		var result = BX.parseJSON(pResponce);
		if(result == "ok" || result == "error")
			return;

		if(!result["status"]) {
			retrieveResults(result);
		}

		if(result["fatal_error_text"]) {
			showCriticalError(result["name"], result["fatal_error_text"]);
		}

		if(result["all_done"] == "Y") {
			completeTesting();
		} else {
			var timeOut = 0;
			if(result["timeout"]) {
				timeOut = result["timeout"];
			}
			setTimeout(function() {sendCheckRequest();}, timeOut*1000);
		}

		if(result["percent"]) {
			setProgress(result["percent"]);
		}

		if(result["name"]) {
			setCurrentTest(result["name"]);
		}
	}
</script>
<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
?>