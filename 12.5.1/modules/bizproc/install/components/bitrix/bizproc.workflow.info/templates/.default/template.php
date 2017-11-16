<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
if ($arResult["NeedAuth"] == "Y")
{
	$APPLICATION->AuthForm("");
}
elseif (strlen($arResult["FatalErrorMessage"]) > 0)
{
	?>
	<span class='errortext'><?= $arResult["FatalErrorMessage"] ?></span><br /><br />
	<?
}
else
{
	?>
	<table class="bizproc-task-form" cellspacing="0" cellpadding="0">
		<tr>
			<th colspan="2">������� �����</th>
		</tr>
		<tr>
			<td align="right" valign="top" width="50%">�������� ������� �������� ������:</td>
			<td width="50%" valign="top"><?= $arResult["WorkflowState"]["NAME"] ?></td>
		</tr>
		<tr>
			<td align="right" valign="top" width="50%">�������� ������� �������� ������:</td>
			<td width="50%" valign="top"><?= $arResult["WorkflowState"]["DESCRIPTION"] ?></td>
		</tr>
		<tr>
			<td align="right" valign="top" width="50%">��� �������� ������:</td>
			<td width="50%" valign="top"><?= $arResult["WorkflowState"]["ID"] ?></td>
		</tr>
		<tr>
			<td align="right" valign="top" width="50%">������� ��������� �������� ������:</td>
			<td width="50%" valign="top"><?
			if (strlen($arResult["WorkflowState"]["STATE"]) > 0)
			{
				if (strlen($arResult["WorkflowState"]["STATE_TITLE"]) > 0)
					echo $arResult["WorkflowState"]["STATE_TITLE"]." (".$arResult["WorkflowState"]["STATE"].")";
				else
					echo $arResult["WorkflowState"]["STATE"];
			}
			else
			{
				echo "&nbsp;";
			}
			?></td>
		</tr>
		<tr>
			<td colspan="2">&nbsp;</td>
		</tr>
		<tr>
			<th colspan="2">������� ���������� �������� ������:</th>
		</tr>
		<tr>
			<td colspan="2">
				<?
				foreach ($arResult["WorkflowTrack"] as $track)
				{
					echo $track["PREFIX"];

					$strMessageTemplate = "";
					switch ($track["TYPE"])
					{
						case 1:
							$strMessageTemplate = "�������� �������� '#ACTIVITY#'#NOTE#";
							break;
						case 2:
							$strMessageTemplate = "��������� �������� '#ACTIVITY#' �� �������� '#STATUS#' � ����������� '#RESULT#'#NOTE#";
							break;
						case 3:
							$strMessageTemplate = "�������� �������� '#ACTIVITY#'#NOTE#";
							break;
						case 4:
							$strMessageTemplate = "������ �������� '#ACTIVITY#'#NOTE#";
							break;
						case 5:
							$strMessageTemplate = "�������� '#ACTIVITY#'#NOTE#";
							break;
						default:
							$strMessageTemplate = "���-�� ������� � ��������� '#ACTIVITY#'#NOTE#";
					}

					$name = (strlen($track["ACTION_TITLE"]) > 0 ? $track["ACTION_TITLE"]." (".$track["ACTION_NAME"].")" : $track["ACTION_NAME"]);

					switch ($track["EXECUTION_STATUS"])
					{
						case CBPActivityExecutionStatus::Initialized:
							$status = "����������������";
							break;
						case CBPActivityExecutionStatus::Executing:
							$status = "�����������";
							break;
						case CBPActivityExecutionStatus::Canceling:
							$status = "����������";
							break;
						case CBPActivityExecutionStatus::Closed:
							$status = "���������";
							break;
						case CBPActivityExecutionStatus::Faulting:
							$status = "������";
							break;
						default:
							$status = "�� ����������";
					}

					switch ($track["EXECUTION_RESULT"])
					{
						case CBPActivityExecutionResult::None:
							$result = "���";
							break;
						case CBPActivityExecutionResult::Succeeded:
							$result = "�������";
							break;
						case CBPActivityExecutionResult::Canceled:
							$result = "��������";
							break;
						case CBPActivityExecutionResult::Faulted:
							$result = "������";
							break;
						case CBPActivityExecutionResult::Uninitialized:
							$result = "�� ����������������";
							break;
						default:
							$status = "�� ����������";
					}

					$note = ((strlen($track["ACTION_NOTE"]) > 0) ? ": ".$track["ACTION_NOTE"] : "");

					echo str_replace(
						array("#ACTIVITY#", "#STATUS#", "#RESULT#", "#NOTE#"),
						array($name, $status, $result, $note),
						$strMessageTemplate
					);
					echo "<br />";
				}
				?>
			</td>
		</tr>
		</table>
	<?
}
?>