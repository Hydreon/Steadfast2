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

namespace pocketmine\block;

use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\math\Vector3;

abstract class Liquid extends Transparent {

	/** @var Vector3 */
	private $temporalVector = null;

	public function hasEntityCollision() {
		return true;
	}

	public function isBreakable(Item $item) {
		return false;
	}

	public function canBeReplaced() {
		return true;
	}

	public function isSolid() {
		return false;
	}

	public $isOptimalFlowDirection = [0, 0, 0, 0];
	public $flowCost = [0, 0, 0, 0];

	/**
	 * не используется
	 * @return float
	 */
	public function getFluidHeightPercent() {
		$d = $this->meta;
		if ($d >= 8) {
			$d = 0;
		}
		return ($d + 1) / 9;
	}

	/**
	 * decay - ослабление, упадок, спад
	 * возвращает состояние блока если он жидкость, иначе - -1
	 * @param Vector3 $pos
	 * @return type
	 */
	protected function getFlowDecay(Vector3 $pos) {
		if (!($pos instanceof Block)) {
			$pos = $this->getLevel()->getBlock($pos);
		}
		if ($pos->getId() !== $this->getId()) {
			return -1;
		}
		return $pos->getDamage();
	}

	/**
	 * фигня какая-то пока
	 * возвращает состояние блока если он жидкость, иначе - -1
	 * @param Vector3 $pos
	 * @return int
	 */
	protected function getEffectiveFlowDecay(Vector3 $pos) {
		if (!($pos instanceof Block)) {
			$pos = $this->getLevel()->getBlock($pos);
		}
		if ($pos->getId() !== $this->getId()) {
			return -1;
		}
		$decay = $pos->getDamage();
		if ($decay >= 8) {
			$decay = 0;
		}
		return $decay;
	}

	public function getFlowVector() {
		if ($this->temporalVector === null) {
			$this->temporalVector = new Vector3(0, 0, 0);
		}

		$vector = new Vector3(0, 0, 0);
		$decay = $this->getEffectiveFlowDecay($this);

		for ($j = 0; $j < 4; ++$j) {
			$x = $this->x;
			$y = $this->y;
			$z = $this->z;

			if ($j === 0) {
				$x--;
			} else if ($j === 1) {
				$x++;
			} else if ($j === 2) {
				$z--;
			} else if ($j === 3) {
				$z++;
			}
			$sideBlock = $this->getLevel()->getBlock($this->temporalVector->setComponents($x, $y, $z));
			$blockDecay = $this->getEffectiveFlowDecay($sideBlock);

			if ($blockDecay < 0) { // if not the same block
				if (!$sideBlock->canBeFlowedInto()) {
					continue;
				}

				$blockDecay = $this->getEffectiveFlowDecay($this->getLevel()->getBlock($this->temporalVector->setComponents($x, $y - 1, $z)));

				if ($blockDecay >= 0) {
					$realDecay = $blockDecay - ($decay - 8);
					$vector->x += ($sideBlock->x - $this->x) * $realDecay;
					$vector->y += ($sideBlock->y - $this->y) * $realDecay;
					$vector->z += ($sideBlock->z - $this->z) * $realDecay;
				}

				continue;
			} else {
				$realDecay = $blockDecay - $decay;
				$vector->x += ($sideBlock->x - $this->x) * $realDecay;
				$vector->y += ($sideBlock->y - $this->y) * $realDecay;
				$vector->z += ($sideBlock->z - $this->z) * $realDecay;
			}
		}

		if ($this->getDamage() >= 8) {
			$falling = false;

			if (!$this->getLevel()->getBlock($this->temporalVector->setComponents($this->x, $this->y, $this->z - 1))->canBeFlowedInto()) {
				$falling = true;
			} elseif (!$this->getLevel()->getBlock($this->temporalVector->setComponents($this->x, $this->y, $this->z + 1))->canBeFlowedInto()) {
				$falling = true;
			} elseif (!$this->getLevel()->getBlock($this->temporalVector->setComponents($this->x - 1, $this->y, $this->z))->canBeFlowedInto()) {
				$falling = true;
			} elseif (!$this->getLevel()->getBlock($this->temporalVector->setComponents($this->x + 1, $this->y, $this->z))->canBeFlowedInto()) {
				$falling = true;
			} elseif (!$this->getLevel()->getBlock($this->temporalVector->setComponents($this->x, $this->y + 1, $this->z - 1))->canBeFlowedInto()) {
				$falling = true;
			} elseif (!$this->getLevel()->getBlock($this->temporalVector->setComponents($this->x, $this->y + 1, $this->z + 1))->canBeFlowedInto()) {
				$falling = true;
			} elseif (!$this->getLevel()->getBlock($this->temporalVector->setComponents($this->x - 1, $this->y + 1, $this->z))->canBeFlowedInto()) {
				$falling = true;
			} elseif (!$this->getLevel()->getBlock($this->temporalVector->setComponents($this->x + 1, $this->y + 1, $this->z))->canBeFlowedInto()) {
				$falling = true;
			}

			if ($falling) {
				$vector = $vector->normalize()->add(0, -6, 0);
			}
		}

		return $vector->normalize();
	}

