<?php

namespace rdx\LfbExtras\Fields;

use Kris\LaravelFormBuilder\Fields\FormField;

class DatalistType extends FormField {

	protected function getTemplate() {
		return 'laravel-form-builder::datalist';
	}

	public function getAllAttributes() {
		return [];
	}

}
