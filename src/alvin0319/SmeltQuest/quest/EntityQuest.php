<?php

declare(strict_types=1);

namespace alvin0319\SmeltQuest\quest;

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
		array $queue
	){
		parent::__construct($name, $description, $clearType, $playingPlayers, $completedPlayers, $records, $rewardMoney, $rewards);
		$this->count = $count;
		$this->queue = $queue;
	}

	public function jsonSerialize() : array{
		return array_merge(
			parent::jsonSerialize(),
			["count" => $this->count, "queue" => $this->queue]
		);
	}
}