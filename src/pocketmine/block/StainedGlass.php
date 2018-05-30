<?php

namespace pocketmine\block;

use pocketmine\item\Item;

class StainedGlass extends Solid {
    
	const WHITE = 0;
	const ORANGE = 1;
	const MAGENTA = 2;
	const LIGHT_BLUE = 3;
	const YELLOW = 4;
	const LIME = 5;
	const PINK = 6;
	const GRAY = 7;
	const LIGHT_GRAY = 8;
	const CYAN = 9;
	const PURPLE = 10;
	const BLUE = 11;
	const BROWN = 12;
	const GREEN = 13;
	const RED = 14;
	const BLACK = 15;
	
    protected $id = self::STAINED_GLASS;
    
    public function __construct($meta = 0){
		$this->meta = $meta;
	}
    
    public function getName() {
        return $this->getColorName() . 'Stained Glass';
    }
    
    public function getHardness() {
        return 0.3;
    }
    
    public function getDrops(Item $item) {
        return [];
    }
    
    protected function getColorName() {
		switch ($this->meta) {
			case self::WHITE:
				return 'White ';
			case self::ORANGE:
				return 'Orange ';
			case self::MAGENTA:
				return 'Magenta ';
			case self::LIGHT_BLUE:
				return 'Light blue ';
			case self::YELLOW:
				return 'Yellow ';
			case self::LIME:
				return 'Lime ';
			case self::PINK:
				return 'Pink ';
			case self::GRAY:
				return 'Gray ';
			case self::LIGHT_GRAY:
				return 'Light gray ';
			case self::CYAN:
				return 'Cyan ';
			case self::PURPLE:
				return 'Purple ';
			case self::BLUE:
				return 'Blue ';
			case self::BROWN:
				return 'Brown ';
			case self::GREEN:
				return 'Green ';
			case self::RED:
				return 'Red ';
			case self::BLACK:
				return 'Black ';
		}
        return '';
    }
    
}
