<?php
namespace Bitrix\Currency;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class CurrencyRateTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> CURRENCY string(3) mandatory
 * <li> DATE_RATE date mandatory
 * <li> RATE_CNT int optional default 1
 * <li> RATE float mandatory default 0.0000
 * </ul>
 *
 * @package Bitrix\Currency
 **/

class CurrencyRateTable extends Entity\DataManager
{
	/**
	 * Returns path to the file which contains definition of the class.
	 *
	 * @return string
	 */
	public static function getFilePath()
	{
		return __FILE__;
	}

	/**
	 * Returns DB table name for entity
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_catalog_currency_rate';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
				'title' => Loc::getMessage('CURRENCY_RATE_ENTITY_ID_FIELD'),
			),
			'CURRENCY' => array(
				'data_type' => 'string',
				'primary' => true,
				'validation' => array(__CLASS__, 'validateCurrency'),
				'title' => Loc::getMessage('CURRENCY_RATE_ENTITY_CURRENCY_FIELD'),
			),
			'DATE_RATE' => array(
				'data_type' => 'date',
				'primary' => true,
				'title' => Loc::getMessage('CURRENCY_RATE_ENTITY_DATE_RATE_FIELD'),
			),
			'RATE_CNT' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('CURRENCY_RATE_ENTITY_RATE_CNT_FIELD'),
			),
			'RATE' => array(
				'data_type' => 'float',
				'required' => true,
				'title' => Loc::getMessage('CURRENCY_RATE_ENTITY_RATE_FIELD'),
			),
		);
	}

	/**
	 * Returns validators for CURRENCY field.
	 *
	 * @return array
	 */
	public static function validateCurrency()
	{
		return array(
			new Entity\Validator\Length(null, 3),
		);
	}
}