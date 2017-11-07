<?php
namespace Bitrix\Main\DB;

use Bitrix\Main\Diag;

class MysqlConnection
	extends MysqlCommonConnection
{
	/**********************************************************
	 * SqlHelper
	 **********************************************************/

	/**
	 * @return SqlHelper
	 */
	protected function createSqlHelper()
	{
		return new MysqlSqlHelper($this);
	}


	/***********************************************************
	 * Connection and disconnection
	 ***********************************************************/

	public function disconnectInternal()
	{
		if (!$this->isConnected)
			return;

		mysql_close($this->resource);

		$this->isConnected = false;
	}

	protected function connectInternal()
	{
		if ($this->isConnected)
			return;

		if (($this->options & self::PERSISTENT) != 0)
			$connection = mysql_pconnect($this->host, $this->login, $this->password);
		else
			$connection = mysql_connect($this->host, $this->login, $this->password, true);

		if (!$connection)
			throw new ConnectionException('Mysql connect error ['.$this->host.', '.gethostbyname($this->host).']', mysql_error());

		if (!mysql_select_db($this->database, $connection))
			throw new ConnectionException('Mysql select db error ['.$this->database.']', mysql_error($connection));

		$this->resource = $connection;
		$this->isConnected = true;

		if ($fn = \Bitrix\Main\Loader::getPersonal("php_interface/after_connect_d7.php"))
			include($fn);
	}


	/*********************************************************
	 * Query
	 *********************************************************/

	/**
	 * @param                           $sql
	 * @param array|null                $arBinds
	 * @param Diag\SqlTrackerQuery|null $trackerQuery
	 *
	 * @throws SqlQueryException
	 * @return resource
	 */
	protected function queryInternal($sql, array $arBinds = null, Diag\SqlTrackerQuery $trackerQuery = null)
	{
		$this->connectInternal();

		if ($trackerQuery != null)
			$trackerQuery->startQuery($sql, $arBinds);

		$result = mysql_query($sql, $this->resource);

		if ($trackerQuery != null)
			$trackerQuery->finishQuery();

		$this->lastQueryResult = $result;

		if (!$result)
			throw new SqlQueryException('Mysql query error', mysql_error($this->resource), $sql);

		return $result;
	}

	/**
	 * @param $result
	 * @param \Bitrix\Main\Diag\SqlTrackerQuery $trackerQuery
	 * @return Result
	 */
	protected function createResult($result, \Bitrix\Main\Diag\SqlTrackerQuery $trackerQuery = null)
	{
		return new MysqlResult($result, $this, $trackerQuery);
	}

	/**
	 * @return integer
	 */
	public function getInsertedId()
	{
		$this->connectInternal();
		return mysql_insert_id($this->resource);
	}

	public function getAffectedRowsCount()
	{
		return mysql_affected_rows($this->getResource());
	}

	/*********************************************************
	 * Type, version, cache, etc.
	 *********************************************************/

	public function getVersion()
	{
		if ($this->version == null)
		{
			$version = $this->queryScalar("SELECT VERSION()");
			if ($version != null)
			{
				$version = trim($version);
				preg_match("#[0-9]+\\.[0-9]+\\.[0-9]+#", $version, $ar);
				$this->version = $ar[0];
			}
		}

		return array($this->version, null);
	}

	public function getType()
	{
		return "mysql";
	}

	protected function getErrorMessage()
	{
		return sprintf("[%s] %s", mysql_errno($this->resource), mysql_error($this->resource));
	}
}
