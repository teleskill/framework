<?php

namespace Teleskill\Framework\DateTime;

use Carbon\Carbon;
use Exception;

final class CarbonDateTime extends Carbon {

	const LOGGER_NS = self::class;

	public static function stringToDateTime(?string $date, string $inputTimezone, string $inputFormat, $outputTimezone) : CarbonDateTime|null {
		try {
			if ($date) {
				$dateObj = parent::createFromFormat($inputFormat, $date, $inputTimezone);
	
				if ($inputTimezone != $outputTimezone) {
					$dateObj->setTimezone($outputTimezone);
				}
				
				return $dateObj;
			}
		} catch (Exception $exception) {

		}
		
		return null;
	}

	public static function stringToDate(?string $date, string $inputFormat, $outputTimezone) : CarbonDateTime|null {
		try {
			if ($date) {
				$dateObj = parent::createFromFormat($inputFormat, $date, $outputTimezone)->setTime(0, 0);

				return $dateObj;
			}
		} catch (Exception $exception) {

		}
		
		return null;
	}

	public static function dateTimeToString(?CarbonDateTime $date, string $outputTimezone, string $outputFormat) : string|null {
		try {
			if ($date) {
				$dateObj = clone $date;

				if ($outputTimezone != $date->timezone->getName()) {
					$dateObj->setTimezone($outputTimezone);				
				}

				return $dateObj->format($outputFormat);
			}
		} catch (Exception $exception) {

		}
		
		return null;
	}

	public static function dateToString(?CarbonDateTime $date, string $outputFormat) : string|null {
		try {
			if ($date) {
				$dateObj = clone $date;

				return $dateObj->format($outputFormat);
			}
		} catch (Exception $exception) {

		}
		
		return null;
	}

}