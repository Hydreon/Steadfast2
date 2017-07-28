<?php

namespace pocketmine\customUI\windows;

use pocketmine\customUI\CustomUI;
use pocketmine\customUI\elements\UIElement;

class CustomForm implements CustomUI {
	
	/** @var string */
	protected $title = '';
	/** @var UIElement[] */
	protected $elements = [];
	/** @var string */
	protected $json = '';
	
	public function __construct($title) {
		$this->title = $title;
	}
	
	/**
	 * Add element to form
	 * 
	 * @param Button $button
	 */
	public function addElement(UIElement $element) {
		$this->elements[] = $element;
		$this->json = '';
	}
	
	final public function toJSON() {
		if ($this->json != '') {
			return $this->json;
		}
		$data = [
			'type' => 'custom_form',
			'title' => $this->title,
			'content' => []
		];
		foreach ($this->elements as $element) {
			$data['content'][] = $element->getDataToJson();
		}
		return $this->json = json_encode($data);
	}
	
	/**
	 * @notice It not final because some logic may 
	 * depends on some elements at the same time
	 * 
	 * @param array $response
	 * @param Player $player
	 */
	public function handle($response, $player) {
		foreach ($response as $elementKey => $elementValue) {
			if (isset($this->elements[$elementKey])) {
				$this->elements[$elementKey]->handle($elementValue, $player);
			} else {
				error_log(__CLASS__ . '::' . __METHOD__ . " Element with index {$elementKey} doesn't exists.");
			}
		}
	}

}
