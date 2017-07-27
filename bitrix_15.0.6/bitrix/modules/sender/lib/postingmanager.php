<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sender
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Sender;

use Bitrix\Main\Event;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type;
use Bitrix\Main\Mail;

Loc::loadMessages(__FILE__);

class PostingManager
{
	const SEND_RESULT_ERROR = false;
	const SEND_RESULT_SENT = true;
	const SEND_RESULT_CONTINUE = 'CONTINUE';

	protected static $emailSentPerIteration = 0;
	protected static $currentMailingChainFields = null;

	/**
	 * @param array $arData
	 * @return array
	 */
	public static function onMailEventMailRead(array $arData)
	{
		$id = intval($arData['RECIPIENT_ID']);
		if ($id > 0)
			static::read($id);

		return $arData;
	}

	/**
	 * @param array $arData
	 * @return array
	 */
	public static function onMailEventMailClick(array $arData)
	{
		$id = intval($arData['RECIPIENT_ID']);
		$url = $arData['URL'];
		if ($id > 0 && strlen($url) > 0)
			static::click($id, $url);

		return $arData;
	}

	/**
	 * @param $recipientId
	 */
	public static function read($recipientId)
	{
		$postingContactPrimary = array('ID' => $recipientId);
		$arPostingEmail = PostingRecipientTable::getRowById($postingContactPrimary);
		if ($arPostingEmail && $arPostingEmail['ID'])
		{
			PostingReadTable::add(array(
				'POSTING_ID' => $arPostingEmail['POSTING_ID'],
				'RECIPIENT_ID' => $arPostingEmail['ID'],
			));
		}
	}

