<?php

namespace TogglSync\Youtrack;

use SimpleXMLElement;
use Zend\Http\Client as HttpClient;
use Zend\Http\Header\ContentType;
use Zend\Http\Request;
use Zend\Json\Json;
use Zend\Uri\Http as UriHttp;

class Gateway {

	/**
	 * @var HttpClient
	 */
	private $httpClient;

	/**
	 * @param array   $config
	 *
	 * @throws Exception
	 * @throws \Zend\Http\Client\Exception\RuntimeException
	 * @throws \Zend\Http\Exception\InvalidArgumentException
	 * @throws \Zend\Http\Exception\RuntimeException
	 * @throws \Zend\Uri\Exception\InvalidArgumentException
	 */
	public function __construct(array $config) {

		$this->httpClient = new HttpClient(new UriHttp($config['uri']));
		$this->project    = (string) $config['project'];

		$this->_loginAndSetHeaders($config);
	}

	/**
	 * @param array [login=>string] $config
	 *
	 * @throws Exception
	 * @throws \Zend\Http\Exception\InvalidArgumentException
	 * @throws \Zend\Http\Client\Exception\RuntimeException
	 * @throws \Zend\Http\Exception\RuntimeException
	 */
	private function _loginAndSetHeaders(array $config) {

		$this->httpClient->setHeaders(['Accept' => 'application/json']);
		$this->httpClient->setMethod(Request::METHOD_POST);
		$this->httpClient->setParameterPost([
			'login' => $config['login'],
			'password' => $config['password'],
		]);
		$this->httpClient->getUri()->setPath('/rest/user/login');

		$response = $this->httpClient->send();

		if (!$response->isOk()) {
			throw new Exception($response->getBody(), $response->getStatusCode());
		}
	}

	/**
	 * @param string $issueId
	 *
	 * @return bool
	 */
	public function issueExists($issueId) {
		$this->httpClient->setMethod(Request::METHOD_GET);
		$this->httpClient->getUri()->setPath('/rest/issue/' . $issueId . '/exists');
		return $this->httpClient->send()->isOk();
	}

	/**
	 * @param $issueId
	 *
	 * @return WorkItem[]
	 * @throws \Zend\Json\Exception\RuntimeException
	 * @throws \Zend\Http\Exception\RuntimeException
	 * @throws \Zend\Http\Client\Exception\RuntimeException
	 * @throws Exception
	 */
	public function getWorkItemsOfIssue($issueId) {
		$this->httpClient->setMethod(Request::METHOD_GET);
		$this->httpClient->getUri()->setPath('/rest/issue/' . $issueId . '/timetracking/workitem/');

		$response = $this->httpClient->send();

		if( ! $response->isOk()) {
			throw new Exception($response->getBody());
		}

		$result = [];

		/** @var array $responseDecoded */
		$responseDecoded = Json::decode($response->getBody());
		foreach ($responseDecoded as $workItemData) {
			$result[] = WorkItem::fromStdClass($workItemData);
		}
		return $result;
	}

	/**
	 * @param string   $issueId
	 * @param WorkItem $item
	 *
	 * @throws Exception
	 * @throws \Zend\Http\Client\Exception\RuntimeException
	 * @throws \Zend\Http\Exception\RuntimeException
	 */
	public function addWorkItemForIssue($issueId, WorkItem $item) {
		$this->httpClient->setMethod(Request::METHOD_POST);
		$this->httpClient->getUri()->setPath('/rest/issue/' . $issueId . '/timetracking/workitem/'); // дублирование
		$this->httpClient->getRequest()->getHeaders()->addHeader(new ContentType('application/xml'));

		$xml = new SimpleXMLElement("<workItem>
				<date>{$item->getDate()->getTimestampMicroseconds()}</date>
				<duration>{$item->getDuration()->toMinutes()}</duration>
		</workItem>");

		if($item->getDescription() !== null) {
			$xml->addChild('description', $item->getDescription());
		}

		if($item->getWorkType() !== null) {
			$xml->addChild('worktype')->addChild('name', $item->getWorkType());
		}

		$this->httpClient->setRawBody($xml->asXML());

		$response = $this->httpClient->send();

		if( ! $response->isSuccess()) {
			throw new Exception($response->getBody());
		}
	}

	/**
	 * @param string $issueId
	 *
	 * @return Issue
	 * @throws \TogglSync\Youtrack\Exception
	 * @throws \Zend\Json\Exception\RuntimeException
	 * @throws \Zend\Http\Client\Exception\RuntimeException
	 * @throws \Zend\Http\Exception\RuntimeException
	 */
	public function getIssue($issueId) {
		$this->httpClient->setMethod(Request::METHOD_GET);
		$this->httpClient->getUri()->setPath('/rest/issue/' . $issueId);

		$response = $this->httpClient->send();

		if( ! $response->isOk()) {
			throw new Exception($response->getBody());
		}

		$responseDecoded = Json::decode($response->getBody());

		return Issue::fromStdClass($responseDecoded);
	}


}
