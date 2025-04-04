<?php

use Timon\Order\OrderController;
use Timon\Order\User;

require $_SERVER['DOCUMENT_ROOT'] . "/bitrix/header.php";


function preDump($var)
{
    echo "<pre>";
    print_r($var);
    echo "</pre>";
}



// try {


// $response = \Bitrix\Catalog\ProductTable::getList([
//     'filter' => ['=NAME' => 'Платье Модница на Прогулке'],
//     'select' => ['ID', 'NAME' => 'IBLOCK_ELEMENT.NAME']
// ]);
//     $res = [];
//     while ($product = $response->fetch()) {
//         $res[] = $product;
//     }
//     $firstProductId = array_shift($res);
//     $order = \Bitrix\Sale\Order::load(49);
//     $basket = $order->getBasket();
//     $item = $basket->addProduct($fields);
//     $order->save();
//     preDump($basket->getBasketItems());
// } catch (Exception $e) {
//     preDump($e->getMessage());
// }


$productId = 7; // Замените на ваш ID товара

// Получаем информацию о товаре

// $result = \Bitrix\Catalog\ProductTable::getList(array(

//     'filter' => array('=ID'=>$productId),

//     'select' => array('ID','QUANTITY','NAME'=>'IBLOCK_ELEMENT.NAME','CODE'=>'IBLOCK_ELEMENT.CODE', 'IBLOCK_ID' => 'IBLOCK_ELEMENT.ID'),

// ));
// $item = $result->fetch();
// $iblockId = $item['IBLOCK_ID'];
// preDump($iBlockId);
// preDump($item);
// die();

// $res = \CIBlockElement::GetProperty($iblockId, $productId);
// while ($prop = $res->Fetch()) {
//     preDump($prop);
// }

// try {
// try{


// $order = \Bitrix\Sale\Order::load(49);

// $http = getHttpClient();

// \Bitrix\Main\Loader::includeModule('catalog');
// $productIds = [];
// $orderBasket = CSaleBasket::GetList([], ["ORDER_ID" => 48]);
// while ($item = $orderBasket->GetNext()) {
    // $products[$item["ID"]] = [
    //     'ID' => $item['ID'],
    //     'NAME' => $item['NAME'],
    //     'PRICE' => $item['PRICE'],
    // ];
    // $productIds[] = $item['ID'];
//     $response = \CCatalogStoreProduct::GetList([], [
//         'PRODUCT_ID' => $item['ID']
//     ], false, false, ['AMOUNT']);
//     $storageProduct = $response->Fetch();
//     preDump($storageProduct);
// }

// $arrData = [
//     "PRODUCT_ID" => $productIds,
//     "AMOUNT" => 120
// ];
// $http->post("https://task.ru/api/products/storage/", $arrData);
// preDump($http->getResult());
// preDump($order->getField('STATUS_ID'));
// $order->setField('CANCELED', 'Y');
// preDump($order);
//     $basket = $order->getBasket();
//     $basketItems = $basket->getBasketItems();
//     $firstItem = array_shift($basketItems);
//     $itemId = $firstItem->getField('ID');
//     $basket->getItemById($itemId)->delete();
//     $basket->save();
//     $order->save();
//     // preDump($firstItem);
// } catch (Exception $e)
// {
//     preDump($e->getMessage());
// }
//     preDump($order->isShipped());
//     if ($order) {
//         $basket = $order->getBasket();

//         // Обновляем сумму заказа (например, добавляем новый товар)
//         // Пример: добавление товара с ID 456 и количеством 1
//         $basketItem = $basket->createItem('catalog', 7);
//         $basketItem->setFields(array(
//             'QUANTITY' => 2,
//             'CUSTOM_PRICE' => 'Y', // Указываем, что цена будет кастомной
//             'PRICE' => 300, // Устанавливаем цену
//             'BASE_PRICE' => 300, 
//             'NAME' => 'Платье Модница на Прогулке',
//             'PRODUCT_PROVIDER_CLASS' => \Bitrix\Catalog\Product\CatalogProvider::class,
//             'LID' => 's1',
//             'MEASURE_NAME' => 'шт',
//             'MEASURE_CODE' => 796,
//             'DELAY' => 'N',
//             'CURRENCY' => 'RUB',
//             // 'SORT' => 100
//         ));

//         // Сохраняем изменения
//         $basket->save();
//         $order->save(); // Сохраняем сам заказ
//     }

//     preDump($basket->getBasketItems([
//         'select' => ['NAME', 'ID', 'PRODUCT_ID', 'ID', 'PRICE']
//     ]));
// $quantity = 1;
// $productId = 7;
// $basketItem = $basket->createItem('catalog', $productId);
// $basketItem->setFields(array(
//     'CURRENCY' => \Bitrix\Currency\CurrencyManager::getBaseCurrency(),
//     'LID' => \Bitrix\Main\Context::getCurrent()->getSite(),
//     'PRODUCT_PROVIDER_CLASS' => 'CCatalogProductProvider',
//     'NAME' => 'Платье Модница на Прогулке'
// ));
// $basketItem->setField('QUANTITY', $quantity);


// $shipmentCollection = $order->getShipmentCollection();
// foreach ($shipmentCollection as $shipment) {
//     if (!$shipment->isSystem()) {
//         $shipmentItemCollection = $shipment->getShipmentItemCollection();
//         $shipmentItem = $shipmentItemCollection->createItem($basketItem);
//         $shipmentItem->setQuantity($basketItem->getQuantity());
//         break;
//     }
// }


// $discount = $order->getDiscount();
// \Bitrix\Sale\DiscountCouponsManager::clearApply(true);
// \Bitrix\Sale\DiscountCouponsManager::useSavedCouponsForApply(true);
// $discount->setOrderRefresh(true);
// $discount->setApplyResult(array());

// /** @var \Bitrix\Sale\Basket $basket */
// if (!($basket = $order->getBasket())) {
//     throw new \Bitrix\Main\ObjectNotFoundException('Entity "Basket" not found');
// }

// $basket->refreshData(array('PRICE', 'COUPONS'));
// $discount->calculate();
// $order->save();
// } catch (Exception $e) {
//     preDump($e->getMessage());
// }

require $_SERVER['DOCUMENT_ROOT'] . "/bitrix/footer.php";
