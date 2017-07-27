<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage iblock
 * @copyright 2001-2012 Bitrix
 */

namespace Bitrix\Iblock;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class SenderEventHandler
{
	/**
	 * @param $data
	 * @return mixed
	 */
	public static function onConnectorListIblock($data)
	{
		$data['CONNECTOR'] = 'Bitrix\Iblock\SenderConnectorIblock';

		return $data;
	}
}


class SenderConnectorIblock extends \Bitrix\Sender\Connector
{
	/**
	 * @return string
	 */
	public function getName()
	{
		return Loc::getMessage('sender_connector_iblock_name');
	}

	/**
	 * @return string
	 */
	public function getCode()
	{
		return "iblock";
	}

	/** @return \CDBResult */
	public function getData()
	{
		$iblockId = $this->getFieldValue('IBLOCK', null);
		$propertyNameId = $this->getFieldValue('PROPERTY_NAME', null);
		$propertyEmailId = $this->getFieldValue('PROPERTY_EMAIL', null);

		if($iblockId && $propertyEmailId)
		{
			// if property is property with code like '123'
			$propertyNameValue = null;
			$propertyEmailValue = null;
			if(is_numeric($propertyEmailId))
			{
				$propertyEmailId = "PROPERTY_" . $propertyEmailId;
				$propertyEmailValue = $propertyEmailId."_VALUE";
			}
			$selectFields = array($propertyEmailValue);

			if($propertyNameId)
			{
				if(is_numeric($propertyNameId))
				{
					$propertyNameId = "PROPERTY_" . $propertyNameId;
					$propertyNameValue = $propertyNameId . "_VALUE";
				}

				$selectFields[] = $propertyNameValue;
			}

			$filter = array('IBLOCK_ID' => $iblockId, '!'.$propertyEmailId => false);
			$iblockElementListDb = \CIBlockElement::getList(array('id' => 'asc'), $filter, false, false, $selectFields);

			// replace property names from PROPERTY_123_VALUE to EMAIL, NAME
			$iblockElementDb = new CDBResultSenderConnector($iblockElementListDb);
			$iblockElementDb->senderConnectorFieldEmail = $propertyEmailValue;
			$iblockElementDb->senderConnectorFieldName = $propertyNameValue;
		}
		else
		{
			$iblockElementDb = new \CDBResult();
		}


		return $iblockElementDb;
	}

	/**
	 * @return string
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public function getForm()
	{
		/*
		 * select iblock list
		*/
		$iblockList = array();
		$iblockDb = IblockTable::getList(array(
			'select' => array('ID', 'NAME'),
		));
		while($iblock = $iblockDb->fetch())
		{
			$iblockList[] = $iblock;
		}
		if(!empty($iblockList))
			$iblockList = array_merge(
				array(array('ID' => '', 'NAME' => Loc::getMessage('sender_connector_iblock_select'))),
				$iblockList
			);
		else
			$iblockList = array_merge(
				array(array('ID' => '', 'NAME' => Loc::getMessage('sender_connector_iblock_empty'))),
				$iblockList
			);

		/*
		 * select properties from all iblocks
		*/
		$propertyToIblock = array();
		$propertyList = array();
		$propertyList[''][] = array('ID' => '', 'NAME' => Loc::getMessage('sender_connector_iblock_select'));
		$propertyList['EMPTY'][] = array('ID' => '', 'NAME' => Loc::getMessage('sender_connector_iblock_prop_empty'));
		$iblockFieldsDb = PropertyTable::getList(array(
			'select' => array('ID', 'NAME', 'IBLOCK_ID'),
			'filter' => array('PROPERTY_TYPE' => PropertyTable::TYPE_STRING)
		));
		while($iblockFields = $iblockFieldsDb->fetch())
		{
			// add default value
			if(!array_key_exists($iblockFields['IBLOCK_ID'], $propertyList))
			{
				$propertyList[$iblockFields['IBLOCK_ID']][] = array(
					'ID' => '',
					'NAME' => Loc::getMessage('sender_connector_iblock_field_select')
				);
			}

			// add property
			$propertyList[$iblockFields['IBLOCK_ID']][] = array(
				'ID' => $iblockFields['ID'],
				'NAME' => $iblockFields['NAME']
			);

			// add property link to iblock
			$propertyToIblock[$iblockFields['ID']] = $iblockFields['IBLOCK_ID'];
		}


		/*
		 * create html-control of iblock list
		*/
		$iblockInput = '<select name="'.$this->getFieldName('IBLOCK').'" id="'.$this->getFieldId('IBLOCK').'" onChange="IblockSelect'.$this->getFieldId('IBLOCK').'()">';
		foreach($iblockList as $iblock)
		{
			$inputSelected = ($iblock['ID'] == $this->getFieldValue('IBLOCK') ? 'selected' : '');
			$iblockInput .= '<option value="'.$iblock['ID'].'" '.$inputSelected.'>';
			$iblockInput .= htmlspecialcharsbx($iblock['NAME']);
			$iblockInput .= '</option>';
		}
		$iblockInput .= '</select>';


