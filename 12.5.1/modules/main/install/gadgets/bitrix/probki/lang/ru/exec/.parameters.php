<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();


$arCity = Array();

$arCity["c213"] = "������ (������)";
$arCity["c2"] = "�����-��������� (������)";
$arCity["c54"] = "������������ (������)";
$arCity["c143"] = "���� (�������)";


$arParameters = Array(
		"PARAMETERS"=> Array(
			"CACHE_TIME" => array(
				"NAME" => "����� �����������, ��� (0-�� ����������)",
				"TYPE" => "STRING",
				"DEFAULT" => "3600"
				),
		"SHOW_URL" => Array(
				"NAME" => "���������� ������ �� ��������� ����������",
				"TYPE" => "CHECKBOX",
				"MULTIPLE" => "N",
				"DEFAULT" => "N",
			),
		),
		"USER_PARAMETERS"=> Array(
			"CITY"=>Array(
				"NAME" => "�����",
				"TYPE" => "LIST",
				"MULTIPLE" => "N",
				"DEFAULT" => "c213",
				"VALUES"=>$arCity,
			),
		),
	);

?>
