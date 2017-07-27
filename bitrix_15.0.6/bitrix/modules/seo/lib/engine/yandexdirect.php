<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage seo
 * @copyright 2001-2013 Bitrix
 */
namespace Bitrix\Seo\Engine;

use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\Context;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Seo\Adv\LogTable;
use Bitrix\Seo\Adv\YandexBannerTable;
use Bitrix\Seo\Adv\YandexCampaignTable;
use Bitrix\Seo\Engine;
use Bitrix\Seo\IEngine;
use Bitrix\Main\Text;

// to use Yandex.Direct Sandbox
// define('YANDEX_DIRECT_API_URL', "https://api-sandbox.direct.yandex.ru/v4/json/");
if(!defined('YANDEX_DIRECT_API_URL'))
{
	define('YANDEX_DIRECT_API_URL', 'https://api.direct.yandex.ru/v4/json/');
}

class YandexDirect extends Engine\YandexBase implements IEngine
{
	const ENGINE_ID = 'yandex_direct';

	const API_URL = YANDEX_DIRECT_API_URL;

	const METHOD_REGION_GET = 'GetRegions';
	const METHOD_CAMPAIGN_ADD = 'CreateOrUpdateCampaign';
	const METHOD_CAMPAIGN_UPDATE = 'CreateOrUpdateCampaign';
	const METHOD_CAMPAIGN_GET = 'GetCampaignsParams';
	const METHOD_CAMPAIGN_LIST = 'GetCampaignsList';
	const METHOD_CAMPAIGN_ARCHIVE = 'ArchiveCampaign';
	const METHOD_CAMPAIGN_UNARCHIVE = 'UnArchiveCampaign';
	const METHOD_CAMPAIGN_STOP = 'StopCampaign';
	const METHOD_CAMPAIGN_RESUME = 'ResumeCampaign';
	const METHOD_CAMPAIGN_DELETE = 'DeleteCampaign';
	const METHOD_BANNER_ADD = 'CreateOrUpdateBanners';
	const METHOD_BANNER_UPDATE = 'CreateOrUpdateBanners';
	const METHOD_BANNER_LIST = 'GetBanners';
	const METHOD_BANNER_MODERATE = 'ModerateBanners';
	const METHOD_BANNER_STOP = 'StopBanners';
	const METHOD_BANNER_RESUME = 'ResumeBanners';
	const METHOD_BANNER_ARCHIVE = 'ArchiveBanners';
	const METHOD_BANNER_UNARCHIVE = 'UnArchiveBanners';
	const METHOD_BANNER_DELETE = 'DeleteBanners';
	const METHOD_WORDSTAT_REPORT_CREATE = 'CreateNewWordstatReport';
	const METHOD_WORDSTAT_REPORT_DELETE = 'DeleteWordstatReport';
	const METHOD_WORDSTAT_REPORT_GET = 'GetWordstatReport';
	const METHOD_WORDSTAT_REPORT_LIST = 'GetWordstatReportList';
	const METHOD_FORECAST_REPORT_CREATE = 'CreateNewForecast';
	const METHOD_FORECAST_REPORT_DELETE = 'DeleteForecastReport';
	const METHOD_FORECAST_REPORT_GET = 'GetForecast';
	const METHOD_FORECAST_REPORT_LIST = 'GetForecastList';

	const BOOL_YES = "Yes";
	const BOOL_NO = "No";

	const STATUS_NEW = "New";
	const STATUS_PENDING = "Pending";

	const PRIORITY_LOW = "Low";
	const PRIORITY_MEDIUM = "Medium";
	const PRIORITY_HIGH = "High";

	const TTL_WORDSTAT_REPORT = 3600; // session report lifetime
	const TTL_WORDSTAT_REPORT_EXT = 18000; // yandex report lifetime
	const TTL_FORECAST_REPORT = 3600; // session report lifetime
	const TTL_FORECAST_REPORT_EXT = 18000; // yandex report lifetime

	const MAX_WORDSTAT_REPORTS = 5;
	const MAX_FORECAST_REPORTS = 5;
	const MAX_CAMPAIGNS_BANNER_UPDATE = 10;

	const ERROR_NOT_FOUND = 27;
	const ERROR_NO_STATS = 2;

	protected $engineId = 'yandex_direct';
	protected $locale = null;

	public function __construct()
	{
		$this->locale = in_array(LANGUAGE_ID, array("ru", "en", "ua")) ? LANGUAGE_ID : 'en';
		parent::__construct();
	}

