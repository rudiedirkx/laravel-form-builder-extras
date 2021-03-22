<?php

namespace rdx\LfbExtras\Fields;

use Carbon\Carbon;
use Kris\LaravelFormBuilder\Fields\InputType;
use Kris\LaravelFormBuilder\Form;

class DateType extends InputType {

	public function __construct($name, $type, Form $parent, array $options = []) {
		$this->valueClosure = function($value) {
			return $this->transformDateFieldValue($value);
		};

		parent::__construct($name, $type, $parent, $options);
	}

	public function transformDateFieldValue($value) {
		return $value instanceof Carbon ? $value->format('Y-m-d') : $value;
	}

}
