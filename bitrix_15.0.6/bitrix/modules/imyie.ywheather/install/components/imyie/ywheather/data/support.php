<?
/* Generate From XML
function translitIt($str) 
{
    $tr = array(
        "�"=>"A","�"=>"B","�"=>"V","�"=>"G",
        "�"=>"D","�"=>"E","�"=>"J","�"=>"Z","�"=>"I",
        "�"=>"Y","�"=>"K","�"=>"L","�"=>"M","�"=>"N",
        "�"=>"O","�"=>"P","�"=>"R","�"=>"S","�"=>"T",
        "�"=>"U","�"=>"F","�"=>"H","�"=>"TS","�"=>"CH",
        "�"=>"SH","�"=>"SCH","�"=>"","�"=>"YI","�"=>"",
        "�"=>"E","�"=>"YU","�"=>"YA","�"=>"a","�"=>"b",
        "�"=>"v","�"=>"g","�"=>"d","�"=>"e","�"=>"j",
        "�"=>"z","�"=>"i","�"=>"y","�"=>"k","�"=>"l",
        "�"=>"m","�"=>"n","�"=>"o","�"=>"p","�"=>"r",
        "�"=>"s","�"=>"t","�"=>"u","�"=>"f","�"=>"h",
        "�"=>"ts","�"=>"ch","�"=>"sh","�"=>"sch","�"=>"y",
        "�"=>"yi","�"=>"","�"=>"e","�"=>"yu","�"=>"ya"
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