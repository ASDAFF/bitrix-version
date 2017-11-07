<?php
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\SystemException;
use Bitrix\Main\Loader;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

/**
 * Class ProductSubscribe
 *
 * It provides an interface to work on a subscription product.
 */
class ProductSubscribe extends \CBitrixComponent
{
	/**
	 * Errors list.
	 * @var string[]
	 */
	protected $errors = array();

	/**
	 * Event called from includeComponent before component execution.
	 */
	public function onIncludeComponentLang()
	{
		Loc::loadMessages(__FILE__);
	}

	/**
	 * Event called from includeComponent before component execution.
	 * Takes component parameters as argument and should return it formatted as needed.
	 *
	 * @param $params
	 * @return mixed
	 */
	public function onPrepareComponentParams($params)
	{
		$params['PRODUCT_ID'] = isset($params['PRODUCT_ID']) ? (int)$params['PRODUCT_ID'] : 0;
		$params['USE_CAPTCHA'] = isset($params['USE_CAPTCHA']) ? (string)$params['USE_CAPTCHA'] : 'N';
		$params['BUTTON_ID'] = isset($params['BUTTON_ID']) ? (string)$params['BUTTON_ID'] : '';
		$params['BUTTON_CLASS'] = isset($params['BUTTON_CLASS']) ? (string)$params['BUTTON_CLASS'] : '';
		$params['DEFAULT_DISPLAY'] = isset($params['DEFAULT_DISPLAY']) ? (bool)$params['DEFAULT_DISPLAY'] : true;

		if(!$params['PRODUCT_ID'])
			$this->errors[] = Loc::getMessage('CPS_REQUIRED_PARAMETER', array('#PARAM#' => 'PRODUCT_ID'));
		if(!$params['BUTTON_ID'])
			$this->errors[] = Loc::getMessage('CPS_REQUIRED_PARAMETER', array('#PARAM#' => 'BUTTON_ID'));

		return $params;
	}

	/**
	 * Check Required Modules
	 * @throws Exception
	 */
	protected function checkModules()
	{
		if (!Loader::includeModule('catalog'))
			throw new SystemException(Loc::getMessage('CPS_MODULE_NOT_INSTALLED', array('#NAME#' => 'catalog')));
	}

	/**
	 * Prepare data to render.
	 * @throws SystemException
	 */
	protected function formatResult()
	{
		if($this->errors)
			throw new SystemException(current($this->errors));

		$this->arResult['PRODUCT_ID'] = $this->arParams['PRODUCT_ID'];
		$this->arResult['USE_CAPTCHA'] = $this->arParams['USE_CAPTCHA'];
		$this->arResult['BUTTON_CLASS'] = $this->arParams['BUTTON_CLASS'];
		$this->arResult['BUTTON_ID'] = $this->arParams['BUTTON_ID'];
		$this->arResult['DEFAULT_DISPLAY'] = $this->arParams['DEFAULT_DISPLAY'];

		if ($this->arResult['USE_CAPTCHA'] == 'Y')
		{
			$_SESSION['SUBSCRIBE_PRODUCT']['useCaptcha'] = 'Y';
		}
		else
		{
			$_SESSION['SUBSCRIBE_PRODUCT']['useCaptcha'] = 'N';
		}

		$this->arResult['ALREADY_SUBSCRIBED'] = false;
		if(!empty($_SESSION['SUBSCRIBE_PRODUCT']['LIST_PRODUCT_ID']))
		{
			if(array_key_exists($this->arParams['PRODUCT_ID'], $_SESSION['SUBSCRIBE_PRODUCT']['LIST_PRODUCT_ID']))
			{
				$this->arResult['ALREADY_SUBSCRIBED'] = true;
			}
		}
	}

	/**
	 * Function calls __includeComponent in order to execute the component.
	 */
	public function executeComponent()
	{
		try
		{
			$this->checkModules();
			$this->formatResult();
			$this->includeComponentTemplate();
		}
		catch (SystemException $e)
		{
			ShowError($e->getMessage());
		}
	}
}