<?php

namespace pocketmine\inventory\transactions;

use pocketmine\item\Item;

class SimpleTransactionData {
	
	/** @var integer */
	public $inventoryId = -1;
	/** @var integer */
	public $slot = -1;
	/** @var Item */
	public $oldItem;
	/** @var Item */
	public $newItem;
	
	public function __construct() {
		$this->oldItem = Item::get(Item::AIR);
		$this->newItem = Item::get(Item::AIR);
	}
	
}
