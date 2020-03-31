<?php

namespace pocketmine\entity;

use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\Player;
use pocketmine\network\protocol\SetEntityLinkPacket;
use pocketmine\level\Level;

abstract class Vehicle extends Entity implements Rideable {

	protected $isUsing = false;
	protected $linkedEntity = null;
	protected $links = [];
	protected $riderOffset = [0, 0, 0];
	protected $interactText = "Ride";

	public function getLinkedEntity() {
		return $this->linkedEntity;
	}

	public function mount($player) {
		if ($this->isUsing) {
			return;
		}
		$this->isUsing = true;
		$this->linkedEntity = $player;
		$player->setVehicle($this);

		$this->links = [
			[
				'to' => $player->getId(),
				'from' => $this->getId(),
				'type' => SetEntityLinkPacket::TYPE_RIDE
			]
		];

		$pk = new SetEntityLinkPacket();
		$pk->to = $player->getId();
		$pk->from = $this->getId();
		$pk->type = SetEntityLinkPacket::TYPE_RIDE;
		foreach ($player->getViewers() as $p) {
			$p->dataPacket($pk);
		}

		$player->dataPacket($pk);
		$player->setDataProperty(self::DATA_SEAT_RIDER_OFFSET, self::DATA_TYPE_VECTOR3, $this->riderOffset);
		$player->sendSelfData();
		$this->scheduleUpdate();
	}

	public function dissMount() {
		if (!$this->isUsing) {
			return;
		}
		$this->isUsing = false;
		$this->links = [];
		$this->direction = -1;
		$this->moveSpeed = 0;
		$this->onDissMount();

		$pk = new SetEntityLinkPacket();
		$pk->to = $this->linkedEntity->getId();
		$pk->from = $this->getId();
		$pk->type = SetEntityLinkPacket::TYPE_REMOVE;
		foreach ($this->linkedEntity->getViewers() as $p) {
			$p->dataPacket($pk);
		}

		$pk = new SetEntityLinkPacket();
		$pk->to = $this->linkedEntity->getId();
		$pk->from = $this->getId();
		$pk->type = SetEntityLinkPacket::TYPE_REMOVE;
		$this->linkedEntity->dataPacket($pk);
		$this->linkedEntity->setDataProperty(self::DATA_SEAT_RIDER_OFFSET, self::DATA_TYPE_VECTOR3, [0, 0, 0]);
		$this->linkedEntity->sendSelfData();
		$this->linkedEntity->removeDataProperty(self::DATA_SEAT_RIDER_OFFSET, false);
		$this->linkedEntity->setVehicle(null);
		$this->linkedEntity = null;
		$this->state = Minecart::STATE_INITIAL;
	}

	protected function onDissMount() {
		
	}

	public function attack($damage, EntityDamageEvent $source) {
		if ($this->isUsing) {
			return;
		}
		parent::attack($damage, $source);
	}
	
	public function onPlayerInteract($player) {
		if ($this->isUsing) {
			return;
		}
		if ($player instanceof Player) {
			$this->mount($player);
		}
	}

	public function spawnTo(Player $player) {
		if (!isset($this->hasSpawned[$player->getId()]) && isset($player->usedChunks[Level::chunkHash($this->chunk->getX(), $this->chunk->getZ())])) {
			$this->hasSpawned[$player->getId()] = $player;
			$pk = new AddEntityPacket();
			$pk->eid = $this->getId();
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
			$pk->links = $this->links;
			$player->dataPacket($pk);
		}
	}

	public function playerMoveVehicle($forward, $sideway) {
		
	}

	public function updateByOwner($x, $y, $z, $yaw, $pitch) {
		
	}
	
	public function onNearPlayer($player) {
		$player->setInteractButtonText($this->interactText);
	}

}
