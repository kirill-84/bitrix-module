<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/local/modules/xyz.geolocation/prolog.php");

if(!($USER->CanDoOperation('view_subordinate_users') || $USER->CanDoOperation('view_all_users') || $USER->CanDoOperation('edit_all_users') || $USER->CanDoOperation('edit_subordinate_users')))
  $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\UserTable;
use Bitrix\Main\UserGroupTable;
use Bitrix\Main\Grid\Options as GridOptions;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Text\HtmlFilter;
use Bitrix\Main\Type\DateTime;

IncludeModuleLangFile(__FILE__);

//authorize as user
if($_REQUEST["action"] == "authorize" && check_bitrix_sessid() && $USER->CanDoOperation('edit_php'))
{
  $USER->Logout();
  $USER->Authorize(intval($_REQUEST["id"]));
  LocalRedirect("xyz_geolocation_list.php?lang=".LANGUAGE_ID."&grid_id=CITY&grid_action=sort&by=NAME&id=".(intval($_REQUEST["id"])));
}

//logout user
if($_REQUEST["action"] == "logout_user" && check_bitrix_sessid() && $USER->CanDoOperation('edit_php'))
{
  \Bitrix\Main\UserAuthActionTable::addLogoutAction($_REQUEST["ID"]);
  LocalRedirect("xyz_geolocation_list.php?lang=".LANGUAGE_ID."&grid_id=CITY&grid_action=sort&by=NAME&id=".(intval($_REQUEST["id"])));
}

Bitrix\Main\Loader::IncludeModule("xyz.geolocation");

$APPLICATION->SetTitle("Геоданные");

$list_id = xyz\geolocation\DataTable::getTableName();

$grid_options = new GridOptions($list_id);

$reg_id = intval($_REQUEST["id"]);

/**
 * print_r
 */
function p($arr){
  echo "<pre>";print_r($arr);echo "</pre>";
}

/**
 * Навигация
 */
$nav_params = $grid_options->GetNavParams();
 
$nav = new PageNavigation('request_list');
 
$nav->allowAllRecords(true)//Показать все
  ->setRecordCount($DB->query("SELECT COUNT(*) as CNT FROM CITY GROUP BY REGION_ID")->fetch()['CNT'])//Для работы кнопки "показать все"
  ->setPageSize($nav_params['nPageSize'])//Параметр сколько отображать на странице
  ->initFromUri();


/**
 * Параметры запроса
 */
$sql_where = "WHERE `city_info`.`REGION_ID`='{$reg_id}'";
 
$sql_joint = '';
 
$sql_order = 'ORDER BY `city_info`.`NAME` ASC';
 
$sql_limit = 'LIMIT ' . $nav->getLimit();
 
$sql_offset = 'OFFSET ' . $nav->getOffset();

/**
 * Сортировка
 */
if (($_GET['grid_id'] ?? null) === $list_id) {
  if (isset($_GET['grid_action']) and $_GET['grid_action'] === 'sort') {
    $sql_order = "ORDER BY `{$DB->ForSql($_GET['by'])}` {$DB->ForSql($_GET['order'])}";
  }
}

/**
 * Выборка пользователей
 */

$users = array(""=>"Не выбрано");
$a = array();
$b = array();
$city = array();

/**
 * Another city query
 */

$sql = <<<EOT
  SELECT `city_info`.`NAME` AS `NAME` FROM `CITY` AS `city_info` WHERE `city_info`.`REGION_ID`='{$reg_id}'
EOT;

$res = $DB->query($sql);

// cities by id
while($r = $res->fetch()) {
  foreach ($r as $val) {
    $city[] = $val;
  }
}

$arFilter = Array("ACTIVE" => "Y","!UF_CITY" => false,);
$arFld = array("ID", "NAME",);
$arSel = array("UF_CITY");
$rsUsers = CUser::GetList(($by="name"), ($o="asc"), $arFilter, array("FIELDS"=>$arFld, "SELECT"=>$arSel));
while($arr = $rsUsers->fetch()) :
  $rs = CUserFieldEnum::GetList(array(), array("ID" => $arr["UF_CITY"]));
  while($ar = $rs->fetch()):
    // all users and further fields
    if(in_array($ar["VALUE"], $city)){
      $a[] = $ar["VALUE"]."-".$arr["NAME"];
      $b[] = $arr["NAME"];
    }
  endwhile;
endwhile;

//array_unshift($a, "");
//array_unshift($b, "Не выбрано");

$users += array_combine($a, $b);

/**
 * Фильтрация
 */
$filterOption = new Bitrix\Main\UI\Filter\Options($list_id);
 
$filterData = $filterOption->getFilter([]);
 
$filter = [];

foreach ($filterData as $key => $value) {

  /**
    * фильтр по названию города
    */
  if ($key === 'NAME' && strlen($value) > 0) {
    $sql_where .= " AND `city_info`.`NAME` LIKE '%{$DB->ForSql($value)}%'";
  }

  /**
    * фильтр по пользователю
    */
  if ($key === 'BYUSER' && strlen($value) > 0) {
    $str = strstr($value, '-', true);
    $sql_where .= " AND `city_info`.`NAME` = '{$DB->ForSql($str)}'";
  }
}

