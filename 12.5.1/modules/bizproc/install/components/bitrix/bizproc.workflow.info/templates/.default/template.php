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
			<th colspan="2">Рабочий поток</th>
		</tr>
		<tr>
			<td align="right" valign="top" width="50%">Название шаблона рабочего потока:</td>
			<td width="50%" valign="top"><?= $arResult["WorkflowState"]["NAME"] ?></td>
		</tr>
		<tr>
			<td align="right" valign="top" width="50%">Описание шаблона рабочего потока:</td>
			<td width="50%" valign="top"><?= $arResult["WorkflowState"]["DESCRIPTION"] ?></td>
		</tr>
		<tr>
			<td align="right" valign="top" width="50%">Код рабочего потока:</td>
			<td width="50%" valign="top"><?= $arResult["WorkflowState"]["ID"] ?></td>
		</tr>
		<tr>
			<td align="right" valign="top" width="50%">Текущее состояние рабочего потока:</td>
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
			<th colspan="2">История выполнения рабочего потока:</th>
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
							$strMessageTemplate = "Запущено действие '#ACTIVITY#'#NOTE#";
							break;
						case 2:
							$strMessageTemplate = "Завершено действие '#ACTIVITY#' со статусом '#STATUS#' и результатом '#RESULT#'#NOTE#";
							break;
						case 3:
							$strMessageTemplate = "Отменено действие '#ACTIVITY#'#NOTE#";
							break;
						case 4:
							$strMessageTemplate = "Ошибка действия '#ACTIVITY#'#NOTE#";
							break;
						case 5:
							$strMessageTemplate = "Действие '#ACTIVITY#'#NOTE#";
							break;
						default:
							$strMessageTemplate = "Что-то сделали с действием '#ACTIVITY#'#NOTE#";
					}

					$name = (strlen($track["ACTION_TITLE"]) > 0 ? $track["ACTION_TITLE"]." (".$track["ACTION_NAME"].")" : $track["ACTION_NAME"]);

					switch ($track["EXECUTION_STATUS"])
					{
						case CBPActivityExecutionStatus::Initialized:
							$status = "Инициализировано";
							break;
						case CBPActivityExecutionStatus::Executing:
							$status = "Выполняется";
							break;
						case CBPActivityExecutionStatus::Canceling:
							$status = "Отменяется";
							break;
						case CBPActivityExecutionStatus::Closed:
							$status = "Завершено";
							break;
						case CBPActivityExecutionStatus::Faulting:
							$status = "Ошибка";
							break;
						default:
							$status = "Не определено";
					}

					switch ($track["EXECUTION_RESULT"])
					{
						case CBPActivityExecutionResult::None:
							$result = "Нет";
							break;
						case CBPActivityExecutionResult::Succeeded:
							$result = "Успешно";
							break;
						case CBPActivityExecutionResult::Canceled:
							$result = "Отменено";
							break;
						case CBPActivityExecutionResult::Faulted:
							$result = "Ошибка";
							break;
						case CBPActivityExecutionResult::Uninitialized:
							$result = "Не инициализировано";
							break;
						default:
							$status = "Не определено";
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