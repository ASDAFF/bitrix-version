<?php

class CLearnAccessMacroses
{
	public static function CanUserViewLessonAsPublic ($arParams)
	{
		// Parse options (user_id from $arParams will be automaticaly resolved)
		$options = self::ParseParamsWithUser(
			$arParams,
			array(
				'COURSE_ID' => array(
					'type'          => 'strictly_castable_to_integer',
					'mandatory'     => true
					),
				'LESSON_ID' => array(
					'type'          => 'strictly_castable_to_integer',
					'mandatory'     => true
					)
				)
			);

		// Is it course?
		$linkedLessonId = CCourse::CourseGetLinkedLesson($options['COURSE_ID']);
		if ($linkedLessonId === false)
			return (false);		// Access denied

		$lessonId = $options['LESSON_ID'];
		$breakOnLessonId = $linkedLessonId;	// save resources

		// Is lesson included into given course?
		$isLessonChildOfCourse = false;
		$arOPathes = CLearnLesson::GetListOfParentPathes ($lessonId, $breakOnLessonId);
		foreach ($arOPathes as $oPath)
		{
			$topLessonId = $oPath->GetTop();

			if (($topLessonId !== false) && ($topLessonId == $linkedLessonId))
			{
				$isLessonChildOfCourse = true;
				break;
			}
		}

		if ( ! $isLessonChildOfCourse )
			return (false);		// Access denied

		// Check permissions for course
		$isCourseAccessible = self::CanUserViewLessonContent (array('lesson_id' => $linkedLessonId));

		// Permissions for all lessons/chapters in public are equivalent to course permissions
		return ($isCourseAccessible);
	}


	/**
	 * If $arParams['user_id'] not set, or set to -1 => $USER->GetID() will be used
	 */
	public static function CanUserAddLessonWithoutParentLesson ($arParams = array())
	{
		// Parse options (user_id from $arParams will be automaticaly resolved)
		$options = self::ParseParamsWithUser($arParams, array());

		$oAccess = CLearnAccess::GetInstance($options['user_id']);

		$isAccessGranted = $oAccess->IsBaseAccess(CLearnAccess::OP_LESSON_CREATE);

		return ($isAccessGranted);
	}


	/**
	 * If $arParams['user_id'] not set, or set to -1 => $USER->GetID() will be used
	 * $arParams['parent_lesson_id'] must be set.
	 */
	public static function CanUserAddLessonToParentLesson ($arParams)
	{
		// Parse options (user_id from $arParams will be automaticaly resolved)
		$options = self::ParseParamsWithUser(
			$arParams,
			array(
				'parent_lesson_id' => array(
					'type'          => 'strictly_castable_to_integer',
					'mandatory'     => true
					)
				)
			);

		$parent_lesson_id = $options['parent_lesson_id'];
		$user_id          = $options['user_id'];

		$oAccess = CLearnAccess::GetInstance($user_id);

		$isAccessGranted = 
			$oAccess->IsBaseAccess(CLearnAccess::OP_LESSON_CREATE)
			&& $oAccess->IsBaseAccessForCR(CLearnAccess::OP_LESSON_LINK_TO_PARENTS)
			&& $oAccess->IsLessonAccessible($parent_lesson_id, CLearnAccess::OP_LESSON_LINK_DESCENDANTS);

		return ($isAccessGranted);
	}


	public static function CanUserEditLesson ($arParams)
	{
		// Parse options (user_id from $arParams will be automaticaly resolved)
		$options = self::ParseParamsWithUser(
			$arParams,
			array(
				'lesson_id' => array(
					'type'          => 'strictly_castable_to_integer',
					'mandatory'     => true
					)
				)
			);

		$oAccess = CLearnAccess::GetInstance($options['user_id']);

		$isAccessGranted = $oAccess->IsBaseAccess(CLearnAccess::OP_LESSON_WRITE)
			|| $oAccess->IsLessonAccessible($options['lesson_id'], CLearnAccess::OP_LESSON_WRITE);

		return ($isAccessGranted);
	}


	public static function CanUserRemoveLesson ($arParams)
	{
		// Parse options (user_id from $arParams will be automaticaly resolved)
		$options = self::ParseParamsWithUser(
			$arParams,
			array(
				'lesson_id' => array(
					'type'          => 'strictly_castable_to_integer',
					'mandatory'     => true
					)
				)
			);

		$oAccess = CLearnAccess::GetInstance($options['user_id']);

		$isAccessGranted = $oAccess->IsBaseAccess(CLearnAccess::OP_LESSON_REMOVE)
			|| $oAccess->IsLessonAccessible($options['lesson_id'], CLearnAccess::OP_LESSON_REMOVE);

		return ($isAccessGranted);
	}


