<?php

namespace TogglSync\Youtrack;


use TogglSync\DateInterval;
use TogglSync\DateTime;
use stdClass;

class WorkItem {
	/**
	 * @var DateTime
	 */
	private $date;
	/**
	 * @var DateInterval
	 */
	private $duration;
	/**
	 * @var null|string
	 */
	private $description;
	/**
	 * @var null|string
	 */
	private $workType;


	/**
	 * @param DateTime     $date
	 * @param DateInterval $duration
	 * @param string       $description
	 * @param string       $workType
	 */
	public function __construct(
		DateTime $date,
		DateInterval $duration,
		$description = null,
		$workType = null) {


		$this->date = $date;
		$this->duration = $duration;
		$this->description = $description;
		$this->workType = $workType;
	}

	public static function fromStdClass(stdClass $workItemData) {
		return new static(
			(new DateTime())->setTimestamp($workItemData->date / 1000),
			DateInterval::fromMinutes($workItemData->duration),
			$workItemData->description
		);
	}

	/**
	 * @return string
	 */
	public function getDescription() {
		return $this->description;
	}

	/**
	 * @return DateInterval
	 */
	public function getDuration() {
		return $this->duration;
	}

	/**
	 * @return DateTime
	 */
	public function getDate() {
		return $this->date;
	}

	/**
	 * @return null|string
	 */
	public function getWorkType() {
		return $this->workType;
	}

}