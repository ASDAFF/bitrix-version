<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Main;

use Bitrix\Main\IO;
use Bitrix\Main\Security;

/**
 * Http application extends application. Contains http specific methods.
 */
class HttpApplication extends Application
{
	/**
	 * Creates new instance of http application.
	 */
	protected function __construct()
	{
		parent::__construct();
	}

	/**
	 * Initializes context of the current request.
	 *
	 * @param array $params Request parameters
	 */
	protected function initializeContext(array $params)
	{
		$context = new HttpContext($this);

		$server = new Server($params["server"]);

		$request = new HttpRequest(
			$server,
			$params["get"],
			$params["post"],
			$params["files"],
			$params["cookie"]
		);

		$response = null; //new HttpResponse($context);

		$context->initialize($request, $response, $server, array('env' => $params["env"]));

		$this->setContext($context);
	}

	public function createExceptionHandlerOutput()
	{
		return new Diag\HttpExceptionHandlerOutput();
	}

	/**
	 * Starts request execution. Should be called after initialize.
	 */
	public function start()
	{
		//register_shutdown_function(array($this, "finish"));
	}

	/**
	 * Finishes request execution.
	 * It is registered in start() and called automatically on script shutdown.
	 */
	public function finish()
	{
		//$this->managedCache->finalize();
	}
}
