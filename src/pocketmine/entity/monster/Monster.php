<?php

namespace pocketmine\entity\monster;

use pocketmine\entity\Entity;

interface Monster{

	public function attackEntity(Entity $player);

	public function getDamage(int $difficulty = null);
	public function getMinDamage(int $difficulty = null);
	public function getMaxDamage(int $difficulty = null);

	public function setDamage($damage, int $difficulty = null);
	public function setMinDamage($damage, int $difficulty = null);
	public function setMaxDamage($damage, int $difficulty = null);

}