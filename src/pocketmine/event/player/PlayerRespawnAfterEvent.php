<?php

namespace pocketmine\event\player;

use pocketmine\level\Position;
use pocketmine\Player;

class PlayerRespawnAfterEvent extends PlayerEvent {
	public static $handlerList = null;
	
	public function __construct(Player $player) {
		$this->player = $player;
	}
}
