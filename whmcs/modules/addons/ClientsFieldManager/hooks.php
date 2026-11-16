<?php
include_once dirname(__FILE__) . '/lib/Admin/functions.php';

use WHMCS\Database\Capsule;

add_hook('ClientAreaFooterOutput', 1, 'Client_Fields_Manager_hideForm');
add_hook('ClientAreaFooterOutput', 1, 'Client_Fields_Manager_hideFields');
