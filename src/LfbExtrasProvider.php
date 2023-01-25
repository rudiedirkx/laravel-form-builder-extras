<?php

namespace rdx\LfbExtras;

use Illuminate\Support\ServiceProvider;
use Kris\LaravelFormBuilder\Events\AfterCollectingFieldRules;
use Kris\LaravelFormBuilder\Events\AfterFieldCreation;
use Kris\LaravelFormBuilder\Fields\ChildFormType;
use Kris\LaravelFormBuilder\Fields\CollectionType;
use Kris\LaravelFormBuilder\Fields\FormField;
use Kris\LaravelFormBuilder\Fields\InputType;
use Kris\LaravelFormBuilder\Fields\ParentType;
use Kris\LaravelFormBuilder\Fields\SelectType;
use Kris\LaravelFormBuilder\FormHelper;

class LfbExtrasProvider extends ServiceProvider {

	protected $withDataName;
	protected $withExtendFieldDependencies;

	public function register() {
		$this->callAfterResolving('laravel-form-helper', function(FormHelper $helper) {
			$helper->addCustomField('datalist', Fields\DatalistType::class);
			$helper->addCustomField('date', Fields\DateType::class);
			$helper->addCustomField('radios', Fields\RadiosType::class);
			$helper->addCustomField('checkboxes', Fields\CheckboxesType::class);
			$helper->addCustomField('multiselect', Fields\MultiSelectType::class);
		});
	}

	public function boot() {
		$events = $this->app['events'];
		$validator = $this->app['validator'];

		$this->loadViewsFrom(__DIR__ . '/../views', 'laravel-form-builder');

		$validator->extend('scalar', function($name, $value, $params) {
			return !is_array($value);
		});

		$events->listen(AfterFieldCreation::class, function(AfterFieldCreation $event) {
			$this->addTypeClass($event->getField());
		});

		$events->listen(AfterCollectingFieldRules::class, function(AfterCollectingFieldRules $event) {
			$this->addOptionValidation($event);
			$this->addScalarValidation($event);
			$this->addMaxlengthValidation($event);
			if ($this->withExtendFieldDependencies()) {
				$this->extendFieldDependencies($event);
			}
		});
	}

	/**
	 *
	 */
	protected function withDataName() : bool {
		if ($this->withDataName === null) {
			$this->withDataName = (bool) (config('laravel-form-builder.with_data_name') ?? false);
		}

		return $this->withDataName;
	}

	/**
	 *
	 */
	protected function withExtendFieldDependencies() : bool {
		if ($this->withExtendFieldDependencies === null) {
			$this->withExtendFieldDependencies = (bool) (config('laravel-form-builder.with_extend_field_dependencies') ?? false);
		}

		return $this->withExtendFieldDependencies;
	}

	/**
	 *
	 */
	protected function addTypeClass(FormField $field) : void {
		if ($field instanceof ParentType) {
			$field->setOption('copy_options_to_children', FALSE);
		}

		$options = $field->getOptions();

		if ($this->withDataName()) {
			$field->setOption('wrapper.data-name', $field->getRealName());
		}

		$wrapperClass = $options['wrapper']['class'] ?? '';
		if (strpos($wrapperClass, '__TYPECLASS__') !== false) {
			$wrapperClass = str_replace('__TYPECLASS__', 'form-' . $field->getType(), $wrapperClass);
			$field->setOption('wrapper.class', $wrapperClass);
		}
	}

	/**
	 *
	 */
	protected function addOptionValidation(AfterCollectingFieldRules $event) : void {
		$field = $event->getField();
		if ($field instanceof SelectType) {
			$ruler = $event->getRules();

			$allowed = [];
			foreach ($field->getOption('choices') as $value => $option) {
				if (is_array($option)) {
					$allowed = array_merge($allowed, array_keys($option));
				}
				else {
					$allowed[] = $value;
				}
			}

			$ruler->addFieldRule('in:' . implode(',', $allowed));

			$ruler->append(['attributes' => [$field->getName() => $field->getOption('label')]]);
		}
	}

	/**
	 *
	 */
	protected function addScalarValidation(AfterCollectingFieldRules $event) : void {
		$field = $event->getField();
		$rule = $this->requiresInputType($field);
		if ($rule !== null) {
			$ruler = $event->getRules();
			$rules = $ruler->getFieldRules();
			array_unshift($rules, $rule);
			$ruler->setFieldRules($rules);
		}
	}

	/**
	 *
	 */
	protected function requiresInputType(FormField $field) : ?string {
		if ($field instanceof Fields\CheckboxesType) {
			return 'array';
		}
		if ($field instanceof CollectionType) {
			return 'array';
		}
		if ($field instanceof ChildFormType) {
			return 'array';
		}

		if ($field instanceof SelectType) {
			$attr = $field->getOption('attr');
			if (isset($attr['multiple'])) {
				return 'array';
			}
		}

		if (!count($field->getAllAttributes())) {
			return null;
		}

		return 'scalar';
	}

	/**
	 *
	 */
	protected function addMaxlengthValidation(AfterCollectingFieldRules $event) : void {
		$field = $event->getField();
		if ($field instanceof InputType) {
			$ruler = $event->getRules();

			$rules = $ruler->getFieldRules();

			foreach ($rules as $rule) {
				if (!is_string($rule)) {
					continue;
				}
				if (strpos($rule, 'max:') === 0) {
					return;
				}
				if (in_array($rule, ['integer', 'numeric', 'file', 'image'])) {
					return;
				}
			}

			$ruler->addFieldRule('max:255');
		}
	}

	/**
	 *
	 */
	protected function extendFieldDependencies(AfterCollectingFieldRules $event) : void {
		$form = $event->getField()->getParent();
		if (!$form->getName()) return;

		$ruler = $event->getRules();
		$formHelper = $this->app['laravel-form-helper'];

		$formPrefix = $formHelper->transformToDotSyntax($form->getName()) . '.';

		$rules = $ruler->getFieldRules();

		$changed = false;
		foreach ($rules as $i => &$rule) {
			if (is_string($rule) && preg_match('#^(required_with(?:out)?):(.+)$#', $rule, $match)) {
				$rule = $match[1] . ':' . $formPrefix . $match[2];
				$changed = true;
			}
			unset($rule);
		}

		if ($changed) {
			$ruler->setFieldRules($rules);
		}
	}

}
