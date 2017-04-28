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

namespace pocketmine\event;

use pocketmine\command\defaults\TimingsCommand;
use pocketmine\entity\Living;
use pocketmine\plugin\PluginManager;
use pocketmine\Server;

class TimingsHandler{

	/** @var TimingsHandler[] */
	private static $HANDLERS = [];

	private $name;
	/** @var TimingsHandler */
	private $parent = null;

	private $count = 0;
	private $start = 0;
	private $timingDepth = 0;
	private $totalTime = 0;
	private $curTickTotal = 0;
	private $violations = 0;

	/**
	 * @param string         $name
	 * @param TimingsHandler $parent
	 */
	public function __construct($name, TimingsHandler $parent = null){
		$this->name = $name;
		if($parent instanceof TimingsHandler){
			$this->parent = $parent;
		}

		self::$HANDLERS[spl_object_hash($this)] = $this;
	}

	public static function printTimings($fp){
//		fwrite($fp, "Minecraft" . PHP_EOL);
		$log = "----------------------------------------------------------------". PHP_EOL;
		foreach(self::$HANDLERS as $timings){
			$time = $timings->totalTime;
			$count = $timings->count;
			if($count === 0){
				continue;
			}
			if($timings->violations > 0) {
			//	$avg = $time / $count;
				if($count == 1){
					$log .= "    " . $timings->name . " Time: " . ($time) . PHP_EOL;
				} else {
					$avg = $time / $count;
					$log .= "    " . $timings->name . " Time: " . ($time) . " Count: " . $count . " Avg: " . ($avg) . PHP_EOL;
				}
			}
			//fwrite($fp, "    " . $timings->name . " Time: " . ($time) . " Count: " . $count . " Avg: " . ($avg) . " Violations: " . $timings->violations . PHP_EOL);
		}

//		fwrite($fp, "# Version " . Server::getInstance()->getVersion() . PHP_EOL);
//		fwrite($fp, "# " . Server::getInstance()->getName() . " " . Server::getInstance()->getPocketMineVersion() . PHP_EOL);

		$entities = 0;
		$livingEntities = 0;
		foreach(Server::getInstance()->getLevels() as $level){
			$entities += count($level->getEntities());
			foreach($level->getEntities() as $e){
				if($e instanceof Living){
					++$livingEntities;
				}
			}
		}
		$log .= "# Entities " . $entities . PHP_EOL;
		$log .= "# LivingEntities " . $livingEntities . PHP_EOL;
		file_put_contents($fp, $log, FILE_APPEND | LOCK_EX);
//		fwrite($fp, "# Entities " . $entities . PHP_EOL);
//		fwrite($fp, "# LivingEntities " . $livingEntities . PHP_EOL);
	}

	public static function reload(){
		if(Server::getInstance()->getPluginManager()->useTimings()){
			foreach(self::$HANDLERS as $timings){
				$timings->reset();
			}
			TimingsCommand::$timingStart = microtime(true);
		}
	}

	public static function tick(){
		if(PluginManager::$useTimings){
			foreach(self::$HANDLERS as $timings){
				if($timings->curTickTotal > 0.01){
					$timings->violations ++;//= round($timings->curTickTotal / 0.05);
				}
				$timings->curTickTotal = 0;
				$timings->timingDepth = 0;
			}
		}
	}

	public function startTiming(){
//		if(PluginManager::$useTimings and ++$this->timingDepth === 1){
//			$this->start = microtime(true);
//			if($this->parent instanceof TimingsHandler and ++$this->parent->timingDepth === 1){
//				$this->parent->start = $this->start;
//			}
//		}
	}

	public function stopTiming(){
//		if(PluginManager::$useTimings){
//			if(--$this->timingDepth !== 0 or $this->start === 0){
//				return;
//			}
//
//			$diff = microtime(true) - $this->start;
//			$this->totalTime += $diff;
//			$this->curTickTotal += $diff;
//			$this->count++;
//			$this->start = 0;
//			if($this->parent instanceof TimingsHandler){
//				$this->parent->stopTiming();
//			}
//		}
	}

	public function reset(){
		$this->count = 0;
		$this->violations = 0;
		$this->curTickTotal = 0;
		$this->totalTime = 0;
		$this->start = 0;
		$this->timingDepth = 0;
	}

	public function remove(){
		unset(self::$HANDLERS[spl_object_hash($this)]);
	}

}