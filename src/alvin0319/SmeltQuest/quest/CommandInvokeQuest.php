<?php

declare(strict_types=1);

namespace alvin0319\SmeltQuest\quest;

use function array_merge;

final class CommandInvokeQuest extends Quest{

	protected string $command;

	public function __construct(
		string $name,
		string $description,
		int $clearType,
		array $playingPlayers,
		array $completedPlayers,
		array $records,
		int $rewardMoney,
		array $rewards,
		string $command
	){
		parent::__construct($name, $description, $clearType, $playingPlayers, $completedPlayers, $records, $rewardMoney, $rewards);
		$this->command = $command;
	}

	public static function getIdentifier() : string{
		return "CommandInvoke";
	}

	public function getGoal() : string{
		return "Use {$this->command}";
	}

	public function getCommand() : string{ return $this->command; }

	public function jsonSerialize() : array{
		return array_merge(
			parent::jsonSerialize(),
			["command" => $this->command]
		);
	}

	public static function jsonDeserialize(array $data) : CommandInvokeQuest{
		return new CommandInvokeQuest(
			$data["name"],
			$data["description"],
			$data["clearType"],
			$data["playingPlayers"],
			$data["completedPlayers"],
			$data["records"],
			$data["rewardMoney"],
			$data["rewards"],
			$data["command"]
		);
	}
}