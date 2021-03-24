<?php

namespace rdx\LfbExtras;

use Illuminate\Support\ServiceProvider;
use Kris\LaravelFormBuilder\Events\AfterCollectingFieldRules;
use Kris\LaravelFormBuilder\Events\AfterFieldCreation;
use Kris\LaravelFormBuilder\Fields\FormField;
use Kris\LaravelFormBuilder\Fields\InputType;
use Kris\LaravelFormBuilder\Fields\ParentType;
use Kris\LaravelFormBuilder\Fields\SelectType;
use Kris\LaravelFormBuilder\FormHelper;

class LfbExtrasProvider extends ServiceProvider {

	protected $withDataName;

	public function boot() {
		$events = $this->app['events'];
		$form = $this->app['form'];

		$this->callAfterResolving('laravel-form-helper', function(FormHelper $helper) {
			$helper->addCustomField('datalist', Fields\DatalistType::class);
			$helper->addCustomField('date', Fields\DateType::class);
			$helper->addCustomField('radios', Fields\RadiosType::class);
			$helper->addCustomField('checkboxes', Fields\CheckboxesType::class);
		});

		$this->loadViewsFrom(__DIR__ . '/../views', 'laravel-form-builder');

		$form->macro('checkboxes', function($name, array $options, array $selected = []) use ($form) {
			$html = '<div class="options-wrapper">' . "\n";
			foreach ($options as $value => $label) {
				$id = "for-$name-$value";
				$html .= '<div class="form-option">';
				$html .= $form->checkbox($name . '[]', $value, in_array($value, $selected), ['id' => $id]);
				$html .= ' ';
				$html .= $form->label($id, $label);
				$html .= "</div>\n";
			}
			$html .= "</div>\n";
			return $html;
		});

		$form->macro('radios', function($name, array $options, $selected = null) use ($form) {
			$html = '<div class="options-wrapper">' . "\n";
			foreach ($options as $value => $label) {
				$id = "for-$name-$value";
				$html .= '<div class="form-option">';
				$html .= $form->radio($name, $value, $value == $selected, ['id' => $id]);
				$html .= ' ';
				$html .= $form->label($id, $label);
				$html .= "</div>\n";
			}
			$html .= "</div>\n";
			return $html;
		});

		$events->listen(AfterFieldCreation::class, function(AfterFieldCreation $event) {
			$this->addTypeClass($event->getField());
		});

		$events->listen(AfterCollectingFieldRules::class, function(AfterCollectingFieldRules $event) {
			$this->addOptionValidation($event);
			$this->addMaxlengthValidation($event);
			$this->extendFieldDependencies($event);
		});
	}

	/**
	 *
	 */
	protected function withDataName() {
		if ($this->withDataName === null) {
			$this->withDataName = config('laravel-form-builder.with_data_name') ?? false;
		}

		return $this->withDataName;
	}

	/**
	 *
	 */
	protected function addTypeClass(FormField $field) {
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
	protected function addOptionValidation(AfterCollectingFieldRules $event) {
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

			if ($field instanceof Fields\CheckboxesType) {
				$ruler->addFieldRule('array');
			}
			$ruler->addFieldRule('in:' . implode(',', $allowed));

			$ruler->append(['attributes' => [$field->getName() => $field->getOption('label')]]);
		}
	}

	/**
	 *
	 */
	protected function addMaxlengthValidation(AfterCollectingFieldRules $event) {
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
	protected function extendFieldDependencies(AfterCollectingFieldRules $event) {
		$formHelper = $this->app['laravel-form-helper'];

		$ruler = $event->getRules();
		$fieldName = $formHelper->transformToDotSyntax($event->getField()->getName());
		if (strpos($fieldName, '.') === false) {
			return;
		}

		$prefix = preg_replace('#\.[^\.]+$#', '.', $fieldName);
		$rules = $ruler->getFieldRules();

		foreach ($rules as $i => &$rule) {
			if (is_string($rule) && preg_match('#^(required_with):(.+)$#', $rule, $match)) {
				$rule = $match[1] . ':' . $prefix . $match[2];
			}
			unset($rule);
		}

		$ruler->setFieldRules($rules);
	}

}
