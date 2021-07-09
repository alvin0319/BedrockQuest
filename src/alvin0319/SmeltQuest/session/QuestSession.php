<?php

declare(strict_types=1);

namespace alvin0319\SmeltQuest\session;

use alvin0319\SmeltQuest\quest\BlockBreakQuest;
use alvin0319\SmeltQuest\quest\BlockPlaceQuest;
use alvin0319\SmeltQuest\quest\CommandInvokeQuest;
use alvin0319\SmeltQuest\quest\EntityQuest;
use alvin0319\SmeltQuest\quest\KillEntityQuest;
use alvin0319\SmeltQuest\quest\Quest;
use pocketmine\command\Command;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\event\entity\EntityEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\Player;
use function array_values;
use function preg_match_all;
use function str_replace;
use function stripslashes;
use function substr;

final class QuestSession{

	protected Player $player;

	/** @var Quest[] */
	protected array $quests = [];

	public function __construct(Player $player, array $quests){
		$this->player = $player;
		$this->quests = $quests;
	}

	public function getPlayer() : Player{ return $this->player; }

	public function addQuest(Quest $quest) : void{
		$this->quests[$quest->getName()] = $quest;
	}

	public function getQuest(string $name) : ?Quest{
		return $this->quests[$name] ?? null;
	}

	public function completeQuest(Quest $quest) : void{
		$quest->complete($this->player);
		unset($this->quests[$quest->getName()]);
	}

	public function checkKilled(EntityEvent $event) : void{
		$cause = $event->getEntity()->getLastDamageCause();
		if(!$cause instanceof EntityDamageByEntityEvent){
			return;
		}
		$damager = $cause->getDamager();
		if(!$damager instanceof Player){
			return;
		}
		if($damager !== $this->player){
			return;
		}
		$entityNetId = $event->getEntity()::NETWORK_ID;
		foreach($this->quests as $name => $quest){
			if($quest instanceof EntityQuest){
				if($quest instanceof KillEntityQuest){
					if($quest->getEntityNetId() !== $entityNetId){
						continue;
					}
				}
				$quest->addQueue($this->player, 1);
				if($quest->canComplete($this->player)){
					$this->completeQuest($quest);
				}
			}
		}
	}

	public function onEntityKilled(EntityDeathEvent $event) : void{
		$this->checkKilled($event);
	}

	public function onPlayerKilled(PlayerDeathEvent $event) : void{
		$this->checkKilled($event);
	}

	public function onCommandInvoke(PlayerCommandPreprocessEvent $event) : void{
		$commandString = $event->getMessage();
		if(substr($commandString, 0, 1) === "/" || substr($commandString, 0, 2) === "./"){ // blame shoghi
			$commandString = str_replace(["/", "./"], ["", ""], $commandString);
			$args = [];
			preg_match_all('/"((?:\\\\.|[^\\\\"])*)"|(\S+)/u', $commandString, $matches);
			foreach($matches[0] as $k => $_){
				for($i = 1; $i <= 2; ++$i){
					if($matches[$i][$k] !== ""){
						$args[$k] = stripslashes($matches[$i][$k]);
						break;
					}
				}
			}
			$sentCommandLabel = "";
			$command = $this->player->getServer()->getCommandMap()->matchCommand($sentCommandLabel, $args);
			if($command instanceof Command){
				foreach($this->quests as $name => $quest){
					if($quest instanceof CommandInvokeQuest){
						if($quest->getCommandObj() !== null){
							if($quest->getCommandObj()->getName() === $command->getName() && $args === $quest->getArgs()){
								$this->completeQuest($quest);
							}
						}
					}
				}
			}
		}
	}

	public function onBlockBreak(BlockBreakEvent $event) : void{
		$block = $event->getBlock();
		foreach($this->quests as $name => $quest){
			if($quest instanceof BlockBreakQuest){
				if(!$quest->getAllowAllBlocks()){
					if($block->getId() !== $quest->getBlock()->getId()){
						continue;
					}
					if($block->getDamage() !== $quest->getBlock()->getDamage()){
						continue;
					}
				}
				$quest->addQueue($this->player, 1);
				if($quest->canComplete($this->player)){
					$this->completeQuest($quest);
				}
			}
		}
	}

	public function onBlockPlace(BlockPlaceEvent $event) : void{
		$block = $event->getBlock();
		foreach($this->quests as $name => $quest){
			if($quest instanceof BlockPlaceQuest){
				if(!$quest->getAllowAllBlocks()){
					if($block->getId() !== $quest->getBlock()->getId()){
						continue;
					}
					if($block->getDamage() !== $quest->getBlock()->getDamage()){
						continue;
					}
				}
				$quest->addQueue($this->player, 1);
				if($quest->canComplete($this->player)){
					$this->completeQuest($quest);
				}
			}
		}
	}

	/** @return Quest[] */
	public function getQuests() : array{ return array_values($this->quests); }
}