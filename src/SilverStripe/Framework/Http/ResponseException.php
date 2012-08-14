<?php
/**
 * @package framework
 * @subpackage http
 */

namespace SilverStripe\Framework\Http;

/**
 * A {@link Response} encapsulated in an exception, which can interrupt
 * the processing flow and be caught by the request handler.
 *
 * @package framework
 * @subpackage http
 */
class ResponseException extends \Exception {

	protected $response;

	/**
	 * @see Response::__construct();
	 */
	public function __construct($body = null, $statusCode = null, $statusDescription = null) {
		if($body instanceof Response) {
			$this->setResponse($body);
		} else {
			$this->setResponse(new Response($body, $statusCode, $statusDescription));
		}

		parent::__construct($this->getResponse()->getBody(), $this->getResponse()->getStatusCode());
	}

	/**
	 * @return Response
	 */
	public function getResponse() {
		return $this->response;
	}

	/**
	 * @param Response $response
	 */
	public function setResponse(Response $response) {
		$this->response = $response;
	}

}