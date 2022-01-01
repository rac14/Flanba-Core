<?php
/*
* Copyright (C) Sergittos - All Rights Reserved
* Unauthorized copying of this file, via any medium is strictly prohibited
* Proprietary and confidential
*/

declare(strict_types=1);


namespace sergittos\flanbacore\listener;


use pocketmine\block\GlazedTerracotta;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\item\ItemIds;
use pocketmine\player\Player;
use sergittos\flanbacore\event\SessionDeathEvent;
use sergittos\flanbacore\session\SessionFactory;

class MatchListener implements Listener {

    public function onDeath(SessionDeathEvent $event): void {
        $session = $event->getSession();
        $cause = $session->getPlayer()->getLastDamageCause();
        $session->teleportToTeamSpawnPoint();
        if($cause instanceof EntityDamageByEntityEvent) {
            $damager = $cause->getDamager();
            if(!$damager instanceof Player) {
                return;
            }
            $damager_session = SessionFactory::getSession($damager);
            if(!$damager_session->hasMatch()) {
                return;
            }
            if($damager_session->getMatch()->getId() === $session->getMatch()->getId()) {
                $damager_session->getTeam()->addKill();
            }
        }
    }

    public function onDamage(EntityDamageEvent $event): void {
        $entity = $event->getEntity();
        if(!$entity instanceof Player) {
            return;
        }
        $session = SessionFactory::getSession($entity);
        if($session->hasMatch() and $entity->getHealth() - $event->getFinalDamage() <= 0) {
            $death_event = new SessionDeathEvent($session);
            $death_event->call();
            $event->cancel();
        }
    }

    public function onMove(PlayerMoveEvent $event): void {
        $session = SessionFactory::getSession($player = $event->getPlayer());
        if(!$session->hasMatch()) {
            return;
        }

        $match = $session->getMatch();
        $players = $match->getPlayers();
        $session_team = $session->getTeam();
        foreach($match->getTeams() as $team) {
            if(!$team->getGoalArea()->isInside($player->getPosition(), true)) {
                continue;
            }
            if($session_team->getColor() === $team->getColor()) {
                $session->teleportToTeamSpawnPoint();
                return;
            }
            $session_team->addScore();
            $color = $session_team->getColor();
            if($session_team->getScoreNumber() >= 5) {
                $match->setWinnerTeam($session_team);
                $match->setLoserTeam($team);
                $match->setStage($match::ENDING_STAGE);

                foreach($players as $player) {
                    $player->title(
                        $color . strtoupper($session_team->getName()) . " WINS!",
                        $color . $session_team->getScoreNumber() . " {WHITE}- " .
                        $team->getColor() . $team->getScoreNumber()
                    );
                    $player->teleportToTeamSpawnPoint();
                }
                return;
            } else {
                $match->setSessionScored($session);
                $match->setStage($match::OPENING_CAGES_STAGE);
            }

            $countdown = $match->getCountdown();
            $countdown--;
            $match->setCountdown($countdown);
            foreach($players as $player) {
                $player->teleportToTeamSpawnPoint();
                $player->updateScoreboard();
                $match->broadcastTitle(
                    $color . $session->getUsername() . " scored!",
                    "{GRAY}Cages open in {GREEN}{$countdown}s{GRAY}..."
                );

                // TODO: Clean this
            }
        }
    }

    public function onBreak(BlockBreakEvent $event): void {
        if($event->getBlock()->getId() !== ItemIds::TERRACOTTA) {
            $event->cancel();
        }
    }

    public function onQuit(PlayerQuitEvent $event): void {
        $session = SessionFactory::getSession($event->getPlayer());
        if($session->hasMatch()) {
            $session->setMatch(null);
        }
    }

}