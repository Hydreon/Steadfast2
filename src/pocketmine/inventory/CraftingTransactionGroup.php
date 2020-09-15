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

namespace pocketmine\inventory;

use pocketmine\event\inventory\CraftItemEvent;
use pocketmine\item\Item;
use pocketmine\Server;

class CraftingTransactionGroup extends SimpleTransactionGroup{
	/** @var Item[] */
	protected $input = [];
	/** @var Item[] */
	protected $output = [];

	/** @var Recipe */
	protected $recipe = null;

	public function __construct(SimpleTransactionGroup $group){
		parent::__construct();
		$this->transactions = $group->getTransactions();
		$this->inventories = $group->getInventories();
		$this->source = $group->getSource();

		$this->matchItems($this->output, $this->input);
	}

	public function addTransaction(Transaction $transaction){
		parent::addTransaction($transaction);
		$this->input = [];
		$this->output = [];
		$this->matchItems($this->output, $this->input);
	}

	/**
	 * Gets the Items that have been used
	 *
	 * @return Item[]
	 */
	public function getRecipe(){
		return $this->input;
	}

	/**
	 * @return Item
	 */
	public function getResult(){
		reset($this->output);

		return current($this->output);
	}

	public function canExecute(){
		if(count($this->output) !== 1 or count($this->input) === 0){
			return false;
		}

		return $this->getMatchingRecipe() instanceof Recipe;
	}

	/**
	 * @return Recipe
	 */
	public function getMatchingRecipe(){
		if($this->recipe === null){
			$this->recipe = Server::getInstance()->getCraftingManager()->matchTransaction($this);
		}

		return $this->recipe;
	}

	public function execute(){
		if($this->hasExecuted() or !$this->canExecute()){
			return false;
		}

		Server::getInstance()->getPluginManager()->callEvent($ev = new CraftItemEvent($this, $this->getMatchingRecipe()));
		if($ev->isCancelled()){
			foreach($this->inventories as $inventory){
				$inventory->sendContents($inventory->getViewers());
			}

			return false;
		}

		foreach($this->transactions as $transaction){
			$transaction->getInventory()->setItem($transaction->getSlot(), $transaction->getTargetItem(), $this->getSource());
		}
		$this->hasExecuted = true;

		return true;
	}

	public function squashDuplicateSlotChanges() {
		$slotChanges = [];		
		$inventories = [];

		$slots = [];

		foreach($this->transactions as $key => $tr) {
			if (empty($this->getSource()->getCurrentWindow())) {
				continue;
			}
					
			$slotChanges[$h = (spl_object_hash($tr->getInventory()) . "@" . $tr->getSlot())][] = $tr;
			$inventories[$h] = $tr->getInventory();
			$slots[$h] = $tr->getSlot();			
		}

		foreach($slotChanges as $hash => $list) {
			if(count($list) === 1) { 
				continue;
			}
			$inventory = $inventories[$hash];
			$slot = $slots[$hash];

			$sourceItem = $inventory->getItem($slot);

			$targetItem = $this->findResultItem($sourceItem, $list);
			if($targetItem === null){
				return false;
			}

			foreach($list as $transaction){
				unset($this->transactions[spl_object_hash($transaction)]);
			}

			if(!$targetItem->equals($sourceItem) || $targetItem->getCount != $sourceItem->getCount()) {
				$this->addTransaction(new BaseTransaction($inventory, $slot, $sourceItem, $targetItem));
			}
		}
		return true;
	}

	protected function findResultItem($needOrigin, array $possibleActions) : ?Item{
		foreach($possibleActions as $i => $action){
			if($action->getSourceItem()->equalsExact($needOrigin)){
				$newList = $possibleActions;
				unset($newList[$i]);
				if(count($newList) === 0){
					return $action->getTargetItem();
				}
				$result = $this->findResultItem($action->getTargetItem(), $newList);
				if($result !== null){
					return $result;
				}
			}
		}

		return null;
	}

	//TODO - adaptive for SteadFast
	public function validate() : void{
		$this->matchItems($this->output, $this->input);

		$failed = 0;
		foreach($this->craftingManager->matchRecipeByOutputs($this->outputs) as $recipe){
			try{
				//compute number of times recipe was crafted
				$this->repetitions = $this->matchRecipeItems($this->outputs, $recipe->getResultsFor($this->source->getCraftingGrid()), false);
				//assert that $repetitions x recipe ingredients should be consumed
				$this->matchRecipeItems($this->inputs, $recipe->getIngredientList(), true, $this->repetitions);

				//Success!
				$this->recipe = $recipe;
				break;
			}catch(TransactionValidationException $e){
				//failed
				++$failed;
			}
		}

		if($this->recipe === null){
			throw new TransactionValidationException("Unable to match a recipe to transaction (tried to match against $failed recipes)");
		}
	}
}