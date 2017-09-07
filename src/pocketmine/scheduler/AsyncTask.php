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

 *
 *
*/

namespace pocketmine\scheduler;

use pocketmine\Server;
use pocketmine\Collectable;

/**
 * Class used to run async tasks in other threads.
 *
 * WARNING: Do not call PocketMine-MP API methods, or save objects from/on other Threads!!
 */
abstract class AsyncTask extends Collectable{

	private $result = null;
	/** @var int */
	private $taskId = null;
	
	protected $isFinished = false;

	public function run(){		
		$this->result = null;

		$this->onRun();
		$this->isFinished = true;
		//$this->setGarbage();
	}

	/**
	 * @deprecated
	 *
	 * @return bool
	 */
	public function isFinished(){
		return $this->isFinished;
	}

	/**
	 * GETs an URL using cURL
	 *
	 * @param     $page
	 * @param int $timeout default 10
	 *
	 * @return bool|mixed
	 */
	public static function getURL($page, $timeout = 10){
		$ch = curl_init($page);
		curl_setopt($ch, CURLOPT_HTTPHEADER, ["User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64; rv:12.0) Gecko/20100101 Firefox/12.0 PocketMine-MP"]);
		curl_setopt($ch, CURLOPT_AUTOREFERER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
		curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, (int) $timeout);
		$ret = curl_exec($ch);
		curl_close($ch);

		return $ret;
	}

	/**
	 * POSTs data to an URL
	 *
	 * @param              $page
	 * @param array|string $args
	 * @param int          $timeout
	 *
	 * @return bool|mixed
	 */
	public static function postURL($page, $args, $timeout = 10){
		$ch = curl_init($page);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
		curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $args);
		curl_setopt($ch, CURLOPT_AUTOREFERER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, ["User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64; rv:12.0) Gecko/20100101 Firefox/12.0 PocketMine-MP"]);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, (int) $timeout);
		$ret = curl_exec($ch);
		curl_close($ch);

		return $ret;
	}

	/**
	 * @return mixed
	 */
	public function getResult(){
		return unserialize($this->result);
	}

	/**
	 * Gets something into the local thread store.
	 * You have to initialize this in some way from the task on run
	 *
	 * @param string $identifier
	 * @return mixed
	 */
	public function getFromThreadStore($identifier){
		global $store;
		return $this->isFinished() ? null : $store[$identifier];
	}
	
	/**
	 * @return bool
	 */
	public function hasResult(){
		return $this->result !== null;
	}

	/**
	 * @param mixed $result
	 */
	public function setResult($result){
		$this->result = serialize($result);
	}

	public function setTaskId($taskId){
		$this->taskId = $taskId;
	}

	public function getTaskId(){
		return $this->taskId;
	}

	/**
	 * Actions to execute when run
	 *
	 * @return void
	 */
	public abstract function onRun();

	/**
	 * Actions to execute when completed (on main thread)
	 * Implement this if you want to handle the data in your AsyncTask after it has been processed
	 *
	 * @param Server $server
	 *
	 * @return void
	 */
	public function onCompletion(Server $server){

	}
	
	public function cleanObject(){
		foreach($this as $p => $v){
			if(!($v instanceof \Threaded)){
				$this->{$p} = null;
			}
		}
	}

	public function saveToThreadStore($identifier, $value){
		global $store;
		if(!$this->isFinished()){
			$store[$identifier] = $value;
		}
	}
	
}