	/**
	 * Sends request to create new campaign
	 *
	 * @param array $campaignParam Campaign params.
	 *
	 * @returns string XML_ID for newly created campaign
	 * @throws SystemException
	 * @throws YandexDirectException
	 * @see YandexCampaignTable::createParam
	 */
	public function addCampaign(array $campaignParam)
	{
		$result = $this->query(static::METHOD_CAMPAIGN_ADD, $campaignParam);
		$result = YandexJson::decode($result->getResult());

		if(!empty($result['error_code']))
		{
			throw new YandexDirectException($result);
		}

		return $result["data"];
	}

	/**
	 * Sends request to update an existing campaign
	 *
	 * @param array $campaignParam Campaign params.
	 *
	 * @returns string XML_ID for newly created campaign
	 * @throws SystemException
	 * @throws YandexDirectException
	 * @see YandexCampaignTable::createParam
	 */
	public function updateCampaign(array $campaignParam)
	{
		$result = $this->query(static::METHOD_CAMPAIGN_UPDATE, $campaignParam);
		$result = YandexJson::decode($result->getResult());

		if(!empty($result['error_code']))
		{
			throw new YandexDirectException($result);
		}

		return $result["data"];
	}

	/**
	 * Returns campaign params got from Yandex
	 *
	 * @param mixed $campaignId XML_ID or array of XML_IDs
	 *
	 * @return array with campaign data
	 * @throws SystemException
	 * @throws YandexDirectException
	 */
	public function getCampaign($campaignId)
	{
		if(empty($campaignId))
		{
			throw new ArgumentNullException("campaignId");
		}

		if(!is_array($campaignId))
		{
			$campaignId = array($campaignId);
		}

		$result = $this->query(static::METHOD_CAMPAIGN_GET, array(
			'CampaignIDS' => $campaignId
		));
		$result = YandexJson::decode($result->getResult());

		if(!empty($result['error_code']))
		{
			throw new YandexDirectException($result);
		}

		return $result["data"];
	}

	public function getCampaignList()
	{
		$result = $this->query(static::METHOD_CAMPAIGN_LIST);
		$result = YandexJson::decode($result->getResult());

		if(!empty($result['error_code']))
		{
			throw new YandexDirectException($result);
		}

		return $result["data"];
	}

	public function archiveCampaign($campaignId)
	{

		$result = $this->query(static::METHOD_CAMPAIGN_ARCHIVE, array("CampaignID" => $campaignId));
		$result = YandexJson::decode($result->getResult());

		if(!empty($result['error_code']))
		{
			throw new YandexDirectException($result);
		}

		return $result["data"];
	}

	public function unArchiveCampaign($campaignId)
	{

		$result = $this->query(static::METHOD_CAMPAIGN_UNARCHIVE, array("CampaignID" => $campaignId));
		$result = YandexJson::decode($result->getResult());

		if(!empty($result['error_code']))
		{
			throw new YandexDirectException($result);
		}

		return $result["data"];
	}

	public function resumeCampaign($campaignId)
	{

		$result = $this->query(static::METHOD_CAMPAIGN_RESUME, array("CampaignID" => $campaignId));
		$result = YandexJson::decode($result->getResult());

		if(!empty($result['error_code']))
		{
			throw new YandexDirectException($result);
		}

		return $result["data"];
	}

	public function stopCampaign($campaignId)
	{

		$result = $this->query(static::METHOD_CAMPAIGN_STOP, array("CampaignID" => $campaignId));
		$result = YandexJson::decode($result->getResult());

		if(!empty($result['error_code']))
		{
			throw new YandexDirectException($result);
		}

		return $result["data"];
	}

	public function deleteCampaign($campaignId)
	{

		$result = $this->query(static::METHOD_CAMPAIGN_DELETE, array("CampaignID" => $campaignId));
		$result = YandexJson::decode($result->getResult());

		if(!empty($result['error_code']))
		{
			throw new YandexDirectException($result);
		}

		return $result["data"];
	}

	/**
	 * Sends request to create new banner
	 *
	 * @param array $bannerParam Banner params.
	 *
	 * @returns string XML_ID for newly created banner
	 * @throws SystemException
	 * @throws YandexDirectException
	 * @see YandexBannerTable::createParam
	 */
	public function addBanner(array $bannerParam)
	{
		$result = $this->query(static::METHOD_BANNER_ADD, array($bannerParam));
		$result = YandexJson::decode($result->getResult());

		if(!empty($result['error_code']))
		{
			throw new YandexDirectException($result);
		}

		return $result["data"][0];
	}

