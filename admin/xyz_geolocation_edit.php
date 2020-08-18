<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/local/modules/xyz.geolocation/prolog.php");

use Bitrix\Main\UserTable;
use Bitrix\Main\UserGroupTable;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Grid\Options as GridOptions;

IncludeModuleLangFile(__FILE__);


$POST_RIGHT = $APPLICATION->GetGroupRight("xyz.geolocation");

if ($POST_RIGHT == "D")
  $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

/**
 * print_r
 */
function p($arr){
  echo "<pre>";print_r($arr);echo "</pre>";
}


$ID = intval($_REQUEST["ID"]);
$message = null;
$bVarsFromForm = false;

if(
    $REQUEST_METHOD == "POST"
    &&
    ($save!="" || $apply!="")
    &&
    $POST_RIGHT=="W"
    &&
    check_bitrix_sessid()
)
{

	$user = new CUser;

	if(isset($_POST["USER_ID"])){
		$users = $_POST["USER_ID"];
	}
	if(isset($_POST["city_key"])){
		$city_key = $_POST["city_key"];
	}

	if(count($users) > 0){
		foreach ($users as $val) {
			$res = $user->Update($val, array("UF_CITY"=>$city_key));
		}
	}

	if($res){
		if ($apply != "")
			LocalRedirect("/bitrix/admin/xyz_geolocation_edit.php?lang=".LANG."&ID=".intval($_POST["ID"]));
		else 
			LocalRedirect("/bitrix/admin/xyz_geolocation_list.php?lang=".LANG."&grid_id=CITY&grid_action=sort&by=NAME&id=".intval($_POST["region"]));
	} else {
		if($e = $APPLICATION->GetException())
	      $message = new CAdminMessage(GetMessage("xyz_save_error"), $e);
	    $bVarsFromForm = true;
	}
}


$sql_query = <<<EOT
	SELECT `city_info`.`NAME`, `city_info`.`REGION_ID` FROM `CITY` AS `city_info` WHERE `city_info`.`ID`='{$ID}'
EOT;

$rsData = $DB->query($sql_query);


if($ID>0)
	$APPLICATION->SetTitle(GetMessage("EDIT_CITY_TITLE", array("#ID#"=>$ID)));
else 
	$APPLICATION->SetTitle(GetMessage("EDIT_CITY_TITLE", array("#ID#"=>intval($_POST["ID"]))));

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_after.php");

?>
<form method="POST" action="<?echo $APPLICATION->GetCurPage()?>" enctype="multipart/form-data" name="user_edit_form" id="user_edit_form">
	<div class="adm-detail-block">
		<div class="adm-detail-content-wrap">
			<? while($result = $rsData->fetch()) {?>
			<div class="adm-detail-content">
				<div class="adm-detail-title"><?=GetMessage("EDIT_CITY_NAME").htmlspecialcharsbx($result["NAME"])?></div>
				<div class="adm-detail-content-item-block">
					<table class="adm-detail-content-table edit-table">
						<tbody>
							<tr>
								<td class="adm-detail-content-cell-l"><?echo GetMessage("EDIT_CITY_SELECT")?>:</td>
								<td class="adm-detail-content-cell-r">
									<select name="USER_ID[]" multiple="multiple" style="min-width:300px;min-height:250px">
									<?
									$arFilter = Array("ACTIVE" => "Y");
									$arFld = array("ID", "NAME",);
									$arSel = array("UF_CITY");
									$str_USER_ID = array();
									$ind = -1;
									$idx = "";
									$dbUsers = CUser::GetList(($b="name"), ($o="asc"), $arFilter, array("FIELDS"=>$arFld, "SELECT"=>$arSel));
									while($arUsers = $dbUsers->fetch()) {
										if(array_key_exists("UF_CITY", $arUsers)){
											$rs = CUserFieldEnum::GetList(array(), array("ID" => $arUsers["UF_CITY"]));
											while($ar = $rs->fetch()){
												if($result["NAME"] == $ar["VALUE"]){
													$idx = $ar["ID"];
												}
												$str_USER_ID[intval($ar["VALUE"])] = $ar["VALUE"];
											}
										}
										$ind++;
									?>
										<option value="<?=$arUsers["ID"]?>" <? if (in_array($result["NAME"], $str_USER_ID)) echo "selected"; ?>><?=htmlspecialcharsbx($arUsers["NAME"])?></option>
									<? } ?>
									</select>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
				<?=bitrix_sessid_post()?>
				<input type="hidden" name="Update" value="Y">
				<input type="hidden" name="lang" value="<?=LANG?>">
				<input type="hidden" name="city_key" value="<?=$idx?>">
				<input type="hidden" name="region" value="<?=$result["REGION_ID"]?>">
				<?if($ID>0):?>
				<input type="hidden" name="ID" value="<?=$ID?>">
				<?endif;?>
			</div>
			<div class="adm-detail-content-btns-wrap">
				<div class="adm-detail-content-btns">
					<input type="submit" name="save" value="Сохранить" title="Сохранить и вернуться" class="adm-btn-save" />
					<input type="submit" name="apply" value="Применить" title="Сохранить и остаться в форме" />
					<input type="button" value="Вернуться назад" name="cancel" onclick="top.window.location='xyz_geolocation_list.php?lang=<?=LANG?>&grid_id=CITY&grid_action=sort&by=NAME&id=<?=$result["REGION_ID"]?>'" title="Вернуться назад" />
				</div>
			</div>
			<?}?>
		</div>
	</div>
</form>
