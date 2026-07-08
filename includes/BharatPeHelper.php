<?php
// includes/BharatPeHelper.php

class BharatPeHelper
{

    // Note: These endpoints are based on common patterns. 
    // If BharatPe changes their internal API, these constants need to be updated.
    const BASE_URL = "https://api.bharatpe.in/v1";

    public static function sendOtp($mobile)
    {
        $candidates = [
            "https://payments-tesseract.bharatpe.in/api/v1/merchant/login/otp",
            "https://payments-tesseract.bharatpe.in/api/v1/login/otp",
            "https://api.bharatpe.in/v1/merchant/login/otp"
        ];

        foreach ($candidates as $url) {
            $response = self::makeRequest($url, ['mobile' => $mobile]);
            if ($response['success']) {
                return ['status' => true, 'message' => 'OTP sent successfully (via ' . basename(dirname($url)) . ')'];
            }
        }

        return ['status' => false, 'message' => 'Could not auto-login (Endpoints failed). Please use Manual Entry.'];
    }

    private static function makeRequest($url, $data)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
        ]);

        $res = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $json = json_decode($res, true);
        if ($code == 200 && isset($json['status']) && $json['status']) {
            return ['success' => true, 'data' => $json];
        }
        return ['success' => false];
    }

    public static function verifyOtp($mobile, $otp)
    {
        $url = self::BASE_URL . "/login/verify";

        $payload = json_encode(['mobile' => $mobile, 'otp' => $otp]);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        $response = curl_exec($ch);

        // Capture cookies
        preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $response, $matches);
        $cookies = array();
        foreach ($matches[1] as $item) {
            parse_str($item, $cookie);
            $cookies = array_merge($cookies, $cookie);
        }
        // Format cookie string
        $cookieStr = "";
        foreach ($cookies as $key => $val) {
            $cookieStr .= "$key=$val; ";
        }

        curl_close($ch);
        $data = json_decode($response, true);

        if (isset($data['token'])) {
            // Fetch Merchant Details using Token
            $merchantDetails = self::getMerchantDetails($data['token'], $cookieStr);

            return [
                'status' => true,
                'token' => $data['token'],
                'cookie' => $cookieStr,
                'merchantId' => $merchantDetails['merchantId'] ?? 'UNKNOWN',
                'vpa' => $merchantDetails['vpa'] ?? 'UNKNOWN',
            ];
        }

        return ['status' => false, 'message' => $data['message'] ?? 'Invalid OTP'];
    }

    public static function getMerchantDetails($token, $cookie)
    {
        $url = "https://payments-tesseract.bharatpe.in/api/v1/merchant/user"; // Known endpoint

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "token: $token",
            "Cookie: $cookie",
            "User-Agent: Mozilla/5.0"
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);

        // logic to extract MID/VPA from response
        $mid = $data['data']['merchantId'] ?? null;
        $vpa = $data['data']['vpa'] ?? null;

        return ['merchantId' => $mid, 'vpa' => $vpa];
    }

    public static function getTransactions($merchantId, $token, $cookie)
    {
        $fromDate = date('Y-m-d', strtotime('-2 days'));
        $toDate = date('Y-m-d');

        $url = "https://payments-tesseract.bharatpe.in/api/v1/merchant/transactions?module=PAYMENT_QR&merchantId=$merchantId&sDate=$fromDate&eDate=$toDate";

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "token: $token",
            "Cookie: $cookie",
            "User-Agent: Mozilla/5.0 (Linux; Android 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0 Mobile Safari/537.36"
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);

        if (isset($data['status']) && $data['status'] && isset($data['data']['transactions'])) {
            return $data['data']['transactions'];
        }

        return [];
    }
}
?>