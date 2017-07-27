<?
class CSecuritySessionMC
{
	/** @var Memcache $connection*/
	protected static $connection = null;
	protected static $sessionId = null;
	protected static $isReadOnly = false;

	/**
	 * @return bool
	 */
	public static function Init()
	{
		if(self::isConnected())
			return true;

		self::$isReadOnly = defined('BX_SECURITY_SESSION_READONLY');

		return self::newConnection();
	}

	/**
	 * @param string $savePath - unused on this handler
	 * @param string $sessionName - unused on this handler
	 * @return bool
	 */
	public static function open($savePath, $sessionName)
	{
		return CSecuritySessionMC::Init();
	}

	/**
	 * @return bool
	 */
	public static function close()
	{
		if(!self::isConnected() || !self::isValidId(self::$sessionId))
			return false;

		if (!self::$isReadOnly)
		{
			if(isSessionExpired())
				self::destroy(self::$sessionId);

			self::$connection->delete(self::getPrefix().self::$sessionId.".lock");
		}

		self::$sessionId = null;
		self::closeConnection();
		return true;
	}

	/**
	 * @param string $id - session id, must be valid hash
	 * @return string
	 */
	public static function read($id)
	{
		if(!self::isConnected() || !self::isValidId($id))
			return "";

		$sid = self::getPrefix();

		if (!self::$isReadOnly)
		{
			$lockTimeout = 55;//TODO: add setting
			$lockWait = 59000000;//micro seconds = 60 seconds TODO: add setting
			$waitStep = 100;


			while(!self::$connection->add($sid.$id.".lock", 1, 0, $lockTimeout))
			{
				usleep($waitStep);
				$lockWait -= $waitStep;
				if($lockWait < 0)
					CSecuritySession::triggerFatalError('Unable to get session lock within 60 seconds.');

				if($waitStep < 1000000)
					$waitStep *= 2;
			}
		}

		self::$sessionId = $id;
		$res = self::$connection->get($sid.$id);
		if($res === false)
			$res = "";

		return $res;
	}

	/**
	 * @param string $id - session id, must be valid hash
	 * @param string $sessionData
	 * @return bool
	 */
	public static function write($id, $sessionData)
	{
		if(!self::isConnected() || !self::isValidId($id))
			return false;

		if (self::$isReadOnly)
			return true;

		$sid = self::getPrefix();
		$maxLifetime = intval(ini_get("session.gc_maxlifetime"));

		if(CSecuritySession::isOldSessionIdExist())
			$oldSessionId = CSecuritySession::getOldSessionId();
		else
			$oldSessionId = $id;

		self::$connection->delete($sid.$oldSessionId);
		self::$connection->set($sid.$id, $sessionData, 0, time() + $maxLifetime);

		return true;
	}

	/**
	 * @param string $id - session id, must be valid hash
	 * @return bool
	 */
	public static function destroy($id)
	{
		if(!self::isValidId($id))
			return false;

		if (self::$isReadOnly)
			return false;

		$isConnectionRestored = false;
		if(!self::isConnected())
			$isConnectionRestored = self::newConnection();

		if(!self::isConnected())
			return false;

		$sid = self::getPrefix();
		self::$connection->delete($sid.$id);

		if(CSecuritySession::isOldSessionIdExist())
			self::$connection->delete($sid.CSecuritySession::getOldSessionId());

		if($isConnectionRestored)
			self::closeConnection();

		return true;
	}

	/**
	 * @param int $maxLifeTime - unused on this handler
	 * @return bool
	 */
	public static function gc($maxLifeTime)
	{
		return true;
	}

	/**
	 * @return bool
	 */
	public static function isStorageEnabled()
	{
		return defined("BX_SECURITY_SESSION_MEMCACHE_HOST");
	}

	/**
	 * @return bool
	 */
	protected static function isConnected()
	{
		return self::$connection !== null;
	}

	/**
	 * @param string $pId
	 * @return bool
	 */
	protected static function isValidId($pId)
	{
		return (bool) preg_match("/^[\\da-z]{6,32}$/iD", $pId);
	}

	/**
	 * @return string
	 */
	protected static function getPrefix()
	{
		return defined("BX_CACHE_SID")? BX_CACHE_SID: "BX";
	}

	/**
	 * @return bool
	 */
	protected static function newConnection()
	{
		if(!extension_loaded('memcache') || !self::isStorageEnabled())
			return false;

		$port = defined("BX_SECURITY_SESSION_MEMCACHE_PORT")? intval(BX_SECURITY_SESSION_MEMCACHE_PORT): 11211;

		self::$connection = new Memcache;
		return self::$connection->connect(BX_SECURITY_SESSION_MEMCACHE_HOST, $port);
	}

	protected static function closeConnection()
	{
		self::$connection->close();
		self::$connection = null;
	}

}
