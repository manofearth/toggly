<?php

namespace TogglSync\Toggl;

use DateTime;
use Zend\Http\Client as HttpClient;
use Zend\Json\Json;
use Zend\Uri\Http as UriHttp;

class Gateway {

	/**
	 * @var HttpClient
	 */
	private $httpClient;

	/**
	 * @param UriHttp $uri
	 * @param array   $config
	 *
	 * @throws \Zend\Http\Exception\InvalidArgumentException
	 */
	public function __construct(UriHttp $uri, array $config) {
		$this->httpClient = new HttpClient($uri, ['sslverifypeer' => false]);
		$this->httpClient->setAuth($config['api-token'], 'api_token');
	}

	/**
	 * @param DateTime $startDateTime
	 * @param DateTime $endDateTime
	 *
	 * @return TimeEntry[]
	 * @throws \Zend\Json\Exception\RuntimeException
	 * @throws \Zend\Http\Client\Exception\RuntimeException
	 * @throws \Zend\Http\Exception\RuntimeException
	 * @throws Exception
	 */
	public function getTimeEntries(DateTime $startDateTime, DateTime $endDateTime) {
		$this->httpClient->getUri()->setPath('/api/v8/time_entries');
		$this->httpClient->setParameterGet([
			'start_date' => $startDateTime->format('Y-m-d\TH:i:sP'),
			'end_date'   => $endDateTime->format('Y-m-d\TH:i:sP')
		]);

		$response = $this->httpClient->send();

		if( ! $response->isOk()) {
			throw new Exception($response->getBody());
		}

		$result = [];

		/** @var array $responseBody */
		$responseBody = Json::decode($response->getBody());
		foreach ($responseBody as $timeEntryData) {
			$result[] = new TimeEntry($timeEntryData);
		}
		return $result;
	}

	/**
	 * @return TimeEntry[]
	 * @throws \Zend\Json\Exception\RuntimeException
	 * @throws \Zend\Http\Exception\RuntimeException
	 * @throws \Zend\Http\Client\Exception\RuntimeException
	 * @throws Exception
	 */
	public function getTimeEntriesForToday() {
		return $this->getTimeEntries(
			(new DateTime('now'))->setTime(0,0,0),
			(new DateTime('now'))->setTime(23,59,59)
		);
	}

	/**
	 * @return TimeEntry[]
	 * @throws \Zend\Json\Exception\RuntimeException
	 * @throws \Zend\Http\Exception\RuntimeException
	 * @throws \Zend\Http\Client\Exception\RuntimeException
	 * @throws Exception
	 */
	public function getTimeEntriesForYesterday() {
		return $this->getTimeEntries(
			(new DateTime('-1 day'))->setTime(0,0,0),
			(new DateTime('-1 day'))->setTime(23,59,59)
		);
	}

}