	/**
	 * Sends request to update an existing banner
	 *
	 * @param array $bannerParam Banner params.
	 *
	 * @returns string XML_ID for newly created banner
	 * @throws SystemException
	 * @throws YandexDirectException
	 * @see YandexBannerTable::createParam
	 */
	public function updateBanner(array $bannerParam)
	{
		$result = $this->query(static::METHOD_BANNER_UPDATE, array($bannerParam));
		$result = YandexJson::decode($result->getResult());

		if(!empty($result['error_code']))
		{
			throw new YandexDirectException($result);
		}

		return $result["data"][0];
	}

	public function getBanners($bannerId)
	{
		if(empty($bannerId))
		{
			throw new ArgumentNullException("bannerId");
		}

		if(!is_array($bannerId))
		{
			$bannerId = array($bannerId);
		}

		$result = $this->query(static::METHOD_BANNER_LIST, array(
			'BannerIDS' => $bannerId
		));
		$result = YandexJson::decode($result->getResult());

		if(!empty($result['error_code']))
		{
			throw new YandexDirectException($result);
		}

		return $result["data"];
	}

	public function getCampaignBanners($campaignId)
	{
		if(empty($campaignId))
		{
			throw new ArgumentNullException("campaignId");
		}

		if(!is_array($campaignId))
		{
			$campaignId = array($campaignId);
		}

		$result = $this->query(static::METHOD_BANNER_LIST, array(
			'CampaignIDS' => $campaignId
		));
		$result = YandexJson::decode($result->getResult());

		if(!empty($result['error_code']))
		{
			throw new YandexDirectException($result);
		}

		return $result["data"];
	}

	public function moderateBanners($campaignId, array $bannerIDs)
	{
		$queryData = array(
			'CampaignID' => $campaignId,
			'BannerIDS' => $bannerIDs,
		);

		$result = $this->query(static::METHOD_BANNER_MODERATE, $queryData);
		$result = YandexJson::decode($result->getResult());

		if(!empty($result['error_code']))
		{
			throw new YandexDirectException($result);
		}

		return $result["data"];
	}

	public function stopBanners($campaignId, array $bannerIDs)
	{
		$queryData = array(
			'CampaignID' => $campaignId,
			'BannerIDS' => $bannerIDs,
		);

		$result = $this->query(static::METHOD_BANNER_STOP, $queryData);
		$result = YandexJson::decode($result->getResult());

		if(!empty($result['error_code']))
		{
			throw new YandexDirectException($result);
		}

		return $result["data"];
	}

	public function resumeBanners($campaignId, array $bannerIDs)
	{
		$queryData = array(
			'CampaignID' => $campaignId,
			'BannerIDS' => $bannerIDs,
		);

		$result = $this->query(static::METHOD_BANNER_RESUME, $queryData);
		$result = YandexJson::decode($result->getResult());

		if(!empty($result['error_code']))
		{
			throw new YandexDirectException($result);
		}

		return $result["data"];
	}

	public function archiveBanners($campaignId, array $bannerIDs)
	{

		$result = $this->query(static::METHOD_BANNER_ARCHIVE, array(
			"CampaignID" => $campaignId,
			"BannerIDS" => $bannerIDs,
		));
		$result = YandexJson::decode($result->getResult());

		if(!empty($result['error_code']))
		{
			throw new YandexDirectException($result);
		}

		return $result["data"];
	}

	public function unArchiveBanners($campaignId, array $bannerIDs)
	{
		$result = $this->query(static::METHOD_BANNER_UNARCHIVE, array(
			"CampaignID" => $campaignId,
			"BannerIDS" => $bannerIDs,
		));
		$result = YandexJson::decode($result->getResult());

		if(!empty($result['error_code']))
		{
			throw new YandexDirectException($result);
		}

		return $result["data"];
	}

	public function deleteBanners($campaignId, array $bannerIDs)
	{
		$result = $this->query(static::METHOD_BANNER_DELETE, array(
			"CampaignID" => $campaignId,
			"BannerIDS" => $bannerIDs,
		));
		$result = YandexJson::decode($result->getResult());

		if(!empty($result['error_code']))
		{
			throw new YandexDirectException($result);
		}

		return $result["data"];
	}

