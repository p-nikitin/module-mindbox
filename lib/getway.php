<?php


namespace Izifir\Mindbox;


use Bitrix\Main\Config\Option;
use Bitrix\Main\Context;
use Bitrix\Main\Diag\Debug;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Web\Json;

class Getway
{
    /**
     * Отправляет запрос в Mindbox
     * @param $operation
     * @param $data
     * @return mixed
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ArgumentNullException
     * @throws \Bitrix\Main\ArgumentOutOfRangeException
     */
    public static function send($operation, $data)
    {
        if ($_COOKIE['mindboxDeviceUUID']) {
            $secretKey = Option::get('izifir.mindbox', 'secret_key', false);
            $url = Option::get('izifir.mindbox', 'api_url', false);
            $endpointId = Option::get('izifir.mindbox', 'endpoint_id', '');

            $httpClient = new HttpClient();
            $httpClient->setHeaders([
                'Content-Type' => 'application/json; charset=utf-8',
                'Accept' => 'application/json'
            ]);
            if ($secretKey)
                $httpClient->setHeader('Authorization', 'Mindbox secretKey="' . $secretKey . '"');

            $query = [
                'endpointId' => $endpointId,
                'operation' => $operation,
                'deviceUUID' => $_COOKIE['mindboxDeviceUUID']
            ];

            $query = http_build_query($query);

            $result = $httpClient->post($url . '?' . $query, $data);

            return Json::decode($result);
        }
        return [];
    }
}
