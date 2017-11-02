<?php

namespace pocketmine\entity;

use pocketmine\block\Block;
use pocketmine\block\Rail;
use pocketmine\math\Vector3;
use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\Player;
use pocketmine\network\protocol\SetEntityLinkPacket;
use pocketmine\level\Level;

class Minecart extends Vehicle {

	const NETWORK_ID = 84;
	const STATE_INITIAL = 0;
	const STATE_ON_RAIL = 1;
	const STATE_OFF_RAIL = 2;
	const ACCELERATION = 0.01;

	public $height = 0.7;
	public $width = 0.98;
	public $isMoving = false;
	public $moveSpeed = 0;
	private $state = Minecart::STATE_INITIAL;
	private $direction = -1;
	private $moveVector = [];
	protected $isUsing = false;
	protected $linkedEntity = null;
	protected $links = [];
	protected $riderOffset = [0, 0.6, 0];

	public function initEntity() {
		$this->setMaxHealth(1);
		$this->setHealth($this->getMaxHealth());
		$this->moveVector[self::DIRECTION_NORTH] = new Vector3(-1, 0, 0);
		$this->moveVector[self::DIRECTION_SOUTH] = new Vector3(1, 0, 0);
		$this->moveVector[self::DIRECTION_EAST] = new Vector3(0, 0, -1);
		$this->moveVector[self::DIRECTION_WEST] = new Vector3(0, 0, 1);
		parent::initEntity();
	}

	public function getName() {
		return "Minecart";
	}

	public function onUpdate($currentTick) {
		if ($this->closed !== false) {
			return false;
		}

		if ($this->dead === true) {
			$this->removeAllEffects();
			$this->despawnFromAll();
			$this->close();
			return false;
		}

		$tickDiff = $currentTick - $this->lastUpdate;
		if ($tickDiff < 1) {
			return true;
		}

		$this->lastUpdate = $currentTick;

		$hasUpdate = false;

		if ($this->isAlive()) {
			$p = $this->getLinkedEntity();
			if ($p instanceof Player) {
				if ($this->moveSpeed > 0) {
					if ($this->state === Minecart::STATE_INITIAL) {
						$this->checkIfOnRail();
					} elseif ($this->state === Minecart::STATE_ON_RAIL) {
						$hasUpdate = $this->forwardOnRail($p);
						if ($currentTick % 30 == 0) {
							$this->acceleration(false);
						}
						$this->updateMovement();
					}
				}
			}
		}


		return true;
	}

	public function spawnTo(Player $player) {
		if (!isset($this->hasSpawned[$player->getId()]) && isset($player->usedChunks[Level::chunkHash($this->chunk->getX(), $this->chunk->getZ())])) {
			$this->hasSpawned[$player->getId()] = $player;
			$pk = new AddEntityPacket();
			$pk->eid = $this->getId();
			$pk->type = Minecart::NETWORK_ID;
			$pk->x = $this->x;
			$pk->y = $this->y;
			$pk->z = $this->z;
			$pk->speedX = 0;
			$pk->speedY = 0;
			$pk->speedZ = 0;
			$pk->yaw = 0;
			$pk->pitch = 0;
			$pk->metadata = $this->dataProperties;
			$pk->links = $this->links;
			$player->dataPacket($pk);
		}
	}

	public function attack($damage, EntityDamageEvent $source) {
		if ($this->isUsing) {
			return;
		}
		if ($source instanceof EntityDamageByEntityEvent) {
			$player = $source->getDamager();
			if ($player instanceof Player) {
				$this->mount($player);
			}
		}
	}

	public function getLinkedEntity() {
		return $this->linkedEntity;
	}

