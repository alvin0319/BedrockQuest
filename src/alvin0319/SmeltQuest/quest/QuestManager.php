<?php

declare(strict_types=1);

namespace alvin0319\SmeltQuest\quest;

use alvin0319\SmeltQuest\SmeltQuest;
use function array_diff;
use function count;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function is_dir;
use function mkdir;
use function pathinfo;
use function scandir;
use function unlink;
use function yaml_emit;
use function yaml_parse;

final class QuestManager{

	/** @var Quest[] */
	protected array $quests = [];

	protected SmeltQuest $plugin;

	public function __construct(SmeltQuest $plugin){
		$this->plugin = $plugin;
		$this->loadQuests();
	}

	private function loadQuests() : void{
		if(!is_dir($dir = $this->plugin->getDataFolder() . "quests/")){
			mkdir($dir, 0777);
		}
		$files = array_diff(scandir($dir), [".", ".."]);
		foreach($files as $file){
			$realPath = $dir . $file;
			if(pathinfo($realPath, PATHINFO_EXTENSION) === "yml"){
				$data = yaml_parse(file_get_contents($realPath));
				$quest = self::fromData($data);
				if($quest === null){
					$this->plugin->getLogger()->debug("Could not load quest data {$file}");
					continue;
				}
				$this->quests[$quest->getName()] = $quest;
			}
		}
		$this->plugin->getLogger()->notice("Loaded " . count($this->quests) . " quests.");
	}

	public function saveQuests() : void{
		foreach($this->quests as $name => $quest){
			file_put_contents($this->plugin->getDataFolder() . "quests/{$quest->getName()}.yml", yaml_emit($quest->jsonSerialize()));
		}
	}

	public function registerQuest(Quest $quest) : void{
		$this->quests[$quest->getName()] = $quest;
	}

	public function unregisterQuest(Quest $quest) : void{
		if(isset($this->quests[$quest->getName()])){
			unset($this->quests[$quest->getName()]);
			if(file_exists($file = $this->plugin->getDataFolder() . "quests/{$quest->getName()}.yml")){
				unlink($file);
			}
		}
	}

	public function getQuest(string $name) : ?Quest{
		return $this->quests[$name] ?? null;
	}

	public static function fromData(array $data) : ?Quest{
		switch($data["identifier"]){
			case BlockBreakQuest::getIdentifier():
				return BlockBreakQuest::jsonDeserialize($data);
			case BlockPlaceQuest::getIdentifier():
				return BlockPlaceQuest::jsonDeserialize($data);
			case CommandInvokeQuest::getIdentifier():
				return CommandInvokeQuest::jsonDeserialize($data);
			case KillEntityQuest::getIdentifier():
				return KillEntityQuest::jsonDeserialize($data);
			case KillPlayerQuest::getIdentifier():
				return KillPlayerQuest::jsonDeserialize($data);
		}
		return null;
	}
}