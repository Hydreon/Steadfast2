<?php

namespace pocketmine\scoreboard;

use pocketmine\network\protocol\v310\SetDisplayObjectivePacket;
use pocketmine\network\protocol\v310\RemoveObjectivePacket;
use pocketmine\network\protocol\v310\SetScorePacket;
use pocketmine\Server;
use pocketmine\network\protocol\Info;

class Scoreboard {

	protected static $lastScoreboardId = 1;
	protected $lastEntryScoreboardId = 1;
	protected $id;
	protected $name;
	protected $sort;
	protected $players = [];
	protected $entries = [];

	public function __construct($name, $entries = [], $sort = SetDisplayObjectivePacket::SORT_DESC) {
		$this->id = self::$lastScoreboardId++;
		$this->name = $name;
		$this->sort = $sort;
		foreach ($entries as $name => $score) {
			$this->updateEntry($name, $score, false);
		}
	}

	public function updateEntry($name, $score, $withUpdate = true) {
		if (isset($this->entries[$name])) {
			$this->entries[$name]['score'] = $score;
		} else {
			$this->entries[$name] = ['score' => $score, 'scoreboardId' => $this->lastEntryScoreboardId++];
		}
		if ($withUpdate) {
			$this->broadcastUpdateScore();
		}
	}

	public function removeEntry($name, $withUpdate = true) {
		if (isset($this->entries[$name])) {
			unset($this->entries[$name]);
			if ($withUpdate) {
				$this->respawnScoreboard();
			}
		}
	}

	public function addPlayer($player) {
		if ($player->getPlayerProtocol() < Info::PROTOCOL_310) {
			return;
		}
		if (!isset($this->players[$player->getId()])) {
			if (!is_null($oldScoreboard = $player->getScoreboard())) {
				$oldScoreboard->removePlayer($player);
			}
			$this->players[$player->getId()] = $player;
			$player->setScoreboard($this);
			$player->dataPacket($this->getSetObjectivePacket());
			if (!empty($this->entries)) {
				$player->dataPacket($this->getUpdateScorePacket());
			}
		}
	}

	public function removePlayer($player) {
		if (isset($this->players[$player->getId()])) {
			unset($this->players[$player->getId()]);
			$player->setScoreboard(null);
			$player->dataPacket($this->getRemoveObjectivePacket());
		}
	}

	public function broadcastUpdateScore() {
		if (!empty($this->players) && !empty($this->entries)) {
			Server::broadcastPacket($this->players, $this->getUpdateScorePacket());
		}
	}
	
	public function close() {
		foreach ($this->players as $player) {
			$this->removePlayer($player);
		}
		$this->entries = [];
	}

	protected function getUpdateScorePacket() {
		$entries = [];
		foreach ($this->entries as $name => $entry) {
			$entries[] = [
				'scoreboardId' => $entry['scoreboardId'],
				'objectiveName' => $this->id,
				'score' => $entry['score'],
				'type' => SetScorePacket::ENTRY_TYPE_FAKE_PLAYER,
				'customName' => $name . ' ',
			];
		}
		$pk = new SetScorePacket();
		$pk->type = SetScorePacket::TYPE_CHANGE;
		$pk->entries = $entries;
		return $pk;
	}

	protected function getRemoveObjectivePacket() {
		$pk = new RemoveObjectivePacket();
		$pk->objectiveName = $this->id;
		return $pk;
	}

	protected function getSetObjectivePacket() {
		$pk = new SetDisplayObjectivePacket();
		$pk->displaySlot = SetDisplayObjectivePacket::DISPLAY_SLOT_SIDEBAR;
		$pk->objectiveName = $this->id;
		$pk->displayName = $this->name;
		$pk->criteriaName = SetDisplayObjectivePacket::CRITERIA_DUMMY;
		$pk->sortOrder = $this->sort;
		return $pk;
	}

	protected function respawnScoreboard() {
		if (empty($this->players)) {
			return;
		}
		Server::broadcastPacket($this->players, $this->getRemoveObjectivePacket());
		Server::broadcastPacket($this->players, $this->getSetObjectivePacket());
		if (!empty($this->entries)) {
			Server::broadcastPacket($this->players, $this->getUpdateScorePacket());
		}
	}

}
