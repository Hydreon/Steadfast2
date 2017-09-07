<?php

/**
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
 * @link   http://www.pocketmine.net/
 *
 *
 */

namespace pocketmine\event\entity;

use pocketmine\entity\Effect;
use pocketmine\entity\Entity;
use pocketmine\event\Cancellable;
use pocketmine\Player;
use pocketmine\item\enchantment\Enchantment;

class EntityDamageEvent extends EntityEvent implements Cancellable{
	public static $handlerList = null;

	const MODIFIER_BASE = 0;
	const MODIFIER_ARMOR = 1;
	const MODIFIER_STRENGTH = 2;
	const MODIFIER_WEAKNESS = 3;
	const MODIFIER_RESISTANCE = 4;
	// attack effect modifiers
	const MODIFIER_EFFECT_SHARPNESS = 5;
	const MODIFIER_EFFECT_SMITE = 6;
	const MODIFIER_EFFECT_ARTHROPODOS = 7;
	const MODIFIER_EFFECT_KNOCKBACK = 8;
	// defence effect modifiers
	const MODIFIER_EFFECT_PROTECTION = 9;
	const MODIFIER_EFFECT_FIRE_PROTECTION = 10;
	const MODIFIER_EFFECT_BLAST_PROTECTION = 11;
	const MODIFIER_EFFECT_PROJECTILE_PROTECTION = 12;
	const MODIFIER_EFFECT_FALL_PROTECTION = 13;

	
	const CAUSE_ENTITY_ATTACK = 1;
	const CAUSE_PROJECTILE = 2;
	const CAUSE_SUFFOCATION = 3;
	const CAUSE_FALL = 4;
	const CAUSE_FIRE = 5;
	const CAUSE_FIRE_TICK = 6;
	const CAUSE_LAVA = 7;
	const CAUSE_DROWNING = 8;
	const CAUSE_BLOCK_EXPLOSION = 9;
	const CAUSE_ENTITY_EXPLOSION = 10;
	const CAUSE_VOID = 11;
	const CAUSE_SUICIDE = 12;
	const CAUSE_MAGIC = 13;
	const CAUSE_CUSTOM = 14;
	const CAUSE_CONTACT = 15;


	private $cause;
	/** @var array */
	private $modifiers;
	private $originals;


	/**
	 * @param Entity    $entity
	 * @param int       $cause
	 * @param int|int[] $damage
	 *
	 * @throws \Exception
	 */
	public function __construct(Entity $entity, $cause, $damage){
		$this->entity = $entity;
		$this->cause = $cause;
		if(is_array($damage)){
			$this->modifiers = $damage;
		}else{
			$this->modifiers = [
				self::MODIFIER_BASE => $damage
			];
		}

		$this->originals = $this->modifiers;

		if(!isset($this->modifiers[self::MODIFIER_BASE])){
			throw new \InvalidArgumentException("BASE Damage modifier missing");
		}

		if($entity->hasEffect(Effect::DAMAGE_RESISTANCE)){
			$this->setDamage(-($this->getDamage(self::MODIFIER_BASE) * 0.20 * ($entity->getEffect(Effect::DAMAGE_RESISTANCE)->getAmplifier() + 1)), self::MODIFIER_RESISTANCE);
		}
		
		if ($entity instanceof Player && $cause !== self::CAUSE_VOID) {
			$enchantments = $entity->getProtectionEnchantments();
			if (!is_null($enchantments[Enchantment::TYPE_ARMOR_PROTECTION])) {
				$enchantment = $enchantments[Enchantment::TYPE_ARMOR_PROTECTION];
				$this->setDamage(-1 * $enchantment->getLevel(), self::MODIFIER_EFFECT_PROTECTION);
			}
			
			$enchantment = null;
			$multiplier = 2;
			$modifierId = 0;
			switch($cause) {
				case self::CAUSE_FIRE:
				case self::CAUSE_FIRE_TICK:
				case self::CAUSE_LAVA:
					$enchantment = $enchantments[Enchantment::TYPE_ARMOR_FIRE_PROTECTION];
					$multiplier = 2;
					$modifierId = self::MODIFIER_EFFECT_FIRE_PROTECTION;
					break;
				case self::CAUSE_FALL:
					$enchantment = $enchantments[Enchantment::TYPE_ARMOR_FALL_PROTECTION];
					$multiplier = 3;
					$modifierId = self::MODIFIER_EFFECT_FALL_PROTECTION;
					break;
				case self::CAUSE_ENTITY_EXPLOSION:
				case self::CAUSE_BLOCK_EXPLOSION:
					$enchantment = $enchantments[Enchantment::TYPE_ARMOR_EXPLOSION_PROTECTION];
					$multiplier = 2;
					$modifierId = self::MODIFIER_EFFECT_BLAST_PROTECTION;
					break;
				case self::CAUSE_PROJECTILE:
					$enchantment = $enchantments[Enchantment::TYPE_ARMOR_PROJECTILE_PROTECTION];
					$multiplier = 2;
					$modifierId = self::MODIFIER_EFFECT_PROJECTILE_PROTECTION;
					break;
			}
			
			if (!is_null($enchantment)) {
				$this->setDamage(-1 * $enchantment->getLevel() * $multiplier, $modifierId);
			}
		}
	}

	/**
	 * @return int
	 */
	public function getCause(){
		return $this->cause;
	}

	/**
	 * @param int $type
	 *
	 * @return int
	 */
	public function getOriginalDamage($type = self::MODIFIER_BASE){
		if(isset($this->originals[$type])){
			return $this->originals[$type];
		}

		return 0;
	}

	/**
	 * @param int $type
	 *
	 * @return int
	 */
	public function getDamage($type = self::MODIFIER_BASE){
		if(isset($this->modifiers[$type])){
			return $this->modifiers[$type];
		}

		return 0;
	}

	/**
	 * @param float $damage
	 * @param int   $type
	 *
	 * @throws \UnexpectedValueException
	 */
	public function setDamage($damage, $type = self::MODIFIER_BASE){
		$this->modifiers[$type] = $damage;
	}

	/**
	 * @param int $type
	 *
	 * @return bool
	 */
	public function isApplicable($type){
		return isset($this->modifiers[$type]);
	}

	/**
	 * @return int
	 */
	public function getFinalDamage(){
		$damage = 0;
		foreach($this->modifiers as $type => $d){
			$damage += $d;
		}

		return max($damage, 0);
	}

}