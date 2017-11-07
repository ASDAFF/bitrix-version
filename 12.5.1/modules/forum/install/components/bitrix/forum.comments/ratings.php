<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
class CCommentRatings
{
	var $component = null;
	var $arRatings = array();

	function __construct(&$component)
	{
		$this->component = &$component;
		if (
			isset($this->component->arResult['FORUM']['INDEXATION']) &&
			$this->component->arResult['FORUM']['INDEXATION'] == 'Y'
		)
		{
			AddEventHandler("forum", "OnCommentDispay", Array(&$this, "OnCommentDispay"));
			AddEventHandler("forum", "OnPrepareComments", Array($this, "OnPrepareComments"));
		}
	}

	function OnPrepareComments()
	{
		$arResult =& $this->component->arResult;
		$arParams =& $this->component->arParams;

		$arMessages =& $arResult['MESSAGES'];
		$arMessageIDs = array_keys($arMessages);
		$arRatings = CRatings::GetRatingVoteResult('FORUM_POST', $arMessageIDs);
		if ($arRatings)
			foreach($arRatings as $postID => $arRating)
				$this->arRatings[$postID] = $arRating;
	}

	function RatingDisplay($top = true, $commentID, $authorID)
	{
		$arParams = &$this->component->arParams;
		static $arEmptyRating = array(
			"USER_VOTE" => 0,
			"USER_HAS_VOTED" => 'N',
			"TOTAL_VOTES" => 0,
			"TOTAL_POSITIVE_VOTES" => 0,
			"TOTAL_NEGATIVE_VOTES" => 0,
			"TOTAL_VALUE" => 0
		);

		ob_start();
			if ($top) { ?>
				<div class="review-rating rating_vote_graphic">
			<? } else { ?>
				<span class="rating_vote_text">
				<span class="separator"></span>
			<? }
					$arRatingParams = Array(
							"ENTITY_TYPE_ID" => "FORUM_POST",
							"ENTITY_ID" => $commentID,
							"OWNER_ID" => $authorID,
							"PATH_TO_USER_PROFILE" => $arParams["~URL_TEMPLATES_PROFILE_VIEW"]
						);
					if (isset($this->arRatings[$commentID]))
						$arRating = $this->arRatings[$commentID];
					else
						$arRating = $arEmptyRating;
					$arRatingParams = array_merge($arRatingParams, $arRating);
					$GLOBALS["APPLICATION"]->IncludeComponent( "bitrix:rating.vote", "", $arRatingParams, $this->component, array("HIDE_ICONS" => "Y"));
			if ($top) { ?>
				</div>
			<? } else { ?>
				</span>
			<? }
		return ob_get_clean();
	}

	function OnCommentDispay($arComment)
	{
		$arReturn[] = array('DISPLAY' => 'BEFORE_HEADER', 'SORT' => '50', 'TEXT' => $this->RatingDisplay(true, $arComment['ID'], $arComment['AUTHOR_ID']));
		$arReturn[] = array('DISPLAY' => 'AFTER_ACTIONS', 'SORT' => '50', 'TEXT' => $this->RatingDisplay(false, $arComment['ID'], $arComment['AUTHOR_ID']));
		return $arReturn;
	}
}
?>
