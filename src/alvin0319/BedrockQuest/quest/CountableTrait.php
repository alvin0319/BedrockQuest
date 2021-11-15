<?php

declare(strict_types=1);

namespace alvin0319\BedrockQuest\quest;

use pocketmine\Player;

trait CountableTrait{

	protected array $queue = [];

	public function addQueue(Player $player, int $amount) : void{
		if(!$this->isStarted($player)){
			return;
		}
		if(!isset($this->queue[$player->getName()])){
			$this->queue[$player->getName()] = 0;
		}
		$this->queue[$player->getName()] += $amount;
		$this->onProgressAdded($player);
	}

	public function getQueue(Player $player) : int{ return $this->queue[$player->getName()] ?? 0; }

	public function canComplete(Player $player) : bool{
		return $this->getQueue($player) >= $this->count;
	}

	public function onPlayerRemoved(Player $player) : void{
		unset($this->queue[$player->getName()]);
	}
}