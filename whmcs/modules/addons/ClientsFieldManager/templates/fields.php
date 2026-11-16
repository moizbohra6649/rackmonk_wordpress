<?php
try {
    $optionalfields = ["status" => "success", "data" => "firstname,lastname,address1,city,state,postcode,phonenumber"];
    $fields = Illuminate\Database\Capsule\Manager::table("cfm_settings")->get();
    $fieldVisibilitySettings = [];
    foreach ($fields as $field) {
        $fieldVisibilitySettings[$field->field_name] = ["show" => $field->show, "checkbox_state" => $field->checkbox_state];
    }
    $firstField = $fields->first();
    $updatedtime = $firstField->updated_at;
    if ($updatedtime != "") {
        $updatedtime = date("l, d F Y h:i A", strtotime($updatedtime));
    }
    $companyVisibility = $fieldVisibilitySettings["company"]["show"];
    $companyCheckboxState = $fieldVisibilitySettings["company"]["checkbox_state"];
    $address2Visibility = $fieldVisibilitySettings["address2"]["show"];
    $address2CheckboxState = $fieldVisibilitySettings["address2"]["checkbox_state"];
    $validFields = [];
    foreach ($fields as $field) {
        if ($field->field_name == "company" || $field->field_name == "address2") {
        } else {
            $fieldVisibilitySettings[$field->field_name] = ["show" => $field->show, "checkbox_state" => $field->checkbox_state];
            if ($field->checkbox_state == 1) {
                $validFields[] = $field->field_name;
            }
        }
    }
    $validFieldsString = implode(",", $validFields);
    Illuminate\Database\Capsule\Manager::table("tblconfiguration")->where("setting", "ClientsProfileOptionalFields")->update(["value" => $validFieldsString]);
} catch (Exception $e) {
    logModuleCall("ClientsFieldManager", "getFieldValueForCheckboxStateAndShow", $e->getMessage(), $e->getTraceAsString(), NULL);
    return ["status" => "error", "message" => "Error while fetching the record", "action" => "hide"];
}
echo "\r\n<!DOCTYPE html>\r\n<html lang=\"en\">\r\n\r\n<head>\r\n    <meta charset=\"UTF-8\">\r\n    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\r\n    <title></title>\r\n    <link rel=\"stylesheet\" href=\"../modules/addons/ClientsFieldManager/templates/style.css\">\r\n</head>\r\n\r\n<body>\r\n    <div class=\"top-right-time\">\r\n        ";
echo $updatedtime != NULL || $updatedtime != "" ? "Last Updated: " . $updatedtime : "";
echo "    </div>\r\n    <div class=\"form-container\">\r\n        <form id=\"fieldForm\" method=\"POST\" action=\"\">\r\n            <table class=\"datatable\" style=\"width: 100%;\">\r\n                <thead>\r\n                    <tr>\r\n                        <th>Client Fields</th>\r\n                        <th>Visibility</th>\r\n                        <th>Optional</th>\r\n                    </tr>\r\n                <tbody>\r\n                    ";
if ($optionalfields["status"] == "success" && !empty($optionalfields["data"])) {
    $optionalFieldArray = explode(",", $optionalfields["data"]);
    foreach ($optionalFieldArray as $value) {
        $visibility = $fieldVisibilitySettings[$value]["show"] ?? "show";
        $checkboxState = $fieldVisibilitySettings[$value]["checkbox_state"];
        $makecheckboxonoff = "checked";
        if ($checkboxState == "0") {
            $makecheckboxonoff = "";
        }
        if ($value == "firstname") {
            $label = "First Name";
        } else if ($value == "lastname") {
            $label = "Last Name";
        } else if ($value == "phonenumber") {
            $label = "Phone Number";
        } else {
            $label = ucfirst(str_replace("_", " ", $value));
        }
        echo "                            <tr>\r\n                                <td>\r\n                                    <label for=\"";
        echo $value;
        echo "\">";
        echo $label;
        echo " </label>\r\n                                </td>\r\n                                <td>\r\n                                    <div class=\"radio-group\" style=\"text-align: center;\">\r\n                                        <input type=\"radio\" id=\"show_";
        echo $value;
        echo "\" name=\"";
        echo $value;
        echo "Visibility\" value=\"show\" ";
        echo $visibility == "show" ? "checked" : "";
        echo " onclick=\"toggleField('";
        echo $value;
        echo "', true)\" checked>\r\n                                        <label for=\"show_";
        echo $value;
        echo "\" style=\"margin-right:8px;\">Show</label>\r\n                                        <input type=\"radio\" id=\"hide_";
        echo $value;
        echo "\" name=\"";
        echo $value;
        echo "Visibility\" value=\"hide\" ";
        echo $visibility == "hide" ? "checked" : "";
        echo " onclick=\"toggleField('";
        echo $value;
        echo "', false)\">\r\n                                        <label for=\"hide_";
        echo $value;
        echo "\">Hide</label>\r\n                                    </div>\r\n                                </td>\r\n                                <td>\r\n                                    <div id=\"";
        echo $value;
        echo "CheckboxLabel\" class=\"checkboxContainer\" style=\"text-align:center;\">\r\n                                        <label style=\"cursor: pointer;\">\r\n                                            <input type=\"checkbox\" ";
        echo $visibility == "show" ? "" : "disabled";
        echo " name=\"";
        echo $value;
        echo "Checkbox\" id=\"";
        echo $value;
        echo "Checkbox\" ";
        echo $makecheckboxonoff;
        echo " onchange=\"updateField('";
        echo $value;
        echo "')\"> Tick to make field optional at signup time.\r\n                                        </label>\r\n                                    </div>\r\n                                </td>\r\n                            </tr>\r\n\r\n                    ";
    }
}
$makecheckboxonoff = "checked";
if (isset($fieldVisibilitySettings["address2"]) && $fieldVisibilitySettings["address2"]["checkbox_state"] == "0") {
    $makecheckboxonoff = "";
}
echo "                    <tr>\r\n                        <td>\r\n                            <label for=\"address2\">Address2</label>\r\n                        </td>\r\n                        <td>\r\n                            <div class=\"radio-group\" style=\"text-align: center;\">\r\n                                <input type=\"radio\" id=\"showaddress2\" name=\"address2Visibility\" value=\"show\" ";
echo $address2Visibility === "show" ? "checked" : "";
echo " onclick=\"toggleField('address2', true)\" checked>\r\n                                <label for=\"showaddress2\" style=\"margin-right:8px;\">Show</label>\r\n                                <input type=\"radio\" id=\"hideaddress2\" name=\"address2Visibility\" value=\"hide\" ";
echo $address2Visibility === "hide" ? "checked" : "";
echo " onclick=\"toggleField('address2', false)\">\r\n                                <label for=\"hideaddress2\">Hide</label>\r\n                            </div>\r\n                        </td>\r\n                        <td>\r\n                            <div class=\"checkboxContainer\" style=\"text-align:center;\">\r\n                                <p>&nbsp;</p>\r\n                            </div>\r\n                        </td>\r\n                    </tr>\r\n                    <tr>\r\n                        <td>\r\n                            <label for=\"companyname\">Company Name</label>\r\n                        </td>\r\n                        <td>\r\n                            <div class=\"radio-group\" style=\"text-align: center;\">\r\n                                <input type=\"radio\" id=\"showCompany\" name=\"companyVisibility\" value=\"show\" ";
echo $companyVisibility === "show" ? "checked" : "";
echo " onclick=\"toggleField('company', true)\" checked>\r\n                                <label for=\"showCompany\" style=\"margin-right:8px;\">Show</label>\r\n                                <input type=\"radio\" id=\"hideCompany\" name=\"companyVisibility\" value=\"hide\" ";
echo $companyVisibility === "hide" ? "checked" : "";
echo " onclick=\"toggleField('company', false)\">\r\n                                <label for=\"hideCompany\">Hide</label>\r\n                            </div>\r\n                        </td>\r\n                        <td>\r\n                            <div class=\"checkboxContainer\" style=\"text-align:center;\">\r\n                                <p>&nbsp;</p>\r\n                            </div>\r\n                        </td>\r\n                    </tr>\r\n                </tbody>\r\n                <tfoot>\r\n                    <tr>\r\n                        <td colspan=\"3\" style=\"text-align: center;\">\r\n                            <button type=\"button\" name=\"savedata\" onClick=\"submitForm()\" class=\"btn btn-primary submitbtn\">Save Changes <i class=\"fa fa-spinner fa-spin hidden\"></i></button>\r\n                        </td>\r\n                    </tr>\r\n                </tfoot>\r\n            </table>\r\n        </form>\r\n    </div>\r\n\r\n    <!-- Include SweetAlert2 CSS -->\r\n    <link href=\"https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css\" rel=\"stylesheet\">\r\n\r\n    <!-- Include SweetAlert2 JS -->\r\n    <script src=\"https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js\"></script>\r\n\r\n    <script src=\"https://code.jquery.com/jquery-3.6.0.min.js\"></script>\r\n    <script src=\"../modules/addons/ClientsFieldManager/templates/script.js\"></script>\r\n</body>\r\n\r\n</html>";

?>