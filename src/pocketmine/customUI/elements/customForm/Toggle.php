<?php

namespace pocketmine\customUI\elements\customForm;

use pocketmine\customUI\elements\UIElement;

class Toggle extends UIElement {
	
	/** @var boolean */
	protected $defaultValue = false;
	
	/**
	 * 
	 * @param string $text
	 * @param bool $value
	 */
	public function __construct($text, bool $value = false) {
		$this->text = $text;
		$this->defaultValue = $value;
	}
	
	/**
	 * 
	 * @param bool $value
	 */
	public function setDefaultValue(bool $value) {
		$this->defaultValue = $value;
	}
	
	/**
	 * 
	 * @return array
	 */
	final public function getDataToJson() {
		return [
			"type" => "toggle",
			"text" => $this->text,
			"default" => $this->defaultValue
		];
	}

	public function handle($value, $player) {
		
	}

}
