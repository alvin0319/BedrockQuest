<?php

declare(strict_types=1);

namespace alvin0319\BedrockQuest\quest;

use alvin0319\BedrockQuest\SmeltQuest;
use function array_diff;
use function array_values;
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
		if(!is_dir($dir = $this->plugin->getDataFolder() . "quests/") && !mkdir($dir) && !is_dir($dir)){
			throw new \RuntimeException(sprintf('Directory "%s" was not created', $dir));
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

	public function getQuestList() : array{
		return array_values($this->quests);
	}

	public static function fromData(array $data) : ?Quest{
		return match($data["identifier"]){
			BlockBreakQuest::getIdentifier() => BlockBreakQuest::jsonDeserialize($data),
			BlockPlaceQuest::getIdentifier() => BlockPlaceQuest::jsonDeserialize($data),
			CommandInvokeQuest::getIdentifier() => CommandInvokeQuest::jsonDeserialize($data),
			KillEntityQuest::getIdentifier() => KillEntityQuest::jsonDeserialize($data),
			KillPlayerQuest::getIdentifier() => KillPlayerQuest::jsonDeserialize($data),
			default => null,
		};
	}

	public static function getQuests() : array{
		return [
			BlockBreakQuest::getIdentifier(),
			BlockPlaceQuest::getIdentifier(),
			CommandInvokeQuest::getIdentifier(),
			KillEntityQuest::getIdentifier(),
			KillPlayerQuest::getIdentifier()
		];
	}
}