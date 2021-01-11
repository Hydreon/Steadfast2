<?php

namespace pocketmine\item;

use pocketmine\block\Block;

class BucketLava extends Bucket{

	protected $itemIdBucket = self::LAVA_BUCKET;
	protected $targetBlock = Block::LAVA;

	public function __construct($meta = 0, $count = 1){
		parent::__construct($meta, $count);		
	}
}