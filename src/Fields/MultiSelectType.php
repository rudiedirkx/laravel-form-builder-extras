<?php

namespace rdx\LfbExtras\Fields;

use Kris\LaravelFormBuilder\Fields\SelectType;
use Kris\LaravelFormBuilder\Form;

class MultiSelectType extends CheckboxesType {

	public function __construct($name, $type, Form $parent, array $options = []) {
		$options['attr']['multiple'] = '';
		$options['attr']['name'] = $name . '[]';
		parent::__construct($name, $type, $parent, $options);
	}

	protected function getTemplate() {
		return 'laravel-form-builder::select';
	}

}