	/**
	 * Returns Yandex regions list
	 *
	 * @return array of regions
	 * @throws SystemException
	 * @see https://tech.yandex.ru/direct/doc/dg-v4/reference/GetRegions-docpage/
	 */
	public function getRegions()
	{
		$result = $this->query(static::METHOD_REGION_GET);
		$result = YandexJson::decode($result->getResult());

		return $result["data"];
	}

	public function createWordstatReport(array $phrase, $geo = null)
	{
		$queryData = array(
			'Phrases' => $phrase
		);

		if(is_array($geo))
		{
			$queryData['GeoID'] = $geo;
		}

		$result = $this->query(static::METHOD_WORDSTAT_REPORT_CREATE, $queryData);
		$result = YandexJson::decode($result->getResult());

		if(!empty($result['error_code']))
		{
			throw new YandexDirectException($result);
		}

		return $result["data"];
	}

	public function deleteWordstatReport($reportId)
	{
		$result = $this->query(static::METHOD_WORDSTAT_REPORT_DELETE, $reportId);
		$result = YandexJson::decode($result->getResult());

		return $result["data"];
	}

	public function getWordstatReport($reportId)
	{
		$result = $this->query(static::METHOD_WORDSTAT_REPORT_GET, $reportId);
		$result = YandexJson::decode($result->getResult());

		if(!empty($result['error_code']))
		{
			throw new YandexDirectException($result);
		}

		return $result["data"];
	}

	public function getWordstatReportList()
	{
		$result = $this->query(static::METHOD_WORDSTAT_REPORT_LIST);
		$result = YandexJson::decode($result->getResult());

		return $result["data"];
	}

	public function createForecastReport(array $phrase, $geo = null)
	{
		$queryData = array(
			'Phrases' => $phrase
		);

		if(is_array($geo))
		{
			$queryData['GeoID'] = $geo;
		}

		$result = $this->query(static::METHOD_FORECAST_REPORT_CREATE, $queryData);
		$result = YandexJson::decode($result->getResult());

		if(!empty($result['error_code']))
		{
			throw new YandexDirectException($result);
		}

		return $result["data"];
	}

	public function deleteForecastReport($reportId)
	{
		$result = $this->query(static::METHOD_FORECAST_REPORT_DELETE, $reportId);
		$result = YandexJson::decode($result->getResult());

		return $result["data"];
	}

	public function getForecastReport($reportId)
	{
		$result = $this->query(static::METHOD_FORECAST_REPORT_GET, $reportId);
		$result = YandexJson::decode($result->getResult());

		if(!empty($result['error_code']))
		{
			throw new YandexDirectException($result);
		}

		return $result["data"];
	}

	public function getForecastReportList()
	{
		$result = $this->query(static::METHOD_FORECAST_REPORT_LIST);
		$result = YandexJson::decode($result->getResult());

		return $result["data"];
	}


	/**
	 * Returns HttpClient object with query result
	 *
	 * @param string $method Method
	 * @param array $param array of query data
	 * @param bool $skipRefreshAuth Skip authorization refresh. Doesn't work with Yandex.
	 *
	 * @returns \Bitrix\Main\Web\HttpClient
	 * @throws SystemException
	 */
	protected function query($method, $param = array(), $skipRefreshAuth = false)
	{
		if($this->engineSettings['AUTH'])
		{
			$http = new HttpClient();
			$http->setRedirect(false);
			$http->setHeader("Content-Type", "application/json; charset=utf-8");

			$postData = array(
				"method" => $method,
				"locale" => $this->locale,
				"token" => $this->engineSettings['AUTH']['access_token'],
			);

			if(!empty($param))
			{
				$postData["param"] = $param;
			}

			$postData = YandexJson::encode($postData, JSON_UNESCAPED_UNICODE);

			$ts = microtime(true);
			$http->post(static::API_URL, $postData);

			LogTable::add(array(
				'ENGINE_ID' => $this->getId(),
				'REQUEST_URI' => static::API_URL,
				'REQUEST_DATA' => Text\Encoding::convertEncoding($postData, 'UTF-8', SITE_CHARSET),
				'RESPONSE_TIME' => microtime(true)-$ts,
				'RESPONSE_STATUS' => $http->getStatus(),
				'RESPONSE_DATA' => Text\Encoding::convertEncoding($http->getResult(), 'UTF-8', SITE_CHARSET),
			));

			if($http->getStatus() == 401 && !$skipRefreshAuth)
			{
				if($this->checkAuthExpired(false))
				{
					$this->query($method, $param, true);
				}
			}

			return $http;
		}
		else
		{
			throw new SystemException("No Yandex auth data");
		}
	}

