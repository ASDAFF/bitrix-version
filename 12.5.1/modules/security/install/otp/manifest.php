<?
header('Content-Type: text/cache-manifest');
/*
$exp = 60*60*24;
header("Expires: ".gmdate("D, d M Y H:i:s", mktime()+$exp)." GMT");
header("Cache-Control: public, max-age=".$exp);
header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
header("Pragma: public");
*/
?>CACHE MANIFEST
<?
$hashes = '';
$dir = new RecursiveDirectoryIterator(".");
foreach(new RecursiveIteratorIterator($dir) as $file)
{
	if($file->IsFile() 
		&& str_replace("\\", "/", $file) != "./manifest.php" 
		&& str_replace("\\", "/", $file) != "./ws/index.php" 
		&& substr($file->getFilename(), 0, 1) != "."
	)
	{
		if(str_replace("\\", "/", $file) != "./index.html")
			echo "/bitrix/otp/".ltrim(str_replace("\\", "/", $file), "./"). "\n";
		$hashes.= md5_file($file);
	}
}

// FALLBACK:
// ./ ./offline.php
?>

NETWORK:
*
<?
echo "#  Hash: " . md5($hashes) . "\n";
?>