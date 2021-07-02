<?php

declare(strict_types=1);

namespace alvin0319\SmeltQuest\form;

use alvin0319\SmeltQuest\quest\BlockBreakQuest;
use alvin0319\SmeltQuest\quest\BlockPlaceQuest;
use alvin0319\SmeltQuest\SmeltQuest;
use InvalidArgumentException;
use pocketmine\block\BlockFactory;
use pocketmine\form\Form;
use pocketmine\Player;
use function array_map;
use function count;
use function is_numeric;

final class BlockQuestCreateForm implements Form{

	protected array $data;

	public function __construct(array $data){
		$this->data = $data;
	}

	public function jsonSerialize() : array{
		return [
			"type" => "custom_form",
			"title" => "Block quest create form",
			"content" => [
				[
					"type" => "input",
					"text" => "ID of block"
				],
				[
					"type" => "input",
					"text" => "Meta of block"
				],
				[
					"type" => "input",
					"text" => "Count of block that player need to break/place block"
				],
				[
					"type" => "toggle",
					"text" => "Allow players to clear this quest without any condition",
					"default" => false
				]
			]
		];
	}

	public function handleResponse(Player $player, $data) : void{
		$success = false;
		try{
			if($data === null || count($data) !== 4){
				return;
			}
			[$id, $meta, $count, $allowAnyCondition] = $data;

			if(!$allowAnyCondition){
				if(!is_numeric($id) || !is_numeric($meta) || !is_numeric($count)){
					$player->sendMessage(SmeltQuest::$prefix . "ID and Meta must be int");
					return;
				}
				[$id, $meta, $count] = array_map(fn($a) => (int) $a, [$id, $meta, $count]);

			}else{
				$id = 0;
				$meta = 0;
			}
			if(!is_numeric($count) || ($count = (int) $count) < 1){
				$player->sendMessage(SmeltQuest::$prefix . "Count must be at least 1");
				return;
			}
			try{
				$block = BlockFactory::get($id, $meta);
				switch($this->data["questType"]){
					case BlockBreakQuest::getIdentifier():
						$quest = new BlockBreakQuest(
							$this->data["name"],
							$this->data["description"],
							$this->data["clearType"],
							[],
							[],
							[],
							0,
							[],
							$block->getId(),
							$block->getDamage(),
							$count,
							[],
							[],
							$allowAnyCondition
						);
						break;
					case BlockPlaceQuest::getIdentifier():
						$quest = new BlockPlaceQuest(
							$this->data["name"],
							$this->data["description"],
							$this->data["clearType"],
							[],
							[],
							[],
							0,
							[],
							$block->getId(),
							$block->getDamage(),
							$count,
							[],
							[],
							$allowAnyCondition
						);
						break;
					default:
						$player->sendMessage(SmeltQuest::$prefix . "Invalid quest type given.");
						return;
				}
				SmeltQuest::getInstance()->getQuestManager()->registerQuest($quest);
				SmeltQuest::getInstance()->addQuestToCategory($this->data["questCategory"], $quest);
				$player->sendMessage(SmeltQuest::$prefix . "Success! Don't forget to add rewards!");
				$success = true;
			}catch(InvalidArgumentException $e){
				$player->sendMessage(SmeltQuest::$prefix . "Invalid block $id:$meta");
			}
		}finally{
			if(!$success){
				$player->sendForm($this);
			}
		}
	}
}