	public function finance_query($method, $masterToken, $operationNum, $param = array(), $skipRefreshAuth = false)
	{
		if($this->engineSettings['AUTH'])
		{
			$http = new HttpClient();
			$http->setRedirect(false);
			$http->setHeader("Content-Type", "application/json; charset=utf-8");

			$auth = $this->getCurrentUser();

			$financeToken = hash(
				"sha256",
				$masterToken.$operationNum.$method.$auth['login']);

			$postData = array(
				"method" => $method,
				"finance_token" => $financeToken,
				"operation_num" => $operationNum,
				"locale" => $this->locale,
				"token" => $this->engineSettings['AUTH']['access_token'],
			);

			if(!empty($param))
			{
				$postData["param"] = $param;
			}

			$postData = YandexJson::encode($postData, JSON_UNESCAPED_UNICODE);

			$http->post(self::API_URL, $postData);

			if($http->getStatus() == 401 && !$skipRefreshAuth)
			{
				if($this->checkAuthExpired(false))
				{
					$this->query($method, $param, true);
				}
			}

			return $http;
		}
		else
		{
			throw new SystemException("No Yandex auth data");
		}
	}

	public function updateCampaignManual($campaignId = null)
	{
		$newCampaigns = array();

		$res = array(
			'added' => 0,
			'updated' => 0,
			'error' => 0,
		);

		$keys = array();

		if(!is_array($campaignId) && $campaignId > 0)
		{
			$campaignId = array($campaignId);
		}

		if(is_array($campaignId) && count($campaignId) > 0)
		{
			$dbRes = YandexCampaignTable::getList(array(
				'filter' => array(
					'=ID' => $campaignId,
					'=ENGINE_ID' => $this->getId()
				),
				'select' => array('XML_ID')
			));

			while($campaign = $dbRes->fetch())
			{
				$keys[] = $campaign['XML_ID'];
			}
		}
		else
		{
			$campaignList = $this->getCampaignList();
			foreach($campaignList as $campaignInfo)
			{
				$keys[] = $campaignInfo['CampaignID'];
			}
		}

		if(count($keys) > 0)
		{
			$campaignList = $this->getCampaign($keys);

			$campaignListSorted = array();
			foreach($campaignList as $campaignInfo)
			{
				$campaignListSorted[$campaignInfo['CampaignID']] = $campaignInfo;
			}

			$dbCampaigns = YandexCampaignTable::getList(array(
				'filter' => array(
					'=XML_ID' => array_keys($campaignListSorted),
					'=ENGINE_ID' => $this->getId(),
				)
			));

			YandexCampaignTable::setSkipRemoteUpdate(true);
			while($campaign = $dbCampaigns->fetch())
			{
				if(isset($campaignListSorted[$campaign['XML_ID']]))
				{
					$result = YandexCampaignTable::update(
						$campaign['ID'], array(
							"SETTINGS" => $campaignListSorted[$campaign['XML_ID']]
						)
					);

					unset($campaignListSorted[$campaign['XML_ID']]);

					if($result->isSuccess())
					{
						$res['updated']++;
					}
					else
					{
						$res['error']++;
					}
				}
			}

			foreach($campaignListSorted as $campaignId => $campaignInfo)
			{
				$result = YandexCampaignTable::add(array(
					"SETTINGS" => $campaignInfo
				));

				if($result->isSuccess())
				{
					$newCampaigns[] = $result->getId();
					$res['added']++;
				}
				else
				{
					$res['error']++;
				}
			}
			YandexCampaignTable::setSkipRemoteUpdate(false);
		}

		if(count($newCampaigns) > 0)
		{
			set_time_limit(300);

			$res['new'] = $newCampaigns;

			$res['banner'] = array();
			$cnt = ceil(count($newCampaigns)/static::MAX_CAMPAIGNS_BANNER_UPDATE);
			for($i = 0; $i < $cnt; $i++)
			{
				$res['banner'] = array_merge(
					$res['banner'],
					$this->updateBannersManual(
						array_slice(
							$newCampaigns,
							$i*static::MAX_CAMPAIGNS_BANNER_UPDATE,
							static::MAX_CAMPAIGNS_BANNER_UPDATE
						)
					)
				);
			}

			if(count($newCampaigns) <= static::MAX_CAMPAIGNS_BANNER_UPDATE)
			{
				$res['banner'] = $this->updateBannersManual($newCampaigns);
			}
		}

		return $res;
	}

