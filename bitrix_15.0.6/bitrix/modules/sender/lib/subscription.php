<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sender
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Sender;

use Bitrix\Subscribe;
use Bitrix\Main\EventResult;

class Subscription
{
	const MODULE_ID = 'sender';

	/**
	 * @param $arFields
	 * @return string
	 */
	public static function getLinkUnsub($arFields)
	{
		return \Bitrix\Main\Mail\Tracking::getLinkUnsub(static::MODULE_ID, $arFields);
	}

	/**
	 * @param $arData
	 * @return mixed
	 */
	public static function onMailEventSubscriptionList($arData)
	{
		$arData['LIST'] = static::getList($arData);

		return $arData;
	}

	/**
	 * @param $arData
	 * @return EventResult
	 */
	public static function onMailEventSubscriptionEnable($arData)
	{
		$arData['SUCCESS'] = static::subscribe($arData);
		if($arData['SUCCESS'])
			$result = EventResult::SUCCESS;
		else
			$result = EventResult::ERROR;

		return new EventResult($result, $arData, static::MODULE_ID);
	}

	/**
	 * @param $arData
	 * @return EventResult
	 */
	public static function onMailEventSubscriptionDisable($arData)
	{
		$arData['SUCCESS'] = static::unsubscribe($arData);
		if($arData['SUCCESS'])
			$result = EventResult::SUCCESS;
		else
			$result = EventResult::ERROR;

		return new EventResult($result, $arData, static::MODULE_ID);
	}

	/**
	 * @param $arData
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function getList($arData)
	{
		$arMailing = array();

		if(isset($arData['TEST']) && $arData['TEST'] == 'Y')
		{
			$mailing = MailingTable::getRowById(array('ID' => $arData['MAILING_ID']));
			if($mailing)
			{
				$arMailing[] = array(
					'ID' => $mailing['ID'],
					'NAME' => $mailing['NAME'],
					'SELECTED' => true,
				);
			}

			return $arMailing;
		}

		$mailingUnsub = array();
		$recipientUnsubDb = PostingUnsubTable::getList(array(
			'select' => array('MAILING_ID' => 'POSTING.MAILING_ID'),
			'filter' => array('=POSTING_RECIPIENT.EMAIL' => trim(strtolower($arData['EMAIL'])))
		));
		while($recipientUnsub = $recipientUnsubDb->fetch())
			$mailingUnsub[] = $recipientUnsub['MAILING_ID'];

		$mailingDb = PostingRecipientTable::getList(array(
			'select' => array(
				'MAILING_ID' => 'POSTING.MAILING.ID',
				'MAILING_NAME' => 'POSTING.MAILING.NAME',
			),
			'filter' => array(
				'=EMAIL' => trim(strtolower($arData['EMAIL'])),
				'POSTING.MAILING.ACTIVE' => 'Y',
			),
			'group' => array('MAILING_ID', 'MAILING_NAME')
		));
		while ($mailing = $mailingDb->fetch())
		{
			if(!in_array($mailing['MAILING_ID'], $mailingUnsub))
			{
				$arMailing[] = array(
					'ID' => $mailing['MAILING_ID'],
					'NAME' => $mailing['MAILING_NAME'],
					'SELECTED' => in_array($mailing['MAILING_ID'], array($arData['MAILING_ID'])),
				);
			}
		}

		return $arMailing;
	}

	/**
	 * @param $arData
	 * @return bool
	 */
	public static function subscribe($arData)
	{
		return false;
	}

	/**
	 * @param $arData
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function unsubscribe($arData)
	{
		$result = false;

		if(isset($arData['TEST']) && $arData['TEST'] == 'Y')
			return true;

		$postingDb = PostingRecipientTable::getList(array(
			'select' => array('POSTING_ID', 'POSTING_MAILING_ID' => 'POSTING.MAILING_ID'),
			'filter' => array('ID' => $arData['RECIPIENT_ID'], 'EMAIL' => $arData['EMAIL'])
		));
		$arPosting = $postingDb->fetch();

		$mailingDb = MailingTable::getList(array(
			'select' => array('ID'),
			'filter' => array(
				'ID' => $arData['UNSUBSCRIBE_LIST'],
			)
		));
		while($mailing = $mailingDb->fetch())
		{
			$unsub = null;

			if($arPosting && $arPosting['POSTING_MAILING_ID'] == $mailing['ID'])
			{
				$unsub = array(
					'POSTING_ID' => $arPosting['POSTING_ID'],
					'RECIPIENT_ID' => $arData['RECIPIENT_ID'],
				);
			}
			else
			{
				$mailingPostingDb = PostingRecipientTable::getList(array(
					'select' => array('RECIPIENT_ID' => 'ID', 'POSTING_ID'),
					'filter' => array('POSTING.MAILING_ID' => $mailing['ID'], 'EMAIL' => $arData['EMAIL'])
				));
				if($arMailingPosting = $mailingPostingDb->fetch())
				{
					$unsub = $arMailingPosting;
				}
			}

			if(!empty($unsub))
			{
				$unsubExists = PostingUnsubTable::getRowById($unsub);
				if(!$unsubExists)
				{
					PostingUnsubTable::add($unsub);
				}

				$result = true;
			}

		}

		return $result;
	}
}
