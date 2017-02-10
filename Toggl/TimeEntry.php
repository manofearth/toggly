<?php

namespace TogglSync\Toggl;

use DateTimeZone;
use TogglSync\DateInterval;
use TogglSync\DateTime;
use stdClass;

class TimeEntry {

	/**
	 * @var stdClass
	 * id = 421758775
	 * guid = "3ab15f9c-26f1-4117-acab-fe296b211f73"
	 * wid = 1554057
	 * pid = 19445275
	 * billable = false
	 * start = "2016-08-01T06:52:22+00:00"
	 * stop = "2016-08-01T07:21:00+00:00"
	 * duration = 1718
	 * description = "VST-5308 Тесты на SQLite"
	 * tags = {array} [1]
	 * duronly = false
	 * at = "2016-08-01T07:21:03+00:00"
	 * uid = 2345567
	 */
	private $data;

	/**
	 * @param stdClass $timeEntryData
	 */
	public function __construct(stdClass $timeEntryData) {
		$this->data = $timeEntryData;
	}

	/**
	 * @return string
	 */
	public function getDescription() {
		return $this->data->description;
	}

	/**
	 * @return DateTime
	 */
	public function getStop() {
		return (new DateTime($this->data->stop))->setTimezone(new DateTimeZone(date_default_timezone_get()));
	}

	/**
	 * @return DateInterval
	 * @throws \TogglSync\Exception
	 */
	public function getDuration() {
		return DateInterval::fromSeconds($this->data->duration);
	}

	/**
	 * @return string[]
	 */
	public function getTags() {
		if( ! property_exists($this->data, 'tags')) {
			return [];
		}
		return $this->data->tags;
	}

}