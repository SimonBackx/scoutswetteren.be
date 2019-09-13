<?php
namespace Pirate\Wheel\Curl;

use Pirate\Sails\Leiding\Models\Leiding;

class Curl
{
    // Doe een request en return de Json encoded object als response of null bij een failure
    public static function request($method, $url, $headers = [], $data_type = DataType::urlencoded, $data = null)
    {
        $body = null;

        if ($method != Method::GET && isset($data)) {
            if ($data_type == DataType::urlencoded) {
                $headers[] = 'Content-Type: application/x-www-form-urlencoded';
                $body = http_build_query($data);
            }

            if ($data_type == DataType::json) {
                $headers[] = 'Content-Type: application/json;charset=UTF-8';
                $body = json_encode($data);
            }
        }

        try {

            $curl = curl_init();

            $settings = [
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_URL => $url,
                CURLOPT_USERAGENT => 'Scouts Prins Boudewijn Wetteren',
                CURLOPT_HTTPHEADER => $headers,
            ];

            if (isset($_ENV["DEBUG"]) && $_ENV["DEBUG"] == 1) {
                $settings[CURLOPT_SSL_VERIFYPEER] = false;
                $settings[CURLOPT_SSL_VERIFYSTATUS] = false;
                $settings[CURLOPT_SSL_VERIFYHOST] = 0;
            }

            if (isset($body)) {
                $settings[CURLOPT_POSTFIELDS] = $body;
            }

            if ($method == Method::POST) {
                $settings[CURLOPT_POST] = true;
            }

            if ($method == Method::PATCH) {
                $settings[CURLOPT_CUSTOMREQUEST] = 'PATCH';
            }

            curl_setopt_array($curl, $settings);

            $result = curl_exec($curl);

            if (!isset($result)) {
                return null;
            }

            $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);

            if ($status >= 200 && $status < 300) {
                $data = @json_decode($result, true);

                if (!isset($data)) {
                    Leiding::sendErrorMail("Ongeldig antwoord", "Volgend antwoord ontvangen van " . $method . ' ' . $url, json_encode($data) . "\n\nResponse: " . $result);
                }
                return $data;
            } else {

                Leiding::sendErrorMail("Ongeldig antwoord", "Volgend antwoord ontvangen van " . $method . ' ' . $url, json_encode($data) . "\n\nResponse: " . $result);
                // Er ging iets mis
            }
            return null;
        } catch (Exception $e) {
            return null;
        }
    }
}
