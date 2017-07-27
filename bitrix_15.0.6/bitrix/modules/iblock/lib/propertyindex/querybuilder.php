<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage iblock
 */
namespace Bitrix\Iblock\PropertyIndex;

use Bitrix\Main\Loader;

class QueryBuilder
{
	protected $iblockId = 0;
	protected $valid = false;
	protected $skuIblockId = 0;
	protected $skuPropertyId = 0;
	protected $sectionFilter = null;
	protected $priceFilter = null;

	protected $dictionary = null;
	protected $storage = null;

	/**
	 * @param integer $iblockId Information block identifier.
	 */
	public function __construct($iblockId)
	{
		$this->iblockId = intval($iblockId);
		$this->valid = \CIBlock::getArrayByID($this->iblockId, "PROPERTY_INDEX") === "Y";
		if(Loader::includeModule("catalog"))
		{
			$catalogInfo = \CCatalogSKU::getInfoByProductIBlock($this->iblockId);
			if (!empty($catalogInfo) && is_array($catalogInfo))
			{
				$this->skuIblockId = $catalogInfo["IBLOCK_ID"];
				$this->skuPropertyId = $catalogInfo["SKU_PROPERTY_ID"];
				$this->valid = $this->valid && \CIBlock::getArrayByID($this->skuIblockId, "PROPERTY_INDEX") === "Y";
			}
		}
		$this->dictionary = new \Bitrix\Iblock\PropertyIndex\Dictionary($this->iblockId);
		$this->storage = new \Bitrix\Iblock\PropertyIndex\Storage($this->iblockId);
	}

	/**
	 * Returns true if filter rewrite is possible.
	 *
	 * @return boolean
	 */
	public function isValid()
	{
		return $this->valid;
	}

