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

namespace pocketmine\level\particle;

use pocketmine\math\Vector3;

abstract class Particle extends Vector3 {

	const TYPE_BUBBLE = 'TYPE_BUBBLE';
	const TYPE_CRITICAL = 'TYPE_CRITICAL';
	const TYPE_SMOKE = 'TYPE_SMOKE';
	const TYPE_EXPLODE = 'TYPE_EXPLODE';
	const TYPE_WHITE_SMOKE = 'TYPE_WHITE_SMOKE';
	const TYPE_FLAME = 'TYPE_FLAME';
	const TYPE_LAVA = 'TYPE_LAVA';
	const TYPE_LARGE_SMOKE = 'TYPE_LARGE_SMOKE';
	const TYPE_REDSTONE = 'TYPE_REDSTONE';
	const TYPE_ITEM_BREAK = 'TYPE_ITEM_BREAK';
	const TYPE_SNOWBALL_POOF = 'TYPE_SNOWBALL_POOF';
	const TYPE_LARGE_EXPLODE = 'TYPE_LARGE_EXPLODE';
	const TYPE_HUGE_EXPLODE = 'TYPE_HUGE_EXPLODE';
	const TYPE_MOB_FLAME = 'TYPE_MOB_FLAME';
	const TYPE_HEART = 'TYPE_HEART';
	const TYPE_TERRAIN = 'TYPE_TERRAIN';
	const TYPE_TOWN_AURA = 'TYPE_TOWN_AURA';
	const TYPE_PORTAL = 'TYPE_PORTAL';
	const TYPE_WATER_SPLASH = 'TYPE_WATER_SPLASH';
	const TYPE_WATER_WAKE = 'TYPE_WATER_WAKE';
	const TYPE_DRIP_WATER = 'TYPE_DRIP_WATER';
	const TYPE_DRIP_LAVA = 'TYPE_DRIP_LAVA';
	const TYPE_DUST = 'TYPE_DUST'; // meta: color
	const TYPE_MOB_SPELL = 'TYPE_MOB_SPELL'; // meta: color
	const TYPE_MOB_SPELL_AMBIENT = 'TYPE_MOB_SPELL_AMBIENT'; // meta: color
	const TYPE_MOB_SPELL_INSTANTANEOUS = 'TYPE_MOB_SPELL_INSTANTANEOUS'; // meta: color 
	const TYPE_INK = 'TYPE_INK';
	const TYPE_SLIME = 'TYPE_SLIME';
	const TYPE_RAIN_SPLASH = 'TYPE_RAIN_SPLASH';
	const TYPE_VILLAGER_ANGRY = 'TYPE_VILLAGER_ANGRY';
	const TYPE_VILLAGER_HAPPY = 'TYPE_VILLAGER_HAPPY';
	const TYPE_ENCHANTMENT_TABLE = 'TYPE_ENCHANTMENT_TABLE';
	const TYPE_NOTE = 'TYPE_NOTE';
	const TYPE_WITCH_MAGIC = 'TYPE_WITCH_MAGIC';
	const TYPE_ICE_CRYSTAL = 'TYPE_ICE_CRYSTAL';

	abstract public function spawnFor($players);
}
