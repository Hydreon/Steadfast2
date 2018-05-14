<?php

namespace pocketmine\entity;

use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\level\Level;
use pocketmine\math\AxisAlignedBB;
use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\network\protocol\RemoveEntityPacket;
use pocketmine\Player;

class ArmorStand extends Entity {
	
	const NETWORK_ID = 61;
	const POSE_COUNT = 13;
	
	public function __construct(Level $level, $x, $y, $z) {
		$this->id = Entity::$entityCount++;
		$this->chunk = $level->getChunk($x >> 4, $z >> 4);
		$this->setLevel($level);
		$this->server = $level->getServer();
		$this->server->addSpawnedEntity($this);
		$this->x = $x;
		$this->y = $y;
		$this->z = $z;
		$this->boundingBox = new AxisAlignedBB($this->x - 0.01, $this->y - 0.01, $this->z - 0.01, $this->x + 0.01, $this->y + 0.01, $this->z + 0.01);
		$this->chunk->addEntity($this);
		$this->level->addEntity($this);
		$this->lastUpdate = $this->server->getTick();
		$this->dataProperties = [
			static::DATA_LEAD_HOLDER => [ static::DATA_TYPE_LONG, -1 ],
			static::DATA_POSE_INDEX => [ static::DATA_TYPE_INT, 0 ],
		];
		$this->scheduleUpdate();
		$this->spawnToAll();
	}
	
	public function spawnTo(Player $player) {
		if (!isset($this->hasSpawned[$player->getId()])) {
			$pk = new AddEntityPacket();
			$pk->eid = $this->getID();
			$pk->type = static::NETWORK_ID;
			$pk->x = $this->x;
			$pk->y = $this->y;
			$pk->z = $this->z;
			$pk->speedX = 0;
			$pk->speedY = 0;
			$pk->speedZ = 0;
			$pk->yaw = $this->yaw;
			$pk->pitch = $this->pitch;
			$pk->metadata = $this->dataProperties;
			$player->dataPacket($pk);

			$this->hasSpawned[$player->getId()] = $player;
		}
	}
	
	public function setPose($value) {
		$value = $value % static::POSE_COUNT;
		$this->setDataProperty(static::DATA_POSE_INDEX, static::DATA_TYPE_INT, $value);
	}
	
	public function getPose() {
		return $this->getDataProperty(Entity::DATA_POSE_INDEX);
	}

	public function despawnFrom(Player $player) {
		if (isset($this->hasSpawned[$player->getId()])) {
			$pk = new RemoveEntityPacket();
			$pk->eid = $this->getId();
			$player->dataPacket($pk);
			unset($this->hasSpawned[$player->getId()]);
		}
	}
	
	public function attack($damage, EntityDamageEvent $source) {
		
	}
	
	public function canCollideWith(Entity $entity) {
		return false;
	}
	
	public function onUpdate($currentTick) {
		return !$this->closed;
	}
	
	public function isNeedSaveOnChunkUnload() {
		return false;
	}

}
