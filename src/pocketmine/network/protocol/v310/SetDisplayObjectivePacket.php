<?php

namespace pocketmine\network\protocol\v310;

use pocketmine\network\protocol\Info310;
use pocketmine\network\protocol\PEPacket;

class SetDisplayObjectivePacket extends PEPacket {

	const NETWORK_ID = Info310::SET_DISPLAY_OBJECTIVE_PACKET;
	const PACKET_NAME = "SET_DISPLAY_OBJECTIVE_PACKET";
	const SORT_ASC = 0;
	const SORT_DESC = 1;
	const CRITERIA_DUMMY = 'dummy';
	const DISPLAY_SLOT_SIDEBAR = 'sidebar';
	const DISPLAY_SLOT_LIST = 'list';
	const DISPLAY_SLOT_BELLOW_NAME = 'belowname';

	public $displaySlot;
	public $objectiveName;
	public $displayName;
	public $criteriaName;
	public $sortOrder;

	public function encode($playerProtocol) {
		$this->reset($playerProtocol);
		$this->putString($this->displaySlot);
		$this->putString($this->objectiveName);
		$this->putString($this->displayName);
		$this->putString($this->criteriaName);
		$this->putByte($this->sortOrder);
	}

	public function decode($playerProtocol) {
		
	}

}
