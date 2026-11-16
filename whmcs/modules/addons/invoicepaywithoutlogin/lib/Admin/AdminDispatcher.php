<?php
namespace WHMCS\Module\Addon\invoicepaywithoutlogin\Admin;

class AdminDispatcher
{
    public function dispatch($action, $parameters)
    {
        if (!$action) {
            $action = "index";
        }
        $controller = new Controller();
        if (is_callable([$controller, $action])) {
            return $controller->{$action}($parameters);
        }
        return "<p>Invalid action requested. Please go back and try again.</p>";
    }
}

?>