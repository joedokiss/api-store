<?php
namespace Lib;

class Filter
{
	public static function filterString($input, $isArray = false)
	{
		if ($isArray) {
			$result = [];

			foreach ($input as $key => $value) {
				$result[$key] = filter_var(trim($value), FILTER_SANITIZE_STRING);
			}

			return $result;
		}

		return filter_var(trim($input), FILTER_SANITIZE_STRING);
	}

	public static function filterHTML($input)
	{
		return htmlspecialchars(trim($input), ENT_QUOTES);
	}

	//removes all non-numeric characters
	public static function trimNonNumeric($intput){
		$output = preg_replace( '/[^0-9]/', '', $input);

		return ($output == '') ? false : true;
	}

}