	/**
	 * @param $recipientId
	 * @param $url
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function click($recipientId, $url)
	{
		$postingContactPrimary = array('ID' => $recipientId);
		$arPostingEmail = PostingRecipientTable::getRowById($postingContactPrimary);
		if ($arPostingEmail && $arPostingEmail['ID'])
		{
			$arPostingRead = PostingReadTable::getRowById(array(
				'POSTING_ID' => $arPostingEmail['POSTING_ID'],
				'RECIPIENT_ID' => $arPostingEmail['ID']
			));
			if ($arPostingRead === null)
			{
				static::read($recipientId);
			}

			$postingDb = PostingTable::getList(array(
				'select' => array('ID'),
				'filter' => array('ID' => $arPostingEmail['POSTING_ID'], 'MAILING.TRACK_CLICK' => 'Y'),
			));
			if ($postingDb->fetch())
			{
				PostingClickTable::add(array(
					'POSTING_ID' => $arPostingEmail['POSTING_ID'],
					'RECIPIENT_ID' => $arPostingEmail['ID'],
					'URL' => $url
				));
			}
		}
	}


	/**
	 * @param $mailingId
	 * @return array|null
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function getChainReSend($mailingId)
	{
		$result = array();
		$mailChainDb = MailingChainTable::getList(array(
			'select' => array('ID'),
			'filter' => array(
				'MAILING.ID' => $mailingId,
				'MAILING.ACTIVE' => 'Y',
				'REITERATE' => 'N',
				'MAILING_CHAIN.STATUS' => MailingChainTable::STATUS_END,
			)
		));
		while($mailChain = $mailChainDb->fetch())
		{
			$result[] = $mailChain['ID'];
		}

		return (empty($result) ? null : $result);
	}

	/**
	 * @param $mailingChainId
	 * @param array $arParams
	 * @return string
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\DB\Exception
	 */
	protected static function sendInternal($mailingChainId, array $arParams)
	{
		if(static::$currentMailingChainFields !== null)
		{
			if(static::$currentMailingChainFields['ID'] != $mailingChainId)
				static::$currentMailingChainFields = null;
		}

		if(static::$currentMailingChainFields === null)
		{
			$mailingChainDb = MailingChainTable::getList(array(
				'select' => array('*', 'SITE_ID' => 'MAILING.SITE_ID'),
				'filter' => array('ID' => $mailingChainId)
			));
			if(!($arMailingChain = $mailingChainDb->fetch()))
				return PostingRecipientTable::SEND_RESULT_ERROR;


			$charset = false;
			$siteDb = \Bitrix\Main\SiteTable::getList(array(
				'select'=>array('NAME', 'CULTURE_CHARSET'=>'CULTURE.CHARSET'),
				'filter' => array('LID' => $arMailingChain['SITE_ID'])
			));
			if($arSiteDb = $siteDb->fetch())
			{
				$charset = $arSiteDb['CULTURE_CHARSET'];
			}
			else
			{
				throw new \Bitrix\Main\DB\Exception(Loc::getMessage('SENDER_POSTING_MANAGER_ERR_SITE', array('#SITE_ID#' => $arMailingChain['SITE_ID'])));
			}

			if(!$charset)
			{
				throw new \Bitrix\Main\DB\Exception(Loc::getMessage('SENDER_POSTING_MANAGER_ERR_CHARSET', array('#SITE_ID#' => "[".$arMailingChain['SITE_ID']."]".$arSiteDb['NAME'])));
			}


			static::$currentMailingChainFields = array();

			static::$currentMailingChainFields['ID'] = $arMailingChain['ID'];
			static::$currentMailingChainFields['MESSAGE'] = array(
				'BODY_TYPE' => 'html',
				'EMAIL_FROM' => $arMailingChain['EMAIL_FROM'],
				'EMAIL_TO' => '#EMAIL_TO#',
				'SUBJECT' => $arMailingChain['SUBJECT'],
				'MESSAGE' => $arMailingChain['MESSAGE'],
				'MESSAGE_PHP' => \Bitrix\Main\Mail\Internal\EventMessageTable::replaceTemplateToPhp($arMailingChain['MESSAGE']),
			);
			static::$currentMailingChainFields['SITE'] = array($arMailingChain['SITE_ID']);
			static::$currentMailingChainFields['CHARSET'] = $charset;
		}


		$arMessageParams = array(
			'FIELDS' => $arParams['FIELDS'],
			'MESSAGE' => static::$currentMailingChainFields['MESSAGE'],
			'SITE' => static::$currentMailingChainFields['SITE'],
			'CHARSET' => static::$currentMailingChainFields['CHARSET'],
		);

		$message = Mail\EventMessageCompiler::createInstance($arMessageParams);
		$message->compile();

		// send mail
		$result = Mail\Mail::send(array(
			'TO' => $message->getMailTo(),
			'SUBJECT' => $message->getMailSubject(),
			'BODY' => $message->getMailBody(),
			'HEADER' => $message->getMailHeaders(),
			'CHARSET' => $message->getMailCharset(),
			'CONTENT_TYPE' => $message->getMailContentType(),
			'MESSAGE_ID' => '',
			'ATTACHMENT' => $message->getMailAttachment(),
			'TRACK_READ' => (isset($arParams['TRACK_READ']) ? $arParams['TRACK_READ'] : null),
			'TRACK_CLICK' => (isset($arParams['TRACK_CLICK']) ? $arParams['TRACK_CLICK'] : null)
		));

		if($result)
			return PostingRecipientTable::SEND_RESULT_SUCCESS;
		else
			return PostingRecipientTable::SEND_RESULT_ERROR;
	}

	/**
	 * @param $mailingChainId
	 * @param $address
	 * @return bool
	 * @throws \Bitrix\Main\DB\Exception
	 */
	public static function sendToAddress($mailingChainId, $address)
	{
		$recipientEmail = $address;
		$arEmailParts = explode('@', $recipientEmail);
		$recipientName = $arEmailParts[0];

		$mailingChain = MailingChainTable::getRowById(array('ID' => $mailingChainId));
		$arParams = array(
			'FIELDS' => array(
				'NAME' => $recipientName,
				'EMAIL_TO' => $address,
				'USER_ID' => '',
				'UNSUBSCRIBE_LINK' => Subscription::getLinkUnsub(array(
					'MAILING_ID' => !empty($mailingChain) ? $mailingChain['MAILING_ID'] : 0,
					'EMAIL' => $address,
					'TEST' => 'Y'
				)),
			)
		);

		$mailSendResult = static::sendInternal($mailingChainId, $arParams);


		switch($mailSendResult)
		{
			case PostingRecipientTable::SEND_RESULT_SUCCESS:
				$mailResult = static::SEND_RESULT_SENT;
				break;

			case PostingRecipientTable::SEND_RESULT_ERROR:
			default:
				$mailResult = static::SEND_RESULT_ERROR;
		}

		return $mailResult;
	}

