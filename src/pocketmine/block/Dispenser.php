<?php

namespace pocketmine\block;

use pocketmine\block\Block;
use pocketmine\block\Solid;
use pocketmine\entity\Entity;
use pocketmine\item\Bucket;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\Compound;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\Enum;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\Player;
use pocketmine\tile\Dispenser as DispenserTile;
use pocketmine\tile\Tile;

class Dispenser extends Solid {
	
	public function __construct($meta = 0){
		$this->id = self::DISPENSER;
		$this->meta = $meta;
	}
	
	public function place(Item $item, Block $block, Block $target, $face, $fx, $fy, $fz, Player $player = null) {
		// pitch part not so accurate as wanted
		if ($player->pitch > 45) {
			$this->meta = 1;
		} else if ($player->pitch < -45) {
			$this->meta = 0;
		} else {
			if ($player->yaw <= 45 || $player->yaw > 315) {
				$this->meta = 2;
			} else if ($player->yaw > 45 && $player->yaw <= 135) {
				$this->meta = 5;
			} else if ($player->yaw > 135 && $player->yaw <= 225) {
				$this->meta = 3;
			} else {
				$this->meta = 4;
			}
		}
		if (parent::place($item, $block, $target, $face, $fx, $fy, $fz, $player)) {
			$nbt = new Compound("", [
				new Enum("Items", []),
				new StringTag("id", Tile::DISPENSER),
				new IntTag("x", $this->x),
				new IntTag("y", $this->y),
				new IntTag("z", $this->z)
			]);
			$nbt->Items->setTagType(NBT::TAG_Compound);
			Tile::createTile(Tile::DISPENSER, $this->level->getChunk($this->x >> 4, $this->z >> 4), $nbt);
			return true;
		}
		return false;
	}
	
	public function needScheduleOnUpdate() {
		return true;
	}
	
	public function onUpdate($type, $deep) {
		if (!Block::onUpdate($type, $deep)) {
			return false;
		}
		static $offsets = [
			self::SIDE_UP => [0, 1, 0],
			self::SIDE_DOWN => [0, -1, 0],
			self::SIDE_EAST => [1, 0, 0],
			self::SIDE_WEST => [-1, 0, 0],
			self::SIDE_SOUTH => [0, 0, 1],
			self::SIDE_NORTH => [0, 0, -1],
		];
		$tmpVector = new Vector3();
		foreach ($offsets as $side => $offset) {
			$isShouldBeActivated = false;
			$tmpVector->setComponents($this->x + $offset[0], $this->y + $offset[1], $this->z + $offset[2]);
			$blockId = $this->level->getBlockIdAt($tmpVector->x, $tmpVector->y, $tmpVector->z);
			$isSolidBlock = isset(self::$solid[$blockId]) && self::$solid[$blockId];
			if ($side == self::SIDE_UP) {
				if ($isSolidBlock) {
					$block = $this->level->getBlock($tmpVector);
					$isShouldBeActivated = $block->getPoweredState() != Solid::POWERED_NONE;
				}
			} else {
				switch ($blockId) {
					case self::REDSTONE_WIRE:
						$wire = $this->level->getBlock($tmpVector);
						$isShouldBeActivated = $wire->meta > 0;
						break;
					case self::REDSTONE_TORCH_ACTIVE:
						$isShouldBeActivated = true;
						break;
					case self::WOODEN_BUTTON:
					case self::STONE_BUTTON:
					case self::LEVER:
					case self::WOODEN_PRESSURE_PLATE:
					case self::STONE_PRESSURE_PLATE:
					case self::WEIGHTED_PRESSURE_PLATE_LIGHT:
					case self::WEIGHTED_PRESSURE_PLATE_HEAVY:
						$backBlock = $this->level->getBlock($tmpVector);
						$isShouldBeActivated = $backBlock->isActive();
						break;
					case self::REDSTONE_REPEATER_BLOCK_ACTIVE:
						$activeRepeater = $this->level->getBlock($tmpVector);
						$frontBlockCoords = $activeRepeater->getFrontBlockCoords();
						if ($this->x == $frontBlockCoords->x && $this->y == $frontBlockCoords->y && $this->z == $frontBlockCoords->z) {
							$isShouldBeActivated = true;
						}
						break;
					default:
						if ($isSolidBlock) {
							$solidBlock = $this->level->getBlock($tmpVector);
							$isShouldBeActivated = $solidBlock->getPoweredState() != Solid::POWERED_NONE;
						}
						break;
				}
			}
			if ($isShouldBeActivated) {
				if (!$this->isWasActivated()) {
					$this->activate();
					return;
				}
				break;
			}
		}
		if (!$isShouldBeActivated && $this->isWasActivated()) {
			$this->deactivate();
		}
	}
	
	private function isWasActivated() {
		return $this->meta >> 3;
	}
	
	private function activate() {
		$this->meta |= 0x08;
		$this->level->setBlock($this, $this, false, false);
		$this->shoot();
	}
	
	private function deactivate() {
		$this->meta &= 0x07;
		$this->level->setBlock($this, $this, false, false);
	}
	
	public function canBeActivated() {
		return true;
	}