	public static function CanUserViewLessonContent ($arParams)
	{
		// Parse options (user_id from $arParams will be automaticaly resolved)
		$options = self::ParseParamsWithUser(
			$arParams,
			array(
				'lesson_id' => array(
					'type'          => 'strictly_castable_to_integer',
					'mandatory'     => true
					)
				)
			);

		$oAccess = CLearnAccess::GetInstance($options['user_id']);

		$isAccessGranted = $oAccess->IsBaseAccess(CLearnAccess::OP_LESSON_READ)
			|| $oAccess->IsLessonAccessible($options['lesson_id'], CLearnAccess::OP_LESSON_READ);

		return ($isAccessGranted);
	}


	public static function CanUserViewLessonRelations ($arParams)
	{
		$isAccessGranted = false;

		if (self::CanUserViewLessonContent ($arParams)
			|| self::CanUserPerformAtLeastOneRelationAction ($arParams)
		)
		{
			$isAccessGranted = true;	// Access granted
		}

		return ($isAccessGranted);
	}


	public static function CanUserPerformAtLeastOneRelationAction ($arParams)
	{
		static $arPermissiveOperations = array(
			CLearnAccess::OP_LESSON_LINK_TO_PARENTS,
			CLearnAccess::OP_LESSON_UNLINK_FROM_PARENTS,
			CLearnAccess::OP_LESSON_LINK_DESCENDANTS,
			CLearnAccess::OP_LESSON_UNLINK_DESCENDANTS
			);

		// Parse options (user_id from $arParams will be automaticaly resolved)
		$options = self::ParseParamsWithUser(
			$arParams,
			array(
				'lesson_id' => array(
					'type'          => 'strictly_castable_to_integer',
					'mandatory'     => true
					)
				)
			);

		$oAccess = CLearnAccess::GetInstance($options['user_id']);

		foreach ($arPermissiveOperations as $operation)
		{
			if ($oAccess->IsLessonAccessible(
				$options['lesson_id'],
				$operation)
			)
			{
				return (true);	// Yeah, there is some rights for some actions with relations
			}
		}

		return (false);
	}


	public static function CanUserEditLessonRights ($arParams)
	{
		// Parse options (user_id from $arParams will be automaticaly resolved)
		$options = self::ParseParamsWithUser(
			$arParams,
			array(
				'lesson_id' => array(
					'type'          => 'strictly_castable_to_integer',
					'mandatory'     => true
					)
				)
			);

		$oAccess = CLearnAccess::GetInstance($options['user_id']);

		$isAccessGranted = $oAccess->IsLessonAccessible(
			$options['lesson_id'],
			CLearnAccess::OP_LESSON_MANAGE_RIGHTS
			);
			
		return ($isAccessGranted);
	}


	public static function CanUserViewLessonRights ($arParams)
	{
		$isAccessGranted = self::CanUserViewLessonContent ($arParams)
			|| self::CanUserEditLessonRights ($arParams);

		return ($isAccessGranted);
	}


	/**
	 * Parse params throughs CLearnSharedArgManager::StaticParser(),
	 * but includes shared field 'user_id' and automatically replace
	 * user_id === -1 to user_id = $USER->GetID();
	 */
	protected static function ParseParamsWithUser ($arParams, $arParserOptions)
	{
		if ( ! (is_array($arParams) && is_array($arParserOptions)) )
		{
			throw new LearnException(
				'EA_LOGIC: $arParams and $arParserOptions must be arrays', 
				LearnException::EXC_ERR_ALL_GIVEUP 
				| LearnException::EXC_ERR_ALL_LOGIC
				| LearnException::EXC_ERR_ALL_ACCESS_DENIED);
		}

		if (array_key_exists('user_id', $arParserOptions))
		{
			throw new LearnException(
				'EA_LOGIC: unexpected user_id in $arParams', 
				LearnException::EXC_ERR_ALL_GIVEUP 
				| LearnException::EXC_ERR_ALL_LOGIC
				| LearnException::EXC_ERR_ALL_ACCESS_DENIED);
		}

		$arParserOptions['user_id'] = array(
			'type'          => 'strictly_castable_to_integer',
			'mandatory'     => false,
			'default_value' => -1	// it means, we must should use current user id
			);

		// Parse options
		try
		{
			$options = CLearnSharedArgManager::StaticParser(
				$arParams,
				$arParserOptions
				);
		}
		catch (Exception $e)
		{
			throw new LearnException(
				'EA_OTHER: CLearnSharedArgManager::StaticParser() throws an exception with code: ' 
					. $e->GetCode()	. ' and message: ' . $e->GetMessage(), 
				LearnException::EXC_ERR_ALL_GIVEUP 
				| LearnException::EXC_ERR_ALL_ACCESS_DENIED);
		}

		if ($options['user_id'] === -1)
			$options['user_id'] = self::GetCurrentUserId();

		return ($options);
	}


	protected static function GetCurrentUserId()
	{
		global $USER;

		if ( ! (is_object($USER) && method_exists($USER, 'GetID')) )
		{
			throw new LearnException(
				'EA_OTHER: $USER isn\'t available.', 
				LearnException::EXC_ERR_ALL_ACCESS_DENIED
				| LearnException::EXC_ERR_ALL_GIVEUP 
				| LearnException::EXC_ERR_ALL_LOGIC);
		}

		return ( (int) $USER->GetID() );
	}
}