	/**
	 * @param $id
	 * @param int $timeout
	 * @param int $maxMailCount
	 * @return bool|string
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\DB\Exception
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 * @throws \Exception
	 */
	public static function send($id, $timeout=0, $maxMailCount=0)
	{
		$start_time = getmicrotime();
		@set_time_limit(0);

		static::$emailSentPerIteration = 0;

		$postingDb = PostingTable::getList(array(
			'select' => array(
				'ID',
				'STATUS',
				'MAILING_ID',
				'TRACK_CLICK' => 'MAILING.TRACK_CLICK',
				'MAILING_CHAIN_ID',
				'MAILING_CHAIN_REITERATE' => 'MAILING_CHAIN.REITERATE',
			),
			'filter' => array(
				'ID' => $id,
				'MAILING.ACTIVE' => 'Y',
				'MAILING_CHAIN.STATUS' => MailingChainTable::STATUS_SEND,
			)
		));
		$arPosting = $postingDb->fetch();

		// if posting in new status, then import recipients from groups and set right status for sending
		if($arPosting && $arPosting["STATUS"] == PostingTable::STATUS_NEW)
		{
			PostingTable::initGroupRecipients($arPosting['ID']);
			PostingTable::update(array('ID' => $arPosting['ID']), array('STATUS' => PostingTable::STATUS_PART));
			$arPosting["STATUS"] = PostingTable::STATUS_PART;
		}

		// posting not found or not in right status
		if(!$arPosting || $arPosting["STATUS"] != PostingTable::STATUS_PART)
		{
			return static::SEND_RESULT_ERROR;
		}

		// lock posting for exclude double parallel sending
		if(static::lockPosting($id) === false)
		{
			throw new \Bitrix\Main\DB\Exception(Loc::getMessage('SENDER_POSTING_MANAGER_ERR_LOCK'));
		}


		// select all recipients of posting, only not processed
		$postingEmailDb = PostingRecipientTable::getList(array(
			'filter' => array(
				'POSTING_ID' => $arPosting['ID'],
				'STATUS' => PostingRecipientTable::SEND_RESULT_NONE
			),
			'limit' => $maxMailCount
		));

		while($arEmail = $postingEmailDb->fetch())
		{
			// create name from email
			$recipientEmail = $arEmail["EMAIL"];
			if(empty($arEmail["NAME"]))
			{
				$arEmailParts = explode('@', $recipientEmail);
				$recipientName = $arEmailParts[0];
			}
			else
			{
				$recipientName = $arEmail["NAME"];
			}


			// prepare params for send
			$arParams = array(
				'FIELDS' => array(
					'EMAIL_TO' => $recipientEmail,
					'NAME' => $recipientName,
					'USER_ID' => $arEmail["USER_ID"],
					'UNSUBSCRIBE_LINK' => Subscription::getLinkUnsub(array(
						'MAILING_ID' => $arPosting['MAILING_ID'],
						'EMAIL' => $recipientEmail,
						'RECIPIENT_ID' => $arEmail["ID"]
					)),
				),
				'TRACK_READ' => array(
					'MODULE_ID' => "sender",
					'FIELDS' => array('RECIPIENT_ID' => $arEmail["ID"])
				)
			);
			if($arPosting['TRACK_CLICK'] == 'Y')
			{
				$arParams['TRACK_CLICK'] = array(
					'MODULE_ID' => "sender",
					'FIELDS' => array('RECIPIENT_ID' => $arEmail["ID"])
				);
			}

			// set sending result to recipient
			$mailSendResult = static::sendInternal($arPosting['MAILING_CHAIN_ID'], $arParams);
			PostingRecipientTable::update(array('ID' => $arEmail["ID"]), array('STATUS' => $mailSendResult, 'DATE_SENT' => new Type\DateTime()));

			// limit executing script by time
			if($timeout > 0 && getmicrotime()-$start_time >= $timeout)
				break;

			// increment sending statistic
			static::$emailSentPerIteration++;
		}

		//set status and delivered and error emails
		$arStatuses = PostingTable::getRecipientCountByStatus($id);
		if(!array_key_exists(PostingRecipientTable::SEND_RESULT_NONE, $arStatuses))
		{
			if(array_key_exists(PostingRecipientTable::SEND_RESULT_ERROR, $arStatuses))
				$STATUS = PostingTable::STATUS_SENT_WITH_ERRORS;
			else
				$STATUS = PostingTable::STATUS_SENT;

			$DATE = new Type\DateTime();
		}
		else
		{
			$STATUS = PostingTable::STATUS_PART;
			$DATE = null;
		}


		// unlock posting for exclude double parallel sending
		static::unlockPosting($id);


		// update status of posting
		PostingTable::update(array('ID' => $id), array('STATUS' => $STATUS, 'DATE_SENT' => $DATE));

		// return status to continue or end of sending
		if($STATUS == PostingTable::STATUS_PART)
			return static::SEND_RESULT_CONTINUE;
		else
			return static::SEND_RESULT_SENT;
	}

