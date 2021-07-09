<?php

declare(strict_types=1);

namespace alvin0319\SmeltQuest\quest;

use pocketmine\network\mcpe\protocol\AddActorPacket;
use function array_merge;
use function str_replace;

final class KillEntityQuest extends EntityQuest{

	protected int $entityNetId;

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
		int $entityNetId = -1,
		array $additionalRewardMessage = []
	){
		parent::__construct($name, $description, $clearType, $playingPlayers, $completedPlayers, $records, $rewardMoney, $rewards, $count, $executeCommands, $queue, $additionalRewardMessage);
		$this->entityNetId = $entityNetId;
	}

	public static function getIdentifier() : string{
		return "KillEntity";
	}

	public function getEntityNetId() : int{ return $this->entityNetId; }

	public function getGoal() : string{
		return "Kill " . str_replace("minecraft:", "", AddActorPacket::LEGACY_ID_MAP_BC[$this->entityNetId] ?? "Unknown") . " x{$this->count}";
	}

	public function jsonSerialize() : array{
		return array_merge(
			parent::jsonSerialize(),
			["entityNetId" => $this->entityNetId]
		);
	}

	public static function jsonDeserialize(array $data) : KillEntityQuest{
		return new KillEntityQuest(
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
			$data["entityNetId"],
			$data["additionalRewardMessage"]
		);
	}
}