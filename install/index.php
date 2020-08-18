<?

IncludeModuleLangFile(__FILE__);
use \Bitrix\Main\ModuleManager;
use \Bitrix\Main\UserTable;
use \Bitrix\Main\UserFieldTable;


Class xyz_geolocation extends CModule
{
    var $MODULE_ID = "xyz.geolocation";
    var $MODULE_VERSION;
    var $MODULE_VERSION_DATE;
    var $MODULE_NAME;
    var $MODULE_DESCRIPTION;
    var $errors;

    function __construct()
    {
        $this->MODULE_VERSION = "1.0.0";
        $this->MODULE_VERSION_DATE = "31.07.2020";
        $this->MODULE_NAME = "xyz.geolocation - модуль геоданных";
        $this->MODULE_DESCRIPTION = "Тестовый модуль xyz.geolocation.";
    }

    function DoInstall()
    {
        $this->InstallDB();
        $this->InstallEvents();
        $this->InstallFiles();
        $this->entity();
        $this->addUsers();
        \Bitrix\Main\ModuleManager::RegisterModule("xyz.geolocation");
        return true;
    }

    function DoUninstall()
    {
        $this->UnInstallDB();
        $this->UnInstallEvents();
        $this->UnInstallFiles();
        \Bitrix\Main\ModuleManager::UnRegisterModule("xyz.geolocation");
        return true;
    }

    function InstallDB()
    {
        global $DB;
        $this->errors = false;
        $this->errors = $DB->RunSQLBatch($_SERVER['DOCUMENT_ROOT'] . "/local/modules/xyz.geolocation/install/db/install.sql");
        if (!$this->errors) {
            return true;
        } else
            return $this->errors;
    }

    function UnInstallDB()
    {
        global $DB;
        $this->errors = false;
        $this->errors = $DB->RunSQLBatch($_SERVER['DOCUMENT_ROOT'] . "/local/modules/xyz.geolocation/install/db/uninstall.sql");
        if (!$this->errors) {
            return true;
        } else
            return $this->errors;
    }

    function entity(){
        $connection = Bitrix\Main\Application::getConnection();
        $sqlHelper = $connection->getSqlHelper();

        $sql = "SELECT NAME, CODE FROM CITY";
        $data = array();
        $recordset = $connection->query($sql);
        while ($record = $recordset->fetch()) {
            $data[] = $record;
        }

        $ob = new CUserTypeEntity();
        $arFields = array(
            'ENTITY_ID' => 'USER',
            'FIELD_NAME' => 'UF_CITY',
            'USER_TYPE_ID' => 'enumeration',
            'XML_ID' => '24',
            'SORT' => 100,
            'MULTIPLE' => 'N',
            'MANDATORY' => 'N',
            'SHOW_FILTER' => 'I',
            'SHOW_IN_LIST' => 'Y',
            'EDIT_IN_LIST' => 'Y',
            'IS_SEARCHABLE' => 'Y'
        );
        $FIELD_ID = $ob->Add($arFields);

        $arFields = $GLOBALS['USER_FIELD_MANAGER']->GetUserFields("USER");

        if(array_key_exists("UF_CITY", $arFields)) {

            $FIELD_ID = $arFields["UF_CITY"]["ID"];
            $obEnum = new CUserFieldEnum();

            foreach($data as $i => $val){
                $arAddEnum['n'.$i] = array(
                  'XML_ID' => $val['CODE'],
                  'VALUE' => $val['NAME'],
                  'DEF' => 'N',
                  'SORT' => $i*10
               );
            }
            $obEnum->SetEnumValues($FIELD_ID, $arAddEnum);
        }
        return true;
    }

    function addUsers(){
        $user = new CUser;
        $n = 1;
        while($n < 6){
            $arFields = Array(
              "NAME"              => "User".$n,
              "LAST_NAME"         => "",
              "EMAIL"             => "user".$n."@noreply.ru",
              "LOGIN"             => "User".$n,
              "LID"               => "ru",
              "ACTIVE"            => "Y",
              "GROUP_ID"          => array(5),
              "PASSWORD"          => "123456",
              "CONFIRM_PASSWORD"  => "123456",
              "PERSONAL_PHOTO"    => "",
              "UF_CITY"       => ""
            );
            $n++;
            $ID = $user->Add($arFields);
        }
        return true;
    }

    function InstallEvents()
    {
        return true;
    }

    function UnInstallEvents()
    {
        return true;
    }

    function InstallFiles()
    {
        CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/local/modules/xyz.geolocation/install/admin", $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin", true, true);
        return true;
    }

    function UnInstallFiles()
    {
        DeleteDirFiles($_SERVER["DOCUMENT_ROOT"]."/local/modules/xyz.geolocation/install/admin", $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin");
        return true;
    }
}
?>