	/**
	 * Returns filter join with index tables.
	 *
	 * @param array &$filter Filter which may be rewrited.
	 * @param array &$sqlSearch Additional result of rewrite.
	 *
	 * @return string
	 */
	public function getFilterSql(&$filter, &$sqlSearch)
	{
		$fcJoin = "";
		$where = array();
		$toUnset = array();
		if (
			!is_array($filter["IBLOCK_ID"]) && $filter["IBLOCK_ID"] > 0
			&& !is_array($filter["SECTION_ID"]) && $filter["SECTION_ID"] > 0
			&& isset($filter["ACTIVE"]) && $filter["ACTIVE"] === "Y"
		)
		{
			$where = array();
			$toUnset[] = array(&$filter, "SECTION_ID");

			if ($filter["INCLUDE_SUBSECTIONS"] === "Y")
			{
				$subsectionsCondition = "";
				$toUnset[] = array(&$filter, "INCLUDE_SUBSECTIONS");
			}
			else
			{
				$subsectionsCondition = "INCLUDE_SUBSECTIONS=1";
				if (array_key_exists("INCLUDE_SUBSECTIONS", $filter))
					$toUnset[] = array(&$filter, "INCLUDE_SUBSECTIONS");
			}

			$hasAdditionalFilters = false;
			$this->fillWhere($where, $hasAdditionalFilters, $toUnset, $filter);

			if (!$where)
			{
				$where[] = array(
					"TYPE" => Storage::DICTIONARY,
					"OP" => "=",
					"FACET_ID" => 1,
					"VALUES" => array(0),
				);
			}

			if (
				isset($filter["=ID"]) && is_object($filter["=ID"])
				&& $filter["=ID"]->arFilter["IBLOCK_ID"] == $this->skuIblockId
				&& $filter["=ID"]->strField === "PROPERTY_".$this->skuPropertyId
			)
			{
				$hasAdditionalFilters = false;
				$this->fillWhere($where, $hasAdditionalFilters, $toUnset, $filter["=ID"]->arFilter);
				if (!$hasAdditionalFilters)
				{
					$toUnset[] = array(&$filter, "=ID");
				}
			}

			if ($where)
			{
				$filter["SECTION_ID"] = (isset($filter["SECTION_ID"]) ? (int)$filter["SECTION_ID"] : 0);
				$distinctSelectCapable = (\Bitrix\Main\Application::getConnection()->getType() == "mysql");
				if (count($where) == 1 && $distinctSelectCapable)
				{
					$fcJoin = "INNER JOIN ".$this->storage->getTableName()." FC on FC.ELEMENT_ID = BE.ID";
					foreach ($where as $facetFilter)
					{
						if ($facetFilter["OP"] == "=" && ($facetFilter["TYPE"] == Storage::DICTIONARY || $facetFilter["TYPE"] == Storage::STRING))
							$sqlSearch[] = "FC.SECTION_ID = ".$filter["SECTION_ID"]." AND FC.FACET_ID = ".$facetFilter["FACET_ID"]." AND FC.VALUE_NUM = 0 AND FC.VALUE in (".implode(", ", $facetFilter["VALUES"]).") ".($subsectionsCondition !== '' ? "and FC.".$subsectionsCondition : '');
						elseif (($facetFilter["OP"] == ">=" || $facetFilter["OP"] == "<=") && $facetFilter["TYPE"] == Storage::NUMERIC)
							$sqlSearch[] = "FC.SECTION_ID = ".$filter["SECTION_ID"]." AND FC.FACET_ID = ".$facetFilter["FACET_ID"]." AND FC.VALUE_NUM ".$facetFilter["OP"]." ".current($facetFilter["VALUES"])." AND FC.VALUE = 0 ".($subsectionsCondition !== '' ? "and FC.".$subsectionsCondition : '');
						elseif ($facetFilter["OP"] == "><" && $facetFilter["TYPE"] == Storage::NUMERIC)
							$sqlSearch[] = "FC.SECTION_ID = ".$filter["SECTION_ID"]." AND FC.FACET_ID = ".$facetFilter["FACET_ID"]." AND FC.VALUE_NUM BETWEEN ".$facetFilter["VALUES"][0]." AND ".$facetFilter["VALUES"][1]." AND FC.VALUE = 0 ".($subsectionsCondition !== '' ? "and FC.".$subsectionsCondition : '');
					}
				}
				elseif (count($where) <= 5)
				{
					$fcJoin = "
						INNER JOIN (
							SELECT ".($distinctSelectCapable? "": "DISTINCT")." FC0.ELEMENT_ID
							FROM ".$this->storage->getTableName()." FC0
					";
					$i = 0;
					foreach ($where as $facetFilter)
					{
						if ($i > 0)
						{
							$fcJoin .= "INNER JOIN ".$this->storage->getTableName()." FC$i ON FC$i.ELEMENT_ID = FC0.ELEMENT_ID\n";
						}
						$i++;
					}
					$fcJoin .= "
						WHERE
					";
					$i = 0;
					foreach ($where as $facetFilter)
					{
						if ($i > 0)
						{
							$fcJoin .= " AND ";
						}
						if ($facetFilter["OP"] == "=" && ($facetFilter["TYPE"] == Storage::DICTIONARY || $facetFilter["TYPE"] == Storage::STRING))
							$fcJoin .= "FC$i.SECTION_ID = ".$filter["SECTION_ID"]." AND FC$i.FACET_ID = ".$facetFilter["FACET_ID"]." AND FC$i.VALUE_NUM = 0 AND FC$i.VALUE in (".implode(", ", $facetFilter["VALUES"]).") ".($subsectionsCondition !== '' ? "and FC$i.".$subsectionsCondition : '');
						elseif (($facetFilter["OP"] == ">=" || $facetFilter["OP"] == "<=") && $facetFilter["TYPE"] == Storage::NUMERIC)
							$fcJoin .= "FC$i.SECTION_ID = ".$filter["SECTION_ID"]." AND FC$i.FACET_ID = ".$facetFilter["FACET_ID"]." AND FC$i.VALUE_NUM ".$facetFilter["OP"]." ".current($facetFilter["VALUES"])." AND FC$i.VALUE = 0 ".($subsectionsCondition !== '' ? "and FC$i.".$subsectionsCondition : '');
						elseif ($facetFilter["OP"] == "><" && $facetFilter["TYPE"] == Storage::NUMERIC)
							$fcJoin .= "FC$i.SECTION_ID = ".$filter["SECTION_ID"]." AND FC$i.FACET_ID = ".$facetFilter["FACET_ID"]." AND FC$i.VALUE_NUM BETWEEN ".$facetFilter["VALUES"][0]." AND ".$facetFilter["VALUES"][1]." AND FC$i.VALUE = 0 ".($subsectionsCondition !== '' ? "and FC$i.".$subsectionsCondition : '');
						$i++;
					}
					$fcJoin .= "
						) FC on FC.ELEMENT_ID = BE.ID
					";
				}
				else
				{
					$condition = array();
					foreach ($where as $facetFilter)
					{
						if ($facetFilter["OP"] == "=" && ($facetFilter["TYPE"] == Storage::DICTIONARY || $facetFilter["TYPE"] == Storage::STRING))
							$condition[] = "FC0.FACET_ID = ".$facetFilter["FACET_ID"]." AND FC0.VALUE_NUM = 0 AND FC0.VALUE in (".implode(", ", $facetFilter["VALUES"]).") ".($subsectionsCondition !== '' ? "and FC0.".$subsectionsCondition : '');
						elseif (($facetFilter["OP"] == ">=" || $facetFilter["OP"] == "<=") && $facetFilter["TYPE"] == Storage::NUMERIC)
							$condition[] = "FC0.FACET_ID = ".$facetFilter["FACET_ID"]." AND FC0.VALUE_NUM ".$facetFilter["OP"]." ".current($facetFilter["VALUES"])." AND FC0.VALUE = 0 ".($subsectionsCondition !== '' ? "and FC0.".$subsectionsCondition : '');
						elseif ($facetFilter["OP"] == "><" && $facetFilter["TYPE"] == Storage::NUMERIC)
							$condition[] = "FC0.FACET_ID = ".$facetFilter["FACET_ID"]." AND FC0.VALUE_NUM BETWEEN ".$facetFilter["VALUES"][0]." AND ".$facetFilter["VALUES"][1]." AND FC0.VALUE = 0 ".($subsectionsCondition !== '' ? "and FC0.".$subsectionsCondition : '');
					}
					$fcJoin = "
						INNER JOIN (
							SELECT FC0.ELEMENT_ID
							FROM ".$this->storage->getTableName()." FC0
							WHERE FC0.SECTION_ID = ".$filter["SECTION_ID"]."
							AND (
							(".implode(")OR(", $condition).")
							)
						GROUP BY FC0.ELEMENT_ID
						HAVING count(DISTINCT FC0.FACET_ID) = ".count($condition)."
						) FC on FC.ELEMENT_ID = BE.ID
					";
				}

				foreach ($toUnset as $command)
				{
					unset($command[0][$command[1]]);
				}
			}
			else
			{
				$fcJoin = "";
			}
		}
		return $fcJoin;
	}

