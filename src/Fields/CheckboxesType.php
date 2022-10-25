<?php

namespace rdx\LfbExtras\Fields;

use Illuminate\Database\Eloquent\Collection;
use Kris\LaravelFormBuilder\Fields\SelectType;

class CheckboxesType extends SelectType {

	public function setValue($value) {
		if ($value instanceof Collection) {
			$value = $value->modelKeys();
		}

		parent::setValue($value);
	}

	public function getDefaults() {
		return parent::getDefaults() + [
			'item_attributes' => [],
			'option_attributes' => [],
			'label_attributes' => [],
		];
	}

	protected function getTemplate() {
		return 'laravel-form-builder::checkboxes';
	}

}
