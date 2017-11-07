<?php
namespace Bitrix\Main\Data;

interface ICacheEngine
{
	public function isAvailable();
	public function clean($baseDir, $initDir = false, $filename = false);
	public function read(&$arAllVars, $baseDir, $initDir, $filename, $TTL);
	public function write($arAllVars, $baseDir, $initDir, $filename, $TTL);
	public function isCacheExpired($path);
}
