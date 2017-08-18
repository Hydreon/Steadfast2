<?php

namespace pocketmine\customUI\elements\customForm;

use pocketmine\customUI\elements\UIElement;

class StepSlider extends UIElement {
	
	/** @var string[] */
	protected $steps = [];
	/** @var integer Step index */
	protected $defaultStepIndex = 0;
	
	/**
	 * 
	 * @param string $text
	 * @param string[] $steps
	 */
	public function __construct($text, $steps = []) {
		$this->text = $text;
		$this->steps = $steps;
	}
	
	/**
	 * 
	 * @param string $stepText
	 * @param boolean $isDefault
	 */
	public function addStep($stepText, $isDefault = false) {
		if ($isDefault) {
			$this->defaultStepIndex = count($this->steps);
		}
		$this->steps[] = $stepText;
	}
	
	/**
	 * 
	 * @param string $stepText
	 * @return boolean
	 */
	public function setStepAsDefault($stepText) {
		$index = array_search($stepText, $this->steps);
		if ($index === false) {
			return false;
		}
		$this->defaultStepIndex = $index;
		return true;
	}
	
	/**
	 * Replace all steps
	 * 
	 * @param string[] $steps
	 */
	public function setSteps($steps) {
		$this->steps = $steps;
	}

	/**
	 * 
	 * @return array
	 */
	final public function getDataToJson() {
		return [
			'type' => 'step_slider',
			'text' => $this->text,
			'steps' => $this->steps,
			'default' => $this->defaultStepIndex
		];
	}

	public function handle($value, $player) {
		
	}

}
