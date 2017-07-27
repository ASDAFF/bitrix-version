<?php
namespace Bitrix\Catalog;

use Bitrix\Main;
use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale;

Loc::loadMessages(__FILE__);

/**
 * Class DiscountCouponTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> DISCOUNT_ID int mandatory
 * <li> ACTIVE bool optional default 'Y'
 * <li> COUPON string(32) mandatory
 * <li> DATE_APPLY datetime optional
 * <li> ONE_TIME bool optional default 'Y'
 * <li> TIMESTAMP_X datetime optional
 * <li> MODIFIED_BY int optional
 * <li> DATE_CREATE datetime optional
 * <li> CREATED_BY int optional
 * <li> DESCRIPTION string optional
 * </ul>
 *
 * @package Bitrix\Catalog
 **/

class DiscountCouponTable extends Main\Entity\DataManager
{
	const TYPE_ONE_ROW = 'Y';
	const TYPE_ONE_ORDER = 'O';
	const TYPE_NO_LIMIT = 'N';

	protected static $existCouponsManager = null;
	protected static $types = array();

	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_catalog_discount_coupon';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'ID' => new Main\Entity\IntegerField('ID', array(
				'primary' => true,
				'autocomplete' => true,
				'title' => Loc::getMessage('DISCOUNT_COUPON_ENTITY_ID_FIELD')
			)),
			'DISCOUNT_ID' => new Main\Entity\IntegerField('DISCOUNT_ID', array(
				'required' => true,
				'title' => Loc::getMessage('DISCOUNT_COUPON_ENTITY_DISCOUNT_ID_FIELD')
			)),
			'ACTIVE' => new Main\Entity\BooleanField('ACTIVE', array(
				'values' => array('N', 'Y'),
				'default_value' => 'Y',
				'title' => Loc::getMessage('DISCOUNT_COUPON_ENTITY_ACTIVE_FIELD')
			)),
			'COUPON' => new Main\Entity\StringField('COUPON', array(
				'required' => true,
				'unique' => true,
				'validation' => array(__CLASS__, 'validateCoupon'),
				'title' => Loc::getMessage('DISCOUNT_COUPON_ENTITY_COUPON_FIELD')
			)),
			'DATE_APPLY' => new Main\Entity\DatetimeField('DATE_APPLY', array(
				'default_value' => null,
				'title' => Loc::getMessage('DISCOUNT_COUPON_ENTITY_DATE_APPLY_FIELD')
			)),
			'TYPE' => new Main\Entity\EnumField('TYPE', array(
				'column_name' => 'ONE_TIME',
				'values' => array(self::TYPE_ONE_ROW, self::TYPE_ONE_ORDER, self::TYPE_NO_LIMIT),
				'default_value' => self::TYPE_ONE_ROW,
				'title' => Loc::getMessage('DISCOUNT_COUPON_ENTITY_ONE_TIME_FIELD')
			)),
			'TIMESTAMP_X' => new Main\Entity\DatetimeField('TIMESTAMP_X', array(
				'required' => true,
				'default_value' => new Main\Type\DateTime(),
				'title' => Loc::getMessage('DISCOUNT_COUPON_ENTITY_TIMESTAMP_X_FIELD')
			)),
			'MODIFIED_BY' => new Main\Entity\IntegerField('MODIFIED_BY', array(
				'default_value' => null,
				'title' => Loc::getMessage('DISCOUNT_COUPON_ENTITY_MODIFIED_BY_FIELD')
			)),
			'DATE_CREATE' => new Main\Entity\DatetimeField('DATE_CREATE', array(
				'default_value' => new Main\Type\DateTime(),
				'title' => Loc::getMessage('DISCOUNT_COUPON_ENTITY_DATE_CREATE_FIELD')
			)),
			'CREATED_BY' => new Main\Entity\IntegerField('CREATED_BY', array(
				'default_value' => null,
				'title' => Loc::getMessage('DISCOUNT_COUPON_ENTITY_CREATED_BY_FIELD')
			)),
			'DESCRIPTION' => new Main\Entity\TextField('DESCRIPTION', array(
				'default_value' => null,
				'title' => Loc::getMessage('DISCOUNT_COUPON_ENTITY_DESCRIPTION_FIELD')
			)),
			'CREATED_BY_USER' => new Main\Entity\ReferenceField(
				'CREATED_BY_USER',
				'Bitrix\Main\User',
				array('=this.CREATED_BY' => 'ref.ID')
			),
			'MODIFIED_BY_USER' => new Main\Entity\ReferenceField(
				'MODIFIED_BY_USER',
				'Bitrix\Main\User',
				array('=this.MODIFIED_BY' => 'ref.ID')
			),
			'DISCOUNT' => new Main\Entity\ReferenceField(
				'DISCOUNT',
				'Bitrix\Catalog\Discount',
				array('=this.DISCOUNT_ID' => 'ref.ID')
			)
		);
	}

	/**
	 * Returns validators for COUPON field.
	 *
	 * @return array
	 */
	public static function validateCoupon()
	{
		return array(
			new Main\Entity\Validator\Length(null, 32),
			array(__CLASS__, 'checkCoupon')
		);
	}

	/**
	 * Validate coupon.
	 *
	 * @param int $value					Coupon.
	 * @param array|int $primary			Primary key.
	 * @param array $row					Current data.
	 * @param Main\Entity\Field $field		Field object.
	 * @return bool|string
	 */
	public static function checkCoupon($value, $primary, array $row, Main\Entity\Field $field)
	{
		$value = trim((string)$value);
		if ($value == '')
		{
			return Loc::getMessage('DISCOUNT_COUPON_VALIDATOR_COUPON_EMPTY');
		}
		$existCoupon = Sale\DiscountCouponsManager::isExist($value);
		if (!empty($existCoupon))
		{
			$currentId = (int)(is_array($primary) ? $primary['ID'] : $primary);
			if ($existCoupon['MODULE'] != 'catalog' || $currentId != $existCoupon['ID'])
				return Loc::getMessage('DISCOUNT_COUPON_VALIDATOR_COUPON_EXIST');
		}
		return true;
	}

	/**
	 * Return methods for coupons manager.
	 *
	 * @param Main\Event $event			Event from coupons manager.
	 * @return Main\EventResult
	 */
	public static function couponManager(Main\Event $event)
	{
		self::$existCouponsManager = true;
		self::$types = array(
			self::TYPE_ONE_ROW => Sale\Internals\DiscountCouponTable::TYPE_BASKET_ROW,
			self::TYPE_ONE_ORDER => Sale\Internals\DiscountCouponTable::TYPE_ONE_ORDER,
			self::TYPE_NO_LIMIT => Sale\Internals\DiscountCouponTable::TYPE_MULTI_ORDER
		);
		$result = new Main\EventResult(
			Main\EventResult::SUCCESS,
			array(
				'mode' => Sale\DiscountCouponsManager::COUPON_MODE_SIMPLE,
				'getData' => array('\Bitrix\Catalog\DiscountCouponTable', 'getData'),
				'isExist' => array('\Bitrix\Catalog\DiscountCouponTable', 'isExist'),
				'saveApplied' => array('\Bitrix\Catalog\DiscountCouponTable', 'saveApplied'),
			),
			'catalog'
		);
		return $result;
	}

	/**
	 * Return coupon description.
	 *
	 * @param string $coupon			Coupon for search.
	 * @return array|false
	 */
	public static function getData($coupon)
	{
		$couponIterator = self::getList(array(
			'select' => array(
				'ID', 'COUPON', 'DISCOUNT_ID', 'TYPE', 'ACTIVE',
				'DISCOUNT_NAME' => 'DISCOUNT.NAME', 'DISCOUNT_ACTIVE' => 'DISCOUNT.ACTIVE',
				'DISCOUNT_ACTIVE_FROM' => 'DISCOUNT.ACTIVE_FROM', 'DISCOUNT_ACTIVE_TO' => 'DISCOUNT.ACTIVE_TO'
			),
			'filter' => array('=COUPON' => $coupon)
		));
		if ($existCoupon = $couponIterator->fetch())
		{
			$existCoupon['TYPE'] = (
				isset(self::$types[$existCoupon['TYPE']])
				? self::$types[$existCoupon['TYPE']]
				: Sale\Internals\DiscountCouponTable::TYPE_UNKNOWN
			);
			return $existCoupon;
		}
		return false;
	}

	/**
	 * Check existing coupon.
	 *
	 * @param string $coupon			Coupon for checking.
	 * @return array|false
	 */
	public static function isExist($coupon)
	{
		$couponIterator = self::getList(array(
			'select' => array('ID', 'COUPON'),
			'filter' => array('=COUPON' => $coupon)
		));
		if ($existCoupon = $couponIterator->fetch())
		{
			return array(
				'ID' => $existCoupon['ID'],
				'COUPON' => $existCoupon['COUPON'],
				'MODULE' => 'catalog'
			);
		}
		return false;
	}

	/**
	 * Save apllied coupons.
	 *
	 * @param array $coupons					Coupons list.
	 * @param int $userId						Order user id.
	 * @param Main\Type\DateTime $currentTime		Apply time.
	 * @return array|bool
	 */
	public static function saveApplied($coupons, $userId, Main\Type\DateTime $currentTime)
	{
		$currentTimestamp = $currentTime->getTimestamp();
		if ($userId === null || (int)$userId == 0)
			return false;
		if (!is_array($coupons))
			$coupons = array($coupons);
		if (empty($coupons))
			return false;
		Main\Type\Collection::normalizeArrayValuesByInt($coupons);
		if (empty($coupons))
			return false;

		$deactivateCoupons = array();
		$multiCoupons = array();
		$couponIterator = self::getList(array(
			'select' => array(
				'ID', 'COUPON', 'DISCOUNT_ID', 'TYPE', 'ACTIVE',
				'DISCOUNT_ACTIVE' => 'DISCOUNT.ACTIVE',
				'DISCOUNT_ACTIVE_FROM' => 'DISCOUNT.ACTIVE_FROM', 'DISCOUNT_ACTIVE_TO' => 'DISCOUNT.ACTIVE_TO'
			),
			'filter' => array('@ID' => $coupons, '=ACTIVE' => 'Y'),
			'order' => array('ID' => 'ASC')
		));
		while ($existCoupon = $couponIterator->fetch())
		{
			if ($existCoupon['DISCOUNT_ACTIVE'] != 'Y')
				continue;
			if (
				($existCoupon['DISCOUNT_ACTIVE_FROM'] instanceof Main\Type\DateTime && $existCoupon['DISCOUNT_ACTIVE_FROM']->getTimestamp() > $currentTimestamp)
				||
				($existCoupon['DISCOUNT_ACTIVE_TO'] instanceof Main\Type\DateTime && $existCoupon['DISCOUNT_ACTIVE_TO']->getTimestamp() < $currentTimestamp)
			)
				continue;

			if (
				$existCoupon['TYPE'] == self::TYPE_ONE_ROW
				|| $existCoupon['TYPE'] == self::TYPE_ONE_ORDER
			)
			{
				$deactivateCoupons[$existCoupon['COUPON']] = $existCoupon['ID'];
			}
			else
			{
				$multiCoupons[$existCoupon['COUPON']] = $existCoupon['ID'];
			}
		}
		unset($existCoupon, $couponIterator, $coupons);
		if (!empty($deactivateCoupons) || !empty($multiCoupons))
		{
			$conn = Application::getConnection();
			$helper = $conn->getSqlHelper();
			$tableName = $helper->quote(self::getTableName());
			if (!empty($deactivateCoupons))
			{
				$conn->queryExecute(
					'update '.$tableName.' set '.$helper->quote('ACTIVE').' = \'N\', '.$helper->quote('DATE_APPLY').' = '.$helper->getCurrentDateTimeFunction().
					' where '.$helper->quote('ID').' in ('.implode(',', $deactivateCoupons).')'
				);
			}
			if (!empty($multiCoupons))
			{
				$conn->queryExecute(
					'update '.$tableName.' set '.$helper->quote('DATE_APPLY').' = '.$helper->getCurrentDateTimeFunction().
					' where '.$helper->quote('ID').' in ('.implode(',', $multiCoupons).')'
				);
			}
			unset($tableName, $helper);
		}
		return array(
			'DEACTIVATE' => $deactivateCoupons,
			'INCREMENT' => $multiCoupons
		);
	}

	/**
	 * Returns coupon types list.
	 *
	 * @param bool $extendedMode			Get type ids or ids with title.
	 * @return array
	 */
	public static function getCouponTypes($extendedMode = false)
	{
		$extendedMode = ($extendedMode === true);
		if ($extendedMode)
		{
			return array(
				self::TYPE_ONE_ROW => Loc::getMessage('DISCOUNT_COUPON_TABLE_TYPE_ONE_ROW'),
				self::TYPE_ONE_ORDER => Loc::getMessage('DISCOUNT_COUPON_TABLE_TYPE_ONE_ORDER'),
				self::TYPE_NO_LIMIT => Loc::getMessage('DISCOUNT_COUPON_TABLE_TYPE_NO_LIMIT')
			);
		}
		return array(self::TYPE_ONE_ROW, self::TYPE_ONE_ORDER, self::TYPE_NO_LIMIT);
	}

	/**
	 * Init use mode.
	 *
	 * @return void
	 */
	protected static function initUseMode()
	{
		if (self::$existCouponsManager === null)
			self::$existCouponsManager = Main\ModuleManager::isModuleInstalled('sale') && Main\Loader::includeModule('sale');
	}
}