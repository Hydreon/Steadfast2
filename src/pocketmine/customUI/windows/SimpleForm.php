<?php

namespace pocketmine\customUI\windows;

use pocketmine\customUI\CustomUI;
use pocketmine\customUI\elements\simpleForm\Button;

abstract class SimpleForm implements CustomUI {
	
	/** @var string */
	protected $title = '';
	/** @var string */
	protected $content = '';
	/** @var Button[] */
	protected $buttons = [];
	/** @var string */
	protected $json = '';
	
	/**
	 * 
	 * @param string $title
	 * @param string $content
	 */
	public function __construct($title, $content) {
		$this->title = $title;
		$this->content = $content;
	}
	
	/**
	 * Add button to for
	 * 
	 * @param Button $button
	 */
	public function addButton(Button $button) {
		$this->buttons[] = $button;
	}

	/**
	 * Convert class to JSON string
	 * 
	 * @return string
	 */
	final public function toJSON() {
		if ($this->json != '') {
			return $this->json;
		}
		$data = [
			'type' => 'form',
			'title' => $this->title,
			'content' => $this->content,
			'buttons' => []
		];
		foreach ($this->buttons as $button) {
			$data['buttons'][] = $button->getDataToJson();
		}
		return $this->json = json_encode($data);
	}
	
	abstract public function handle($response, $player);
}
