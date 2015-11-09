<?php

namespace pocketmine\block;


use pocketmine\item\Item;
use pocketmine\item\Tool;

class DarkOakDoor extends Door{
    protected $id = self::DARK_OAK_DOOR_BLOCK;

    public function __construct($meta = 0){
        $this->meta = $meta;
    }

    public function getName(){
        return "Dark Oak Door Block";
    }

    public function canBeActivated(){
        return \true;
    }

    public function getHardness(){
        return 3;
    }

    public function getToolType(){
        return Tool::TYPE_AXE;
    }

    public function getDrops(Item $item){
        return [
            [Item::DARK_OAK_DOOR, 0, 1],
        ];
    }
}