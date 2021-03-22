<?php

namespace rdx\LfbExtras\Fields;

use Kris\LaravelFormBuilder\Fields\SelectType;

class CheckboxesType extends SelectType {

	protected function getTemplate() {
		return 'laravel-form-builder::checkboxes';
	}

}
