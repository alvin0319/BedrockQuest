<?php

declare(strict_types=1);

namespace alvin0319\SmeltQuest\quest;

use pocketmine\Player;
use function array_merge;

abstract class EntityQuest extends Quest{
	use CountableTrait;

	protected int $count;

	public function __construct(
		string $name,
		string $description,
		int $clearType,
		array $playingPlayers,
		array $completedPlayers,
		array $records,
		int $rewardMoney,
		array $rewards,
		int $count,
		array $executeCommands = [],
		array $queue = [],
		array $additionalRewardMessage = []
	){
		parent::__construct($name, $description, $clearType, $playingPlayers, $completedPlayers, $records, $rewardMoney, $rewards, $executeCommands, $additionalRewardMessage);
		$this->count = $count;
		$this->queue = $queue;
	}

	public function canComplete(Player $player) : bool{
		return $this->getQueue($player) >= $this->count;
	}

	public function jsonSerialize() : array{
		return array_merge(
			parent::jsonSerialize(),
			["count" => $this->count, "queue" => $this->queue]
		);
	}

	public function getProgress(Player $player) : float{
		$queue = $this->getQueue($player);
		if($queue === 0){
			return 0;
		}
		return (float) ($queue / $this->count) * 100;
	}
}