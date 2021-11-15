<?php

declare(strict_types=1);

namespace alvin0319\BedrockQuest\quest;

final class KillPlayerQuest extends EntityQuest{

	public static function getIdentifier() : string{
		return "KillPlayer";
	}

	public function getGoal() : string{
		return "Kill Player x{$this->count}";
	}

	public static function jsonDeserialize(array $data) : KillPlayerQuest{
		return new KillPlayerQuest(
			$data["name"],
			$data["description"],
			$data["clearType"],
			$data["playingPlayers"],
			$data["completedPlayers"],
			$data["records"],
			$data["rewardMoney"],
			$data["rewards"],
			$data["count"],
			$data["executeCommands"],
			$data["queue"],
			$data["additionalRewardMessage"]
		);
	}
}