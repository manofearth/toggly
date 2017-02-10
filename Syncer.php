<?php

namespace TogglSync;

use TogglSync\Toggl\Gateway as TogglGateway;
use TogglSync\Toggl\TimeEntry;
use TogglSync\Youtrack\Gateway as YoutrackGateway;

abstract class Syncer {

	/**
	 * @var TogglGateway
	 */
	protected $toggl;
	/**
	 * @var YoutrackGateway
	 */
	protected $youtrack;


	/**
	 * @param TogglGateway    $toggl
	 * @param YoutrackGateway $youtrack
	 */
	public function __construct(TogglGateway $toggl, YoutrackGateway $youtrack) {
		$this->toggl = $toggl;
		$this->youtrack = $youtrack;
	}

	public function syncForPeriod(DateTime $startDate, DateTime $endDate) {
		foreach ($this->toggl->getTimeEntries($startDate, $endDate) as $timeEntry) {

			if ($this->skipTimeEntry($timeEntry)) {
				continue;
			}

			$newWorkItem = new \TogglSync\Youtrack\WorkItem(
				$this->extractWorkDate($timeEntry),
				$this->extractWorkDuration($timeEntry),
				$this->extractWorkDescription($timeEntry),
				$this->extractWorkType($timeEntry)
			);

			$this->youtrack->addWorkItemForIssue($this->extractIssueCode($timeEntry), $newWorkItem);
		}
	}

	/**
	 * @param TimeEntry $timeEntry
	 *
	 * @return bool
	 */
	protected abstract function skipTimeEntry(TimeEntry $timeEntry);

	/**
	 * @param TimeEntry $timeEntry
	 *
	 * @return null|string
	 */
	protected abstract function extractWorkType(TimeEntry $timeEntry);

	/**
	 * @param TimeEntry $timeEntry
	 *
	 * @return null|string
	 */
	protected abstract function extractIssueCode(TimeEntry $timeEntry);

	/**
	 * @param TimeEntry $timeEntry
	 *
	 * @return \TogglSync\DateTime
	 */
	protected abstract function extractWorkDate(TimeEntry $timeEntry);

	/**
	 * @param TimeEntry $timeEntry
	 *
	 * @return \TogglSync\DateInterval
	 */
	protected abstract function extractWorkDuration(TimeEntry $timeEntry);

	/**
	 * @param TimeEntry $timeEntry
	 *
	 * @return string
	 */
	protected abstract function extractWorkDescription(TimeEntry $timeEntry);
}