	/**
	 * Goes through the $filter and creates unified conditions in $where array.
	 *
	 * @param array &$where Output result.
	 * @param boolean &$hasAdditionalFilters Whenever has some filters left or not.
	 * @param array &$toUnset Output $filter keys which may be excluded.
	 * @param array &$filter Filter to go through.
	 *
	 * @return void
	 */
	private function fillWhere(&$where, &$hasAdditionalFilters, &$toUnset, &$filter)
	{
		$properties = $this->getFilterProperty();
		foreach ($filter as $filterKey => $filterValue)
		{
			if (preg_match("/^(=)PROPERTY\$/i", $filterKey, $keyDetails) && is_array($filterValue))
			{
				foreach ($filterValue as $propertyId => $value)
				{
					$facetId = $this->storage->propertyIdToFacetId($propertyId);
					if ($properties[$propertyId] == Storage::DICTIONARY || $properties[$propertyId] == Storage::STRING)
					{
						$sqlValues = $this->getInSql($value, $properties[$propertyId] == Storage::STRING);
						if ($sqlValues)
						{
							$where[] = array(
								"TYPE" => $properties[$propertyId],
								"OP" => $keyDetails[1],
								"FACET_ID" => $facetId,
								"VALUES" => $sqlValues,
							);
							$toUnset[] = array(&$filter[$filterKey], $propertyId);
						}
					}
				}
			}
			elseif (preg_match("/^(=)PROPERTY_(\\d+)\$/i", $filterKey, $keyDetails))
			{
				$propertyId = $keyDetails[2];
				$value = $filterValue;
				$facetId = $this->storage->propertyIdToFacetId($propertyId);
				if ($properties[$propertyId] == Storage::DICTIONARY || $properties[$propertyId] == Storage::STRING)
				{
					$sqlValues = $this->getInSql($value, $properties[$propertyId] == Storage::STRING);
					if ($sqlValues)
					{
						$where[] = array(
							"TYPE" => $properties[$propertyId],
							"OP" => $keyDetails[1],
							"FACET_ID" => $facetId,
							"VALUES" => $sqlValues,
						);
						$toUnset[] = array(&$filter[$filterKey], $propertyId);
					}
				}
			}
			elseif (preg_match("/^(>=|<=)PROPERTY\$/i", $filterKey, $keyDetails) && is_array($filterValue))
			{
				foreach ($filterValue as $propertyId => $value)
				{
					$facetId = $this->storage->propertyIdToFacetId($propertyId);
					if ($properties[$propertyId] == Storage::NUMERIC)
					{
						if (is_array($value))
							$doubleValue = doubleval(current($value));
						else
							$doubleValue = doubleval($value);
						$where[] = array(
							"TYPE" => Storage::NUMERIC,
							"OP" => $keyDetails[1],
							"FACET_ID" => $facetId,
							"VALUES" => array($doubleValue),
						);
						$toUnset[] = array(&$filter[$filterKey], $propertyId);
					}
				}
			}
			elseif (preg_match("/^(><)PROPERTY\$/i", $filterKey, $keyDetails) && is_array($filterValue))
			{
				foreach ($filterValue as $propertyId => $value)
				{
					$facetId = $this->storage->propertyIdToFacetId($propertyId);
					if ($properties[$propertyId] == Storage::NUMERIC)
					{
						if (is_array($value) && count($value) == 2)
						{
							$doubleMinValue = doubleval(current($value));
							$doubleMaxValue = doubleval(end($value));
							$where[] = array(
								"TYPE" => Storage::NUMERIC,
								"OP" => $keyDetails[1],
								"FACET_ID" => $facetId,
								"VALUES" => array($doubleMinValue, $doubleMaxValue),
							);
							$toUnset[] = array(&$filter[$filterKey], $propertyId);
						}
					}
				}
			}
			elseif (
				$filterKey !== "IBLOCK_ID"
				&& $filterKey !== "ACTIVE"
				&& $filterKey !== "ACTIVE_DATE"
			)
			{
				$hasAdditionalFilters = true;
			}
		}
	}

