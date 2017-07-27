<?php

namespace pocketmine\customUI;

interface CustomUI {

	public function handle($response, $player);
	
	public function toJSON();
	
}