	public function updateBannersManual($campaignId, $bannerId = null)
	{
		$res = array(
			'added' => 0,
			'updated' => 0,
			'error' => 0,
		);

		if(!is_array($bannerId) && $bannerId > 0)
		{
			$bannerId = array($bannerId);
		}

		$bannerList = array();
		if(is_array($bannerId) && count($bannerId) > 0)
		{
			$dbRes = YandexBannerTable::getList(array(
				'filter' => array(
					'=ID' => $bannerId,
					'=ENGINE_ID' => $this->getId()
				),
				'select' => array('XML_ID')
			));

			while($banner = $dbRes->fetch())
			{
				$keys[] = $banner['XML_ID'];
			}

			$bannerList = $this->getBanners($keys);
		}
		else
		{
			$dbCampaigns = YandexCampaignTable::getList(array(
				'filter' => array(
					'=ID' => $campaignId,
					'=ENGINE_ID' => $this->getId(),
				),
				'select' => array('ID', 'XML_ID'),
			));

			while($campaign = $dbCampaigns->fetch())
			{
				$campaignIndex[$campaign['XML_ID']] = $campaign['ID'];
			}

			if(count($campaignIndex) > 0)
			{
				$bannerList = $this->getCampaignBanners(array_keys($campaignIndex));
			}
		}
		if(count($bannerList) > 0)
		{
			$bannerListSorted = array();
			foreach($bannerList as $bannerInfo)
			{
				$bannerListSorted[$bannerInfo['BannerID']] = $bannerInfo;
			}

			$dbBanners = YandexBannerTable::getList(array(
				'filter' => array(
					'=XML_ID' => array_keys($bannerListSorted),
					'=ENGINE_ID' => $this->getId(),
				)
			));

			YandexBannerTable::setSkipRemoteUpdate(true);
			while($banner = $dbBanners->fetch())
			{
				if(isset($bannerListSorted[$banner['XML_ID']]))
				{
					$result = YandexBannerTable::update(
						$banner['ID'], array(
							"SETTINGS" => $bannerListSorted[$banner['XML_ID']]
						)
					);

					unset($bannerListSorted[$banner['XML_ID']]);

					if($result->isSuccess())
					{
						$res['updated']++;
					}
					else
					{
						$res['error']++;
					}
				}
			}

			foreach($bannerListSorted as $bannerId => $bannerInfo)
			{
				$result = YandexBannerTable::add(array(
					"CAMPAIGN_ID" => $campaignIndex[$bannerInfo['CampaignID']],
					"SETTINGS" => $bannerInfo,
				));

				if($result->isSuccess())
				{
					$res['added']++;
				}
				else
				{
					$res['error']++;
				}
			}
			YandexBannerTable::setSkipRemoteUpdate(false);
		}

		return $res;
	}


	public static function updateAgent()
	{
		$engine = new self();
		if($engine->getAuthSettings())
		{
			try
			{
				$dbRes = YandexCampaignTable::getList(array(
					'filter' => array(
						'<LAST_UPDATE' => DateTime::createFromTimestamp(time() - YandexCampaignTable::CACHE_LIFETIME),
						'=ENGINE_ID' => $engine->getId(),
					),
					'select' => array('CNT'),
					'runtime' => array(
						new ExpressionField('CNT', 'COUNT(*)')
					)
				));

				$res = $dbRes->fetch();
				if($res['CNT'] > 0)
				{
					$engine->updateCampaignManual();
				}

				$availableCampaigns = array();
				$campaignList = $engine->getCampaignList();
				foreach($campaignList as $campaignInfo)
				{
					$availableCampaigns[] = $campaignInfo['CampaignID'];
				}

				if(count($availableCampaigns) > 0)
				{
					$dbRes = YandexBannerTable::getList(array(
						'group' => array('CAMPAIGN_ID'),
						'filter' => array(
							'<LAST_UPDATE' => DateTime::createFromTimestamp(time() - YandexBannerTable::CACHE_LIFETIME),
							'=ENGINE_ID' => $engine->getId(),
							'=CAMPAIGN.XML_ID' => $availableCampaigns,
						),
						'select' => array('CAMPAIGN_ID'),
					));

					$campaignId = array();
					while($res = $dbRes->fetch())
					{
						$campaignId[] = $res['CAMPAIGN_ID'];
					}

					if(count($campaignId) > 0)
					{
						$engine->updateBannersManual($campaignId);
					}
				}
			}
			catch(YandexDirectException $e)
			{}
		}

		return __CLASS__."::updateAgent();";
	}
}
