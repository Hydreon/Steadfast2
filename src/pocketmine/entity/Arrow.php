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
namespace pocketmine\entity;

use pocketmine\block\WoodenButton;
use pocketmine\item\Item;
use pocketmine\level\format\FullChunk;
use pocketmine\level\Level;
use pocketmine\level\particle\CriticalParticle;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\Compound;
use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\Player;
use pocketmine\block\Lava;
use function var_dump;

class Arrow extends Projectile {

	const NETWORK_ID = 80;

	public $width = 0.5;
	public $length = 0.5;
	public $height = 0.5;
	protected $gravity = 0.03;
	protected $drag = 0.01;
	protected $damage = 2;

	public function __construct(FullChunk $chunk, Compound $nbt, Entity $shootingEntity = null, $critical = false) {
		parent::__construct($chunk, $nbt, $shootingEntity);
		$this->setCritical($critical);
	}

	public function onUpdate($currentTick) {
		if ($this->closed) {
			return false;
		}
		$hasUpdate = parent::onUpdate($currentTick);

		if ($this->onGround || $this->hadCollision) {
		    $this->setCritical(false);
		}

		if ($this->age > 1200) {
			$this->kill();
			$hasUpdate = true;
		} else if ($this->y < 1) {
			$this->kill();
			$hasUpdate = true;
		}
		return $hasUpdate;
	}

	public function isCritical(){
	    return $this->getDataFlag(self::DATA_FLAGS, self::DATA_FLAG_CRITICAL);
    }

    public function setCritical($critical = true){
        $this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_CRITICAL, $critical);
    }

	public function spawnTo(Player $player) {
		if (!isset($this->hasSpawned[$player->getId()]) && isset($player->usedChunks[Level::chunkHash($this->chunk->getX(), $this->chunk->getZ())])) {
			$this->hasSpawned[$player->getId()] = $player;
			$pk = new AddEntityPacket();
			$pk->type = static::NETWORK_ID;
			$pk->eid = $this->getId();
			$pk->x = $this->x;
			$pk->y = $this->y;
			$pk->z = $this->z;
			$pk->speedX = $this->motionX;
			$pk->speedY = $this->motionY;
			$pk->speedZ = $this->motionZ;
            $pk->metadata = $this->dataProperties;
			$player->dataPacket($pk);
		}
	}
	
	public function getBoundingBox() {
		$bb = clone parent::getBoundingBox();
		return $bb;
	}
		
	public function move($dx, $dy, $dz) {
		$this->blocksAround = null;
		if ($dx == 0 && $dz == 0 && $dy == 0) {
			return true;
		}
		$pos = new Vector3($this->x + $dx, $this->y + $dy, $this->z + $dz);
		if (!$this->setPosition($pos)) {
			return false;
		}
		$this->onGround = false;
		$bb = clone $this->boundingBox;
		$blocks = $this->level->getCollisionBlocks($bb);
		foreach ($blocks as $block) {
			if (!$block->isLiquid() && $block instanceof Lava) {
				$this->onGround = true;
				break;
			}
		}
		$this->isCollided = $this->onGround;
		$this->updateFallState($dy, $this->onGround);
		return true;
	}

	public function kill() {
		if (!$this->dead) {
			$bb = clone $this->boundingBox;
			$collidedBlocks = $this->level->getCollisionBlocks($bb);
			foreach ($collidedBlocks as $block) {
				if ($block instanceof WoodenButton) {
					$block->onActivate(Item::get(Item::AIR), null);
				}
			}
		}
		parent::kill();
	}

}
