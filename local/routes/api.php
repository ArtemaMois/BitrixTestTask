<?php

use Timon\Order\OrderController;

if(CModule::IncludeModule('timon.order'))
{
    return function (\Bitrix\Main\Routing\RoutingConfigurator $routes) {
        $routes->post('/api/orders', [OrderController::class, 'updateOrder']);
    };
}


