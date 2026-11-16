<?php
namespace WHMCS\Module\Addon\AddonModule\Client;

class ClientDispatcher
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
    }
}

?>