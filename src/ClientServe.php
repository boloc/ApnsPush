<?php

declare(strict_types=1);

namespace Tigzzr\ApnsPush;

use GuzzleHttp\Client;

/**
 * 以Guzzle形式请求APNS服务
 * @package \GuzzleHttp\Client;
 */
class ClientServe
{
    /**
     * 证书
     * 在此服务中，证书只能是string
     * @var string
     */
    public string $certificateFile;

    /**
     * 证书密码
     * 在此服务中，证书密码只能是string
     * @var string
     */
    public string $certPassword;

    /**
     * payload
     * 为json_decode后的body体
     * @var string
     */
    public string $payload;

    public function setDeviceToken(string $deviceToken)
    {
        $this->deviceToken = $deviceToken;
        return $this;
    }

    public function setCertificateFile($certificateFile, $certPassword = NULL)
    {
        $this->certificateFile = $certificateFile;
        $this->certPassword = $certPassword;
        return $this;
    }

    public function setOptions(array $options)
    {
        $this->options = $options;
        return $this;
    }

    public function setHeader(array $headers)
    {
        $data = [];
        foreach ($headers as $v) {
            [$key, $value] = explode(':', $v);
            $data[$key] = $value;
        }
        $this->headers = $data;
        return $this;
    }

    public function setPayload(array $payload)
    {
        $this->payload = json_encode($payload);
        return $this;
    }

    /**
     * 推送沙盒
     */
    public function pushSandbox()
    {
        $url = 'https://api.sandbox.push.apple.com/3/device/' . $this->deviceToken;
        return $this->_build($url);
    }

    /**
     * 推送生产
     */
    public function pushProd()
    {
        $url = 'https://api.push.apple.com/3/device/' . $this->deviceToken;
        return $this->_build($url);
    }

    private function _build($url)
    {
        $client = new Client();
        $response = $client->post($url, [
            'headers' => $this->headers,
            'cert'    => $this->certificateFile,
            'curl'    => [
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2_0,
                CURLOPT_SSLKEYTYPE => 'PEM',
                CURLOPT_SSLCERTPASSWD => $this->certPassword
            ],
            'body' => $this->payload,
        ]);
        return $response;
    }
}
