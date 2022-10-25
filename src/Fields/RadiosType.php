<?php

namespace rdx\LfbExtras\Fields;

use Kris\LaravelFormBuilder\Fields\SelectType;

class RadiosType extends SelectType {

	public function getDefaults() {
		return parent::getDefaults() + [
			'item_attributes' => [],
			'option_attributes' => [],
			'label_attributes' => [],
		];
	}

	protected function getTemplate() {
		return 'laravel-form-builder::radios';
	}

}
