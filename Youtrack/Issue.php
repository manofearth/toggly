<?php

namespace TogglSync\Youtrack;


use stdClass;

class Issue {

	/**
	 * @var string
	 */
	protected $id;
	/**
	 * @var string
	 */
	protected $summary;

	/**
	 * @param string $id
	 * @param string $summary
	 */
	public function __construct($id, $summary) {
		$this->id = $id;
		$this->summary = $summary;
	}

	/**
	 * @param stdClass $data
	 *
	 * @return static
	 */
	public static function fromStdClass(stdClass $data) {

		$fields = ['summary' => null];

		foreach ($data->field as $field) {
			if( ! array_key_exists($field->name, $fields)) {
				continue;
			}
			$fields[$field->name] = $field->value;
		}

		return new static(
			$data->id,
			$fields['summary']
		);
	}

	/**
	 * @return string
	 */
	public function getSummary() {
		return $this->summary;
	}

	/**
	 * @return string
	 */
	public function getId() {
		return $this->id;
	}
}