	/**
	 * @param $id
	 * @return bool
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 * @throws \Exception
	 */
	public static function lockPosting($id)
	{
		$id = intval($id);

		$uniq = \COption::GetOptionString("main", "server_uniq_id", "");
		if($uniq == '')
		{
			$uniq = md5(uniqid(rand(), true));
			\COption::SetOptionString("main", "server_uniq_id", $uniq);
		}

		$connection = \Bitrix\Main\Application::getInstance()->getConnection();
		if($connection instanceof \Bitrix\Main\DB\MysqlCommonConnection)
		{
			$db_lock = $connection->query("SELECT GET_LOCK('".$uniq."_sendpost_".$id."', 0) as L", false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$ar_lock = $db_lock->fetch();
			if($ar_lock["L"]=="1")
				return true;
			else
				return false;
		}
		elseif($connection instanceof \Bitrix\Main\DB\MssqlConnection)
		{
			//Clean up locks
			$i=\COption::GetOptionInt("sender", "posting_interval");
			//For at least 5 seconds
			if($i<5) $i=5;
			$connection->query("DELETE FROM B_SENDER_POSTING_LOCK WHERE DATEDIFF(SECOND, TIMESTAMP_X, GETDATE())>".$i);
			$connection->query("SET LOCK_TIMEOUT 1");
			$db_lock = $connection->query("INSERT INTO B_SENDER_POSTING_LOCK (ID, TIMESTAMP_X) VALUES (".$id.", GETDATE())");
			$connection->query("SET LOCK_TIMEOUT -1");
			return $db_lock->getResource()!==false;
		}
		elseif($connection instanceof \Bitrix\Main\DB\OracleConnection)
		{
			try
			{
				$db_lock = $connection->query("
					declare
						my_lock_id number;
						my_result number;
						lock_failed exception;
						pragma exception_init(lock_failed, -54);
					begin
						my_lock_id:=dbms_utility.get_hash_value(to_char('" . $uniq . "_sendpost_" . $id . "'), 0, 1024);
						my_result:=dbms_lock.request(my_lock_id, dbms_lock.x_mode, 0, true);
						--  Return value:
						--    0 - success
						--    1 - timeout
						--    2 - deadlock
						--    3 - parameter error
						--    4 - already own lock specified by 'id' or 'lockhandle'
						--    5 - illegal lockhandle
						if(my_result<>0 and my_result<>4)then
							raise lock_failed;
						end if;
					end;
				");
			}
			catch(\Bitrix\Main\Db\SqlQueryException $exception)
			{
				if(strpos($exception->getDatabaseMessage(), "ORA-00054") === false)
					throw $exception;
			}

			return $db_lock->getResource()!==false;
		}

		return false;
	}

	/**
	 * @param $id
	 * @return bool
	 */
	public static function unlockPosting($id)
	{
		$id = intval($id);

		$connection = \Bitrix\Main\Application::getInstance()->getConnection();
		if($connection instanceof \Bitrix\Main\DB\MysqlCommonConnection)
		{
			$uniq = \COption::GetOptionString("main", "server_uniq_id", "");
			if(strlen($uniq)>0)
			{
				$db_lock = $connection->query("SELECT RELEASE_LOCK('".$uniq."_sendpost_".$id."') as L");
				$ar_lock = $db_lock->fetch();
				if($ar_lock["L"]=="0")
					return false;
				else
					return true;
			}
		}
		elseif($connection instanceof \Bitrix\Main\DB\MssqlConnection)
		{
			$connection->query("DELETE FROM B_SENDER_POSTING_LOCK WHERE ID=".$id);
			return true;
		}
		elseif($connection instanceof \Bitrix\Main\DB\OracleConnection)
		{
			//lock released on commit
			return true;
		}

		return false;
	}
}