	/**
	 * Returns array on integers representing values for sql query.
	 *
	 * @param mixed $value Value to be intvaled.
	 * @param boolean $lookup Whenever to convert the value from string to dictionary or not.
	 *
	 * @return integer[]
	 */
	protected function getInSql($value, $lookup)
	{
		$result = array();

		if (is_array($value))
		{
			foreach ($value as $val)
			{
				if (strlen($val) > 0)
				{
					if ($lookup)
					{
						$result[] = $this->dictionary->getStringId($val, false);
					}
					else
					{
						$result[] = intval($val);
					}
				}
			}
		}
		elseif (strlen($value) > 0)
		{
			if ($lookup)
			{
				$result[] = $this->dictionary->getStringId($value, false);
			}
			else
			{
				$result[] = intval($value);
			}
		}

		return $result;
	}

	/**
	 * Returns map of properties to their types.
	 * Properties of iblock and its sku returned
	 * which marked as for smart filter.
	 *
	 * @return integer[]
	 */
	private function getFilterProperty()
	{
		if (!isset($this->propertyFilter))
		{
			$this->propertyFilter = array();
			$propertyList = \Bitrix\Iblock\SectionPropertyTable::getList(array(
				"select" => array("PROPERTY_ID", "PROPERTY.PROPERTY_TYPE"),
				"filter" => array(
					"=IBLOCK_ID" => array($this->iblockId, $this->skuIblockId),
					"=SMART_FILTER" => "Y",
				),
			));
			while ($link = $propertyList->fetch())
			{
				if ($link["IBLOCK_SECTION_PROPERTY_PROPERTY_PROPERTY_TYPE"] === "N")
					$this->propertyFilter[$link["PROPERTY_ID"]] = Storage::NUMERIC;
				elseif ($link["IBLOCK_SECTION_PROPERTY_PROPERTY_PROPERTY_TYPE"] === "S")
					$this->propertyFilter[$link["PROPERTY_ID"]] = Storage::STRING;
				else
					$this->propertyFilter[$link["PROPERTY_ID"]] = Storage::DICTIONARY;
			}
		}
		return $this->propertyFilter;
	}
}
