<?php

namespace TogglSync;

use TogglSync\Toggl\TimeEntry;

class VastSyncer extends Syncer {

	/**
	 * @inheritdoc
	 */
	protected function extractWorkType(TimeEntry $timeEntry) {

		$workTypesMap = [
			'development'   => 'Development',
			'testing'       => 'Testing',
			'inspection'    => 'Inspection',
			'bugfixing'     => 'Bug fixing',
			'inspfixing'    => 'Inspection fixing',
			'planning'      => 'Planning',
			'deploying'     => 'Merging, Deploying',
			'learning'      => 'Learning',
			'communicating' => 'Communicating',
			'sysadmin'      => 'System administration',
		];

		$workTypesTags = array_intersect($timeEntry->getTags(), array_flip($workTypesMap));

		if (count($workTypesTags) === 0) {
			return null;
		} else {
			return $workTypesMap[$workTypesTags[0]];
		}
	}

	/**
	 * @return string
	 */
	protected function getProjectPrefix() {
		return 'VST';
	}

	/**
	 * @inheritdoc
	 */
	protected function extractIssueCode(TimeEntry $timeEntry) {
		$matches = [];
		if( ! preg_match('/^(' . $this->getProjectPrefix() . '-\\d+)/', $timeEntry->getDescription(), $matches)) {
			return null;
		}
		return $matches[1];
	}

	/**
	 * @param TimeEntry $timeEntry
	 *
	 * @return string
	 */
	protected function extractIssueSummary(TimeEntry $timeEntry) {
		$matches = [];
		if( ! preg_match('/^' . $this->getProjectPrefix() . '-\d+ *(.*)$/', $timeEntry->getDescription(), $matches)) {
			return '';
		}
		return $matches[1];
	}

	/**
	 * @param TimeEntry $timeEntry
	 *
	 * @return bool
	 * @throws \Zend\Json\Exception\RuntimeException
	 * @throws \TogglSync\Exception
	 * @throws \Zend\Http\Exception\RuntimeException
	 * @throws \Zend\Http\Client\Exception\RuntimeException
	 * @throws \TogglSync\Youtrack\Exception
	 */
	protected function skipTimeEntry(TimeEntry $timeEntry) {

		$issueCode = $this->extractIssueCode($timeEntry);

		if ($issueCode === null) {
			return true;
		}

		$workType = $this->extractWorkType($timeEntry);
		$workDescription = iconv('utf-8', 'cp866', $this->extractWorkDescription($timeEntry));

		echo "{$issueCode}: {$timeEntry->getDuration()->toMinutes()}m $workType $workDescription";

		if( ! $this->youtrack->issueExists($issueCode)) {
			echo ' not found' . PHP_EOL;
			return true;
		}

		$existentWorkItems = $this->youtrack->getWorkItemsOfIssue($issueCode);
		foreach ($existentWorkItems as $workItem) {
			/** @noinspection TypeUnsafeComparisonInspection */
			if ($timeEntry->getStop()->isSameDay($workItem->getDate())
				&& $timeEntry->getDuration()->toMinutes() == $workItem->getDuration()->toMinutes()) {
				echo ' skipped' . PHP_EOL;
				return true;
			}
		}

		echo PHP_EOL;
		return false;
	}

	/**
	 * @inheritdoc
	 */
	protected function extractWorkDate(TimeEntry $timeEntry) {
		return $timeEntry->getStop();
	}

	/**
	 * @inheritdoc
	 * @throws \TogglSync\Exception
	 */
	protected function extractWorkDuration(TimeEntry $timeEntry) {
		return $timeEntry->getDuration();
	}

	/**
	 * @inheritdoc
	 * @throws \TogglSync\Youtrack\Exception
	 * @throws \Zend\Http\Client\Exception\RuntimeException
	 * @throws \Zend\Http\Exception\RuntimeException
	 * @throws \Zend\Json\Exception\RuntimeException
	 */
	protected function extractWorkDescription(TimeEntry $timeEntry) {
		$issue = $this->youtrack->getIssue($this->extractIssueCode($timeEntry));
		if($issue->getSummary() === $this->extractIssueSummary($timeEntry)) {
			return '';
		} else {
			return $this->extractIssueSummary($timeEntry);
		}
	}
}