	public function addVelocityToEntity(Entity $entity, Vector3 $vector) {
		$flow = $this->getFlowVector();
		$vector->x += $flow->x;
		$vector->y += $flow->y;
		$vector->z += $flow->z;
	}

	public function tickRate() {
		if ($this instanceof Water) {
			return 5;
		} else if ($this instanceof Lava) {
			return 30;
		}
		return 0;
	}

	public function onUpdate($type, $deep) {
		if (!Block::onUpdate($type, $deep)) {
			return false;
		}
		$deep++;
		if ($type === Level::BLOCK_UPDATE_NORMAL) {
			$this->checkForHarden($deep);
			$this->getLevel()->scheduleUpdate($this, $this->tickRate());
		} elseif ($type === Level::BLOCK_UPDATE_SCHEDULED) {
			if ($this->temporalVector === null) {
				$this->temporalVector = new Vector3(0, 0, 0);
			}

			$decay = $this->getFlowDecay($this);
			$multiplier = $this instanceof Lava ? 2 : 1;

			$bottomBlock = $this->level->getBlock($this->temporalVector->setComponents($this->x, $this->y - 1, $this->z));

			if ($bottomBlock->canBeFlowedInto() || $bottomBlock instanceof Liquid) {
				if (($this instanceof Lava && $bottomBlock instanceof Water) || ($this instanceof Water && $bottomBlock instanceof Lava)) {
					$this->getLevel()->setBlock($bottomBlock, Block::get(Item::STONE), true, true, $deep);
					return;
				}
				$this->getLevel()->setBlock($bottomBlock, Block::get($this->id, $decay >= 8 ? $decay : $decay + 8), true, true, $deep);
				$this->getLevel()->scheduleUpdate($bottomBlock, $this->tickRate());
			} elseif ($decay === 0 || $decay > 0 && !$bottomBlock->canBeFlowedInto()) {
				$flags = $this->getOptimalFlowDirections();

				$l = $decay + $multiplier;

				if ($decay >= 8) {
					$l = 1;
				}

				if ($l >= 8) {
					$this->checkForHarden($deep);
					return;
				}

				if ($flags[0]) {
					$this->flowIntoBlock($this->level->getBlock($this->temporalVector->setComponents($this->x - 1, $this->y, $this->z)), $l, $deep);
				}

				if ($flags[1]) {
					$this->flowIntoBlock($this->level->getBlock($this->temporalVector->setComponents($this->x + 1, $this->y, $this->z)), $l, $deep);
				}

				if ($flags[2]) {
					$this->flowIntoBlock($this->level->getBlock($this->temporalVector->setComponents($this->x, $this->y, $this->z - 1)), $l, $deep);
				}

				if ($flags[3]) {
					$this->flowIntoBlock($this->level->getBlock($this->temporalVector->setComponents($this->x, $this->y, $this->z + 1)), $l, $deep);
				}
//				sleep(5);
			}
			$this->checkForHarden($deep);
		}
	}

	private function flowIntoBlock(Block $block, $newFlowDecay, $deep) {
		if ($block->canBeFlowedInto()) {
			if ($block->getId() > 0) {
				$this->getLevel()->useBreakOn($block);
			}

			$this->getLevel()->setBlock($block, Block::get($this->getId(), $newFlowDecay), true, false, $deep);
			$this->getLevel()->scheduleUpdate($block, $this->tickRate());
		}
	}

