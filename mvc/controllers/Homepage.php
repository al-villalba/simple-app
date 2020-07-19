<?php

namespace App\Controller;

use \Simple\Http\Response as HttpResponse;

/**
 * Homepage controller
 * 
 * @author Alvaro <alvaro.simplemvc@gmail.com>
 * @license https://opensource.org/licenses/MIT MIT
 */
class Homepage extends ControllerAbstract
{
	/**
	 * Middleware before action
	 * 
	 * @param string $action
	 */
//	public function _beforeAction($action)
//	{
//		...
//		parent::_beforeAction($action);
//	}

	/**
	 * Handle request GET /
	 * (cli: Homepage/index)
	 * 
	 * @return \Simple\ResponseInterface
	 */
	public function index()
	{
		$response = new HttpResponse();
		$response->setBody( __METHOD__ );

		return $response;
	}

}
