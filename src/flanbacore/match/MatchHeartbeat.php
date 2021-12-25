<?php
/*
* Copyright (C) Sergittos - All Rights Reserved
* Unauthorized copying of this file, via any medium is strictly prohibited
* Proprietary and confidential
*/

declare(strict_types=1);


namespace flanbacore\match;


use flanbacore\FlanbaCore;
use pocketmine\scheduler\Task;

class MatchHeartbeat extends Task {

    public function onRun(): void {
        foreach(FlanbaCore::getInstance()->getMatchManager()->getMatches() as $match) {
            $match->tick();
        }
    }

}