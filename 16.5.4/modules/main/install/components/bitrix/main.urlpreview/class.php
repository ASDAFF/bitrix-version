<?php

use Bitrix\Main;
use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UrlPreview\UrlMetadataTable;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

Loc::loadMessages(__FILE__);

class UrlPreviewComponent extends \CBitrixComponent
{
	protected $editMode = false;
	protected $checkAccess = false;
	protected $multiple = false;
	protected $metadataId;
	protected $mobileApp = false;
	protected $showEmbed = false;

	protected function prepareParams()
	{
		$this->editMode = ($this->arParams['EDIT'] === 'Y');
		$this->mobileApp = ($this->arParams['PARAMS']['MOBILE'] === 'Y');
		$this->showEmbed = !$this->mobileApp;

		if($this->mobileApp)
			$this->setTemplateName('mobile');
		else
			$this->setTemplateName('.default');

		return $this;
	}

	/**
	 * Sets component arResult array
	 */
	protected function prepareData()
	{
		$signer = new Main\Security\Sign\Signer();
		$this->arResult['METADATA'] = $this->arParams['METADATA'];
		$this->setDynamicPreview();

		$this->arResult['FIELD_NAME'] = $this->arParams['PARAMS']['arUserField']['FIELD_NAME'];
		if($this->arResult['METADATA']['ID'] > 0)
			$this->arResult['FIELD_VALUE'] = $signer->sign($this->arResult['METADATA']['ID'], Main\UrlPreview\UrlPreview::SIGN_SALT);
		else
			$this->arResult['FIELD_VALUE'] = null;

		$this->arResult['FIELD_ID'] = $this->arParams['PARAMS']['arUserField']['ID'];
		$this->arResult['ELEMENT_ID'] = $this->arParams['PARAMS']['urlPreviewId'];

		if(isset($this->arParams['~METADATA']['EMBED']) && $this->arParams['~METADATA']['EMBED'] != '' && $this->showEmbed)
			$this->arResult['METADATA']['EMBED'] = $this->arParams['~METADATA']['EMBED'];
		else
			$this->arResult['METADATA']['EMBED'] = null;

		$this->arResult['SELECT_IMAGE'] = (
				$this->editMode
				&& empty($this->arResult['METADATA']['EMBED'])
				&& is_array($this->arResult['METADATA']['EXTRA'])
				&& is_array($this->arResult['METADATA']['EXTRA']['IMAGES'])
		);

		if($this->arResult['SELECT_IMAGE'])
		{
			$this->arResult['SELECTED_IMAGE'] = $this->arResult['METADATA']['EXTRA']['SELECTED_IMAGE'] ?: 0;
		}
		else
		{
			$this->arResult['METADATA']['CONTAINER']['CLASSES'] = "";

			if ($this->arResult['METADATA']['IMAGE_ID'] > 0
					&& $imageFile = \CFile::GetFileArray($this->arResult['METADATA']['IMAGE_ID']))
			{
				$this->arResult['METADATA']['IMAGE'] = $imageFile['SRC'];
				if($imageFile['HEIGHT'] > $imageFile['WIDTH'] * 1.5)
				{
					$this->arResult['METADATA']['CONTAINER']['CLASSES'] .= " urlpreview__container-left";
				}
			}
			$this->arResult['SHOW_CONTAINER'] = isset($this->arResult['METADATA']['IMAGE']) && $this->arResult['METADATA']['IMAGE'] != ''
					|| isset($this->arResult['METADATA']['EMBED']) && $this->arResult['METADATA']['EMBED'] != '';

			if( isset($this->arResult['METADATA']['IMAGE'])
					&& $this->arResult['METADATA']['IMAGE'] != ''
					&& isset($this->arResult['METADATA']['EMBED'])
					&& $this->arResult['METADATA']['EMBED'] != ''
			)
			{
				$this->arResult['METADATA']['CONTAINER']['CLASSES'] .= " urlpreview__container-switchable";
				$this->arResult['METADATA']['CONTAINER']['CLASSES'] .= " urlpreview__container-hide-embed";
			}
		}
	}

	/**
	 * Sets main element style
	 */
	protected function prepareStyle()
	{
		$this->arResult['STYLE'] = '';
		if(!isset($this->arResult['METADATA']['ID']))
		{
			$this->arResult['STYLE'] .= "display:none; ";
		}
		if(isset($this->arParams['PARAMS']['STYLE']))
		{
			$this->arResult['STYLE'] .= $this->arParams['PARAMS']['STYLE']."; ";
		}
	}

	protected function setDynamicPreview()
	{
		if ($this->arParams['METADATA']['TYPE'] == UrlMetadataTable::TYPE_DYNAMIC)
		{
			if (is_array($this->arParams['METADATA']['HANDLER']))
			{
				$module = $this->arParams['METADATA']['HANDLER']['MODULE'];
				$className = $this->arParams['METADATA']['HANDLER']['CLASS'];
				$buildMethod = $this->arParams['METADATA']['HANDLER']['BUILD_METHOD'];
				$parameters = $this->arParams['METADATA']['HANDLER']['PARAMETERS'];
				if (Loader::includeModule($module) && method_exists($className, $buildMethod))
				{
					$this->arResult['DYNAMIC_PREVIEW'] = $className::$buildMethod($parameters);
				}
			} else
			{
				$this->arResult['METADATA']['ID'] = null;
			}
		}
	}

	/**
	 * Executes component
	 */
	public function executeComponent()
	{
		$this->prepareParams();
		if(
			!isset($this->arParams['METADATA']['ID'])
			|| $this->arParams['METADATA']['TYPE'] == UrlMetadataTable::TYPE_STATIC
			|| (
					$this->arParams['METADATA']['TYPE'] == UrlMetadataTable::TYPE_DYNAMIC
					&& !$this->mobileApp
				)
		  )
		{
			$this->prepareData();
			$this->prepareStyle();
			if($this->arParams['METADATA']['TYPE'] == UrlMetadataTable::TYPE_DYNAMIC && $this->arResult['DYNAMIC_PREVIEW'] == '')
				return;

			$this->includeComponentTemplate($this->editMode ? 'edit' : 'show');
		}
	}
}