<?php
namespace WHMCS\Module\Addon\AquaskyThemeActivator\Admin;

class Controller
{
    public function index($vars)
    {
        tabs("licence");
        include_once __DIR__ . "/functions.php";
        $result = aquaskythemeactivator_doCheckLicense();
        $licenseKey = $result["licensekey"] ?? "N/A";
        $licenseStatus = $result["status"] ?? "N/A";
        $expirationDate = $result["expirationDate"] ?? "N/A";
        $productname = $result["productname"] ?? "N/A";
        $billingCycle = $result["billingcycle"] ?? "N/A";
        $version = $vars["version"] ?? "1.0";
        if ($licenseStatus == "Active") {
            $licenseStatus = "<span class=\"text-success\">Active</span>";
        } else {
            $licenseStatus = "<span class=\"text-danger\">" . $licenseStatus . "</span>";
        }
        return "       <div class=\"tab-content admin-tabs\">\n        <div class=\"tab-pane active\">\n        <div class=\"row\">\n            <div class=\"col-md-6\"> \n                <div class=\"card\">\n                    <div class=\"card-header\">\n                        <h2 class=\"card-title\">License Information</h2>\n                    </div>\n                    <div class=\"card-body\">\n                        <table class=\"table table-bordered\">\n                            <tr>\n                                <th>License Key</th>\n                                <td>" . $licenseKey . "</td>\n                            </tr>\n                            <tr>\n                                <th>Product Name</th>\n                                <td>" . $productname . "</td>\n                            <tr>\n                            <tr>\n                                <th>Billing Cycle</th>\n                                <td>" . $billingCycle . "</td>\n                            <tr>\n                                <th>License Status</th>\n                                <td>" . $licenseStatus . "</td>\n                            </tr>\n                            <tr>\n                                <th>Expiration Date</th>\n                                <td>" . $expirationDate . "</td>\n                            </tr>\n                            <tr>\n                                <th>Version</th>\n                                <td>" . $version . "</td>\n                            </tr>\n                        </table>\n                    </div>\n                    \n\n                </div>\n                \n            </div>\n            </div>\n            </div>\n            </div>\n\n            \n        </div>\n\n";
    }
    public function banner($vars)
    {
        tabs("banner");
        global $CONFIG;
        $systemurl = $CONFIG["SystemURL"];
        $cssUrl = $systemurl . "/modules/addons/aquaskythemeactivator/css/style.css";
        $cssTag = "<link rel='stylesheet' href='" . $cssUrl . "' />";
        $settings = \WHMCS\Database\Capsule::table("mod_portal_content")->whereIn("setting", ["heading", "description", "button_text", "button_link", "position", "banner_image"])->pluck("value", "setting");
        $heading = $settings["heading"] ?? "";
        $description = $settings["description"] ?? "";
        $buttonText = $settings["button_text"] ?? "";
        $buttonLink = $settings["button_link"] ?? "#";
        $position = $settings["position"] ?? "center";
        $left = $position == "left" ? "<option value=\"left\" selected >Left</option>" : "<option value=\"left\" >Left</option>";
        $center = $position == "center" ? "<option value=\"center\" selected >Center</option>" : "<option value=\"center\" >Center</option>";
        $right = $position == "right" ? "<option value=\"right\" selected >Right</option>" : "<option value=\"right\" >Right</option>";
        $actionUrl = "addonmodules.php?module=aquaskythemeactivator&action=banner_save";
        $image = $settings["banner_image"] ?? "";
        $imageHtml = "";
        if (!empty($image)) {
            $imageHtml = "\n        <div class=\"form-group row-inline\">\n            <label>Current Image:</label>\n            <img src=\"../templates/aqua-sky-one/images/" . $image . "\" alt=\"Banner Image\" width=\"400\">\n            <button type=\"submit\" name=\"remove_image\" value=\"1\" class=\"btn btn-danger ml-2\">\n                Remove Image\n            </button>\n        </div>";
        }
        return "\n" . $cssTag . "\n<form method=\"post\" action=\"" . $actionUrl . "\" enctype=\"multipart/form-data\">\n    \n<div class=\"tab-content admin-tabs\">\n    <div class=\"tab-pane active\">\n\n        <div class=\"form-group row-inline\">\n            <label for=\"heading\">Heading (HTML allowed)</label>\n            <textarea name=\"heading\" id=\"heading\" rows=\"2\" class=\"form-control\">" . $heading . "</textarea>\n        </div>\n\n        <div class=\"form-group row-inline\">\n            <label for=\"description\">Description (HTML allowed)</label>\n            <textarea name=\"description\" id=\"description\" rows=\"2\" class=\"form-control\">" . $description . "</textarea>\n        </div>\n\n        <div class=\"form-group row-inline\">\n            <label for=\"button_text\">Button Text</label>\n            <input type=\"text\" name=\"button_text\" id=\"button_text\" class=\"form-control\" value=\"" . $buttonText . "\" />\n        </div>\n\n        <div class=\"form-group row-inline\">\n            <label for=\"button_link\">Button Link (URL)</label>\n            <input type=\"text\" name=\"button_link\" id=\"button_link\" class=\"form-control\" value=\"" . $buttonLink . "\" />\n        </div>\n\n        <div class=\"form-group row-inline\">\n            <label for=\"position\">Button Position (e.g., center, left, right)</label>\n            <select name=\"position\" id=\"position\" class=\"form-control\">\n                " . $left . "\n                " . $center . "\n                " . $right . "\n\n            </select>\n        </div>\n\n        <div class=\"form-group row-inline\">\n            <label for=\"banner_image\">Upload Banner Image</label>\n            <input type=\"file\" name=\"banner_image\" id=\"banner_image\" class=\"form-control-file\" />\n        </div>\n\n        " . $imageHtml . "\n    \n    </div>\n</div>\n    <div class=\"card-footer\" style=\" margin-top: 16px; text-align: center;\">\n            <button class=\"btn btn-success\" type=\"submit\">\n                <i class=\"fa fa-save\"></i> Save Changes\n            </button>\n            <a href=\"addonmodules.php?module=aquaskythemeactivator\" class=\"btn btn-default\">\n                Cancel\n            </a>\n        </div>\n</form>\n";
    }
    public function banner_save($vars)
    {
        $uploadDir = ROOTDIR . "/templates/aqua-sky-one/images/";
        $imagePath = "";
        if (!empty($_FILES["banner_image"]["name"])) {
            $filename = basename($_FILES["banner_image"]["name"]);
            $filename = preg_replace("/[^a-zA-Z0-9_\\.-]/", "_", $filename);
            $targetFile = $uploadDir . $filename;
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 493, true);
            }
            if (file_exists($targetFile)) {
                unlink($targetFile);
            }
            if (move_uploaded_file($_FILES["banner_image"]["tmp_name"], $targetFile)) {
                $imagePath = $filename;
            }
        }
        if (!empty($_POST["remove_image"]) && $_POST["remove_image"] == "1") {
            \WHMCS\Database\Capsule::table("mod_portal_content")->where("setting", "banner_image")->delete();
        }
        if (!$imagePath) {
            $imagePath = \WHMCS\Database\Capsule::table("mod_portal_content")->where("setting", "banner_image")->value("value") ?? "";
        }
        $fields = ["heading" => $_POST["heading"] ?? "", "description" => $_POST["description"] ?? "", "button_text" => $_POST["button_text"] ?? "", "button_link" => $_POST["button_link"] ?? "", "position" => $_POST["position"] ?? "", "banner_image" => $imagePath];
        foreach ($fields as $setting => $value) {
            \WHMCS\Database\Capsule::table("mod_portal_content")->updateOrInsert(["setting" => $setting], ["value" => $value]);
        }
        header("Location: addonmodules.php?module=aquaskythemeactivator&action=banner&saved=1");
        exit;
    }
    public function show($vars)
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
        return "\n<h2>Show</h2>\n\n<p>This is the <em>show</em> action output of the sample addon module.</p>\n\n<p>The currently installed version is: <strong>" . $version . "</strong></p>\n\n<p>\n    <a href=\"" . $modulelink . "\" class=\"btn btn-info\">\n        <i class=\"fa fa-arrow-left\"></i>\n        Back to home\n    </a>\n</p>\n";
    }
}
function tabs($active_tab)
{
    echo "<ul class=\"nav nav-tabs admin-tabs nav-tabs>\" role=\"tablist\" >\n    \n    <li class=\"" . (isset($active_tab) && $active_tab == "licence" ? "active" : "") . "\">\n        <a href=\"addonmodules.php?module=aquaskythemeactivator\">licence</a>\n    </li>\n     <li class=\"" . (isset($active_tab) && $active_tab == "banner" ? "active" : "") . "\">\n        <a href=\"addonmodules.php?module=aquaskythemeactivator&action=banner\">General Settings</a>\n    </li>\n    \n    </ul>";
}

?>