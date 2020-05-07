<?php

namespace App\Services\Kakao;

use GuzzleHttp\Client;
use PHPHtmlParser\Dom;

class SendLink
{
    private $client, $dom;
    public $cookies, $payload;

    /**
     * SendLink constructor.
     * @param Authentication $continue
     */
    public function __construct($continue)
    {
        $this->payload = $continue->payload;
        $this->cookies = $continue->cookies;
        $this->client = new Client([
            'headers' => [
                'User-Agent' => config('kakao.client.user-agent')
            ],
            'cookies' => $this->cookies
        ]);
        $this->dom = new Dom();
    }

    /**
     * Send Templated KakaoLink Sharer
     * @param $roomTitle
     * @param $data
     * @param $type
     * @return int StatusCode
     */
    public function sendTemplate($roomTitle, $data, $type)
    {
        $this->setSharer($data, $type);
        $this->setRooms();

        $roomId = $this->getRoom($roomTitle);

        $response = $this->client->request('POST', config('kakao.endpoint.sharer') . 'api/talk/message/link', [
            'headers' => [
                'Referer' => config('kakao.endpoint.sharer') . 'talk/friends/picker/link',
                'Csrf-Token' => $this->payload['csrf'],
                'App-Key' => config('kakao.auth.key'),
            ],
            'json' => [
                'receiverChatRoomMemberCount' => [1],
                'receiverIds' => [$roomId],
                'receiverType' => 'chat',
                'securityKey' => $this->payload['rooms']->securityKey,
                'validatedTalkLink' => $this->payload['parsedTemplate']
            ]
        ]);

        return $response->getStatusCode();
    }

    /**
     * Get session information from Kakao
     * @param $data
     * @param $type
     */
    protected function setSharer($data, $type)
    {
        $response = $this->client->request('POST', config('kakao.endpoint.sharer') . 'talk/friends/picker/link', [
            'headers' => [
                'Referer' => $this->payload['loginReferer'][0]
            ],
            'form_params' => [
                'app_key' => config('kakao.auth.key'),
                'validation_action' => $type,
                'validation_params' => json_encode($data, JSON_UNESCAPED_UNICODE),
                'ka' => config('kakao.client.kakao-agent'),
                'lcba' => '',
            ]
        ]);

        $html = $response->getBody()->getContents();
        $this->dom->loadStr($html);

        $this->payload['parsedTemplate'] = json_decode(htmlspecialchars_decode(explode('" id="validatedTalkLink" />', trim(strip_tags(explode('<input type="hidden" value="', $html)[1])))[0]));
        $this->payload['csrf'] = explode("'", $this->dom->find('div[ng-init]')->tag->getAttribute('ng-init')['value'])[1];
    }

    /**
     * Retrieve chatroom list from Sharer UI
     */
    protected function setRooms()
    {
        $response = $this->client->request('GET', config('kakao.endpoint.sharer') . 'api/talk/chats', [
            'headers' => [
                'Referer' => config('kakao.endpoint.sharer') . 'talk/friends/picker/link',
                'Csrf-Token' => $this->payload['csrf'],
                'App-Key' => config('kakao.auth.key')
            ],
        ]);

        $this->payload['rooms'] = json_decode($response->getBody()->getContents());
    }

    /**
     * Check RoomID exists in chatroom list
     * @param $roomTitle
     * @return int RoomId
     */
    protected function getRoom($roomTitle)
    {
        foreach ($this->payload['rooms']->chats as $room) {
            if ($roomTitle === $room->title) {
                return $room->id;
            }
        }
    }
}
