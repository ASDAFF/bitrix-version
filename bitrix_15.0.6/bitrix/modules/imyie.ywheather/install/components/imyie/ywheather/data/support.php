<?
/* Generate From XML
function translitIt($str) 
{
    $tr = array(
        "À"=>"A","Á"=>"B","Â"=>"V","Ã"=>"G",
        "Ä"=>"D","Å"=>"E","Æ"=>"J","Ç"=>"Z","È"=>"I",
        "É"=>"Y","Ê"=>"K","Ë"=>"L","Ì"=>"M","Í"=>"N",
        "Î"=>"O","Ï"=>"P","Ð"=>"R","Ñ"=>"S","Ò"=>"T",
        "Ó"=>"U","Ô"=>"F","Õ"=>"H","Ö"=>"TS","×"=>"CH",
        "Ø"=>"SH","Ù"=>"SCH","Ú"=>"","Û"=>"YI","Ü"=>"",
        "Ý"=>"E","Þ"=>"YU","ß"=>"YA","à"=>"a","á"=>"b",
        "â"=>"v","ã"=>"g","ä"=>"d","å"=>"e","æ"=>"j",
        "ç"=>"z","è"=>"i","é"=>"y","ê"=>"k","ë"=>"l",
        "ì"=>"m","í"=>"n","î"=>"o","ï"=>"p","ð"=>"r",
        "ñ"=>"s","ò"=>"t","ó"=>"u","ô"=>"f","õ"=>"h",
        "ö"=>"ts","÷"=>"ch","ø"=>"sh","ù"=>"sch","ú"=>"y",
        "û"=>"yi","ü"=>"","ý"=>"e","þ"=>"yu","ÿ"=>"ya"
    );
    return strtr($str,$tr);
}

$cities_file = $_SERVER["DOCUMENT_ROOT"].'/bitrix/components/imyie/ywheather/data/cities.xml';

$data = file_get_contents($cities_file);

$rule = '|<city id="(.*)" region="(.*)" head="(.*)" type="(.*)" country="(.*)" part="(.*)" resort="(.*)" climate="(.*)">(.*)</city>|siU';
preg_match_all($rule, $data, $matches);

$arCitiesID = $matches[1];
$arCountries = $matches[5];
$arCountriesUNIQ = array_unique($matches[5], SORT_STRING);
$arCitiesNAME = $matches[9];

$arAll = array();
foreach($arCountries as $key => $country)
{
	$translName = translitIt($country);
	$cityID = $arCitiesID[$key];
	$cityNAME = $arCitiesNAME[$key];
	$arAll[$translName]["COUNTRY"] = $country;
	$arAll[$translName]["CITIES"][$cityID] = $cityNAME;
}

//echo"<pre>";print_r($arAll);echo"</pre>";

$res = '';
$res .= '<textarea>';
$res .= '$DATA = array(';
foreach($arAll as $k => $v)
{
	$res .= '"'.$k.'" => array(';
		$res .= '"COUNTRY" => "'.$v["COUNTRY"].'",';
		$res .= '"CITIES" => array(';
		$arrCts = $v["CITIES"];
		asort($arrCts, SORT_STRING);
			foreach($arrCts as $CID => $CNAME)
			{
				$res .= $CID.' => "'.$CNAME.'",';
			}
		$res .= ')';
	$res .= '),';
}
$res .= ');';
$res .= '</textarea>';

echo $res;
*/
?>