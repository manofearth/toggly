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
	 * @return array
	 */
	protected function getProjectPrefixes() {
        // @todo: query for project codes to which we have access in YouTrack and just use those
        // @todo: Define project property properly and add getter
	    if (is_string($this->youtrack->project)) {
            return array($this->youtrack->project);
        }

        return $this->youtrack->project;
	}

	/**
	 * @inheritdoc
	 */
	protected function extractIssueCode(TimeEntry $timeEntry) {
	    // Try to match against all possible project codes and return the first one that matches.
        $projectPrefixes = $this->getProjectPrefixes();

        foreach ($projectPrefixes as $projectPrefix) {
            $matches = [];
            if (!preg_match('/^(' . $projectPrefix . '-\\d+)/', $timeEntry->getDescription(), $matches)) {
                continue;
            }
            return $matches[1];
        }

        return null;
	}

	/**
	 * @param TimeEntry $timeEntry
	 *
	 * @return string
	 */
	protected function extractIssueSummary(TimeEntry $timeEntry) {
        // Try to match against all possible project codes and return the first one that matches.
        $projectPrefixes = $this->getProjectPrefixes();

        foreach ($projectPrefixes as $projectPrefix) {
            $matches = [];
            if (!preg_match('/^' . $projectPrefix . '-\d+ *(.*)$/', $timeEntry->getDescription(),
                $matches)) {
                continue;
            }
            return $matches[1];
        }

        return '';
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
	    // First and foremost; skip the running time entry.
        if ($timeEntry->isRunning()) {
            return true;
        }

		$issueCode = $this->extractIssueCode($timeEntry);

		if ($issueCode === null) {
			return true;
		}

		$workType = $this->extractWorkType($timeEntry);
		$workDescription = iconv('utf-8', 'cp866', $this->extractWorkDescription($timeEntry));

        $timeEntryInMinutes = $timeEntry->getDuration()->toMinutes();
        echo "{$issueCode}: {$timeEntryInMinutes}m $workType $workDescription";

		if( ! $this->youtrack->issueExists($issueCode)) {
			echo ' not found' . PHP_EOL;
			return true;
		}

		// Record how many tasks with the same key, date, and duration we've already skipped. Keep skipping until we've
        // skipped every match.
        static $skipped = [];
        $timeEntrySkipKey = $issueCode . '_' . $timeEntry->getStop()->format('Y-m-d') . '_' . $timeEntryInMinutes;
        $skipped += [$timeEntrySkipKey => 0];

        $existentWorkItems = $this->youtrack->getWorkItemsOfIssue($issueCode);
        $matched = 0;
		foreach ($existentWorkItems as $workItem) {
			/** @noinspection TypeUnsafeComparisonInspection */
			if ($timeEntry->getStop()->isSameDay($workItem->getDate())
				&& $timeEntryInMinutes == $workItem->getDuration()->toMinutes()) {
                $matched++;
			}
		}

		// Skip until the number matched is the number skipped. After that, we should allow all entries through.
        if ($skipped[$timeEntrySkipKey] < $matched) {
            $skipped[$timeEntrySkipKey]++;
            echo ' skipped' . PHP_EOL;
            return true;
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
