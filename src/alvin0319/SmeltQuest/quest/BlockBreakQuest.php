<?php

declare(strict_types=1);

namespace alvin0319\SmeltQuest\quest;

final class BlockBreakQuest extends BlockQuest{

	public function getGoal() : string{
		return "Break block {$this->block->getName()} x{$this->count}";
	}

	public static function getIdentifier() : string{
		return "BlockBreak";
	}

	public static function jsonDeserialize(array $data) : BlockBreakQuest{
		return new BlockBreakQuest(
			$data["name"],
			$data["description"],
			$data["clearType"],
			$data["playingPlayers"],
			$data["completedPlayers"],
			$data["records"],
			$data["rewardMoney"],
			$data["rewards"],
			$data["blockId"],
			$data["blockMeta"],
			$data["count"],
			$data["blockQueue"]
		);
	}
}