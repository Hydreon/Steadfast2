<?php

namespace pocketmine\network\protocol\v120;

use pocketmine\inventory\transactions\SimpleTransactionData;
use pocketmine\network\protocol\ContainerSetContentPacket;
use pocketmine\network\protocol\Info120;
use pocketmine\network\protocol\PEPacket;

class InventoryTransactionPacket extends PEPacket {
	
	const NETWORK_ID = Info120::INVENTORY_TRANSACTION_PACKET;
	const PACKET_NAME = "INVENTORY_TRANSACTION_PACKET";
	
	const TRANSACTION_TYPE_NORMAL = 0;
	const TRANSACTION_TYPE_INVENTORY_MISMATCH = 1;
	const TRANSACTION_TYPE_ITEM_USE = 2;
	const TRANSACTION_TYPE_ITEM_USE_ON_ENTITY = 3;
	const TRANSACTION_TYPE_ITEM_RELEASE = 4;
	
	const INV_SOURCE_TYPE_CONTAINER = 0;
	const INV_SOURCE_TYPE_GLOBAL = 1;
	const INV_SOURCE_TYPE_WORLD_INTERACTION = 2;
	const INV_SOURCE_TYPE_CREATIVE = 3;
	
	public $transactionType;
	/** @var SimpleTransactionData */
	public $transactions;
	public $b;
	
	public function decode($playerProtocol) {
		$this->transactionType = $this->getVarInt();
		$this->transactions = $this->getTransactions($playerProtocol);
		// complex inventory transaction
		// exist for each transaction, need look on sample from client
	}

	public function encode($playerProtocol) {
		$this->reset($playerProtocol);
		/** @todo Доделать */
	}
	
	private function getTransactions($playerProtocol) {
		$transactions = [];
		$actionsCount = $this->getVarInt();
		for ($i = 0; $i < $actionsCount; $i++) {
			$tr = new SimpleTransactionData();
			$sourceType = $this->getVarInt();
			switch ($sourceType) {
				case self::INV_SOURCE_TYPE_CONTAINER;
					$tr->inventoryId = $this->getSignedVarInt(); 
					break;
				case self::INV_SOURCE_TYPE_GLOBAL: // ???
					break;
				case self::INV_SOURCE_TYPE_WORLD_INTERACTION:
					$this->getVarInt(); // flags NoFlag = 0 WorldInteraction_Random = 1
					break;
				case self::INV_SOURCE_TYPE_CREATIVE:
					$tr->inventoryId = ContainerSetContentPacket::SPECIAL_CREATIVE; 
					break;
				default:
					continue;
			}
			$tr->slot = $this->getVarInt();
			$tr->oldItem = $this->getSlot($playerProtocol);
			$tr->newItem = $this->getSlot($playerProtocol);
			$transactions[] = $tr;
		}
		return $transactions;
	}
	
}
