<?php

namespace pocketmine\block;

class StainedGlassPane extends StainedGlass {
    
    protected $id = self::STAINED_GLASS_PANE;
    
    public function getName() {
        return $this->getColorName() . 'Stained Glass Pane';
    }
    
}
