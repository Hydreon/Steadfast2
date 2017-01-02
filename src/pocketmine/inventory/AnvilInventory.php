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

use pocketmine\item\Armor;
use pocketmine\item\Item;
use pocketmine\item\Tool;
use pocketmine\level\Position;
use pocketmine\Player;


class AnvilInventory extends ContainerInventory{
    
    static protected $materialList = [
        'tools' => [
            Tool::TIER_GOLD => [Item::GOLD_INGOT],
            Tool::TIER_STONE => [Item::STONE],
            Tool::TIER_IRON => [Item::IRON_INGOT],
            Tool::TIER_DIAMOND => [Item::DIAMOND],
        ],
        'armor' => [
            Armor::TIER_LEATHER => [Item::LEATHER],
            Armor::TIER_GOLD => [Item::GOLD_INGOT],
            Armor::TIER_CHAIN => [Item::IRON_INGOT],
            Armor::TIER_IRON => [Item::IRON_INGOT],
            Armor::TIER_DIAMOND => [Item::DIAMOND],
        ]
    ];


    public function __construct(Position $pos){
		parent::__construct(new FakeBlockMenu($this, $pos), InventoryType::get(InventoryType::ANVIL));
	}

	/**
	 * @return FakeBlockMenu
	 */
	public function getHolder(){
		return $this->holder;
	}
    
    public function onOpen(Player $who) {
        parent::onOpen($who);
        $who->craftingType = Player::CRAFTING_ANVIL;
    }

	public function onClose(Player $who) {
		parent::onClose($who);
        
		for($i = 0; $i < 2; ++$i){
			$this->getHolder()->getLevel()->dropItem($this->getHolder()->add(0.5, 0.5, 0.5), $this->getItem($i));
			$this->clear($i);
		}
        
        $who->craftingType = Player::CRAFTING_DEFAULT;
	}
    
    public function putItem(Item $item) {
        foreach ($this->slots as $slotItem) {
            if ($slotItem->equals($item)) {
                $slotItem->setCount($slotItem->getCount() + $item->getCount());
                return;
            }
        }
        if (count($this->slots) >= 2) {
            return;
        }
        $this->slots[] = $item;
    }
    
    public function takeAwayItem(Item $item) {
        foreach($this->slots as $i => $slotItem) {
            if ($slotItem->deepEquals($item)) {
                unset($this->slots[$i]);
                return true;
            }
        }
        return false;
    }
    
    public function clearItems($returnItemToHolder = false) {
        if (!($this->holder instanceof Player)) {
            $this->slots = [];
            return;
        }
        foreach ($this->slots as $i => $slotItem) {
            if ($returnItemToHolder) {
                $this->holder->getInventory()->addItem($slotItem);
            }
        }
        $this->slots = [];
        $this->sendContents($this->holder);
    }
    
    /**
     * 
     * @todo add combining for enchanting books
     * @todo add support of experience
     * 
     * @param Item $item
     * @return boolean
     */
    public function checkResult(Item $item) {
        if (!($item instanceof Armor) && !($item instanceof Tool)) {
            return false;
        }
        $foundItem = false;
        $foundMaterial = false;
        foreach ($this->slots as $i => $slotItem) {
            if ($slotItem->getId() === $item->getId()) {
                if ($foundItem === true) {
                    $foundMaterial = true;
                    break;
                }
                $foundItem = true;
            } else {
                $materials = ($item instanceof Armor) ? self::$materialList['armor'] : self::$materialList['tools'];
                if (isset($materials[$item->getTier()])) {
                    $foundMaterial = in_array($slotItem->getId(), $materials[$item->getTier()]);
                }
            }
        }
        // checking renaming
        if ($foundItem && !$foundMaterial) {
            $foundMaterial = !empty($item->getCustomName());
        }
        return $foundItem && $foundMaterial;
    }
}