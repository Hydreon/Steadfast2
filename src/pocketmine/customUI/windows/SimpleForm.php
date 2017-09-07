<?php

namespace pocketmine\customUI\windows;

use pocketmine\customUI\CustomUI;
use pocketmine\customUI\elements\simpleForm\Button;
use pocketmine\Player;

class SimpleForm implements CustomUI {
	
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
	 * Add button to form
	 * 
	 * @param Button $button
	 */
	public function addButton(Button $button) {
		$this->buttons[] = $button;
		$this->json = '';
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
	
	/**
	 * To handle manual closing
	 * 
	 * @var Player $player
	 */
	public function close($player) {
	}
	
	/**
	 * 
	 * 
	 * @param int $response Button index
	 * @param Player $player
	 * @throws \Exception
	 */
	final public function handle($response, $player) {
		if (isset($this->buttons[$response])) {
			$this->buttons[$response]->handle(true, $player);
		} else {
			error_log(__CLASS__ . '::' . __METHOD__ . " Button with index {$response} doesn't exists.");
		}
	}
}
