<?php

namespace sergittos\flanbacore\item\presets;

use pocketmine\item\ItemIds;
use pocketmine\item\ItemUseResult;
use pocketmine\math\Vector3;
use pocketmine\player\Player;

class UnlockedItem extends \sergittos\flanbacore\item\FlanbaItem{

	public function __construct(){
		parent::__construct("{GOLD}Unlocked", ItemIds::TOTEM);
	}

	public function onClickAir(Player $player, Vector3 $directionVector) : ItemUseResult{
		return ItemUseResult::SUCCESS();
	}

}