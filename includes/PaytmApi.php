<?php
// includes/PaytmApi.php
require_once 'PaytmChecksum.php';

class PaytmApi
{

    // PROD URLS
    const URL_CREATE_QR = "https://securegw.paytm.in/paymentservices/qr/create";
    const URL_STATUS = "https://securegw.paytm.in/v3/order/status";

    // STAGING URLS (For Reference)
    // const URL_CREATE_QR = "https://securegw-stage.paytm.in/paymentservices/qr/create";
    // const URL_STATUS    = "https://securegw-stage.paytm.in/v3/order/status";

    public static function createQr($mid, $key, $orderId, $amount, $isProd = true)
    {
        $body = [
            "mid" => $mid,
            "orderId" => $orderId,
            "amount" => $amount,
            "businessType" => "UPI_QR_CODE",
            "posId" => "S12_123"
        ];

        return self::call($mid, $key, $body, self::URL_CREATE_QR);
    }

    public static function getStatus($mid, $key, $orderId, $isProd = true)
    {
        $body = [
            "mid" => $mid,
            "orderId" => $orderId
        ];

        return self::call($mid, $key, $body, self::URL_STATUS);
    }

    private static function call($mid, $key, $bodyParams, $url)
    {
        try {
            $checksum = PaytmChecksum::generateSignature(json_encode($bodyParams, JSON_UNESCAPED_SLASHES), $key);

            $head = [
                "clientId" => "C11",
                "version" => "v1",
                "channelId" => "WEB",
                "signature" => $checksum
            ];

            $postData = json_encode(["body" => $bodyParams, "head" => $head], JSON_UNESCAPED_SLASHES);

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Content-Type: application/json",
            ]);

            $response = curl_exec($ch);
            curl_close($ch);

            return json_decode($response, true);
        } catch (Exception $e) {
            return null;
        }
    }
}
?>