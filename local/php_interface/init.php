<?php


$eventManager = \Bitrix\Main\EventManager::getInstance();
$eventManager->addEventHandler('sale', 'OnOrderSave', 'sendOrderInBX24');

$eventManager->addEventHandler('sale', 'OnSaleStatusOrderChange', 'changeOrderStatusInBX24');

$eventManager->addEventHandler('sale', 'OnSaleCancelOrder', 'changeCanceledInBX24');


// обработка события отмены / отмены отмены заказа
function changeCanceledInBX24($orderId, $value, $description)
{
    try {
        $http = getHttpClient();
        $dealId = getDealByOrderId($orderId);
        $updateDealStatusUrl = "https://" . getIp() . "/rest/1/6lwm51qd52y135tk/crm.deal.update.json?ID={$dealId}&FIELDS[STAGE_ID]=";
        if ($value == 'N') {
            $order = getOrderById($orderId);
            $orderStatus = $order->getField('STATUS_ID');
            $dealStatus = match ($orderStatus) {
                'N' => 'NEW',
                'P' => 'EXECUTING',
                'F' => 'WON'
            };
            $updateDealStatusUrl .= $dealStatus;
            $http->get($updateDealStatusUrl);
            $json = $http->getResult();
            file_put_contents($_SERVER['DOCUMENT_ROOT'] . "/local/" . uniqid() . ".json", $json);
        } else if ($value == 'Y') {
            $dealStatus = 'LOSE';
            $updateDealStatusUrl .= $dealStatus;
            $http->get($updateDealStatusUrl);
        }
        $json = json_encode(['dealId' => $dealId, 'value' => $value, 'result' => $http->getResult()]);

        file_put_contents($_SERVER['DOCUMENT_ROOT'] . "/local/" . uniqid() . ".json", $json);
    } catch (Exception $e) {
        $json = json_encode(['error' => $e->getMessage()]);
        file_put_contents($_SERVER['DOCUMENT_ROOT'] . "/local/" . uniqid() . ".json", $json);
    }
}


function getIp()
{
    return "192.168.31.50";
    // return "192.168.59.113";
}

// обновление статуса заказа в BX24

// Статусы заказов 
// N - принят, ожидается оплата
// P - оплачен, формируется к отправке
// F - Заказ доставлен и оплачен
function changeOrderStatusInBX24($event)
{
    $order = $event->getParameter("ENTITY");
    $statusId = $order->getField('STATUS_ID');
    $orderId = $order->getField("ID");
    $http = getHttpClient();
    $dealId = getDealByOrderId($orderId);
    $statusID = match ($statusId) {
        'N' => 'NEW',
        'P' => 'EXECUTING',
        'F' => 'WON'
    };
    $http->get("https://" . getIp() . "/rest/1/13mobu4hbuzyc43p/crm.deal.update.json?ID={$dealId}&FIELDS[STAGE_ID]={$statusID}");
    $status = json_decode($http->getStatus());
    if ($status == 200); {
        return true;
    }
}


//получить сделку по id заказа
function getDealByOrderId($orderId)
{
    $http = getHttpClient();
    $response = $http->get("https://" .  getIp() . "/rest/1/13mobu4hbuzyc43p/crm.deal.list.json?FILTER[ORIGIN_ID]={$orderId}&SELECT[]=ID");
    return json_decode($response)->result[0]->ID;
}



// создание сделки в BX24
function sendOrderInBX24($orderId, $fields, $orderFields, $isNew)
{
    if ($isNew == 1) {
        try {
            $user = getCurrentUserData();
            $contactId = getContactId($user);
            $products = getProductsByOrderId($orderId);
            $productsQuantity = getProductsQuantity($orderId);
            $dealProducts = getProductFromBX24($products, $productsQuantity);
            $opportunity = getOrderOpportunity($orderId);
            $dealId = makeDeal($contactId, "RUB", $orderId, $opportunity);
            attachProductsToDeal($dealId, $dealProducts);
            makeInvoice($contactId, $dealId, $opportunity, $dealProducts);
        } catch (Exception $e) {
            preDump($e->getMessage());
        }
    }
}


function makeDealInvoice(int $dealId, int $contactId, array $products, int $opportunity, string $currency = "RUB", $dateBill = null) {}



function getCurrentUserData()
{
    $cuser = new CUser();
    $user = [
        'email' => $cuser->GetEmail(),
        'name' => $cuser->GetFirstName(),
        'lastName' => $cuser->GetLastName(),
        'secondName' => $cuser->GetSecondName(),
    ];
    return $user;
}

function getOrderById(int $orderId)
{
    return \Bitrix\Sale\Order::load($orderId);
}
function getOrderOpportunity($orderId)
{
    $order = getOrderById($orderId);
    $opportunity = $order->getPrice();
    return $opportunity;
}


