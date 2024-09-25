<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);
IncludeModuleLangFile(__FILE__);

$aMenu = array(
	'parent_menu' => 'global_menu_content',
	'sort' => 1,
	'skip_chain'=>true,
	'items_id' => 'menu_content',
	'text' => "Геоданные",
	'title' => "",
	'items' => array()
);

$connection = Bitrix\Main\Application::getConnection();
$sqlHelper = $connection->getSqlHelper();

$sql = "SELECT ID, NAME FROM REGIONS";

$recordset = $connection->query($sql);

while ($record = $recordset->fetch()) {
	$aMenu["items"][] =  array(
        	"text" => $record["NAME"],
        	"url"  => "xyz_geolocation_list.php?lang=".LANGUAGE_ID."&grid_id=CITY&grid_action=sort&by=NAME&id=".$record["ID"],
        	"icon" => "fileman_menu_icon_sections",
		"title" => ""
	);
}

return $aMenu;
?>
