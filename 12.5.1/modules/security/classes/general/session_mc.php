<?
global $SECURITY_SESSION_MC;
$SECURITY_SESSION_MC = null;

global $SECURITY_SESSION_ID;
$SECURITY_SESSION_ID = null;

class CSecuritySessionMC
{
	function Init()
	{
		global $SECURITY_SESSION_MC;

		if(isset($SECURITY_SESSION_MC))
			return true;

		if(extension_loaded('memcache') && defined("BX_SECURITY_SESSION_MEMCACHE_HOST"))
		{
			$port = defined("BX_SECURITY_SESSION_MEMCACHE_PORT")? intval(BX_SECURITY_SESSION_MEMCACHE_PORT): 11211;

			$SECURITY_SESSION_MC = memcache_connect(BX_SECURITY_SESSION_MEMCACHE_HOST, $port);
			if(is_object($SECURITY_SESSION_MC))
				return true;
		}

		return false;
	}

	function open($save_path, $session_name)
	{
		return CSecuritySessionMC::Init();
	}

	function close()
	{
		global $SECURITY_SESSION_MC, $SECURITY_SESSION_ID;

		if(
			isset($SECURITY_SESSION_MC)
			&& isset($SECURITY_SESSION_ID)
		)
		{
			$sid = defined("BX_CACHE_SID")? BX_CACHE_SID: "BX";

			$SECURITY_SESSION_MC->delete($sid.$SECURITY_SESSION_ID.".lock");
			$SECURITY_SESSION_MC->close();
			$SECURITY_SESSION_MC = null;
			$SECURITY_SESSION_ID = null;
		}

		return true;
	}

	function read($id)
	{
		global $SECURITY_SESSION_MC, $SECURITY_SESSION_ID;

		if(
			preg_match("/^[\da-z]{1,32}$/i", $id)
			&& isset($SECURITY_SESSION_MC)
		)
		{
			$locktimeout = 55;//TODO: add setting
			$lockwait = 59000000;//micro seconds = 60 seconds TODO: add setting
			$waitstep = 100;
			$sid = defined("BX_CACHE_SID")? BX_CACHE_SID: "BX";

			while(!$SECURITY_SESSION_MC->add($sid.$id.".lock", 1, 0, $locktimeout))
			{
				usleep($waitstep);
				$lockwait -= $waitstep;
				if($lockwait < 0)
					die('Unable to get session lock within 60 seconds.');

				if($waitstep < 1000000)
					$waitstep *= 2;
			}

			$SECURITY_SESSION_ID = $id;
			$res = $SECURITY_SESSION_MC->get($sid.$id);
			if($res !== false && $res !== '')
			{
				return $res;
			}
		}
	}

	function write($id, $sess_data)
	{
		global $SECURITY_SESSION_MC;

		if(
			preg_match("/^[\da-z]{1,32}$/i", $id)
			&& isset($SECURITY_SESSION_MC)
		)
		{
			$sid = defined("BX_CACHE_SID")? BX_CACHE_SID: "BX";
			$maxlifetime = intval(ini_get("session.gc_maxlifetime"));

			if($SECURITY_SESSION_OLD_ID && preg_match("/^[\da-z]{1,32}$/i", $SECURITY_SESSION_OLD_ID))
				$old_sess_id = $SECURITY_SESSION_OLD_ID;
			else
				$old_sess_id = $id;

			$SECURITY_SESSION_MC->delete($sid.$old_sess_id);
			$SECURITY_SESSION_MC->set($sid.$id, $sess_data, 0, time()+$maxlifetime);
		}
	}

	function destroy($id)
	{
		global $SECURITY_SESSION_MC;

		if(
			preg_match("/^[\da-z]{1,32}$/i", $id)
			&& isset($SECURITY_SESSION_MC)
		)
		{
			$sid = defined("BX_CACHE_SID")? BX_CACHE_SID: "BX";

			$SECURITY_SESSION_MC->delete($sid.$id);

			if($SECURITY_SESSION_OLD_ID && preg_match("/^[\da-z]{1,32}$/i", $SECURITY_SESSION_OLD_ID))
				$SECURITY_SESSION_MC->delete($sid.$SECURITY_SESSION_OLD_ID);
		}
	}

	function gc($maxlifetime)
	{
	}
}
?>