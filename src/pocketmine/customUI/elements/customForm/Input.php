<?php

namespace pocketmine\customUI\elements\customForm;

use pocketmine\customUI\elements\UIElement;

class Input extends UIElement {
	
	/** @var string Text displaying in input without text */
	protected $placeholder = '';
	/** @var string Default text for input */
	protected $defaultText = '';
	
	/**
	 * 
	 * @param string $text
	 * @param string $placeholder
	 * @param string $defaultText
	 */
	public function __construct($text, $placeholder, $defaultText = '') {
		$this->text = $text;
		$this->placeholder = $placeholder;
		$this->defaultText = $defaultText;
	}
	
	/**
	 * 
	 * @return array
	 */
	final public function getDataToJson() {
		return [
			"type" => "input",
			"text" => $this->text,
			"placeholder" => $this->placeholder,
			"default" => $this->defaultText
		];
	}
	
	public function handle($value, $player) {
		
	}

}
