<?php

namespace Izifir\Mindbox;


use Bitrix\Main\Application;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\Diag\Debug;
use Bitrix\Main\IO\Directory;
use Bitrix\Main\Loader;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Config\Option;

class Operations
{
    /**
     * Операция "Просмотр страницы категории"
     * @param $categoryId
     * @return mixed
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ArgumentNullException
     * @throws \Bitrix\Main\ArgumentOutOfRangeException
     */
    public static function onlineCategoryView($categoryId)
    {
        if (self::canDo()) {
            $data = [
                'viewProductCategory' => [
                    'productCategory' => [
                        'ids' => [
                            'u4Group' => $categoryId
                        ]
                    ]
                ]
            ];

            $result = Getway::send('OnlineCategoryView', Json::encode($data));

            if ($result['status'] != 'Success')
                Debug::writeToFile($result, '', self::logDir() . 'OnlineCategoryView.txt');

            return $result;
        }
        return false;
    }

    /**
     * Операция "Расчет цен в каталоге и на карточке товара"
     * Операция
     * @param $elements
     * @return mixed
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ArgumentNullException
     * @throws \Bitrix\Main\ArgumentOutOfRangeException
     */
    public static function processingCalculateProductListNotAuth($elements)
    {
        if (self::canDo()) {
            $data = [
                'productList' => [
                    'items' => []
                ]
            ];
            foreach ($elements as $element) {
                $data['productList']['items'][] = [
                    'basePricePerItem' => $element['PRICE'],
                    'product' => [
                        'ids' => [
                            'u4Group' => $element['XML_ID']
                        ]
                    ]
                ];
            }

            $result = Getway::send('Website.ProcessingCalculateProductListNotAuth', Json::encode($data));

            Debug::writeToFile($data, '', self::logDir() . 'request.ProcessingCalculateProductListNotAuth.txt');

            if ($result['status'] != 'Success')
                Debug::writeToFile($result, '', self::logDir() . 'ProcessingCalculateProductListNotAuth.txt');
            else
                Debug::writeToFile($result, '', self::logDir() . 'response.ProcessingCalculateProductListNotAuth.txt');

            return $result;
        }
        return  false;
    }

    /**
     * Расчет стоимости товаров в корзине
     * @param $basket
     * @return mixed
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ArgumentNullException
     * @throws \Bitrix\Main\ArgumentOutOfRangeException
     */
    public static function calculateUnauthorizedCart($basket)
    {
        if (self::canDo()) {
            $goods = [];
            $arGoods = [];
            $arNewData = [];
            $basePriceId = Option::get('izifir.marccony', 'base_price', '1');
            $rrpPriceId = Option::get('izifir.marccony', 'rrp_price', '1');

            foreach ($basket as $basketItem) {
                $goods[$basketItem->getProductId()] = $basketItem->getProductId();
            }

            $elementsIterator = \CIBlockElement::GetList(
                [],
                ['ID' => $goods],
                false,
                false,
                ['ID', 'XML_ID', "PRICE_{$basePriceId}", "PRICE_{$rrpPriceId}"]
            );

            while ($element = $elementsIterator->fetch()) {
                $arGoods[$element['ID']]['xml'] = $element['XML_ID'];
                $arGoods[$element['ID']]['price'] = $element["PRICE_{$rrpPriceId}"];
            }

            foreach ($basket as $basketItem) {
                $arItemInfo = array();
                $arItemInfo['basePricePerItem'] = ($arGoods[$basketItem->getProductId()]['price']) ? $arGoods[$basketItem->getProductId()]['price'] : $basketItem->getPrice();
                $arItemInfo['quantity'] = $basketItem->getQuantity();
                $arItemInfo['quantityType'] = 'int';
                $arItemInfo['product']['ids']['u4Group'] = $arGoods[$basketItem->getProductId()]['xml'];
                $arItemInfo['status']['ids']['externalId'] = 'New';
                $arNewData['order']['lines'][] = $arItemInfo;
            }
            //Debug::writeToFile(Json::encode($arNewData), '', 'request.Website.CalculateUnauthorizedCart.json');
            $result = Getway::send('Website.CalculateUnauthorizedCart', Json::encode($arNewData));
            //Debug::writeToFile(Json::encode($result), '','response.Website.CalculateUnauthorizedCart.json');

            if ($result['status'] != 'Success')
                Debug::writeToFile($result, '', self::logDir() . 'CalculateUnauthorizedCart.txt');

            return $result;
        }
        return false;
    }

