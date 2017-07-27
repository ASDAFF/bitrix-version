<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Main;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class UserTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_user';
	}

	public static function getUfId()
	{
		return 'USER';
	}

	public static function getMap()
	{
		$connection = Application::getConnection();
		$helper = $connection->getSqlHelper();

		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'LOGIN' => array(
				'data_type' => 'string'
			),
			'PASSWORD' => array(
				'data_type' => 'string'
			),
			'EMAIL' => array(
				'data_type' => 'string'
			),
			'ACTIVE' => array(
				'data_type' => 'boolean',
				'values' => array('N','Y')
			),
			'DATE_REGISTER' => array(
				'data_type' => 'datetime'
			),
			'DATE_REG_SHORT' => array(
				'data_type' => 'datetime',
				'expression' => array(
					$helper->getDatetimeToDateFunction('%s'), 'DATE_REGISTER'
				)
			),
			'LAST_LOGIN' => array(
				'data_type' => 'datetime'
			),
			'LAST_LOGIN_SHORT' => array(
				'data_type' => 'datetime',
				'expression' => array(
					$helper->getDatetimeToDateFunction('%s'), 'LAST_LOGIN'
				)
			),
			'LAST_ACTIVITY_DATE' => array(
				'data_type' => 'datetime'
			),
			'NAME' => array(
				'data_type' => 'string'
			),
			'PERSONAL_PHONE' => array(
				'data_type' => 'string'
			),
			'PERSONAL_MOBILE' => array(
				'data_type' => 'string'
			),
			'PERSONAL_WWW' => array(
				'data_type' => 'string'
			),
			'PERSONAL_ICQ' => array(
				'data_type' => 'string'
			),
			'PERSONAL_FAX' => array(
				'data_type' => 'string'
			),
			'PERSONAL_PAGER' => array(
				'data_type' => 'string'
			),
			'PERSONAL_STREET' => array(
				'data_type' => 'string'
			),
			'PERSONAL_CITY' => array(
				'data_type' => 'string'
			),
			'PERSONAL_STATE' => array(
				'data_type' => 'string'
			),
			'PERSONAL_ZIP' => array(
				'data_type' => 'string'
			),
			'PERSONAL_COUNTRY' => array(
				'data_type' => 'string'
			),
			'WORK_COMPANY' => array(
				'data_type' => 'string'
			),
			'PERSONAL_PROFESSION' => array(
				'data_type' => 'string'
			),
			'SECOND_NAME' => array(
				'data_type' => 'string'
			),
			'LAST_NAME' => array(
				'data_type' => 'string'
			),
			'LID' => array(
				'data_type' => 'string'
			),
			'WORK_PHONE' => array(
				'data_type' => 'string'
			),
			'WORK_POSITION' => array(
				'data_type' => 'string'
			),
			'PERSONAL_BIRTHDAY' => array(
				'data_type' => 'date'
			),
			'PERSONAL_GENDER' => array(
				'data_type' => 'string'
			),
			'PERSONAL_PHOTO' => array(
				'data_type' => 'integer'
			),
			'SHORT_NAME' => array(
				'data_type' => 'string',
				'expression' => array(
					$helper->getConcatFunction("%s","' '", "UPPER(".$helper->getSubstrFunction("%s", 1, 1).")", "'.'"),
					'LAST_NAME', 'NAME'
				)
			),
			'IS_ONLINE' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'expression' => array(
					'CASE WHEN %s > '.$helper->addSecondsToDateTime('(-120)').' THEN \'Y\' ELSE \'N\' END',
					'LAST_ACTIVITY_DATE',
				)
			),
			'EXTERNAL_AUTH_ID' => array(
				'data_type' => 'string'
			),
			'XML_ID' => array(
				'data_type' => 'string'
			),
			'TITLE' => array(
				'data_type' => 'string'
			),
		);
	}

	public static function getActiveUsersCount()
	{
		if (ModuleManager::isModuleInstalled("intranet"))
		{
			$sql = "SELECT COUNT(U.ID) ".
				"FROM b_user U ".
				"WHERE U.ACTIVE = 'Y' ".
				"   AND U.LAST_LOGIN IS NOT NULL ".
				"   AND EXISTS(".
				"       SELECT 'x' ".
				"       FROM b_utm_user UF, b_user_field F ".
				"       WHERE F.ENTITY_ID = 'USER' ".
				"           AND F.FIELD_NAME = 'UF_DEPARTMENT' ".
				"           AND UF.FIELD_ID = F.ID ".
				"           AND UF.VALUE_ID = U.ID ".
				"           AND UF.VALUE_INT IS NOT NULL ".
				"           AND UF.VALUE_INT <> 0".
				"   )";
		}
		else
		{
			$sql = "SELECT COUNT(ID) ".
				"FROM b_user ".
				"WHERE ACTIVE = 'Y' ".
				"   AND LAST_LOGIN IS NOT NULL";
		}

		$connection = Application::getConnection();
		return $connection->queryScalar($sql);
	}

	public static function add(array $data)
	{
		throw new NotImplementedException("Use CUser class.");
	}

	public static function update($primary, array $data)
	{
		throw new NotImplementedException("Use CUser class.");
	}

	public static function delete($primary)
	{
		throw new NotImplementedException("Use CUser class.");
	}
}
