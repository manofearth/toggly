<?php

namespace TogglSync;

class DateInterval extends \DateInterval {

	/**
	 * @return int
	 */
	public function toMinutes() {
		return $this->h * 60 + $this->i + ($this->s < 30 ? 0 : 1);
	}

	/**
	 * @return int
	 */
	public function toSeconds() {
		return $this->h * 3600 + $this->i * 60 + $this->s;
	}

	/**
	 * @param int $seconds
	 *
	 * @return DateInterval
	 * @throws Exception
	 */
	public static function fromSeconds($seconds) {
		$interval = static::fromMinutes((int)($seconds / 60));
		$interval->s = $seconds % 60;
		return $interval;
	}

	/**
	 * @param int $minutes
	 *
	 * @return DateInterval
	 * @throws Exception
	 */
	public static function fromMinutes($minutes) {

		if($minutes >= 1440) {
			throw new Exception('Minutes interval must be within one day range');
		}

		$intervalSpecification = 'PT';

		/** @noinspection SummerTimeUnsafeTimeManipulationInspection */
		$hours = (int)($minutes / 60);
		if($hours > 0) {
			$intervalSpecification .= $hours . 'H';
		}

		$_minutes = $minutes % 60;
		if($minutes > 0) {
			$intervalSpecification .= $_minutes . 'M';
		}

		return new static($intervalSpecification);
	}

}
