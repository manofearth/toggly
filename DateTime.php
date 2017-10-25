<?php

namespace TogglSync;


class DateTime extends \DateTime {

	/**
	 * @return int
	 */
	public function getTimestampMicroseconds() {
		return $this->getTimestamp() * 1000;
	}

	/**
	 * @param DateTime $anotherDate
	 *
	 * @return bool
	 */
	public function isSameDay(DateTime $anotherDate) {
		$thisStartOfDay    = \DateTimeImmutable::createFromMutable($this)->setTime(0, 0);
		$anotherStartOfDay = \DateTimeImmutable::createFromMutable($anotherDate)->setTime(0, 0);

		/** @noinspection TypeUnsafeComparisonInspection */
		return $thisStartOfDay == $anotherStartOfDay;
	}
}
