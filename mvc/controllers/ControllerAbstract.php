<?php

namespace App\Controller;

/**
 * ControllerAbstract: This is the right place to define global dependencies
 * (aka services)
 * 
 * @author Alvaro <alvaro.simplemvc@gmail.com>
 * @license https://opensource.org/licenses/MIT MIT
 */
abstract class ControllerAbstract extends \Simple\Controller\ControllerAbstract
{
	/**
	 * Constructor: Start session
	 */
	public function __construct()
	{
		if( session_status() != PHP_SESSION_ACTIVE &&
			!(isset($_SERVER['XDEBUG_CONFIG']) && 
			$_SERVER['XDEBUG_CONFIG'] == 'idekey=netbeans-xdebug') // not debugging unit tests
		) {
			ini_set('session.cookie_lifetime', 
				\Simple::app()['config']['app']['session']['cookie_lifetime']); // 7 days
			ini_set('session.gc_maxlifetime', 
				\Simple::app()['config']['app']['session']['cookie_lifetime']);
			session_start();
		}

		parent::__construct();
	}

	/**
	 * Register dependencies with callables here
	 * 
	 * @see \App\Command\CommandAbstract:_beforeAction()
	 * 
	 * @param string $controllerName
	 */
	public function _beforeController($controllerName)
	{
		$app = \Simple::app();

		// internet connection: bool
		$app->isInternetConnected = false;
		if( isset($_SESSION) && !empty($_SESSION['isInternetConnected']) ) {
			$app->isInternetConnected = true;
		}
		if( !$app->isInternetConnected ) {
			$this->_checkConnection();
		}
	}

	/**
	 * Check the connection to Internet and save the status in
	 * $app->isInternetConnected
	 */
	protected function _checkConnection()
	{
		// TODO: service curl
		return;

		$app = \Simple::app();

		$host = 'pocholadas.biz';
		$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
		$head = <<<HEAD
GET / HTTP/1.1
Accept: */*
Accept-Encoding: gzip, deflate, br
Accept-Language: en-GB,en;q=0.5
Connection: keep-alive
Content-Type: text/plain
Host: $host
User-Agent: $userAgent
HEAD;
		try {
			$curl = new \Poch\Curl("http://$host/_status.php", explode("\n", $head));
			$curlResponse = $curl->exec();
			\Simple\Logger::debug($curlResponse);
			// if connection didn't succeed Exception is thrown
			$app->isInternetConnected = true;
		} catch(\Throwable $e) {
			\Simple\Logger::error($e->__toString());
		}

		$_SESSION['isInternetConnected'] = $app->isInternetConnected;
	}

}
