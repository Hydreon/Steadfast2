<?php

namespace pocketmine\block;


use pocketmine\item\Item;
use pocketmine\item\Tool;

class JungleDoor extends Door{
    protected $id = self::JUNGLE_DOOR_BLOCK;

    public function __construct($meta = 0){
        $this->meta = $meta;
    }

    public function getName(){
        return "Jungle Door Block";
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
            [Item::JUNGLE_DOOR, 0, 1],
        ];
    }
}
