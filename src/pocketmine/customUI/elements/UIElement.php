<?php

namespace pocketmine\customUI\elements;

abstract class UIElement {
	
	protected $text = '';
	
	abstract public function getDataToJson();
	
}