// создание сделки
function makeDeal(int $contactId, string $currency, int $orderId, int $opportunity, $endDate = null)
{
    $http = getHttpClient();
    $newDealUrl = "https://" . getIp() . "/rest/1/fui3z67mkji7kjj0/crm.deal.add.json?FIELDS[CONTACT_ID]=$contactId&FIELDS[CURRENCY_ID]=$currency&FIELDS[OPENED]=Y&FIELDS[ORIGIN_ID]={$orderId}&FIELDS[IS_MANUAL_OPPORTUNITY]=Y&FIELDS[OPPORTUNITY]={$opportunity}";
    $http->get($newDealUrl);
    $response = json_decode($http->getResult());
    $dealId = $response->result;
    return $dealId;
}
// прикрепление товаров к сделке
function attachProductsToDeal(int $dealId, $products)
{
    $http = getHttpClient();
    $productsUrl = makeQueryStringForProductAddToDeal($products);
    $attachProuctsUrl = "https://" . getIp() . "/rest/1/fui3z67mkji7kjj0/crm.deal.productrows.set.json?id={$dealId}" . $productsUrl;
    $http->get($attachProuctsUrl);
}
// создает строку запроса для добавления товара в сделку
function makeQueryStringForProductAddToDeal(array $bx24products)
{
    $i = 0;
    $queryString = implode("&", array_map(function ($item) use (&$i) {
        $productName = replaceSpaceToPlus($item['PRODUCT_NAME']);
        $result = "rows[{$i}][PRODUCT_ID]={$item['PRODUCT_ID']}&rows[{$i}][PRODUCT_NAME]={$productName}&rows[{$i}][QUANTITY]={$item['QUANTITY']}&rows[{$i}][PRICE]={$item['PRICE']}";
        $i++;
        return $result;
    }, $bx24products));
    return $queryString;
}



// работа с продуктами
// получить все товары, привязанные к заказу
function getProductsByOrderId($orderId)
{
    $products = [];
    $orderBasket = CSaleBasket::GetList(array("NAME", "ID", "PRICE", "DESCRIPTION", "PRODUCT_ID"), ["ORDER_ID" => $orderId]);
    while ($item = $orderBasket->GetNext()) {
        $products[$item["ID"]] = [
            'ID' => $item['ID'],
            'NAME' => $item['NAME'],
            'PRICE' => $item['PRICE'],
        ];
    }
    return $products;
}

// получение количества продуктов в заказе 
function getProductsQuantity(int $orderId)
{
    $order = getOrderById($orderId);
    $quantites = $order->getBasket()->getQuantityList();
    return $quantites;
}

// получение только уникальных значений товаров для проверки и создания
function getUniqueProducts(array $products)
{
    $result = [];
    foreach ($products as $product) {
        if (!in_array($product["NAME"], array_map(function ($item) {
            return $item['NAME'];
        }, $result))) {
            $result[] = $product;
        }
    }
    return $result;
}


// проверка существования товаров в bx24
function getProductFromBX24(array $products, array $productQuantities)
{
    $bx24Products = [];
    $http = getHttpClient();
    if (CModule::IncludeModule("sale")) {
        foreach ($products as $product) {
            $productNameForQuery = replaceSpaceToPlus($product['NAME']);
            $webhookUrl = "https://" . getIp() . "/rest/1/fui3z67mkji7kjj0/crm.product.list.json?SELECT[]=NAME&SELECT[]=ID&SELECT[]=PRICE&FILTER[NAME]={$productNameForQuery}";
            $http->get($webhookUrl);
            $response = json_decode($http->getResult());
            if (count($response->result) != 0) {
                $bx24Product = $response->result[0];
                $bx24Products[$bx24Product->ID] = [
                    'PRODUCT_ID' => $bx24Product->ID,
                    'PRODUCT_NAME' => $bx24Product->NAME,
                    'PRICE' => $bx24Product->PRICE,
                    'MEASURE_NAME' => 'шт',
                    'QUANTITY' => $productQuantities[$product['ID']]
                ];
            } else {
                $newBx24Product = makeNewProductInBX24($product);
                $bx24Products[$newBx24Product['PRODUCT_ID']] = $newBx24Product;
            }
        }
        return $bx24Products;
    } else {
        return false;
    }
}

// создание нового продукта
function makeNewProductInBX24(array $product)
{
    $http = getHttpClient();
    $product['NAME'] = replaceSpaceToPlus($product['NAME']);
    $webhookUrl = "https://" . getIp() . "/rest/1/fui3z67mkji7kjj0/crm.product.add.json?FIELDS[NAME]={$product['NAME']}&FIELDS[PRICE]={$product['PRICE']}&FIELDS[CURRENCY_ID]=RUB";
    $http->get($webhookUrl);
    $response = json_decode($http->getResult());
    $newBx24Product = [
        'PRODUCT_ID' => $response->result,
        'PRODUCT_NAME' => $product['NAME'],
        'PRICE' => $product['PRICE'],
        'MEASURE_NAME' => 'шт',
        "QUANTITY" => 1
    ];
    return $newBx24Product;
}





// функции для работы с контактами
// получение информации о теущем пользователе