		/*
		 * create html-control of properties list for name
		*/
		$iblockPropertyNameInput = '<select name="'.$this->getFieldName('PROPERTY_NAME').'" id="'.$this->getFieldId('PROPERTY_NAME').'">';
		if(array_key_exists($this->getFieldValue('PROPERTY_NAME', 0), $propertyToIblock))
		{
			$propSet = $propertyList[$propertyToIblock[$this->getFieldValue('PROPERTY_NAME', 0)]];
		}
		else
		{
			$propSet = $propertyList[''];
		}
		foreach($propSet as $property)
		{
			$inputSelected = ($property['ID'] == $this->getFieldValue('PROPERTY_NAME') ? 'selected' : '');
			$iblockPropertyNameInput .= '<option value="'.$property['ID'].'" '.$inputSelected.'>';
			$iblockPropertyNameInput .= htmlspecialcharsbx($property['NAME']);
			$iblockPropertyNameInput .= '</option>';
		}
		$iblockPropertyNameInput .= '</select>';


		/*
		 *  create html-control of properties list for email
		*/
		$iblockPropertyEmailInput = '<select name="'.$this->getFieldName('PROPERTY_EMAIL').'" id="'.$this->getFieldId('PROPERTY_EMAIL').'">';
		if(array_key_exists($this->getFieldValue('PROPERTY_EMAIL', 0), $propertyToIblock))
		{
			$propSet = $propertyList[$propertyToIblock[$this->getFieldValue('PROPERTY_EMAIL', 0)]];
		}
		else
		{
			$propSet = $propertyList[''];
		}
		foreach($propSet as $property)
		{
			$inputSelected = ($property['ID'] == $this->getFieldValue('PROPERTY_EMAIL') ? 'selected' : '');
			$iblockPropertyEmailInput .= '<option value="'.$property['ID'].'" '.$inputSelected.'>';
			$iblockPropertyEmailInput .= htmlspecialcharsbx($property['NAME']);
			$iblockPropertyEmailInput .= '</option>';
		}
		$iblockPropertyEmailInput .= '</select>';


		$jsScript = "
		<script>
			function IblockSelect".$this->getFieldId('IBLOCK')."()
			{
				var iblock = BX('".$this->getFieldId('IBLOCK')."');
				IblockPropertyAdd(iblock, BX('".$this->getFieldId('PROPERTY_NAME')."'));
				IblockPropertyAdd(iblock, BX('".$this->getFieldId('PROPERTY_EMAIL')."'));
			}
			function IblockPropertyAdd(iblock, iblockProperty)
			{
				if(iblockProperty.length>0)
				{
					for (var j in iblockProperty.options)
					{
						iblockProperty.options.remove(j);
					}
				}
				var propList = {};
				if(iblockProperties[iblock.value] && iblockProperties[iblock.value].length>0)
					propList = iblockProperties[iblock.value];
				else
					propList = iblockProperties['EMPTY'];
				for(var i in propList)
				{
					var optionName = propList[i]['NAME'];
					var optionValue = propList[i]['ID'];
					iblockProperty.options.add(new Option(optionName, optionValue));
				}

			}

			var iblockProperties = ".\CUtil::PhpToJSObject($propertyList).";
		</script>
		";



		return '
			'.Loc::getMessage('sender_connector_iblock_required_settings').'
			<br/><br/>
			<table>
				<tr>
					<td>'.Loc::getMessage('sender_connector_iblock_field_iblock').'</td>
					<td>'.$iblockInput.'</td>
				</tr>
				<tr>
					<td>'.Loc::getMessage('sender_connector_iblock_field_name').'</td>
					<td>'.$iblockPropertyNameInput.'</td>
				</tr>
				<tr>
					<td>'.Loc::getMessage('sender_connector_iblock_field_email').'</td>
					<td>'.$iblockPropertyEmailInput.'</td>
				</tr>
			</table>
			'.$jsScript.'
		';
	}
}

class CDBResultSenderConnector extends \CDBResult
{
	public $senderConnectorFieldName = null;
	public $senderConnectorFieldEmail = null;


	/**
	 * @return array|null
	 */
	public function Fetch()
	{
		$fields = parent::Fetch();
		if($fields)
		{
			if ($this->senderConnectorFieldName)
			{
				$fields['NAME'] = $fields[$this->senderConnectorFieldName."_VALUE"];
				unset($fields[$this->senderConnectorFieldName."_VALUE"]);
				unset($fields[$this->senderConnectorFieldName."_VALUE"."_ID"]);
			}

			if ($this->senderConnectorFieldName)
			{
				$fields['EMAIL'] = $fields[$this->senderConnectorFieldEmail."_VALUE"];
				unset($fields[$this->senderConnectorFieldEmail."_VALUE"]);
				unset($fields[$this->senderConnectorFieldEmail."_VALUE"."_ID"]);
			}
		}

		return $fields;
	}
}
