<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
*/


namespace pocketmine\item;

use pocketmine\nbt\tag\Compound;
use pocketmine\nbt\tag\IntTag;
use pocketmine\item\enchantment\Enchantment;

abstract class Armor extends Item{
	
	public function __construct($id, $meta = 0, $count = 1, $name = "Unknown", $obtainTime = null) {
		parent::__construct($id, $meta, $count, $name, $obtainTime);
		$this->checkDamage();
	}

	public function getMaxStackSize(){
		return 1;
	}
	
	public function removeDurability($count = 1) {
		$ench = $this->getEnchantment(Enchantment::TYPE_UNBREAKING);
		if (!is_null($ench)) {
			$enchLevel = $ench->getLevel();
			$chance = 60 + 40 / ($enchLevel + 1);
			if (mt_rand(1, 100) > $chance) {
				return;
			}
		}
		$this->meta += $count;
		$this->checkDamage();
	}
	
	public function isArmor(){
		return true;
	}
	
	public function setDamage($meta) {
		parent::setDamage($meta);
		$this->checkDamage();
	}

	public function checkDamage() {
		if ($this->meta == 0) {
			if ($this->hasCompound()) {
				$tag = $this->getNamedTag();
				if (isset($tag->Damage)) {
					unset($tag->Damage);
					parent::setCompound($tag);
				}
			}
		} else {
			if (!$this->hasCompound()) {
				$tag = new Compound("", []);
			} else {
				$tag = $this->getNamedTag();
			}
			$tag->Damage = new IntTag("Damage", $this->meta);
			parent::setCompound($tag);
		}
	}
	
	public function setCompound($tags) {
		if($tags instanceof Compound){
			if (isset($tags['Damage'])) {
				$this->meta = $tags['Damage'];
			}
		}
		parent::setCompound($tags);
		$this->checkDamage();
		return $this;
	}
	
	/**
	 * The following types of damage are reduced by armor and, consequently, damage the armor itself:
	 *  - Direct attacks from mobs and players
	 *  - This includes the Strength effect and the Sharpness enchantment.
	 *  - Getting hit with an arrow
	 *  - This includes extra damage from enchantments.
	 *  - Getting hit with a fireball from a ghast or blaze, a fire charge, or ender acid
	 *  - Touching fire, lava or cacti
	 *  - Explosions
	 *  - Getting struck by lightning
	 *  - Getting hit with a falling anvil
	 *  - Getting hit by chicken eggs
	 *  - Getting hit with a fishing rod lure
	 * 
	 * The following types of damage are not reduced by armor and have no effect on the armor itself:
	 *  - Ongoing damage from being on fire
	 *  - Suffocating inside a block
	 *  - Drowning in water
	 *  - Starvation
	 *  - Falling (including ender pearls)
	 *  - Falling to the void
	 *  - Status effects
	 *  - Instant damage from a potion of Harming
	 *  - /kill
	 *  - Standing next to where lightning strikes.
	 *  - Getting hit by snowballs.
	 * 
	 * However, all sources of damage will damage all armor pieces worn in Pocket Edition.
	 * 
	 * Any hit from a damage source that can be blocked by armor will remove 
	 * one point of durability from each piece of armor worn for every 4 (2 hearts) 
	 * of incoming damage (rounded down, but never below 1). 
	 * 
	 *  Material	Helmet	Chestplate	Leggings	Boots
	 *	Leather		56		81			76			66
	 *	Golden		78		113			106			92
	 *	Chain/Iron	166		241			226			196
	 *	Diamond		364		529			496			430
	 */
	
}