<?
// *******************************************************************************************************
// Install new right system: operation and tasks
// *******************************************************************************************************
// ############ IBLOCK MODULE OPERATION ###########
$arFOp = Array();
$arFOp[] = Array('iblock_admin_display', 'iblock');
$arFOp[] = Array('iblock_edit', 'iblock');
$arFOp[] = Array('iblock_delete', 'iblock');
$arFOp[] = Array('iblock_rights_edit', 'iblock');
$arFOp[] = Array('iblock_export', 'iblock');

$arFOp[] = Array('section_read', 'iblock');
$arFOp[] = Array('section_edit', 'iblock');
$arFOp[] = Array('section_delete', 'iblock');
$arFOp[] = Array('section_element_bind', 'iblock');
$arFOp[] = Array('section_section_bind', 'iblock');
$arFOp[] = Array('section_rights_edit', 'iblock');

$arFOp[] = Array('element_read', 'iblock');
$arFOp[] = Array('element_edit', 'iblock');
$arFOp[] = Array('element_edit_any_wf_status', 'iblock');
$arFOp[] = Array('element_edit_price', 'iblock');
$arFOp[] = Array('element_delete', 'iblock');
$arFOp[] = Array('element_bizproc_start', 'iblock');
$arFOp[] = Array('element_rights_edit', 'iblock');

// ############ IBLOCK MODULE TASKS ###########
$arTasksF = Array();
$arTasksF[] = Array('iblock_deny', 'D', 'iblock');
$arTasksF[] = Array('iblock_read', 'R', 'iblock');
$arTasksF[] = Array('iblock_element_add', 'E', 'iblock');
$arTasksF[] = Array('iblock_limited_edit', 'U', 'iblock');
$arTasksF[] = Array('iblock_full_edit', 'W', 'iblock');
$arTasksF[] = Array('iblock_full', 'X', 'iblock');

//Operations in Tasks
$arOInT = Array();
//IBLOCK: module

$arOInT['iblock_deny'] = Array(
);
$arOInT['iblock_read'] = Array(
	'section_read',
	'element_read',
);
$arOInT['iblock_element_add'] = Array(
	'section_element_bind',
);
$arOInT['iblock_limited_edit'] = Array(
	'iblock_admin_display',
	'section_read', 'section_element_bind',
	'element_read', 'element_edit', 'element_edit_price', 'element_delete', 'element_bizproc_start',
);
$arOInT['iblock_full_edit'] = Array(
	'iblock_admin_display',
	'section_read', 'section_edit', 'section_delete', 'section_element_bind', 'section_section_bind',
	'element_read', 'element_edit', 'element_edit_price', 'element_delete', 'element_edit_any_wf_status', 'element_bizproc_start',
);
$arOInT['iblock_full'] = Array(
	'iblock_admin_display', 'iblock_edit', 'iblock_delete', 'iblock_rights_edit', 'iblock_export',
	'section_read', 'section_edit', 'section_delete', 'section_element_bind', 'section_section_bind', 'section_rights_edit',
	'element_read', 'element_edit', 'element_edit_price', 'element_delete', 'element_edit_any_wf_status', 'element_bizproc_start', 'element_rights_edit',
);

$arDBOperations = array();
$rsOperations = $DB->Query("SELECT NAME FROM b_operation WHERE MODULE_ID = 'iblock'");
while($ar = $rsOperations->Fetch())
	$arDBOperations[$ar["NAME"]] = $ar["NAME"];

foreach($arFOp as $ar)
{
	if(!isset($arDBOperations[$ar[0]]))
	{
		$DB->Query("
			INSERT INTO b_operation
			(NAME, MODULE_ID, BINDING)
			VALUES
			('".$ar[0]."', 'iblock', '".$ar[1]."')
		", false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
	}
}

$arDBTasks = array();
$rsTasks = $DB->Query("SELECT NAME FROM b_task WHERE MODULE_ID = 'iblock' AND SYS = 'Y'");
while($ar = $rsTasks->Fetch())
	$arDBTasks[$ar["NAME"]] = $ar["NAME"];

foreach($arTasksF as $ar)
{
	if(!isset($arDBTasks[$ar[0]]))
	{
		$DB->Query("
			INSERT INTO b_task
			(NAME, LETTER, MODULE_ID, SYS, BINDING)
			VALUES
			('".$ar[0]."', '".$ar[1]."', 'iblock', 'Y', '".$ar[2]."')
		", false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
	}
}

// ############ b_task_operation ###########
foreach($arOInT as $tname => $arOp)
{
	$sql_str = "
		INSERT INTO b_task_operation
		(TASK_ID,OPERATION_ID)
		SELECT T.ID TASK_ID, O.ID OPERATION_ID
		FROM
			b_task T
			,b_operation O
		WHERE
			T.SYS='Y'
			AND T.NAME='".$tname."'
			AND O.NAME in ('".implode("','", $arOp)."')
			AND O.NAME not in (
				SELECT O2.NAME
				FROM
					b_task T2
					inner join b_task_operation TO2 on TO2.TASK_ID = T2.ID
					inner join b_operation O2 on O2.ID = TO2.OPERATION_ID
				WHERE
					T2.SYS='Y'
					AND T2.NAME='".$tname."'
			)
	";
	$z = $DB->Query($sql_str, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
}

global $CACHE_MANAGER;
if(is_object($CACHE_MANAGER))
{
	$CACHE_MANAGER->CleanDir("b_task");
	$CACHE_MANAGER->CleanDir("b_task_operation");
}

?>