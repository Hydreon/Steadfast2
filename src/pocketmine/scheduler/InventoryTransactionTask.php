<?php
namespace pocketmine\scheduler;

class InventoryTransactionTask extends Task{

	CONST MAX_ATTEMPTS = 20;

	static public $data = []; 

	public function onRun($currentTicks){		
		foreach (self::$data as $k => $trGroup) {			
			$trGroup->attempts++;			
			try {
				if (!$trGroup->execute()) {				
					//echo '[INFO] Transaction execute fail.'.PHP_EOL;
					if ($trGroup->attempts >= self::MAX_ATTEMPTS) {
						$trGroup->sendInventories();
						unset(self::$data[$k]);	
					}
				} else {
					unset(self::$data[$k]);					
	//				echo '[INFO] Transaction successfully executed.'.PHP_EOL;
				}
			} catch (\Exception $ex) {				
	//			echo '[INFO] Transaction execute exception. ' . $ex->getMessage() .PHP_EOL;
			}
		}

	}

}