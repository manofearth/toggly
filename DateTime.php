<?php

namespace TogglSync;


class DateTime extends \DateTime {

	/**
	 * @return int
	 */
	public function getTimestampMicroseconds() {
		return $this->getTimestamp() * 1000;
	}
}