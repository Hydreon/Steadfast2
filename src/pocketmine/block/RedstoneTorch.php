<?php

namespace pocketmine\block;

use pocketmine\item\Item;
use pocketmine\math\Vector3;

class RedstoneTorch extends RedstoneTorchActive {

	protected $id = self::REDSTONE_TORCH;

	public function __construct($meta = 0) {
		$this->meta = $meta;
	}

	public function getName() {
		return "Redstone Torch";
	}

	public function getLightLevel() {
		return 0;
	}

	public function getDrops(Item $item) {
		return [
			[self::REDSTONE_TORCH_ACTIVE, 0, 1],
		];
	}
	
	public function redstoneUpdate($power, $fromDirection, $fromSolid = false) {
		if (!$fromSolid && $fromDirection != self::DIRECTION_SELF) {
			return;
		}
		$this->updateNeighbors();
		if ($fromDirection == $this->meta && $power == self::REDSTONE_POWER_MIN) { // power from attached block
			$litTorch = Block::get(Block::REDSTONE_TORCH_ACTIVE, $this->meta);
			$this->level->setBlock($this, $litTorch, true, true);
			$power = self::REDSTONE_POWER_MAX;
		}
				
		foreach ($this->neighbors as $neighborDirection => $neighbor) {
			if (in_array($neighbor->getId(), self::REDSTONE_BLOCKS)) {
				$neighbor->redstoneUpdate($power, $neighborDirection);
			} else if ($neighbor->isSolid()) {
				static $offsets = [
					self::DIRECTION_TOP => [0, 1, 0],
					self::DIRECTION_NORTH => [1, 0, 0],
					self::DIRECTION_SOUTH => [-1, 0, 0],
					self::DIRECTION_EAST => [0, 0, 1],
					self::DIRECTION_WEST => [0, 0, -1],
				];
				foreach ($offsets as $direction => $offset) {
					$blockId = $this->level->getBlockIdAt($neighbor->x + $offset[0], $neighbor->y + $offset[1], $neighbor->z + $offset[2]);
					if (!in_array($blockId, self::REDSTONE_BLOCKS)) {
						continue;
					}		
					$rsComponent = $this->level->getBlock(new Vector3($neighbor->x + $offset[0], $neighbor->y + $offset[1], $neighbor->z + $offset[2]));
					$rsComponent->redstoneUpdate($power, $direction, true);
				}
			}
		}
	}

}
