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
use pocketmine\network\multiversion\MultiversionEnums;
use pocketmine\network\protocol\Info;
use pocketmine\network\protocol\LevelEventPacket;
use pocketmine\network\protocol\v310\SpawnParticleEffectPacket;
use pocketmine\Server;

class GenericParticle extends Particle{
	
	protected $id;
	protected $data;
	protected $customSpawnName = null;

	public function __construct(Vector3 $pos, $id, $data = 0){
		parent::__construct($pos->x, $pos->y, $pos->z);
		$this->id = $id;
		$this->data = $data;
	}
	
	public function spawnFor($players) {
		if (!empty($players)) {
			if (!is_null($this->customSpawnName)) {
				$pk = new SpawnParticleEffectPacket();
				$pk->x = $this->x;
				$pk->y = $this->y;
				$pk->z = $this->z;
				$pk->particleName = $this->customSpawnName;
				Server::broadcastPacket($players, $pk);
			} elseif (isset(MultiversionEnums::$particleIds[Info::PROTOCOL_419][$this->id]) && MultiversionEnums::$particleIds[Info::PROTOCOL_419][$this->id] > 0) {
				$pk = new LevelEventPacket();
				$pk->evid = LevelEventPacket::EVENT_ADD_PARTICLE_MASK | MultiversionEnums::$particleIds[Info::PROTOCOL_419][$this->id];
				$pk->x = $this->x;
				$pk->y = $this->y;
				$pk->z = $this->z;
				$pk->data = $this->data;
				Server::broadcastPacket($players, $pk);
			}
		}
	}
}