	private function calculateFlowCost(Block $block, $accumulatedCost, $previousDirection) {
//		echo "------ BEGIN RECURSION {$accumulatedCost} ------" . PHP_EOL;
		$costs = [];
		for ($j = 0; $j < 4; ++$j) {
			if (($j === 0 && $previousDirection === 1) || 
				($j === 1 && $previousDirection === 0) || 
				($j === 2 && $previousDirection === 3) || 
				($j === 3 && $previousDirection === 2)) {
				
//				var_dump($j . " skip previous direction");
				continue;
			}
			
			if ($accumulatedCost >= 7) {
				$costs[$j] = 7;
				continue;
			}
			
			$x = $block->x;
			$y = $block->y;
			$z = $block->z;

			if ($j === 0) {
				$x--;
			} elseif ($j === 1) {
				$x++;
			} elseif ($j === 2) {
				$z--;
			} elseif ($j === 3) {
				$z++;
			}
			$blockSide = $this->getLevel()->getBlock($this->temporalVector->setComponents($x, $y, $z));
			if ($blockSide->canBeFlowedInto()) {
				$this->temporalVector->setComponents($x, $y - 1, $z);
				if ($this->getLevel()->getBlock($this->temporalVector)->canBeFlowedInto()) {
//					var_dump($j . " may fall down");
					$costs[$j] = $accumulatedCost;
					continue;
				}
//				var_dump($j . " normal");
				$costs[$j] = $this->calculateFlowCost($blockSide, $accumulatedCost + 1, $j);
//				echo "------ END RECURSION {$accumulatedCost} ------" . PHP_EOL;
			} else {
				if ($blockSide->getId() === $this->id) {
					$this->temporalVector->setComponents($x, $y - 1, $z);
					if ($this->getLevel()->getBlock($this->temporalVector)->canBeFlowedInto()) {
//						var_dump($j . " may fall down");
						$costs[$j] = $accumulatedCost;
						continue;
					}
				}
//				var_dump($j . " not flowable");
				$costs[$j] = 7;
				continue;
			}
		}

		return empty($costs) ? 1000 : min($costs);
	}
	
//	private function calculateFlowCost(Block $block, $accumulatedCost, $previousDirection) {
//		$cost = 1000;
//
//		for ($j = 0; $j < 4; ++$j) {
//			if (($j === 0 && $previousDirection === 1) || 
//				($j === 1 && $previousDirection === 0) || 
//				($j === 2 && $previousDirection === 3) || 
//				($j === 3 && $previousDirection === 2)) {
//				
//				$x = $block->x;
//				$y = $block->y;
//				$z = $block->z;
//
//				if ($j === 0) {
//					--$x;
//				} elseif ($j === 1) {
//					++$x;
//				} elseif ($j === 2) {
//					--$z;
//				} elseif ($j === 3) {
//					++$z;
//				}
//				$blockSide = $this->getLevel()->getBlock($this->temporalVector->setComponents($x, $y, $z));
//
//				if (!$blockSide->canBeFlowedInto() and ! ($blockSide instanceof Liquid)) {
//					continue;
//				} elseif ($blockSide instanceof Liquid and $blockSide->getDamage() === 0) {
//					continue;
//				} elseif ($this->getLevel()->getBlock($this->temporalVector->setComponents($x, $y - 1, $z))->canBeFlowedInto()) {
//					return $accumulatedCost;
//				}
//
//				if ($accumulatedCost >= 4) {
//					continue;
//				}
//
//				$realCost = $this->calculateFlowCost($blockSide, $accumulatedCost + 1, $j);
//
//				if ($realCost < $cost) {
//					$cost = $realCost;
//				}
//			}
//		}
//
//		return $cost;
//	}

	public function getHardness() {
		return 100;
	}

	private function getOptimalFlowDirections() {
		if ($this->temporalVector === null) {
			$this->temporalVector = new Vector3(0, 0, 0);
		}
//		echo "------ BEGIN MAIN ------" . PHP_EOL;
//		echo "X: ".$this->x." Y: ".$this->y." Z: ".$this->z. PHP_EOL;
		for ($j = 0; $j < 4; ++$j) {
			$this->flowCost[$j] = 1000;

			$x = $this->x;
			$y = $this->y;
			$z = $this->z;

			if ($j === 0) {
				--$x;
			} elseif ($j === 1) {
				++$x;
			} elseif ($j === 2) {
				--$z;
			} elseif ($j === 3) {
				++$z;
			}
			$block = $this->getLevel()->getBlock($this->temporalVector->setComponents($x, $y, $z));

			if ($block->canBeFlowedInto()) {
				$this->temporalVector->setComponents($x, $y - 1, $z);
				if ($this->getLevel()->getBlock($this->temporalVector)->canBeFlowedInto()) {
//					var_dump($j . " may fall down");
					$this->flowCost[$j] = 0;
					continue;
				}
//				var_dump($j . " normal");
				$this->flowCost[$j] = $this->calculateFlowCost($block, 1, $j);
			} else {
				if ($block->getId() === $this->id) {
					$this->temporalVector->setComponents($x, $y - 1, $z);
					if ($this->getLevel()->getBlock($this->temporalVector)->canBeFlowedInto()) {
//						var_dump($j . " may fall down");
						$this->flowCost[$j] = 0;
						continue;
					}
				}
//				var_dump($j . " not flowable");
				continue;
			}
		}

//		print_r($this->flowCost);
//		echo '------ END MAIN ------' . PHP_EOL;
		$minCost = 1000;
		for ($i = 0; $i < 4; $i++) {
			if ($this->flowCost[$i] < $minCost) {
				$minCost = $this->flowCost[$i];
			}
		}

		for ($i = 0; $i < 4; $i++) {
			$this->isOptimalFlowDirection[$i] = ($this->flowCost[$i] === $minCost && $this->flowCost[$i] < 8);
		}

		return $this->isOptimalFlowDirection;
	}

	private function checkForHarden($deep) {
		if ($this instanceof Lava) {
			$colliding = false;
			for ($side = 0; $side <= 5 and ! $colliding; ++$side) {
				$colliding = $this->getSide($side) instanceof Water;
			}

			if ($colliding) {
				if ($this->getDamage() === 0) {
					$this->getLevel()->setBlock($this, Block::get(Item::OBSIDIAN), true, true, $deep);
				} elseif ($this->getDamage() <= 4) {
					$this->getLevel()->setBlock($this, Block::get(Item::COBBLESTONE), true, true, $deep);
				}
			}
		}
	}


	public function getDrops(Item $item) {
		return [];
	}
	
	public function isLiquid() {
		return true;
	}
	
	public function isMayBeDestroyedByPiston() {
        return true;
    }
	
}
