# iOS APNS推送服务PHP端口
2022年新版本
[文档地址](https://developer.apple.com/documentation/usernotifications/setting_up_a_remote_notification_server/sending_notification_requests_to_apns)

目前仅采用`GuzzleHttp\Client`实现

调用方法
~~~php
$deviceToken = '设备deviceToken'; //设备token,ios端生成，如果选择voip模式必须为voip的deviceToken
$apnsTopic = '{开发者自己的bundleId}.voip'; // voip模式要求最后带上.voip
$certificateFile = __DIR__ . '/cert/voip.pem'; // 证书路径
$pemPassword = '123456a'; // 证书密码
$client = new ApnsServer($apnsTopic, $certificateFile, $pemPassword);
$response = $client
    ->setPushType('voip') // 推送类型
    ->addTitle('这是标题')
    ->addBody('这是文本')
    ->push($deviceToken, ApnsServer::ENVIRONMENT_SANDBOX);
~~~
