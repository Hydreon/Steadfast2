<?php

namespace pocketmine\event\server;

class SendRecipiesList extends ServerEvent {
	
	public static $handlerList = null;
	protected $recipiesList;
	
	public function __construct($recipiesList) {
		$this->recipiesList = $recipiesList;
	}
	
	public function getRecipies() {
		return $this->recipiesList;
	}
	
	public function setRecipies($recipiesList) {
		$this->recipiesList = $recipiesList;
	}
	
}
