<?php

namespace pocketmine\customUI\elements;

use pocketmine\Player;

abstract class UIElement {
	
	protected $text = '';
	
	/**
	 * @return array
	 */
	abstract public function getDataToJson();
	
	/**
	 * @param Player $player
	 */
	abstract public function handle($value, $player);
	
}
