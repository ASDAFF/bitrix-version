<?

class CSecurityCloudMonitorTest extends CSecurityBaseTest
{
	const DEFAULT_RECEIVE_RESULTS_TIME = 15;
	const MAX_CHECKING_REQUEST_REPEATE_COUNT = 1;
	const MAX_RESULTS_REQUEST_REPEATE_COUNT = 50;
//	const PERCENT_PER_STEP = 10;

	protected $internalName = "CloudMonitor";
	/** @var CSecurityTemporaryStorage */
	protected $sessionData = null;
	protected $checkingResults = array();

	public function __construct()
	{
		require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/update_client.php");
		IncludeModuleLangFile(__FILE__);
	}

	/**
	 * Run test and return results
	 * @param array $pParams
	 * @return array
	 */
	public function check($pParams)
	{
		$this->initializeParams($pParams);
		$testID = $this->getParam("TEST_ID", $this->internalName);
		$this->sessionData = new CSecurityTemporaryStorage($testID);

		if($this->isCheckRequestNotSended())
		{
			$this->doCheckRequest();
		}
		else
		{
			$this->receiveResults();
		}

		return $this->getResult();
	}

	/**
	 * Return checking results with default values (if it not present before)
	 * @return array
	 */
	protected function getResult()
	{
		if(!is_array($this->checkingResults))
			$this->checkingResults = array();
		if(!isset($this->checkingResults["name"]))
			$this->checkingResults["name"] = $this->getName();
		if(!isset($this->checkingResults["timeout"]))
			$this->checkingResults["timeout"] = $this->getTimeout();
		if(!isset($this->checkingResults["status"]))
			$this->checkingResults["in_progress"] = true;
		return $this->checkingResults;
	}

	/**
	 * Try to receive checking results from Bitrix
	 */
	protected function receiveResults()
	{
		if($this->sessionData->getInt("results_repeat_count") > self::MAX_RESULTS_REQUEST_REPEATE_COUNT)
			$this->stopChecking(GetMessage("SECURITY_SITE_CHECKER_CLOUD_UNAVAILABLE"));

		$response = new CSecurityCloudMonitorRequest("get_results", $this->getCheckingToken());
		if($response->isOk())
		{
			$this->sessionData->flushData();
			$results = $response->getValue("results");
			if(is_array($results) && count($results) > 0)
			{
				$isSomethingFound = true;
				$problemCount = count($results);
				$errors = self::formatResults($results);
			}
			else
			{
				$isSomethingFound = false;
				$problemCount = 0;
				$errors = array();
			}
			$this->setCheckingResult(array(
				"problem_count" => $problemCount,
				"errors" => $errors,
				"status" => !$isSomethingFound
			));

		}
		elseif($response->isFatalError())
		{
			$this->stopChecking($response->getValue("error_text"));
		}
		else
		{
			$this->sessionData->increment("results_repeat_count");
		}
	}

	/**
	 * @return bool
	 */
	protected function isCheckRequestNotSended()
	{
		return ($this->getParam("STEP", 0) === 0 || $this->sessionData->getBool("repeat_request"));
	}

	/**
	 * Try to start checking (send special request to Bitrix)
	 */
	protected function doCheckRequest()
	{
		$response = new CSecurityCloudMonitorRequest("check");
		if($response->isOk())
		{
			$this->sessionData->flushData();
			$this->setTimeOut($response->getValue("processing_time"));
			$this->setCheckingToken($response->getValue("testing_token"));
		}
		elseif($response->isFatalError())
		{
			$this->stopChecking($response->getValue("error_text"));
		}
		else
		{
			if($this->sessionData->getBool("repeat_request"))
			{
				if($this->sessionData->getInt("check_repeat_count") > self::MAX_CHECKING_REQUEST_REPEATE_COUNT)
				{
					$this->stopChecking(GetMessage("SECURITY_SITE_CHECKER_CLOUD_UNAVAILABLE"));
				}
				else
				{
					$this->sessionData->increment("check_repeat_count");
				}
			}
			else
			{
				$this->sessionData->flushData();
				$this->sessionData->setData("repeat_request", true);
			}
		}
	}

	/**
	 * @param $pToken
	 */
	protected function setCheckingToken($pToken)
	{
		if(is_string($pToken) && $pToken != "")
		{
			$this->sessionData->setData("testing_token", $pToken);
		}
	}

	/**
	 * @return string
	 */
	protected function getCheckingToken()
	{
		return $this->sessionData->getString("testing_token");
	}

	/**
	 * @param $pTimeOut
	 */
	protected function setTimeOut($pTimeOut)
	{
		if(intval($pTimeOut) > 0 )
		{
			$this->sessionData->setData("timeout", $pTimeOut);
		}
	}

	/**
	 * @param $pResult
	 */
	protected function setCheckingResult($pResult)
	{
		$this->checkingResults = $pResult;
	}

	/**
	 * @param string $pMessage
	 */
	protected function stopChecking($pMessage = "")
	{
		$this->checkingResults["status"] = true;
		$this->checkingResults["fatal_error_text"] = $pMessage;
	}

	/**
	 * Format test results for checking output
	 * @param $pResults
	 * @return array
	 */
	protected static function formatResults($pResults)
	{
		$formattedResult = array();
		$count = 0;
		foreach($pResults as $result)
		{
			if(isset($result["name"]))
			{
				$formattedResult[$count]["title"] = $result["name"];
				$formattedResult[$count]["critical"] = isset($result["critical"])? $result["critical"]: CSecurityCriticalLevel::LOW;
			}
			if(isset($result["detail"]))
			{
				$formattedResult[$count]["detail"] = $result["detail"];
			}
			if(isset($result["recommendation"]))
			{
				$formattedResult[$count]["recommendation"] = $result["recommendation"];
				$formattedResult[$count]["recommendation"] .= isset($result["additional_info"])? "<br>".$result["additional_info"]: "";
			}
			$count++;
		}
		return $formattedResult;
	}

	/**
	 * @return int
	 */
	protected function getTimeout()
	{
		if($this->sessionData->getString("timeout") > 0)
		{
			return intval($this->sessionData->getString("timeout"));
		}
		else
		{
			return self::DEFAULT_RECEIVE_RESULTS_TIME;
		}
	}
}
