<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arActivityDescription = array(
	"NAME" => GetMessage("BPAA_DESCR_NAME"),
	"DESCRIPTION" => GetMessage("BPAA_DESCR_DESCR"),
	"TYPE" => "activity",
	"CLASS" => "ApproveActivity",
	"JSCLASS" => "ApproveActivity",
	"CATEGORY" => array(
		"ID" => "document",
	),
	"RETURN" => array(
		"Comments" => array(
			"NAME" => GetMessage("BPAA_DESCR_CM"),
			"TYPE" => "string",
		),
		"VotedCount" => array(
			"NAME" => GetMessage("BPAA_DESCR_VC"),
			"TYPE" => "int",
		),
		"TotalCount" => array(
			"NAME" => GetMessage("BPAA_DESCR_TC"),
			"TYPE" => "int",
		),
		"VotedPercent" => array(
			"NAME" => GetMessage("BPAA_DESCR_VP"),
			"TYPE" => "int",
		),
		"ApprovedPercent" => array(
			"NAME" => GetMessage("BPAA_DESCR_AP"),
			"TYPE" => "int",
		),
		"NotApprovedPercent" => array(
			"NAME" => GetMessage("BPAA_DESCR_NAP"),
			"TYPE" => "int",
		),
		"ApprovedCount" => array(
			"NAME" => GetMessage("BPAA_DESCR_AC"),
			"TYPE" => "int",
		),
		"NotApprovedCount" => array(
			"NAME" => GetMessage("BPAA_DESCR_NAC"),
			"TYPE" => "int",
		),
		"LastApprover" => array(
			"NAME" => GetMessage("BPAA_DESCR_LA"),
			"TYPE" => "user",
		),
		"Approvers" => array(
			"NAME" => GetMessage("BPAA_DESCR_APPROVERS"),
			"TYPE" => "string",
		),
		"Rejecters" => array(
			"NAME" => GetMessage("BPAA_DESCR_REJECTERS"),
			"TYPE" => "string",
		),
		"IsTimeout" => array(
			"NAME" => GetMessage("BPAA_DESCR_TA1"),
			"TYPE" => "int",
		),
	),
);
?>