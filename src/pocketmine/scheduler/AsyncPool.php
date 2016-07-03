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

class AsyncPool{

	/** @var Server */
	private $server;

	protected $size;

	/** @var AsyncTask[] */
	private $tasks = [];
	/** @var int[] */
	private $taskWorkers = [];

	/** @var AsyncWorker[] */
	private $workers = [];
	/** @var int[] */
	private $workerUsage = [];

	public function __construct(Server $server, $size){
		$this->server = $server;
		$this->size = (int) $size;

		for($i = 0; $i < $this->size; ++$i){
			$this->workerUsage[$i] = 0;
			$this->workers[$i] = new AsyncWorker();
			$this->workers[$i]->setClassLoader($this->server->getLoader());
			$this->workers[$i]->start();
		}
	}

	public function submitTask(AsyncTask $task){
		if(isset($this->tasks[$task->getTaskId()]) or $task->isFinished()){
			return;
		}

		$this->tasks[$task->getTaskId()] = $task;

		$selectedWorker = mt_rand(0, $this->size - 1);
		$selectedTasks = $this->workerUsage[$selectedWorker];
		for($i = 0; $i < $this->size; ++$i){
			if($this->workerUsage[$i] < $selectedTasks){
				$selectedWorker = $i;
				$selectedTasks = $this->workerUsage[$i];
			}
		}
		
		$this->workers[$selectedWorker]->stack($task);
		$this->workerUsage[$selectedWorker]++;
		$this->taskWorkers[$task->getTaskId()] = $selectedWorker;
	}

	private function removeTask(AsyncTask $task){
		if(!$task->isTerminated() and ($task->isRunning() or !$task->isFinished())){
			return;
		}

		if(isset($this->taskWorkers[$task->getTaskId()])){
			$this->workerUsage[$this->taskWorkers[$task->getTaskId()]]--;
		}

		unset($this->tasks[$task->getTaskId()]);
		unset($this->taskWorkers[$task->getTaskId()]);	
		$task->cleanObject();
	}

	public function removeTasks(){
		foreach($this->tasks as $task){
			$this->removeTask($task);
		}

		for($i = 0; $i < $this->size; ++$i){
			$this->workerUsage[$i] = 0;
		}

		$this->taskWorkers = [];
		$this->tasks = [];
	}

	public function collectTasks(){
		foreach($this->tasks as $task){
			if($task->isFinished()){
				$task->onCompletion($this->server);
				$this->removeTask($task);
			}elseif($task->isTerminated()){
				$this->removeTask($task);
				$this->server->getLogger()->critical("Could not execute asynchronous task " . get_class($task));				
			}
		}
	}
	
	
	
	public function getSize(){
		return $this->size;
	}
	
	public function submitTaskToWorker(AsyncTask $task, $worker){
		if(isset($this->tasks[$task->getTaskId()]) or $task->isFinished()){
			return;
		}

		$worker = (int) $worker;
		if($worker < 0 or $worker >= $this->size){
			throw new \InvalidArgumentException("Invalid worker $worker");
		}

		$this->tasks[$task->getTaskId()] = $task;

		$this->workers[$worker]->stack($task);
		$this->workerUsage[$worker]++;
		$this->taskWorkers[$task->getTaskId()] = $worker;
	}
}
