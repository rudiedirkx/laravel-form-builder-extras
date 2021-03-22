<?php

namespace rdx\LfbExtras\Fields;

use Kris\LaravelFormBuilder\Fields\SelectType;

class RadiosType extends SelectType {

	protected function getTemplate() {
		return 'laravel-form-builder::radios';
	}

}
