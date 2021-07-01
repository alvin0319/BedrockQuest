<?php

declare(strict_types=1);

namespace alvin0319\SmeltQuest\util;

final class TimeUtil{

	public static function convertTime(int $time) : array{
		$day = 0;
		$hour = 0;
		$minute = 0;
		while($time >= 60 * 60 * 24){
			$day += 1;
			$time -= 60 * 60 * 24;
		}
		while($time >= 60 * 60){
			$hour += 1;
			$time -= 60 * 60;
		}
		while($time >= 60){
			$minute += 1;
			$time -= 60;
		}
		return [$day, $hour, $minute, $time];
	}
}