<?php

namespace Guave\translatetool;

class SimpleTester {

	private static $testCount = 0;
	private static $testFailedCount = 0;
	private static $testSuccessCount = 0;

	public static function printStats() {
		echo "\n\n\n";
		echo "\nTestruns: " . self::$testCount;
		echo "\n\033[32mSuccess : " . self::$testSuccessCount . "\033[0m";
		echo "\n\033[31mFailed  : " . self::$testFailedCount . "\033[0m";
		echo "\n";
		if(self::$testFailedCount === 0) {
			echo "CONGRATS! NO TEST FAILED!\n";
			echo "\n\033[32m-_\033[33m-_\033[31m-_\033[35m-_\033[36m-_\033[34m-_\033[36m-_\033[32m-_\033[0m,------,";
			echo "\n\033[32m_-\033[33m_-\033[31m_-\033[35m_-\033[36m_-\033[34m_-\033[36m_-\033[32m_-\033[0m|   /\\_/\\ ";
			echo "\n\033[32m-_\033[33m-_\033[31m-_\033[35m-_\033[36m-_\033[34m-_\033[36m-_\033[32m-\033[0m~|__( ^ .^)";
			echo "\n\033[32m_-\033[33m_-\033[31m_-\033[35m_-\033[36m_-\033[34m_-\033[36m_-\033[32m_-\033[0m\"\"  \"\"";
			echo "\n\n";
		}
	}

	public static function isNotEmpty($n, $arr) {
		self::$testCount++;
		if(!empty($arr)) {
			self::$testSuccessCount++;
			echo sprintf("\n\033[32m o \033[0m" . '%s is not empty', $n);
		} else {
			self::$testFailedCount++;
			echo sprintf("\n\033[31m x %s should not be empty\033[0m", $n);
		}
	}

	public static function isEmpty($n, $arr) {
		self::$testCount++;
		if(empty($arr)) {
			self::$testSuccessCount++;
			echo sprintf("\n\033[32m o \033[0m" . '%s is empty', $n);
		} else {
			self::$testFailedCount++;
			echo sprintf("\n\033[31m x %s should be empty\033[0m", $n);
		}
	}

	public static function hasKey($n, $arr, $key) {
		self::$testCount++;
		if(array_key_exists($key, $arr)) {
			self::$testSuccessCount++;
			echo sprintf("\n\033[32m o \033[0m" . 'Key ' . $key . ' exists in %s', $n);
		} else {
			self::$testFailedCount++;
			echo sprintf("\n\033[31m x Key " . $key . " should exist in %s\033[0m", $n);
		}
	}

	public static function isEqual($n, $v1, $v2) {
		self::$testCount++;
		if($v1 === $v2) {
			self::$testSuccessCount++;
			echo sprintf("\n\033[32m o \033[0m" . '%s "%s" is equal to "%s"', $n, self::stringify($v1), self::stringify($v2));
		} else {
			self::$testFailedCount++;
			echo sprintf("\n\033[31m x %s '%s' should be equal to '%s'\033[0m", $n, self::stringify($v1), self::stringify($v2));
		}
	}

	public static function isArray($n, $arr) {
		self::$testCount++;
		if(is_array($arr)) {
			self::$testSuccessCount++;
			echo sprintf("\n\033[32m o \033[0m" . '%s is an Array', $n);
		} else {
			self::$testFailedCount++;
			echo sprintf("\n\033[31m x %s should be an Array\033[0m", $n);
		}
	}

	//Via github:
	//https://github.com/beberlei/assert/blob/master/lib/Assert/Assertion.php
	private static function stringify($v) {
		if(is_bool($v)) {
			return $v? 'true' : 'false';
		}
		if(is_array($v)) {
			return 'Array';
		}
		if(is_object($v)) {
			return get_class($v);
		}
		if($v === NULL) {
			return 'null';
		}
		if(is_string($v)) {
			return $v;
		}
		if(is_scalar($v)) {
			$v = (string) $v;
			if(strlen($v) > 100) {
				$v = substr($v, 0, 97) . '...';
			}
			return $v;
		}
	}

}