if (!empty($filterData["FIND"])){
  $m = array();
  $n_arr = array();
  $val = "";
  foreach($filterData as $key => $value){
    if($key == 'FIND' && strlen($value) > 0) {
      foreach($a as $meaning) {
        $m[] = $meaning;
        $sub = substr($meaning, strpos($meaning, "-")+1);
        $n_arr[] = $sub;
      }
      $k = array_search($value, $n_arr);
      if($k !== false){
        $str = strstr($m[$k], '-', true);
        $val = $str;
      }
      $sql_where .= " AND `city_info`.`NAME` LIKE '%{$DB->ForSql($value)}%' OR `city_info`.`NAME` = '{$DB->ForSql($val)}'";
    }
  }
}

/**
 * Весь запрос
 */
$sql_query = <<<EOT
	SELECT 
	`city_info`.`ID` AS `ID`, 
	`city_info`.`NAME` AS `NAME`, 
  `city_info`.`REGION_ID` AS `REGION_ID`, 
	`regions_info`.`NAME` AS `R_NAME` 
	FROM `CITY` AS `city_info` 
	LEFT JOIN `REGIONS` AS `regions_info` ON `city_info`.`REGION_ID` = `regions_info`.`ID` 
	{$sql_joint} 
  {$sql_where} 
	GROUP BY 
  `city_info`.`NAME` 
  {$sql_order} 
	{$sql_limit} 
	{$sql_offset}
EOT;

//p($sql_query);

/**
 * Результат запроса для отображения таблицы
 */
$rsData = $DB->query($sql_query);


/**
 * Список фильтров
 */
$filter_list = array(
    array(
        "id" => "NAME",
        "type" => "text",
        "name" => "По названию города",
        "default" => true
    ),
    array(
        "id" => "BYUSER",
        "type" => "list",
        "name" => "По пользователю",
        "items" => $users,
        "filterable" => "%",
        "default" => true
    ),
);

/**
 * Колонки таблицы
 */
$arHeaders = array(
    array("id" => "ID", "name" => "ID", "sort" => "ID", "align" => "center", "default" => true),
    array("id" => "NAME", "name" => "Город", "sort" => "NAME", "align" => "center", "default" => true),
    array("id" => "R_NAME", "name" => "Регион", "sort" => "R_NAME", "align" => "center", "default" => true),
);

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_after.php");
?>
<div class="adm-toolbar-panel-container">
    <div class="adm-toolbar-panel-flexible-space">
        <?php
        $APPLICATION->IncludeComponent('bitrix:main.ui.filter', '', [
            'FILTER_ID' => $list_id,
            'GRID_ID' => $list_id,
            'FILTER' => $filter_list,
            'VALUE_REQUIRED_MODE' => true,
            'ENABLE_LIVE_SEARCH' => true,
            'ENABLE_LABEL' => true
        ]);
        ?>
    </div>
    <div class="adm-toolbar-panel-align-right">
    </div>
</div>

<?php
/**
 * Данные по каждому выбранному элементу таблицы
 */
$list = [];
while ($row = $rsData->fetch()) {
  $url_params = http_build_query(
          [
              //'REGION_ID' => $row['REGION_ID'],
              'lang' => LANGUAGE_ID,
              'ID' => $row['ID'],
          ]
  );
 
  $list[] = [
      'data' => $row,
      'actions' => [
          [
              'text' => 'Редактировать',
              'default' => true,
              'onclick' => "document.location.href='/bitrix/admin/xyz_geolocation_edit.php?{$url_params}'"
          ],
      ]
  ];

}

$APPLICATION->IncludeComponent('bitrix:main.ui.grid', '', [
    'GRID_ID' => $list_id,
    'COLUMNS' => $arHeaders,
    'ROWS' => $list,
    'SHOW_ROW_CHECKBOXES' => true,
    'NAV_OBJECT' => $nav,
    'AJAX_MODE' => 'Y',
    'AJAX_ID' => \CAjax::getComponentID('bitrix:main.ui.grid', '.default', ''),
    'PAGE_SIZES' => [
        ['NAME' => '5', 'VALUE' => '5'],
        ['NAME' => '20', 'VALUE' => '20'],
        ['NAME' => '50', 'VALUE' => '50'],
        ['NAME' => '100', 'VALUE' => '100']
    ],
    'AJAX_OPTION_JUMP' => 'N',
    'SHOW_CHECK_ALL_CHECKBOXES' => true,
    'SHOW_ROW_ACTIONS_MENU' => true,
    'SHOW_GRID_SETTINGS_MENU' => true,
    'SHOW_NAVIGATION_PANEL' => true,
    'SHOW_PAGINATION' => true,
    'SHOW_SELECTED_COUNTER' => true,
    'SHOW_TOTAL_COUNTER' => true,
    'SHOW_PAGESIZE' => true,
    'SHOW_ACTION_PANEL' => true,
    'ALLOW_COLUMNS_SORT' => true,
    'ALLOW_COLUMNS_RESIZE' => true,
    'ALLOW_HORIZONTAL_SCROLL' => true,
    'ALLOW_SORT' => true,
    'ALLOW_PIN_HEADER' => true,
    'AJAX_OPTION_HISTORY' => 'N',
    'TOTAL_ROWS_COUNT_HTML' => '<span class="main-grid-panel-content-title">Всего:</span> <span class="main-grid-panel-content-text">' . $nav->getRecordCount() . '</span>',
]);
?>
