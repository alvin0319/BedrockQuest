<?php

declare(strict_types=1);

namespace alvin0319\BedrockQuest\event;

use alvin0319\BedrockQuest\quest\Quest;
use pocketmine\event\Cancellable;
use pocketmine\event\player\PlayerEvent;
use pocketmine\Player;

abstract class QuestEvent extends PlayerEvent implements Cancellable{

	protected Quest $quest;

	public function __construct(Player $player, Quest $quest){
		$this->player = $player;
		$this->quest = $quest;
	}

	public function getQuest() : Quest{
		return $this->quest;
	}
}