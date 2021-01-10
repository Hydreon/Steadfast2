<?php

namespace pocketmine\item;

use pocketmine\block\Block;

class BucketWater extends Bucket{

	protected $itemIdBucket = self::WATER_BUCKET;
	protected $targetBlock = Block::WATER;

	public function __construct($meta = 0, $count = 1){
		parent::__construct($meta, $count);		
	}
}