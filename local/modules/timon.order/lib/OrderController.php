<?php

namespace Timon\Order;

use Bitrix\Main\Engine\Controller;
use Exception;

class OrderController extends Controller
{
    public function getDefaultPreFilters(): array
    {
        return [];
    }
    public function updateOrderAction()
    {
        $orderId = $this->request->getPost("ID");
        if (\Bitrix\Main\Loader::includeModule("sale")) {
            if ($status = $this->request->getPost('STATUS_ID')) {
                $response = $this->updateStatus($orderId, $status);
                return $response;
            }
            if ($price = $this->request->getPost('PRICE')) {
                $currency = $this->request->getPost('CURRENCY');
                $response = $this->updateOpportunityAndCurrency($orderId, $price, $currency);
                return $response;
            }
            if ($products = $this->request->getPost('PRODUCTS')) {
                $response = $this->updateProductList($orderId, $products);
                // $basketItems = $this->updateProductList($orderId, $products);
                // return json_encode(['PRODUCTS' => $products, 'BASKET' => $basketItems]);
                return json_encode($response);
            }
        } else {
            return json_encode(['status' => 'failed', 'error' => 'Module not included']);
        }
    }

    public function getOrderById(int $orderId)
    {
        $order = \Bitrix\Sale\Order::load($orderId);
        return $order;
    }

    public function updateStatus($orderId, $status)
    {
        $order = $this->getOrderById($orderId);
        if (is_array($status)) {
            $order->setField("STATUS_ID", 'N');
            $order->save();
            $order->setField('CANCELED', 'Y');
            $order->save();
        } else {
            $order->setField("CANCELED", 'N');
            $order->setField('STATUS_ID', $status);
            $order->save();
        }
        return json_encode(['status' => 'success']);
    }

    public function updateOpportunityAndCurrency(int $orderId, $price, $currency)
    {
        $order = $this->getOrderById($orderId);
        $order->setField('PRICE', $price);
        $order->doFinalAction(true);
        $order->save();
        return json_encode(['status' => 'success', 'price' => $price]);
    }

    public function updateProductList(int $orderId, array $products)
    {
        try {
            $order = $this->getOrderById($orderId);
            $basket = $order->getBasket();
            $basketItems = $basket->getBasketItems();
            $productsForAdded = [];
            $productsForDeleting = [];
            $productsForUpQuantity = [];
            $productsForDownQuantity = [];
            foreach ($basketItems as $basketItem) {
                $basketItemName = $basketItem->getField('NAME');
                $basketItemQuantity = $basketItem->getField('QUANTITY');
                $bx24Item = $products[$basketItemName];
                if (isset($bx24Item)) {
                    if ($basketItemQuantity > $bx24Item['QUANTITY']) {
                        $productsForDownQuantity[] = [
                            'PRODUCT_NAME' => $bx24Item['PRODUCT_NAME'],
                            'QUANTTITY_DIFFERENT' => $bx24Item['QUANTITY'] - $basketItemQuantity
                        ];
                        $basketItem->setField('QUANTITY', $bx24Item['QUANTITY']);
                        $basket->save();
                        $order->save();
                    } else if ($basketItemQuantity < $bx24Item['QUANTITY']) {
                        $productsForUpQuantity[] = [
                            'PRODUCT_NAME' => $bx24Item['PRODUCT_NAME'],
                            'QUANTTITY_DIFFERENT' => $bx24Item['QUANTITY'] - $basketItemQuantity
                        ];
                        $basketItem->setField('QUANTITY', $bx24Item['QUANTITY']);
                        $basket->save();
                        $order->save();
                    }
                    unset($products[$basketItemName]);
                } else {
                    $productsForDeleting[] = [
                        'NAME' => $basketItem->getField('NAME'),
                        'QUANTITY' => $basketItem->getField('QUANTITY')
                    ];
                    $basketItemId = $basketItem->getField('ID');
                    $basket->getItemById($basketItemId)->delete();
                }
            }

            $productsForAdded = $products;
            $this->addItemsInOrder($order, $basket, $productsForAdded);
            $basket->save();
            $order->save();
            return [
                'forDeleting' => $productsForDeleting,
                'forUpQuantity' => $productsForUpQuantity,
                'forDownQuantity' => $productsForDownQuantity,
                'forAdded' => $productsForAdded
            ];
        } catch (Exception $e) {
            return ['error' => $e->getMessage() . " " . $e->getLine() . " " . $e->getTraceAsString()];
        }
        // $basketItems = $this->getFilteredBasketItems($basket->getBasketItems());
        // return $basketItems;
    }

    public function addItemsInOrder(&$order, &$basket, $bx24Products)
    {
        foreach ($bx24Products as $bx24Item) {
            $response = \Bitrix\Catalog\ProductTable::getList([
                'filter' => ['=NAME' => $bx24Item['PRODUCT_NAME']],
                'select' => ['ID', 'NAME' => 'IBLOCK_ELEMENT.NAME']
            ]);
            $item = $response->fetch();
            $basketItem = $basket->createItem('catalog', $item['ID']);
            $basketItem->setFields(array(
                'QUANTITY' => $bx24Item['QUANTITY'],
                'CUSTOM_PRICE' => 'Y', // Указываем, что цена будет кастомной
                'PRICE' => $bx24Item['PRICE'], // Устанавливаем цену
                'BASE_PRICE' => $bx24Item['BASE_PRICE'],
                'NAME' => $bx24Item['PRODUCT_NAME'],
                'PRODUCT_PROVIDER_CLASS' => \Bitrix\Catalog\Product\CatalogProvider::class,
                'LID' => 's1',
                'MEASURE_NAME' => $bx24Item['MEASURE_NAME'],
                'MEASURE_CODE' => $bx24Item['MEASURE_CODE'],
                'DELAY' => 'N',
                'CURRENCY' => $bx24Item['CURRENCY'],
                'SORT' => 100
            ));
            $basket->save();
        }
    }

    public function removeProductsFromBasket($basket, $products)
    {
        $basketItems = $this->getFilteredBasketItems($basket->getBasketItems()); // возвращает NAME элементов, которые есть в корзине, но нет в bx24 продуктах
        $itemsForRemoving = [];
        $bx24ProductsName = array_map(function ($item) {
            return $item['PRODUCT_NAME'];
        }, $products);
        foreach ($basketItems as $item) {
            if (!in_array($item, $bx24ProductsName)) {
                $itemsForRemoving[] = $item;
            }
        }
        return $itemsForRemoving;
        // return array_udiff($basketItems, $products, );
    }


    public function addProductToBasket($basket, array $products)
    {
        $basketItems = $this->getFilteredBasketItems($basket->getBasketItems()); // возвращает NAME элементы, которые есть в bx24, но нет в корзине
        $basketItemsNames = array_Map(function ($item) {
            return $item['NAME'];
        }, $basketItems);
        $itemsForAdded = [];
        foreach ($products as $product) {
            if (!in_array($product['PRODUCT_NAME'], $basketItemsNames)) {
                $itemsForAdded[] = $product;
            }
        }
        return $itemsForAdded;
        // return array_diff($products, $basketItems);
    }

    public function getFilteredBasketItems($basketItems)
    {
        $result = [];
        foreach ($basketItems as $item) {
            $result[] = [
                'NAME' => $item->getField("NAME"),
                'QUANTITY' => $item->getField("QUANTITY")
            ];
        }

        return $result;
    }
}
