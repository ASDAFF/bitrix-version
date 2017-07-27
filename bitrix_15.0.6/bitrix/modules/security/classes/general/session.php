<?
class CSecuritySession
{
	const GC_AGENT_NAME = "CSecuritySession::CleanUpAgent();";
	protected static $oldSessionId = null;

	public static function Init()
	{
		if(CSecuritySessionVirtual::isStorageEnabled())
		{
			static::registerHandler('CSecuritySessionVirtual');
		}
		elseif(CSecuritySessionMC::isStorageEnabled())
		{
			static::registerHandler('CSecuritySessionMC');
		}
		else
		{
			static::registerHandler('CSecuritySessionDB');
		}
	}

	/**
	 * @param string $pMessage
	 */
	public static function triggerFatalError($pMessage = "")
	{
		CHTTP::SetStatus("500 Internal Server Error");
		trigger_error($pMessage, E_USER_ERROR);
		die();
	}

	/**
	 * @return string
	 */
	public static function CleanUpAgent()
	{
		global $DB;
		$maxlifetime = intval(ini_get("session.gc_maxlifetime"));

		if($maxlifetime && !CSecuritySessionMC::isStorageEnabled())
		{
			$strSql = "
				delete from b_sec_session
				where TIMESTAMP_X < ".CSecurityDB::SecondsAgo($maxlifetime)."
			";
			if(CSecurityDB::Init())
				CSecurityDB::Query($strSql, "Module: security; Class: CSecuritySession; Function: CleanUpAgent; File: ".__FILE__."; Line: ".__LINE__);
			else
				$DB->Query($strSql, false, "Module: security; Class: CSecuritySession; Function: CleanUpAgent; File: ".__FILE__."; Line: ".__LINE__);
		}

		return self::GC_AGENT_NAME;
	}

	public static function UpdateSessID()
	{
		$oldSessionId = session_id();
		session_regenerate_id();
		$newSessionId = session_id();

		//Delay database update to session write moment
		if(!self::$oldSessionId)
			self::$oldSessionId = $oldSessionId;
	}

	/**
	 * @return bool
	 */
	public static function isOldSessionIdExist()
	{
		return self::$oldSessionId && self::checkSessionId(self::$oldSessionId);
	}

	/**
	 * @return string
	 */
	public static function getOldSessionId()
	{
		return self::$oldSessionId;
	}

	/**
	 * @param string $id
	 * @return bool
	 */
	public static function checkSessionId($id)
	{
		return (bool) preg_match("/^[\da-z]{10,32}$/iD", $id);
	}

	public static function activate()
	{
		COption::SetOptionString("security", "session", "Y");
		CSecuritySession::Init();
		CAgent::RemoveAgent(self::GC_AGENT_NAME, "security");
		CAgent::Add(array(
			"NAME" => self::GC_AGENT_NAME,
			"MODULE_ID" => "security",
			"ACTIVE" => "Y",
			"AGENT_INTERVAL" => 1800,
			"IS_PERIOD" => "N",
		));
	}

	public static function deactivate()
	{
		COption::SetOptionString("security", "session", "N");
		CAgent::RemoveAgent(self::GC_AGENT_NAME, "security");
	}

	protected static function registerHandler($class)
	{
		if(!class_exists($class))
		{
			self::triggerFatalError(
				sprintf('Session handler "%s" was not found.', $class)
			);
		}

		if(!$class::Init())
		{
			self::triggerFatalError(
				sprintf('Failed to initialize "%s" session handler.', $class)
			);
		}

		//may return false with session.auto_start is set to On
		// ToDo: change to SessionHandlerInterface when Bitrix reached PHP 5.4.0
		if(session_set_save_handler(
			array($class, "open"),
			array($class, "close"),
			array($class, "read"),
			array($class, "write"),
			array($class, "destroy"),
			array($class, "gc")
		))
		{
			register_shutdown_function("session_write_close");
		}
	}
}