	public function mount($player) {
		if ($this->isUsing) {
			return;
		}
		if ($player->getInventory()->getItemInHand()->getId() !== 0) {
			$this->kill();
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

		$pk = new SetEntityLinkPacket();
		$pk->to = $player->getId();
		$pk->from = $this->getId();
		$pk->type = SetEntityLinkPacket::TYPE_RIDE;
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

	private function isRail(Block $rail) {
		return ($rail !== null && in_array($rail->getId(), [Block::RAIL, Block::ACTIVATOR_RAIL, Block::DETECTOR_RAIL, Block::POWERED_RAIL]));
	}

	private function moveIfRail() {
		$nextMoveVector = $this->moveVector[$this->direction];
		$nextMoveVector = $nextMoveVector->multiply($this->moveSpeed);
		$newVector = $this->add($nextMoveVector->x, $nextMoveVector->y, $nextMoveVector->z);
		$possibleRail = $this->getCurrentRail();
		if (in_array($possibleRail->getId(), [Block::RAIL, Block::ACTIVATOR_RAIL, Block::DETECTOR_RAIL, Block::POWERED_RAIL])) {
			$this->moveUsingVector($newVector);
			return true;
		}

		return false;
	}

	private function checkIfOnRail() {
		for ($y = -1; $y < 2 && $this->state === Minecart::STATE_INITIAL; $y++) {
			$positionToCheck = new Vector3(floor($this->x), floor($this->y) + $y, floor($this->z));
			$block = $this->level->getBlock($positionToCheck);
			if ($this->isRail($block)) {
				$minecartPosition = $positionToCheck->floor()->add(0.5, 0, 0.5);
				$this->setPosition($minecartPosition);
				$this->state = Minecart::STATE_ON_RAIL;
				return;
			}
		}
		$this->state = Minecart::STATE_OFF_RAIL;
	}

	private function getCurrentRail() {
		$block = $this->getLevel()->getBlock(new Vector3(floor($this->x), floor($this->y), floor($this->z)));
		if ($this->isRail($block)) {
			return $block;
		}
		$down = $this->getLevel()->getBlock(new Vector3(floor($this->x), floor($this->y) - 1, floor($this->z)));
		if ($this->isRail($down)) {
			return $down;
		}
		return null;
	}

	private function forwardOnRail(Player $player) {
		if ($this->direction === -1) {
			$candidateDirection = $player->getDirection();
		} else {
			$candidateDirection = $this->direction;
		}
		$rail = $this->getCurrentRail();
		if ($rail !== null) {
			$railType = $rail->getDamage();
			if ($rail->getId() != Block::RAIL) {
				$railType &= 0x07;
			}
			$nextDirection = $this->getDirectionToMove($railType, $candidateDirection);
			if ($nextDirection !== -1) {
				$this->direction = $nextDirection;
				$moved = $this->checkForVertical($railType, $nextDirection);
				if (!$moved) {
					return $this->moveIfRail();
				} else {
					return true;
				}
			} else {
				$this->direction = -1;
			}
		} else {
			// Not able to find rail
			$this->state = Minecart::STATE_INITIAL;
		}
		return false;
	}

	private function getDirectionToMove($railType, $candidateDirection) {
		switch ($railType) {
			case Rail::STRAIGHT_NORTH_SOUTH:
			case Rail::SLOPED_ASCENDING_NORTH:
			case Rail::SLOPED_ASCENDING_SOUTH:
				switch ($candidateDirection) {
					case self::DIRECTION_NORTH:
					case self::DIRECTION_SOUTH:
						return $candidateDirection;
				}
				break;
			case Rail::STRAIGHT_EAST_WEST:
			case Rail::SLOPED_ASCENDING_EAST:
			case Rail::SLOPED_ASCENDING_WEST:
				switch ($candidateDirection) {
					case self::DIRECTION_WEST:
					case self::DIRECTION_EAST:
						return $candidateDirection;
				}
				break;
			case Rail::CURVED_SOUTH_EAST:
				switch ($candidateDirection) {
					case self::DIRECTION_SOUTH:
					case self::DIRECTION_EAST:
						return $candidateDirection;
					case self::DIRECTION_NORTH:
						return $this->checkForTurn($candidateDirection, self::DIRECTION_EAST);
					case self::DIRECTION_WEST:
						return $this->checkForTurn($candidateDirection, self::DIRECTION_SOUTH);
				}
				break;
			case Rail::CURVED_SOUTH_WEST:
				switch ($candidateDirection) {
					case self::DIRECTION_SOUTH:
					case self::DIRECTION_WEST:
						return $candidateDirection;
					case self::DIRECTION_NORTH:
						return $this->checkForTurn($candidateDirection, self::DIRECTION_WEST);
					case self::DIRECTION_EAST:
						return $this->checkForTurn($candidateDirection, self::DIRECTION_SOUTH);
				}
				break;
			case Rail::CURVED_NORTH_WEST:
				switch ($candidateDirection) {
					case self::DIRECTION_NORTH:
					case self::DIRECTION_WEST:
						return $candidateDirection;
					case self::DIRECTION_SOUTH:
						return $this->checkForTurn($candidateDirection, self::DIRECTION_WEST);
					case self::DIRECTION_EAST:
						return $this->checkForTurn($candidateDirection, self::DIRECTION_NORTH);
				}
				break;
			case Rail::CURVED_NORTH_EAST:
				switch ($candidateDirection) {
					case self::DIRECTION_NORTH:
					case self::DIRECTION_EAST:
						return $candidateDirection;
					case self::DIRECTION_SOUTH:
						return $this->checkForTurn($candidateDirection, self::DIRECTION_EAST);
					case self::DIRECTION_WEST:
						return $this->checkForTurn($candidateDirection, self::DIRECTION_NORTH);
				}
				break;
		}
		return -1;
	}

	private function checkForTurn($currentDirection, $newDirection) {
		switch ($currentDirection) {
			case self::DIRECTION_NORTH:
				$diff = $this->x - $this->getFloorX();
				if ($diff !== 0 and $diff <= .5) {
					$dx = ($this->getFloorX() + .5) - $this->x;
					$this->move($dx, 0, 0);
					return $newDirection;
				}
				break;
			case self::DIRECTION_SOUTH:
				$diff = $this->x - $this->getFloorX();
				if ($diff !== 0 and $diff >= .5) {
					$dx = ($this->getFloorX() + .5) - $this->x;
					$this->move($dx, 0, 0);
					return $newDirection;
				}
				break;
			case self::DIRECTION_EAST:
				$diff = $this->z - $this->getFloorZ();
				if ($diff !== 0 and $diff <= .5) {
					$dz = ($this->getFloorZ() + .5) - $this->z;
					$this->move(0, 0, $dz);
					return $newDirection;
				}
				break;
			case self::DIRECTION_WEST:
				$diff = $this->z - $this->getFloorZ();
				if ($diff !== 0 and $diff >= .5) {
					$dz = $dz = ($this->getFloorZ() + .5) - $this->z;
					$this->move(0, 0, $dz);
					return $newDirection;
				}
				break;
		}
		return $currentDirection;
	}

	private function checkForVertical($railType, $currentDirection) {
		switch ($railType) {
			case Rail::SLOPED_ASCENDING_SOUTH:
				switch ($currentDirection) {
					case self::DIRECTION_NORTH:
						// Headed north up
						$diff = $this->x - $this->getFloorX();
						if ($diff !== 0 and $diff <= .5) {
							$dx = ($this->getFloorX() - .1) - $this->x;
							$this->acceleration(false);
							$this->move($dx, 1, 0);
							return true;
						}
						break;
					case self::DIRECTION_SOUTH:
						// Headed south down
						$diff = $this->x - $this->getFloorX();
						if ($diff !== 0 and $diff >= .5) {
							$dx = ($this->getFloorX() + 1) - $this->x;
							$this->acceleration(true);
							$this->move($dx, -1, 0);
							return true;
						}
						break;
				}
				break;
			case Rail::SLOPED_ASCENDING_NORTH:
				switch ($currentDirection) {
					case self::DIRECTION_SOUTH:
						// Headed south up
						$diff = $this->x - $this->getFloorX();
						if ($diff !== 0 and $diff >= .5) {
							$dx = ($this->getFloorX() + 1) - $this->x;
							$this->acceleration(false);
							$this->move($dx, 1, 0);
							return true;
						}
						break;
					case self::DIRECTION_NORTH:
						// Headed north down
						$diff = $this->x - $this->getFloorX();
						if ($diff !== 0 and $diff <= .5) {
							$dx = ($this->getFloorX() - .1) - $this->x;
							$this->acceleration(true);
							$this->move($dx, -1, 0);
							return true;
						}
						break;
				}
				break;
			case Rail::SLOPED_ASCENDING_EAST:
				switch ($currentDirection) {
					case self::DIRECTION_EAST:
						// Headed east up
						$diff = $this->z - $this->getFloorZ();
						if ($diff !== 0 and $diff <= .5) {
							$dz = ($this->getFloorZ() - .1) - $this->z;
							$this->acceleration(false);
							$this->move(0, 1, $dz);
							return true;
						}
						break;
					case self::DIRECTION_WEST:
						// Headed west down
						$diff = $this->z - $this->getFloorZ();
						if ($diff !== 0 and $diff >= .5) {
							$dz = ($this->getFloorZ() + 1) - $this->z;
							$this->acceleration(true);
							$this->move(0, -1, $dz);
							return true;
						}
						break;
				}
				break;
			case Rail::SLOPED_ASCENDING_WEST:
				switch ($currentDirection) {
					case self::DIRECTION_WEST:
						// Headed west up
						$diff = $this->z - $this->getFloorZ();
						if ($diff !== 0 and $diff >= .5) {
							$dz = ($this->getFloorZ() + 1) - $this->z;
							$this->acceleration(false);
							$this->move(0, 1, $dz);
							return true;
						}
						break;
					case self::DIRECTION_EAST:
						// Headed east down
						$diff = $this->z - $this->getFloorZ();
						if ($diff !== 0 and $diff <= .5) {
							$dz = ($this->getFloorZ() - .1) - $this->z;
							$this->acceleration(true);
							$this->move(0, -1, $dz);
							return true;
						}
						break;
				}
				break;
		}
		return false;
	}

	private function moveUsingVector(Vector3 $desiredPosition) {
		$dx = $desiredPosition->x - $this->x;
		$dy = $desiredPosition->y - $this->y;
		$dz = $desiredPosition->z - $this->z;
		$this->move($dx, $dy, $dz);
	}

	private function acceleration($positive = true) {
		if ($positive) {
			$this->moveSpeed += self::ACCELERATION;
		} else {
			$this->moveSpeed -= self::ACCELERATION;
		}
		if ($this->moveSpeed > 0.5) {
			$this->moveSpeed = 0.5;
		}
		if ($this->moveSpeed < 0.01) {
			$this->moveSpeed = 0;
			$this->direction = -1;
		}
	}

	public function playerAcceleration($sideway) {
		if ($sideway == 0) {
			return;
		}
		if ($this->linkedEntity instanceof Player) {
			if ($this->moveSpeed == 0) {
				if ($sideway > 0) {
					$this->moveSpeed = 0.05;
				}
				$this->scheduleUpdate();
			} else {
				$acceleration = 0;
				$playerDirection = $this->linkedEntity->getDirection();
				switch ($this->direction) {
					case self::DIRECTION_NORTH:
						switch ($playerDirection) {
							case self::DIRECTION_NORTH:
								$acceleration = self::ACCELERATION;
								break;
							case self::DIRECTION_SOUTH:
								$acceleration = -self::ACCELERATION;
								break;
						}
						break;
					case self::DIRECTION_SOUTH:
						switch ($playerDirection) {
							case self::DIRECTION_SOUTH:
								$acceleration = self::ACCELERATION;
								break;
							case self::DIRECTION_NORTH:
								$acceleration = -self::ACCELERATION;
								break;
						}
						break;
					case self::DIRECTION_EAST:
						switch ($playerDirection) {
							case self::DIRECTION_EAST:
								$acceleration = self::ACCELERATION;
								break;
							case self::DIRECTION_WEST:
								$acceleration = -self::ACCELERATION;
								break;
						}
						break;
					case self::DIRECTION_WEST:
						switch ($playerDirection) {
							case self::DIRECTION_WEST:
								$acceleration = self::ACCELERATION;
								break;
							case self::DIRECTION_EAST:
								$acceleration = -self::ACCELERATION;
								break;
						}
						break;
				}
				if ($sideway > 0) {
					$this->moveSpeed += $acceleration;
				} else {
					$this->moveSpeed -= $acceleration;
				}
				if ($this->moveSpeed > 0.5) {
					$this->moveSpeed = 0.5;
				}
				if ($this->moveSpeed < 0.01) {
					$this->moveSpeed = 0;
					$this->direction = -1;
				}
			}
		}
	}

}
