<?php

namespace Swaply;


use Firebase\JWT\JWT;

class MobilumOtcClient {

    private $version = '1.0';
    private $apiUrl;

    protected $apiKey;
    protected $apiSecret;
    protected $issuer;

    public function __construct($apiKey, $apiSecret, $testServer = false, $issuerName = "Mobilum PHP client") {
        $this->apiUrl = $testServer ? 'https://oxygen.inpay.io' : 'https://oxygen.inpay.io';

        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
        $this->issuer = $issuerName;
    }

    private function call($httpMethod, $uri, $data = []){
        $headers = $this->createHeaders($httpMethod, $uri, $data);

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_URL, $this->apiUrl . $uri);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        if ($httpMethod === 'post'){
            curl_setopt($curl, CURLOPT_POST, true);
        } else if ($httpMethod === 'put') {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
        }

        if($httpMethod !== 'get'){
            $data = $this->createJwtToken($data);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }

        $response = curl_exec($curl);

        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if($httpCode >= 500) {
            $decoded = json_decode($response);
            $errorMsg = isset($decoded->reason) ? $decoded->reason : curl_error($curl);

            throw new \Exception($errorMsg);
        }

        if($response === false) {
            return  curl_error($curl);
        }

        return $response;
    }

    private function createHeaders(){
        $headers = [
            "Accept: application/json",
            "Content-Type: application/json",
            "Api-Key: $this->apiKey",
        ];

        return $headers;
    }

    protected function nonce() {
        $nonce = explode(' ', microtime());
        $nonce = $nonce[1] . substr($nonce[0], 2);
        return $nonce;
    }

    private function createJwtToken($payload) {
        $jwtData = [
            "iss" => $this->issuer,
            "iat" => time(),
            "nonce" => $this->nonce(),
            "payload" => $payload
        ];
        $jwtToken = JWT::encode($jwtData, $this->apiSecret, 'HS256');
        return $jwtToken;
    }

    private function addOptionalParameter(array $data, string $name, $value = null){
        if(!empty($value)){
            $data[$name] = $value;
        }

        return $data;
    }

    public function setApiUrl($apiUrl) {
        $this->apiUrl = $apiUrl;
    }

    public function createAsk($pair, $amount) {
      $data = [
        'pair' => $pair,
        'amount' => $amount
      ];

      $uri = "/$this->version/offers/ask";

        return $this->call('post', $uri, $data);
    }

    public function createBid($pair, $amount) {
      $data = [
        'pair' => $pair,
        'amount' => $amount
      ];

      $uri = "/$this->version/offers/bid";

        return $this->call('post', $uri, $data);
    }

    public function acceptOffer($offerCode) {

      $uri = "/$this->version/offers/$offerCode/accept";

        return $this->call('put', $uri);
    }

    public function getOffers() {
        $uri = "/$this->version/offers/get";

        return $this->call('post', $uri);
    }

    public function getOffer($offerCode) {
        $uri = "/$this->version/offers/$offerCode/get";

        return $this->call('post', $uri);
    }

    public function cancelOffer($offerCode) {
        $uri = "/$this->version/offers/$offerCode";

        return $this->call('delete', $uri);
    }

    public function preTrade($type, $pair, $amount) {
        $data = [
          'type' => $type,
          'pair' => $pair,
          'amount' => $amount
        ];
  
        $uri = "/$this->version/offers/pretrade";
  
          return $this->call('post', $uri, $data);
      }

}