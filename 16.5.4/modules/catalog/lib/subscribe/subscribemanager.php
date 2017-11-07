<?php
namespace Bitrix\Catalog\Subscribe;

use Bitrix\Catalog\ProductTable;
use Bitrix\Catalog\SubscribeAccessTable;
use Bitrix\Catalog\SubscribeTable;
use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Security\Random;
use Bitrix\Main\Event;
use Bitrix\Main\EventManager;

Loc::loadMessages(__FILE__);

/**
 * Class SubscribeManager manages subscriptions.
 *
 **/
class SubscribeManager
{
	const ERROR_REQUIRED_PARAMATERS = 'ERROR_REQUIRED_PARAMATERS_12001';
	const ERROR_ADD_SUBSCRIBE = 'ERROR_ADD_SUBSCRIBE_12002';
	const ERROR_VALIDATE_FIELDS = 'ERROR_VALIDATE_FIELDS_12003';
	const ERROR_SUBSCRIBER_IDENTIFICATION = 'ERROR_SUBSCRIBER_IDENTIFICATION_12004';
	const ERROR_AUTHORIZATION = 'ERROR_AUTHORIZATION_12005';
	const ERROR_DELETE_SUBSCRIBE = 'ERROR_ADD_SUBSCRIBE_12006';
	const ERROR_ADD_SUBSCRIBE_ALREADY_EXISTS = 'ERROR_ADD_SUBSCRIBE_ALREADY_EXISTS_12007';
	const ERROR_ACTIVITY_CHANGE = 'ERROR_ACTIVITY_CHANGE_12008';

	/** @var  ErrorCollection */
	protected $errorCollection;
	/** @var integer */
	protected $userId = 0;
	/** @var bool|null  */
	protected $isAdmin = false;

	public $contactTypes = array();

	protected $fields = array();
	protected $listAvailableFields = array(
		'DATE_TO',
		'USER_CONTACT',
		'CONTACT_TYPE',
		'USER_ID',
		'ITEM_ID',
		'NEED_SENDING',
		'SITE_ID',
	);

	public function __construct()
	{
		$this->errorCollection = new ErrorCollection;

		$this->contactTypes = SubscribeTable::getContactTypes();

		global $USER;
		if(is_object($USER) && $USER->isAuthorized())
		{
			$this->isAdmin = $USER->isAdmin();
			$this->userId = $USER->getId();
		}
	}

	/**
	 * @return Error[]
	 */
	public function getErrors()
	{
		return $this->errorCollection->toArray();
	}

	/**
	 * The method creates a new subscription.
	 *
	 * @param array $subscribeData An array containing the data of a new subscription.
	 * @return bool|int
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Exception
	 */
	public function addSubscribe(array $subscribeData)
	{
		$this->checkRequiredInputParams($subscribeData,
			array('USER_CONTACT', 'ITEM_ID', 'SITE_ID', 'CONTACT_TYPE'));
		if(!$this->errorCollection->isEmpty())
		{
			return false;
		}

		$resultObject = ProductTable::getList(array(
			'select' => array(
				'SUBSCRIBE',
				'USER_CONTACT' => 'Bitrix\Catalog\SubscribeTable:PRODUCT.USER_CONTACT',
				'CONTACT_TYPE' => 'Bitrix\Catalog\SubscribeTable:PRODUCT.CONTACT_TYPE',
				'DATE_TO' => 'Bitrix\Catalog\SubscribeTable:PRODUCT.DATE_TO',
			),
			'filter' => array(
				'=ID' => $subscribeData['ITEM_ID'],
			),
		));
		while($productSubscribeData = $resultObject->fetch())
		{
			if(!$this->checkDataBeforeSave($productSubscribeData, $subscribeData))
				break;
		}
		if(!$this->errorCollection->isEmpty())
		{
			return false;
		}

		$this->fields = array();
		foreach($subscribeData as $fieldId => $fieldValue)
		{
			if(in_array($fieldId, $this->listAvailableFields))
			{
				$this->fields[$fieldId] = $fieldValue;
			}
		}

		$this->validateFields();
		if(!$this->errorCollection->isEmpty())
		{
			return false;
		}

		$result = SubscribeTable::add($this->fields);
		if($result->isSuccess())
		{
			$this->setSessionOfSibscribedProducts($subscribeData['ITEM_ID']);
			return $result->getId();
		}
		else
		{
			foreach($result->getErrorMessages() as $errorMessage)
				$this->errorCollection->add(array(new Error($errorMessage, self::ERROR_ADD_SUBSCRIBE)));
			return false;
		}
	}

