<?php

declare(strict_types=1);

namespace alvin0319\SmeltQuest\quest;

use pocketmine\Player;

trait CountableTrait{

	protected array $queue = [];

	public function addQueue(Player $player, int $amount) : void{
		$this->queue[$player->getName()] = ($this->blockQueue[$player->getName()] ?? 0) + $amount;
	}

	public function getQueue(Player $player) : int{ return $this->queue[$player->getName()] ?? 0; }

	public function canComplete(Player $player) : bool{
		return $this->getQueue($player) >= $this->count;
	}
}