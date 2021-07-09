<?php

declare(strict_types=1);

namespace alvin0319\SmeltQuest\form;

use alvin0319\SmeltQuest\quest\Quest;
use alvin0319\SmeltQuest\SmeltQuest;
use alvin0319\SmeltQuest\util\TimeUtil;
use pocketmine\form\Form;
use pocketmine\item\Item;
use pocketmine\Player;
use function array_map;
use function array_slice;
use function arsort;
use function count;
use function implode;
use function round;
use function str_repeat;

final class QuestInfoForm implements Form{

	protected Player $player;

	protected Quest $quest;

	public function __construct(Player $player, Quest $quest){
		$this->player = $player;
		$this->quest = $quest;
	}

	public function jsonSerialize() : array{
		if($this->quest->canStart($this->player)){
			$status = "§cIncomplete";
		}else{
			if($this->quest->isStarted($this->player)){
				$status = "§cIncomplete";
			}else{
				$status = "§aCompleted";
			}
		}
		$content = "§f - Quest info - \n\n§eDescription: §f{$this->quest->getDescription()}§r\n\n§eGoal: §f{$this->quest->getGoal()}\n\n§eStatus: §f{$status}\n\n§f - Rewards - \n\n";
		if($this->quest->getRewardMoney() > 0){
			$content .= "§eMoney: §f{$this->quest->getRewardMoney()}\n";
		}
		if(count($this->quest->getRewards()) > 0){
			$content .= "§eItems: §f" . implode(", ", array_map(function(Item $item) : string{
					return $item->getName() . " x" . $item->getCount();
				}, $this->quest->getRewards())) . "\n";
		}

		foreach($this->quest->getRewardMessages() as $msg){
			$content .= $msg;
		}
		if(!$this->quest->canStart($this->player) && $this->quest->isStarted($this->player)){
			$progress = round($this->quest->getProgress($this->player), 2);

			$now = (int) (($this->quest->getProgress($this->player) / 100) * 20);
			$left = 20 - $now;

			$content .= "\n§eProgress: " . str_repeat("§a=", $now) . str_repeat("§c=", $left) . "§f({$progress}%%)";
		}

		$records = $this->quest->getRecords();
		arsort($records);

		$records = array_slice($records, 0, 5);

		$content .= "\n\n§f- Quest record rank -\n\n";

		$rank = 0;
		foreach($records as $name => $record){
			++$rank;
			$timeUnit = TimeUtil::convertTime((int) round($record, 2));
			$content .= "§e{$rank}($name)§f: " . $timeUnit[1] . " hours " . $timeUnit[2] . " minutes " . $timeUnit[3] . " seconds\n";
		}

		$data = [
			"type" => "form",
			"title" => $this->quest->getName(),
			"content" => $content,
			"buttons" => [
				["text" => "Leave"]
			]
		];
		if($this->quest->canStart($this->player)){
			$data["buttons"][] = ["text" => "§aStart quest"];
		}
		return $data;
	}

	public function handleResponse(Player $player, $data) : void{
		if($data === null){
			return;
		}
		if($data === 1){
			if($this->quest->canStart($player)){
				$this->quest->start($player);
				SmeltQuest::getInstance()->getSession($player)->addQuest($this->quest);
			}
		}
	}
}