	/**
	 * The method removes a lot of subscriptions received subscribeId list with the account permissions.
	 *
	 * @param array $listSubscribeId List subscribe id.
	 * @param integer $itemId If this parameter is passed, cleaned write to the session.
	 * @return bool
	 * @throws \Exception
	 */
	public function deleteManySubscriptions(array $listSubscribeId, $itemId = 0)
	{
		foreach($listSubscribeId as $subscribeId)
		{
			if($this->checkAccessToSubscription($subscribeId))
			{
				$result = SubscribeTable::delete($subscribeId);
				if(!$result->isSuccess())
				{
					foreach($result->getErrorMessages() as $errorMessage)
						$this->errorCollection->add(array(new Error($errorMessage, self::ERROR_DELETE_SUBSCRIBE)));
					return false;
				}
			}
			else
			{
				$this->errorCollection->add(array(new Error(
					Loc::getMessage('ERROR_ACCESS_DENIDE_DELETE_SUBSCRIBE'), self::ERROR_DELETE_SUBSCRIBE)));
				return false;
			}
		}

		if(!empty($_SESSION['SUBSCRIBE_PRODUCT']['LIST_PRODUCT_ID'][$itemId]))
		{
			unset($_SESSION['SUBSCRIBE_PRODUCT']['LIST_PRODUCT_ID'][$itemId]);
		}

		return true;
	}

	/**
	 * The method checks the access to subscription by using the userId or token.
	 * Administrators subscription is always available.
	 *
	 * @param integer $subscribeId Subscribe id.
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public function checkAccessToSubscription($subscribeId)
	{
		if($this->isAdmin)
		{
			return true;
		}

		$resultObject = SubscribeTable::getList(array(
			'select' => array(
				'USER_ID',
				'TOKEN' => 'Bitrix\Catalog\SubscribeAccessTable:SUBSCRIBE.TOKEN',
			),
			'filter' => array('=ID' => intval($subscribeId)),
		));
		if($subscribeData = $resultObject->fetch())
		{
			if($this->userId)
			{
				if($subscribeData['USER_ID'] == $this->userId)
				{
					return true;
				}
			}
			else
			{
				if(isset($_SESSION['SUBSCRIBE_PRODUCT']['TOKEN'])
					&& $subscribeData['TOKEN'] == $_SESSION['SUBSCRIBE_PRODUCT']['TOKEN'])
				{
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * The method begins the process of identification of the anonymous subscriber.
	 *
	 * @param array $subscriberData An array containing the data necessary for identification.
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentNullException
	 */
	public function runSubscriberIdentification(array $subscriberData)
	{
		$this->checkRequiredInputParams($subscriberData,
			array('CONTACT_TYPE', 'USER_CONTACT', 'SITE_ID'));
		if(!$this->errorCollection->isEmpty())
		{
			return false;
		}

		$currentContactType = $this->contactTypes[$subscriberData['CONTACT_TYPE']];
		if(!preg_match($currentContactType['RULE'], $subscriberData['USER_CONTACT']))
		{
			$this->errorCollection->add(array(new Error(
				Loc::getMessage('ERROR_SUBSCRIBE_ENTRY_CONFIRMATION_CODE'), self::ERROR_SUBSCRIBER_IDENTIFICATION)));
			return false;
		}

		$token = Random::getString(6);

		try
		{
			SubscribeAccessTable::clearOldRows();
			$accessRow = SubscribeAccessTable::getRow(
				array(
					'select' => array('ID'),
					'filter' => array('=USER_CONTACT' => $subscriberData['USER_CONTACT'])
				)
			);
			if($accessRow['ID'])
			{
				$result = SubscribeAccessTable::update($accessRow['ID'], array(
					'DATE_FROM' => new DateTime(),
					'TOKEN' => $token
				));
			}
			else
			{
				$result = SubscribeAccessTable::add(array(
					'DATE_FROM' => new DateTime(),
					'USER_CONTACT' => $subscriberData['USER_CONTACT'],
					'TOKEN' => $token
				));
			}
			if(!$result)
			{
				$this->errorCollection->add(array(new Error(
					Loc::getMessage('ERROR_SUBSCRIBE_ENTRY_CONFIRMATION_CODE'), self::ERROR_SUBSCRIBER_IDENTIFICATION)));
			}
		}
		catch(\Exception $errorObject)
		{
			$this->errorCollection->add(array(new Error($errorObject->getMessage())));
		}

		if(!$this->errorCollection->isEmpty())
		{
			return false;
		}

		/* Preparation of data for the mail template */
		$dataSendToNotice = array();
		$dataSendToNotice[$subscriberData['CONTACT_TYPE']][$subscriberData['USER_CONTACT']][] = array(
			'EVENT_NAME' => 'CATALOG_PRODUCT_SUBSCRIBE_LIST_CONFIRM',
			'EMAIL_TO' => $subscriberData['USER_CONTACT'],
			'EMAIL_FROM' => Option::get('main', 'email_from'),
			'SITE_ID' => $subscriberData['SITE_ID'],
			'TOKEN' => $token,
		);

		foreach($this->contactTypes as $typeId => $typeData)
		{
			$eventKey = EventManager::getInstance()
				->addEventHandler('catalog', 'OnSubscribeSubmit', $typeData['HANDLER']);

			$event = new Event('catalog', 'OnSubscribeSubmit', $dataSendToNotice[$typeId]);
			$event->send();

			EventManager::getInstance()->removeEventHandler('catalog', 'OnSubscribeSubmit', $eventKey);
		}

		return true;
	}