// получение id контакта для заказа
function getContactId(array $user)
{
    $contactId = checkContactExists($user);
    if (!$contactId) {
        $contactId = makeNewContact($user);
    }
    return $contactId;
}
// создание строки с параметрами для извлечения контакта, если он есть
function makeContactListString(array $user)
{
    $webhookUrl = "https://" . getIp() . "/rest/1/q124p5ojbu8b7bxt/crm.contact.list.json?";
    $select = "SELECT[]=ID&SELECT[]=EMAIL&";
    $filter = "FILTER[EMAIL][VALUE]={$user['email']}&FILTER[NAME]={$user['name']}&FILTER[LAST_NAME]={$user['lastName']}&FILTER[SECOND_NAME]={$user['secondName']}&ORDER[DATE_CREATE]=DESC";
    $url = $webhookUrl . $select . $filter;
    return $url;
}
// проверка существования контакта
function checkContactExists(array $user)
{
    $http = getHttpClient();
    $contactListUrl = makeContactListString($user);
    $http->get($contactListUrl);
    $response = json_decode($http->getResult())->result;
    if (count($response) == 0) {
        return false;
    }
    return $response[0]->ID;
}
// создание строки с параметрами
function makeQueryStringForNewContact(array $user)
{
    //     $http->get("https://192.168.31.50/rest/1/5droh0w6kjqi5mi2/crm.contact.add.json?FIELDS[NAME]={$name}&FIELDS[SECOND_NAME]={$secondName}&FIELDS[LAST_NAME]={$lastName}&FIELDS[EMAIL][VALUE]={$email}");
    $webhookUrl = "https://" . getIp() . "/rest/1/fui3z67mkji7kjj0/crm.contact.add.json?";
    $fields = "FIELDS[NAME]={$user['name']}&FIELDS[SECOND_NAME]={$user['secondName']}&FIELDS[LAST_NAME]={$user['lastName']}&FIELDS[EMAIL][][VALUE]={$user['email']}&FIELDS[EMAIL][][VALUE_TYPE]=WORK&FIELDS[OPENED]=Y";
    $url = $webhookUrl . $fields;
    return $url;
}
// создание нового контакта
function makeNewContact(array $user)
{
    $http = getHttpClient();
    $makeContactUrl = makeQueryStringForNewContact($user);
    $http->get($makeContactUrl);
    $response = $http->getResult();
    $contactId = false;
    if (count($http->getError()) == 0) {
        $contactId = json_decode($response)->result;
    }
    return $contactId;
}





// функции для работы со счетами
function makeInvoice(int $contactId, int $dealId, int $price, array $products, int $paySystemId = 2, string $currency = "RUB", string $statusId = "N", int $personTypeId = 4, $dateBill = null)
{
    $http = getHttpClient();
    $orderTopic = replaceSpaceToPlus("Сделка №{$dealId}");
    $productRow = getInvoiceProductRow($products);
    $url = "https://" . getIp() . "/rest/1/lunqs8wn0w5eyxh9/crm.invoice.add.json?FIELDS[ORDER_TOPIC]={$orderTopic}&FIELDS[PERSON_TYPE_ID]=4&FIELDS[PAY_SYSTEM_ID]=4&FIELDS[STATUS_ID]=N&FIELDS[PRICE]={$price}.00&FIELDS[CURRENCY]={$currency}&FIELDS[UF_CONTACT_ID]={$contactId}&FIELDS[UF_DEAL_ID]={$dealId}&" . $productRow;
    $http->get($url);
    $result = $http->getResult();
}


function getInvoiceProductRow(array $bx24Products)
{
    $productArray = getInvoiceProducts($bx24Products);
    $i = 0;
    $result = implode("&", array_map(function ($item) use (&$i) {
        $productName = replaceSpaceToPlus($item['PRODUCT_NAME']);
        $productId = $i + 1;
        $row = "FIELDS[PRODUCT_ROWS][{$i}][ID]={$productId}&FIELDS[PRODUCT_ROWS][{$i}][PRODUCT_ID]={$item['PRODUCT_ID']}&FIELDS[PRODUCT_ROWS][{$i}][PRODUCT_NAME]={$productName}&FIELDS[PRODUCT_ROWS][{$i}][QUANTITY]={$item['QUANTITY']}&FIELDS[PRODUCT_ROWS][{$i}][PRICE]={$item['PRICE']}";
        $i++;
        return $row;
    }, $productArray));
    return $result;
}
function getInvoiceProducts(array $bx24Products)
{
    $i = 0;
    $result = [];
    foreach ($bx24Products as $product) {
        $result[$i]['ID'] = $i;
        $result[$i]['PRODUCT_ID'] = $product['PRODUCT_ID'];
        $result[$i]['PRODUCT_NAME'] = $product['PRODUCT_NAME'];
        $result[$i]['QUANTITY'] = $product['QUANTITY'];
        $result[$i]['PRICE'] = $product['PRICE'];
        $i++;
    }
    return $result;
}


//получение httpCLient
function getHttpClient()
{
    $httpClient = new \Bitrix\Main\Web\HttpClient();
    $httpClient->setAuthorization(
        'admin',
        'bitrix'
    );
    $httpClient->disableSslVerification();
    return $httpClient;
}



// функция для замены пробелов на '+'
function replaceSpaceToPlus(string $str)
{
    return implode("+", explode(" ", $str));
}
