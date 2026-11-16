<?php
namespace WHMCS\Module\Addon\AddonModule\Client;

class Controller
{
    public function index($vars)
    {
        $modulelink = $vars["modulelink"];
        $version = $vars["version"];
        $LANG = $vars["_lang"];
        $configTextField = $vars["Text Field Name"];
        $configPasswordField = $vars["Password Field Name"];
        $configCheckboxField = $vars["Checkbox Field Name"];
        $configDropdownField = $vars["Dropdown Field Name"];
        $configRadioField = $vars["Radio Field Name"];
        $configTextareaField = $vars["Textarea Field Name"];
        return ["pagetitle" => "Sample Addon Module", "breadcrumb" => ["index.php?m=addonmodule" => "Sample Addon Module"], "templatefile" => "publicpage", "requirelogin" => false, "forcessl" => false, "vars" => ["modulelink" => $modulelink, "configTextField" => $configTextField, "customVariable" => "your own content goes here"]];
    }
    public function secret($vars)
    {
        $modulelink = $vars["modulelink"];
        $version = $vars["version"];
        $LANG = $vars["_lang"];
        $configTextField = $vars["Text Field Name"];
        $configPasswordField = $vars["Password Field Name"];
        $configCheckboxField = $vars["Checkbox Field Name"];
        $configDropdownField = $vars["Dropdown Field Name"];
        $configRadioField = $vars["Radio Field Name"];
        $configTextareaField = $vars["Textarea Field Name"];
        return ["pagetitle" => "Sample Addon Module", "breadcrumb" => ["index.php?m=addonmodule" => "Sample Addon Module", "index.php?m=addonmodule&action=secret" => "Secret Page"], "templatefile" => "secretpage", "requirelogin" => true, "forcessl" => false, "vars" => ["modulelink" => $modulelink, "configTextField" => $configTextField, "customVariable" => "your own content goes here"]];
    }
}

?>