	/**
	 * The method authenticates an anonymous subscriber.
	 *
	 * @param array $authorizationData The authentication information.
	 * @return bool
	 */
	public function authorizeSubscriber(array $authorizationData)
	{
		$this->checkRequiredInputParams($authorizationData, array('USER_CONTACT', 'SUBSCRIBER_TOKEN'));
		if(!$this->errorCollection->isEmpty())
		{
			return false;
		}

		try
		{
			SubscribeAccessTable::clearOldRows();
			$accessRow = SubscribeAccessTable::getRow(
				array(
					'select' => array('ID'),
					'filter' => array(
						'TOKEN' => $authorizationData['SUBSCRIBER_TOKEN'],
						'USER_CONTACT' => $authorizationData['USER_CONTACT']
					)
				)
			);

			if(!$accessRow['ID'])
			{
				$this->errorCollection->add(array(new Error(
					Loc::getMessage('ERROR_AUTHORIZATION_ACCESS_ROW_NOT_FOUND'), self::ERROR_AUTHORIZATION)));
				return false;
			}

			$_SESSION['SUBSCRIBE_PRODUCT'] = array(
				'TOKEN' => $authorizationData['SUBSCRIBER_TOKEN'],
				'USER_CONTACT' => $authorizationData['USER_CONTACT']
			);
		}
		catch(\Exception $errorObject)
		{
			$this->errorCollection->add(array(new Error($errorObject->getMessage())));
		}

		if(!$this->errorCollection->isEmpty())
		{
			return false;
		}

		return true;
	}

	/**
	 * The method activates the subscription clearing a field DATE_TO or writing the subscription term.
	 *
	 * @param array $listSubscribeId List subscribe id.
	 * @param int $timePeriod Subscription period in seconds.
	 * @return bool
	 * @throws \Exception
	 */
	public function activateSubscription(array $listSubscribeId, $timePeriod = 0)
	{
		if($timePeriod)
		{
			$fields = array('DATE_TO' => DateTime::createFromTimestamp(time() + intval($timePeriod)));
		}
		else
		{
			$fields = array('DATE_TO' => false);
		}

		foreach($listSubscribeId as $subscribeId)
		{
			$result = SubscribeTable::update($subscribeId, $fields);
			if(!$result->isSuccess())
			{
				foreach($result->getErrorMessages() as $errorMessage)
					$this->errorCollection->add(array(new Error($errorMessage, self::ERROR_ACTIVITY_CHANGE)));
				return false;
			}
		}

		return true;
	}

	/**
	 * The method deactivates the subscription by writing the current date.
	 *
	 * @param array $listSubscribeId List subscribe id.
	 * @return bool
	 * @throws \Exception
	 */
	public function deactivateSubscription(array $listSubscribeId)
	{
		foreach($listSubscribeId as $subscribeId)
		{
			$result = SubscribeTable::update($subscribeId, array('DATE_TO' => new DateTime()));
			if(!$result->isSuccess())
			{
				foreach($result->getErrorMessages() as $errorMessage)
					$this->errorCollection->add(array(new Error($errorMessage, self::ERROR_ACTIVITY_CHANGE)));
				return false;
			}
		}

		return true;
	}

