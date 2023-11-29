<?php

namespace Teleskill\Framework\DateTime;

use Carbon\Carbon;
use Exception;

final class CarbonDate extends Carbon {

	const LOGGER_NS = self::class;

	public static function stringToDate(?string $date, string $inputFormat, $outputTimezone) : CarbonDate|null {
		try {
			if ($date) {
				$dateObj = parent::createFromFormat($inputFormat, $date, $outputTimezone)->setTime(0, 0);

				return $dateObj;
			}
		} catch (Exception $exception) {

		}
		
		return null;
	}

	public static function dateToString(?CarbonDate $date, string $outputFormat) : string|null {
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