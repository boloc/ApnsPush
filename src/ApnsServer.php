<?php

declare(strict_types=1);

namespace Tigzzr\ApnsPush;

use Exception;

/**
 * IOS APNS推送相关业务
 * @see https://developer.apple.com/documentation/usernotifications/setting_up_a_remote_notification_server/sending_notification_requests_to_apns
 *
 * 流程步骤：
 * openssl x509 -in  推送证书名.cer -inform der -out  自定义的证书文件标识名.pem
 * openssl pkcs12 -nocerts -in 专用密钥文件名.p12 -out 自定义的证书密钥标识名.pem
 * cat 自定义的证书文件标识名.pem   自定义的证书密钥标识名.pem> sum.pem
 */
class ApnsServer
{
    // 生产
    const ENVIRONMENT_PRODUCTION = 1;
    // 沙盒
    const ENVIRONMENT_SANDBOX = 2;

    /**
     * @see \apns\ClientServe
     *
     * @var object
     */
    protected $client;

    private string $_bundleId;
    private string $_pushType = 'voip';
    private $_certificateFile = NULL;
    private $_certPassword    = NULL;

    /**
     * 构造函数
     * @param string $bundleId 苹果bundle-id
     * @param mixed $certificateFile PEM证书文件路径
     * @param mixed $certPassword 证书密码
     */
    public function __construct(string $bundleId, $certificateFile, $certPassword = null)
    {
        if (is_array($certificateFile)) {
            foreach ($certificateFile as $file) {
                if (!is_readable($file)) throw new Exception("Unable to read certificate file '{$certificateFile}'");
            }
        } else {
            if (!is_readable($certificateFile)) {
                throw new Exception("Unable to read certificate file '{$certificateFile}'");
            }
        }

        $this->_certificateFile = $certificateFile;
        $this->_certPassword    = $certPassword;
        $this->_bundleId        = $bundleId;
        $this->client           = (new ClientServe());
    }

    /**
     * 改变连接方式
     */
    public function setClient($client)
    {
        $this->client = (new $client());
        return $this;
    }

    /**
     * 设置推送类型
     *
     * @param string $type
     * @see https://developer.apple.com/documentation/usernotifications/setting_up_a_remote_notification_server/sending_notification_requests_to_apns
     *
     * @return self
     */
    public function setPushType(string $type)
    {
        $allowedTypes = [
            'alert', 'background', 'location', 'voip', 'complication', 'fileprovider', 'mdm'
        ];
        if (!in_array($type, $allowedTypes)) throw new Exception("Unknown push types '{$type}'");
        $this->_pushType = $type;
        return $this;
    }


    /**
     * 添加标题
     *
     * @param string $title
     * @return self
     */
    public function addTitle(string $title)
    {
        $this->_title = $title;
        return $this;
    }

    /**
     * 添加副标题
     *
     * @param string $subTitle
     * @return self
     */
    public function addSubTitle(string $subTitle)
    {
        $this->_subTitle = $subTitle;
        return $this;
    }

    /**
     * 添加消息
     *
     * @param string $body
     * @return self
     */
    public function addBody(string $body)
    {
        $this->_body = $body;
        return $this;
    }

    /**
     * 添加自定义内容
     *
     * @param array $options
     * @return self
     */
    public function addCustom(array $options)
    {
        $this->_custom = $options;
        return $this;
    }

    /**
     * apns消息推送
     * @param string $deviceToken 设备Token
     * @param int $environment 环境选择
     */
    public function push(string $deviceToken, int $environment = self::ENVIRONMENT_SANDBOX)
    {
        // 发送环境监测
        if ($environment != self::ENVIRONMENT_PRODUCTION && $environment != self::ENVIRONMENT_SANDBOX) {
            throw new Exception("Invalid environment '{$environment}'");
        }

        // header内容组装
        $headers = $this->_buildHeader();

        // json payload组装
        $payloadArray = $this->_buildPayLoad();

        // 额外options组装（预留，暂无用处，也没开通方法去设置）
        $options = [];

        try {
            // 指定执行方式
            $serve = $this->client
                ->setCertificateFile($this->_certificateFile, $this->_certPassword)
                ->setDeviceToken($deviceToken)
                ->setHeader($headers)
                ->setPayload($payloadArray)
                ->setOptions($options);
            $response = ($environment === self::ENVIRONMENT_SANDBOX) ? $serve->pushSandbox() : $serve->pushProd();
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }
        return $response;
    }

    /**
     * Create the JSON payload
     * @see https://developer.apple.com/documentation/usernotifications/setting_up_a_remote_notification_server/sending_notification_requests_to_apns
     */
    private function _buildHeader(): array
    {
        $headers = [
            "apns-topic:{$this->_bundleId}",
            "apns-push-type:{$this->_pushType}",
            "Content-Type:application/x-www-form-urlencoded",
        ];

        return $headers;
    }

    /**
     * Create the JSON payload
     * @see https://developer.apple.com/documentation/usernotifications/setting_up_a_remote_notification_server/generating_a_remote_notification#2943365
     */
    private function _buildPayLoad(): array
    {
        // 默认的消息
        $alert = [
            'title' => "Default Title",
            'body'  => "Default Body"
        ];

        $alertFields = ['title', 'subtitle', 'body', 'custom'];
        foreach ($alertFields as $field) {
            $attribute = "_$field";
            if (!empty($this->$attribute)) {
                $alert[$field] = $this->$attribute;
            }
        }

        return [
            'aps'   => [
                'alert' => $alert
            ],
            'sound' => 'default',
        ];
    }
}