	/**
	 * The method checks the subscription activity field value DATE_TO.
	 *
	 * @param mixed $dateTo An empty value or an instance DateTime.
	 * @return bool
	 */
	public function checkSubscriptionActivity($dateTo)
	{
		if($dateTo)
		{
			if($dateTo instanceof DateTime && $dateTo->getTimestamp() > time())
			{
				return true;
			}
			return false;
		}
		else
		{
			return true;
		}
	}

	private function checkDataBeforeSave($productSubscribeData, array $subscribeData)
	{
		if(!$productSubscribeData || !is_array($productSubscribeData))
		{
			$this->errorCollection->add(array(new Error(
				Loc::getMessage('ERROR_PRODUCT_NOT_FOUND'), self::ERROR_ADD_SUBSCRIBE)));
			return false;
		}
		if($productSubscribeData['SUBSCRIBE'] == 'N' || ($productSubscribeData['SUBSCRIBE'] == 'D')
			&& (Option::get('catalog', 'default_subscribe') != 'Y')
			|| (Option::get('catalog', 'subscribe_enabled') == 'N'))
		{
			$this->errorCollection->add(array(new Error(
				Loc::getMessage('ERROR_SUBSCRIBE_DENIED'), self::ERROR_ADD_SUBSCRIBE)));
			return false;
		}
		if(!array_key_exists($subscribeData['CONTACT_TYPE'], $this->contactTypes))
		{
			$this->errorCollection->add(array(new Error(
				Loc::getMessage('ERROR_CONTACT_TYPE'), self::ERROR_ADD_SUBSCRIBE)));
			return false;
		}
		if($productSubscribeData['USER_CONTACT'] == $subscribeData['USER_CONTACT']
			&& $productSubscribeData['DATE_TO'] == null)
		{
			$this->setSessionOfSibscribedProducts($subscribeData['ITEM_ID']);
			$this->errorCollection->add(array(new Error(
				Loc::getMessage('ERROR_SUBSCRIBE_ALREADY_EXISTS'), self::ERROR_ADD_SUBSCRIBE_ALREADY_EXISTS)));
			return false;
		}
		return true;
	}

	private function validateFields()
	{
		foreach($this->fields as $fieldId => $fieldValue)
		{
			switch($fieldId)
			{
				case 'DATE_TO':
					if(!($fieldValue instanceof DateTime) ||
						($fieldValue instanceof DateTime && $fieldValue->getTimestamp() < time()))
					{
						$this->errorCollection->add(array(new Error(Loc::getMessage('ERROR_VALIDATE_FIELDS',
							array('#FIELD#' => $fieldId)), self::ERROR_VALIDATE_FIELDS)));
					}
					break;
				case 'USER_CONTACT':
					$currentContactType = $this->contactTypes[$this->fields['CONTACT_TYPE']];
					if(!preg_match($currentContactType['RULE'], $fieldValue))
					{
						$this->errorCollection->add(array(new Error(Loc::getMessage('ERROR_VALIDATE_FIELDS',
							array('#FIELD#' => $fieldId)), self::ERROR_VALIDATE_FIELDS)));
					}
					break;
			}
		}
	}

	private function checkRequiredInputParams(array $inputParams, array $requiredParams)
	{
		foreach ($requiredParams as $param)
		{
			if(!isset($inputParams[$param]) || (!$inputParams[$param] &&
				!(is_string($inputParams[$param]) && strlen($inputParams[$param]))))
			{
				$this->errorCollection->add(array(new Error(Loc::getMessage('ERROR_REQUIRED_PARAMATERS',
					array('#PARAM#' => $param)), self::ERROR_REQUIRED_PARAMATERS)));
				return false;
			}
		}
		return true;
	}

	/**
	 * Write product id to the session to check that the user has subscribed.
	 *
	 * @param $itemId
	 */
	private function setSessionOfSibscribedProducts($itemId)
	{
		$itemId = intval($itemId);
		if(!empty($_SESSION['SUBSCRIBE_PRODUCT']['LIST_PRODUCT_ID'])
			&& is_array($_SESSION['SUBSCRIBE_PRODUCT']['LIST_PRODUCT_ID']))
		{
			$_SESSION['SUBSCRIBE_PRODUCT']['LIST_PRODUCT_ID'][$itemId] = true;
		}
		else
		{
			$_SESSION['SUBSCRIBE_PRODUCT'] = array('LIST_PRODUCT_ID' => array());
			$_SESSION['SUBSCRIBE_PRODUCT']['LIST_PRODUCT_ID'][$itemId] = true;
		}
	}
}