	public function onActivate(Item $item, Player $player = null) {
		$tile = $this->level->getTile($this);
		if (!($tile instanceof DispenserTile)) {
			$nbt = new Compound("", [
				new Enum("Items", []),
				new StringTag("id", Tile::DISPENSER),
				new IntTag("x", $this->x),
				new IntTag("y", $this->y),
				new IntTag("z", $this->z)
			]);
			$nbt->Items->setTagType(NBT::TAG_Compound);
			$tile = Tile::createTile(Tile::DISPENSER, $this->level->getChunk($this->x >> 4, $this->z >> 4), $nbt);
		}
		$player->addWindow($tile->getInventory());
		return true;
	}
	
	public function getFace() {
		return $this->meta & 0x07;
	}
	
	protected function shoot() {
		$tile = $this->level->getTile($this);
		if ($tile instanceof DispenserTile) {
			$dispenserInventory = $tile->getInventory();
			$item = $dispenserInventory->getFirstItem($index);
			if ($item != null) {
				$itemId = $item->getId();
				$itemMeta = $item->getDamage();
				if ($itemId == Item::BUCKET && ($itemMeta == Bucket::BUCKET_WATER || $itemMeta == Bucket::BUCKET_LAVA)) {
					$dispenserInventory->setItem($index, Item::get(Item::BUCKET));
					$params = $this->calculateShootingParams();
					$position = $this->getPositionOfFaceNextBlock();
					$this->level->setBlock($position, Block::get($itemMeta == Bucket::BUCKET_WATER ? Block::WATER : Block::LAVA));
				} else {
					// decreasing item logic
					if ($item->count == 1) { // we shoot last item in slot
						$dispenserInventory->clear($index);
					} else {
						$item->count--;
						$dispenserInventory->setItem($index, $item);
					}
					// drop item or shoot arrow logic
					$params = $this->calculateShootingParams();

					$nbt = new Compound("", [
						"Pos" => new Enum("Pos", [ new DoubleTag("", $params['x']), new DoubleTag("", $params['y']), new DoubleTag("", $params['z']) ]),
						"Rotation" => new Enum("Rotation", [
							new FloatTag("", $params['yawRad'] * 180 / M_PI),
							new FloatTag("", $params['pitchRad'] * 180 / M_PI)
						]),
					]);

					if ($item->getId() == Item::ARROW) {
						$entityType = "Arrow";
						$nbt->Motion = new Enum("Motion", [
							new DoubleTag("", -sin($params['yawRad']) * cos($params['pitchRad'])),
							new DoubleTag("", -sin($params['pitchRad'])),
							new DoubleTag("", cos($params['yawRad']) * cos($params['pitchRad']))
						]);
					} else {
						$entityType = "Item";
						$nbt->Motion = new Enum("Motion", [
							new DoubleTag("", -sin($params['yawRad']) * cos($params['pitchRad']) / 10),
							new DoubleTag("", 0),
							new DoubleTag("", cos($params['yawRad']) * cos($params['pitchRad']) / 10)
						]);
						$nbt->Health = new ShortTag("Health", 5);
						$nbt->Item = NBT::putItemHelper(Item::get($item->getId(), $item->getDamage()));
						$nbt->PickupDelay = new ShortTag("PickupDelay", 20);
					}
					$projectile = Entity::createEntity($entityType, $this->level->getChunk($this->x >> 4, $this->z >> 4), $nbt);
					$projectile->spawnToAll();
				}
			}
		}
	}
	
	protected function getPositionOfFaceNextBlock() {
		$position = new Vector3($this->x, $this->y, $this->z);
		$face = $this->getFace();
		switch ($face) {
			case self::FACE_DOWN:
				$position->y--;
				break;
			case self::FACE_UP:
				$position->y++;
				break;
			case self::FACE_SOUTH:
				$position->z++;
				break;
			case self::FACE_NORTH:
				$position->z--;
				break;
			case self::FACE_WEST:
				$position->x--;
				break;
			case self::FACE_EAST:
				$position->x++;
				break;
		}
		return $position;
	}
	
	protected function calculateShootingParams() {
		$data = [
			'x' => $this->x,
			'y' => $this->y,
			'z' => $this->z,
			'yawRad' => 0,
			'pitchRad' => 0,
		];
		$face = $this->getFace();
		if ($face == self::FACE_DOWN) {
			$data['pitchRad'] = 0.5 * M_PI;
		} else if ($face == self::FACE_UP) {
			$data['pitchRad'] = -0.5 * M_PI;
		} else {
			$data['y'] += 0.5;
			if ($face == self::FACE_SOUTH) {
				$data['x'] += 0.5;
				$data['z'] += 2;
			} else if ($face == self::FACE_NORTH) {
				$data['yawRad'] = M_PI;
				$data['x'] += 0.5;
				$data['z'] -= 1;
			} else if ($face == self::FACE_WEST) {
				$data['yawRad'] = 0.5 * M_PI;
				$data['x'] -= 1;
				$data['z'] += 0.5;
			} else if ($face == self::FACE_EAST) {
				$data['yawRad'] = 1.5 * M_PI;
				$data['x'] += 2;
				$data['z'] += 0.5;
			}
//			$angleOffset = M_PI / 18; // 10 degree
//			$data['pitchRad'] = -$angleOffset * 3;
//			$data['yawRad'] += mt_rand(-$angleOffset, $angleOffset);
		}
		return $data;
	}
}
