<?
require_once(dirname(__FILE__)."/../include/prolog_admin_before.php");

$FN = preg_replace("/[^a-z0-9_\\[\\]:]/i", "", $_REQUEST["FN"]);
$FC = preg_replace("/[^a-z0-9_\\[\\]:]/i", "", $_REQUEST["FC"]);


if (isset($_REQUEST['JSFUNC']))
{
   $JSFUNC = preg_replace("/[^a-z0-9_\\[\\]:]/i", "", $_REQUEST['JSFUNC']);
}
else
{
   $JSFUNC = '';
}

$sTableID = "tbl_location_popup";

$oSort = new CAdminSorting($sTableID, "ID", "asc");

$lAdmin = new CAdminList($sTableID, $oSort);


// инициализация фильтра

$arFilterFields = Array( "city_name", "id",  "city_id",  "country_name");

$lAdmin->InitFilter($arFilterFields);

$arFilter = Array();

$arFilter = Array(
      "ID"         => $id,
      "CITY_ID"   => $city_id,
      "COUNTRY_NAME"   => $country_name,
      "CITY_NAME"   => $city_name,      
      "COUNTRY_LID" => "ru",
      "CITY_LID" => "ru",
      );

// убираем ключи с пустыми значениями
      
foreach($arFilter as $key=>$value)
{
   if(!$value)
     unset($arFilter[$key]);
}

      
/* -------------------------------------- */

CModule::IncludeModule("sale");

// собственно сама выборка, можно выбрать все что угодно.

$rsData = CSaleLocation::GetList(
        array(
                "SORT" => "ASC",
                "COUNTRY_NAME_LANG" => "DESC",
                "CITY_NAME_LANG" => "ASC"
            ),
       $arFilter,
        false,
        false,
        array()
    );  

/* -------------------------------------- */


// постраничная навигация
$rsData = new CAdminResult($rsData, $sTableID);
$rsData->NavStart();
$lAdmin->NavText($rsData->GetNavPrint(GetMessage("PAGES")));

// добавляем заголовки
$lAdmin->AddHeaders(array(
   array("id"=>"ID",            "content"=>"ID",    "sort"=>"id", "default"=>true),      
   array("id"=>"COUNTRY_NAME",            "content"=>"Страна",    "sort"=>"id", "default"=>true),         
   array("id"=>"CITY_ID",            "content"=>"CITY_ID",    "sort"=>"city_id", "default"=>true),   
   array("id"=>"CITY_NAME",            "content"=>"Название", "sort"=>"name",   "default"=>true),   
));


// рисуем таблицу с результатами выборки

while($arRes = $rsData->GetNext())
{
   
   $f_ID = $arRes['ID'];
   $row =& $lAdmin->AddRow($f_ID, $arRes);
   $row->AddViewField("ID", $f_ID);   
   $row->AddViewField("COUNTRY_NAME_ORIG", $arRes["COUNTRY_NAME"]);   
   $row->AddViewField("CITY_ID", $arRes["CITY_ID"]);   
   $row->AddViewField("NAME", $arRes["CITY_NAME"]);
   
   $arActions = array();
   $arActions[] = array(
      "ICON"=>"",
      "TEXT"=>"Выбрать",
      "DEFAULT"=>true,
      "ACTION"=>"SetValue('".$arRes["ID"]."','".$arRes["CITY_NAME"]."');"
   );
   $row->AddActions($arActions);
}



$lAdmin->AddFooter(
   array(
      array("title"=>GetMessage("MAIN_ADMIN_LIST_SELECTED"), "value"=>$rsData->SelectedRowsCount()),
      array("counter"=>true, "title"=>GetMessage("MAIN_ADMIN_LIST_CHECKED"), "value"=>"0"),
   )
);



$lAdmin->AddAdminContextMenu(array());

$lAdmin->CheckListMode();

$APPLICATION->SetTitle(GetMessage("MAIN_PAGE_TITLE"));
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_popup_admin.php")
?>
<script language="JavaScript">
<!--
function SetValue(id, name)
{
   <?if ($JSFUNC <> ''){?>
   window.opener.SUV<?=$JSFUNC?>(id);
   <?}else{?>
   window.opener.document.<?echo $FN;?>["<?echo $FC;?>"].value=id;
   window.opener.document.getElementById('div_<?echo $FC;?>').innerHTML= name;
   window.close();
   <?}?>
}
//-->
</script>
<form name="find_form" method="GET" action="<?echo $APPLICATION->GetCurPage()?>?">


<?
// рисуем табличку фиьтра

$oFilter = new CAdminFilter(
   $sTableID."_filter",
   array(
      "ID",
      "CITY_ID",      
      "Страна",      
      "Город",
      
   )
);
$oFilter->Begin();
?>

<tr>
   <td><b>Найти:</b></td>   
   <td>Название</td>
   <td><input type="text" name="city_name" size="47" value="<?echo htmlspecialchars($city_name)?>"></td>   
</tr>

<tr>
   <td></td>
   <td>ID</td>
   <td><input type="text" name="id" size="47" value="<?echo htmlspecialchars($id)?>"></td>   
</tr>

<tr>
   <td></td>
   <td>CITY_ID</td>
   <td><input type="text" name="city_id" size="47" value="<?echo htmlspecialchars($city_id)?>"></td>   
</tr>

<tr>
   <td></td>
   <td>Страна</td>
   <td><input type="text" name="country_name" size="47" value="<?echo htmlspecialchars($country_name)?>"></td>   
</tr>



<input type="hidden" name="FN" value="<?echo htmlspecialchars($FN)?>">
<input type="hidden" name="FC" value="<?echo htmlspecialchars($FC)?>">
<input type="hidden" name="JSFUNC" value="<?echo htmlspecialchars($JSFUNC)?>">
<?
$oFilter->Buttons(array("table_id"=>$sTableID, "url"=>$APPLICATION->GetCurPage(), "form"=>"find_form"));
$oFilter->End();
?>
</form>
<?
//    
$lAdmin->DisplayList();

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_popup_admin.php");
?>