    public static function setViewedItemList($productId)
    {
        Loader::includeModule('iblock');
        if (self::canDo()) {
            $basePriceId = Option::get('izifir.marccony', 'base_price', '1');
            $cache = Cache::createInstance();
            if ($cache->initCache(3600, md5("SetViewedItemList{$productId}"), 'izifir')) {
                $vars = $cache->getVars();
                $element = $vars['element'];
            } elseif ($cache->startDataCache()) {
                $element = \CIBlockElement::GetList(
                    [],
                    ['ID' => $productId],
                    false,
                    false,
                    ['ID', 'IBLOCK_ID', "PRICE_{$basePriceId}", 'XML_ID']
                )->Fetch();

                if ($element)
                    $cache->endDataCache(['element' => $element]);
                else
                    $cache->abortDataCache();
            }

            if (!$_SESSION['SetViewedItemList'][$element['XML_ID']])
                $_SESSION['SetViewedItemList'][$element['XML_ID']] = $element["PRICE_{$basePriceId}"];

            $dataList = ['productList' => []];
            foreach ($_SESSION['SetViewedItemList'] as $id => $price) {
                if (!$id) continue;
                $arNewItem['product']['ids']['u4Group'] = $id;
                $arNewItem['price'] = $price;
                $arNewItem['count'] = 1;
                $dataList['productList'][] = $arNewItem;
            }

            $result = Getway::send('SetViewedItemList', Json::encode($dataList));

            if ($result['status'] != 'Success')
                Debug::writeToFile($result, '', self::logDir() . 'SetViewedItemList.txt');

            return $result;
        }
        return false;
    }

    public static function onlineProductView($productId)
    {
        Loader::includeModule('iblock');
        if (self::canDo()) {
            $basePriceId = Option::get('izifir.marccony', 'base_price', '1');
            $cache = Cache::createInstance();
            if ($cache->initCache(3600, md5("onlineProductView{$productId}"), 'izifir')) {
                $vars = $cache->getVars();
                $element = $vars['element'];
            } elseif ($cache->startDataCache()) {
                $element = \CIBlockElement::GetList(
                    [],
                    ['ID' => $productId],
                    false,
                    false,
                    ['ID', 'IBLOCK_ID', "PRICE_{$basePriceId}", 'XML_ID']
                )->Fetch();

                if ($element)
                    $cache->endDataCache(['element' => $element]);
                else
                    $cache->abortDataCache();
            }

            $data['viewProduct']['product']['ids']['u4Group'] = $element['XML_ID'];

            $result = Getway::send('OnlineProductView', Json::encode($data));

            if ($result['status'] != 'Success')
                Debug::writeToFile($result, '', self::logDir() . 'OnlineProductView.txt');

            return $result;
        }
        return false;
    }

    public static function subscriptionInFooterForm($email)
    {
        if (self::canDo()) {
            $data = [
                'customer' => [
                    'email' => $email,
                    'subscriptions' => [
                        [
                            'pointOfContact' => 'Email'
                        ]
                    ]
                ]
            ];

            $result = Getway::send('SubscriptionInFooterForm', Json::encode($data));

            if ($result['status'] != 'Success')
                Debug::writeToFile($result, '', self::logDir() . 'SubscriptionInFooterForm.txt');

            return $result;
        }
        return false;
    }

    public static function setKorzinaItemList($data)
    {
        if (self::canDo()) {
            $result = Getway::send('SetKorzinaItemList', Json::encode($data));

            if ($result['status'] != 'Success')
                Debug::writeToFile($result, '', self::logDir() . 'SetKorzinaItemList.txt');

            return $result;
        }
        return false;
    }

    public static function onlineEditUser($arFields)
    {
        if (self::canDo()) {
            if ($arFields["RESULT"]) {
                $arData = [];
                $arName = [];

                if ($arFields['PERSONAL_PHONE']) {
                    $phone = $arFields['PERSONAL_PHONE'];
                } else {
                    $arUser = \CUser::GetList($by, $order, ['ID' => $arFields['ID']], ['FIELDS' => 'PERSONAL_PHONE'])->Fetch();
                    if ($arUser)
                        $phone = $arUser['PERSONAL_PHONE'];
                }

                if ($phone) {
                    $arData['customer']['mobilePhone'] = $phone;
                    $arData['customer']['ids']['u4groupID'] = $phone;
                    if ($arFields['EMAIL']) $arData['customer']['email'] = $arFields['EMAIL'];
                    if ($arFields['LAST_NAME']) $arName[] = $arFields['LAST_NAME'];
                    if ($arFields['NAME']) $arName[] = $arFields['NAME'];
                    if ($arFields['SECOND_NAME']) $arName[] = $arFields['SECOND_NAME'];

                    if ($arName) $arData['customer']['fullName'] = implode(' ', $arName);

                    $result = Getway::send('OnlineEditUser', Json::encode($arData));

                    if ($result['status'] != 'Success')
                        Debug::writeToFile($result, '', self::logDir() . 'OnlineEditUser.txt');

                    return $result;
                }
            }
        }
        return false;
    }

    /**
     * Определяет возможность отправки данных
     * @return bool
     */
    protected static function canDo()
    {
        return true;
    }

    /**
     * Возвращает директорию для хранения логов
     *
     * @return string
     * @throws \Bitrix\Main\ArgumentNullException
     * @throws \Bitrix\Main\ArgumentOutOfRangeException
     */
    protected static function logDir()
    {
        $logDir = Option::get('izifir.mindbox', 'log_dir', '');
        if (empty($logDir))
            $logDir = '/upload/log/mindbox/';

        // Если директории не существует, то создадим ее
        $directory = new Directory(Application::getDocumentRoot() . $logDir);
        $directory->create();

        return $logDir;
    }
}
