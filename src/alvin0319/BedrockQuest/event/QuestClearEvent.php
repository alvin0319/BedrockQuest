<?php

declare(strict_types=1);

namespace alvin0319\BedrockQuest\event;

use alvin0319\BedrockQuest\quest\Quest;
use pocketmine\item\Item;
use pocketmine\Player;

final class QuestClearEvent extends QuestEvent{

	/** @var Item[] */
	protected array $rewards = [];

	protected int $rewardMoney = 0;

	public function __construct(Player $player, Quest $quest, array $rewards, int $rewardMoney){
		parent::__construct($player, $quest);
		$this->rewards = $rewards;
		$this->rewardMoney = $rewardMoney;
	}

	public function getRewards() : array{ return $this->rewards; }

	public function getRewardMoney() : int{ return $this->rewardMoney; }

	/** @param Item[] $rewards */
	public function setRewards(array $rewards) : void{
		(function(Item ...$item) : void{ })(...$rewards); // check valid
		$this->rewards = $rewards;
	}

	public function setRewardMoney(int $rewardMoney) : void{ $this->rewardMoney = $rewardMoney; }
}