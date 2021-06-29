<?php

declare(strict_types=1);

namespace alvin0319\SmeltQuest;

use pocketmine\lang\BaseLang;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;

final class SmeltQuest extends PluginBase{
	use SingletonTrait;

	protected array $quests = [];

	public static string $prefix = "";

	public static BaseLang $lang;

	public function onLoad() : void{
		self::setInstance($this);
	}
}