<?php
namespace App\Http\Traits;

use App\Models\MobileNotification;
use Aws\Exception\AwsException;
use Aws\Pinpoint\PinpointClient;
use Aws\Sns\SnsClient;

trait AmazonSmsApi {
    //Generic function will be written for snsclient
    public function createSubscription($token,$userId="")
    {
        $SnSclient = new SnsClient([
            'version' => 'latest',
            'region'  => 'us-west-2',
            'credentials' => [
                'key'    => env("AWS_KEY"),
                'secret' => env("AWS_SECRET"),
            ]
        ]);
        try {
            $result = $SnSclient->createPlatformEndpoint([
                'Attributes'=>["UserId" => $userId],
                'CustomUserData' => "",
                'Token' => $token,
                'PlatformApplicationArn' => env("AwsPlatformApplicationArn"),
            ]);
            return $result["EndpointArn"];
        } catch (AwsException $e) {
            // output error message if fails
        }
    }

    public function mobilePushNotification($title,$message,$userId)
    {
        $mobileNotificationLists = MobileNotification::query()->where('user_id','=',$userId)->get();
        foreach ($mobileNotificationLists as $mobileNotificationList) {
            $SnSclient = new SnsClient([
                'version' => 'latest',
                'region' => 'us-west-2',
                'credentials' => [
                    'key' => env("AWS_KEY"),
                    'secret' => env("AWS_SECRET"),
                ]
            ]);
            try {
                $result = $SnSclient->publish([
                    "Message" => '{
"GCM":"{ \"notification\": { \"body\": \"' . $message . '\", \"title\"😕"' . $title . '\" } }"
}',
                    'MessageStructure' => "json",
                    'TargetArn' => $mobileNotificationList->endpoint_url,
                ]);
            } catch (AwsException $e) {

            }
        }
    }

    public function deleteSubscription($endpoint=null)
    {
        $SnSclient = new SnsClient([
            'version' => 'latest',
            'region'  => 'us-west-2',
            'credentials' => [
                'key'    => env("AWS_KEY"),
                'secret' => env("AWS_SECRET"),
            ]
        ]);
        try {
            $result = $SnSclient->deleteEndpoint([
                'EndpointArn' => $endpoint
            ]);
            return "User Deleted !";
        } catch (AwsException $e) {
            // output error message if fails
        }
    }

    public function getSubscription($endpoint=null)
    {
        $SnSclient = new SnsClient([
            'version' => 'latest',
            'region'  => 'us-west-2',
            'credentials' => [
                'key'    => env("AWS_KEY"),
                'secret' => env("AWS_SECRET"),
            ]
        ]);
        try {
            $result = $SnSclient->getEndpointAttributes([
                'EndpointArn' => env("EndpointArn")
            ]);
            $userId = $result["Attributes"]["UserId"];
            return $userId;
        } catch (AwsException $e) {
            return $e;
        }
    }

    public function smsSendAws($userId,$toPhone,$toBody,$ipAddress)
    {
        $client = new PinpointClient([
            'version' => 'latest',
            'region'  => 'eu-west-1',
            'credentials' => [
                'key'    => env("AWS_KEY"),
                'secret' => env("AWS_SECRET"),
            ]
        ]);
        try {
            $result = $client->sendMessages([
                'ApplicationId' => env('AMAZON_PINT_SMS_TOKEN'),
                'MessageRequest' => [
                    'Addresses' => [
                        "$toPhone" => [
                            "ChannelType" => 'SMS'
                        ]
                    ],
                    'MessageConfiguration' => [
                        'SMSMessage' => [
                            'Body' => $toBody,
                            "MessageType" => 'TRANSACTIONAL',
                            "Keyword" => env('AMAZON_PINT_KEYWORD'),
                            "OriginationNumber" => env("OriganationNumber"),
                            "senderId" => env("senderId")
                        ],
                    ]
                ]
            ]);
            $this->newLog($userId,$toBody,'smsSuccess',$ipAddress);
        }
        catch (\Exception $exception)
        {
            $this->newLog($userId,$exception->getMessage(),'smsFailed',$ipAddress);
        }
    }
}