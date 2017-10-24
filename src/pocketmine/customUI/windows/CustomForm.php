<?php

namespace pocketmine\customUI\windows;

use pocketmine\customUI\CustomUI;
use pocketmine\customUI\elements\UIElement;
use pocketmine\Player;

class CustomForm implements CustomUI {
	
	/** @var string */
	protected $title = '';
	/** @var UIElement[] */
	protected $elements = [];
	/** @var string */
	protected $json = '';
	/** @var string Only for server settings*/
	protected $iconURL = '';
	
	public function __construct($title) {
		$this->title = $title;
	}
	
	/**
	 * Add element to form
	 * 
	 * @param UIElement $element
	 */
	public function addElement(UIElement $element) {
		$this->elements[] = $element;
		$this->json = '';
	}
	
	/**
	 * Only for server settings
	 * @param string $url
	 */
	public function addIconUrl($url) {
		$this->iconURL = $url;
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
		if ($this->iconURL != '') {
			$data['icon'] = [
				"type" => "url",
				"data" => $this->iconURL
			];
		}
		foreach ($this->elements as $element) {
			$data['content'][] = $element->getDataToJson();
		}
		return $this->json = json_encode($data);
	}
	
	/**
	 * To handle manual closing
	 * 
	 * @var Player $player
	 */
	public function close($player) {
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
