<?php

namespace App\Services\Kakao;

use Blocktrail\CryptoJSAES\CryptoJSAES;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\RedirectMiddleware;
use Illuminate\Support\Facades\Cache;
use PHPHtmlParser\Dom;

class Authentication
{
    private $client, $dom;
    public $cookies, $payload;

    /**
     * Authentication constructor.
     */
    public function __construct()
    {
        $this->cookies = new CookieJar();
        $this->client = new Client([
            'headers' => [
                'User-Agent' => config('kakao.client.user-agent')
            ],
            'cookies' => $this->cookies,
            'allow_redirects' => [
                'track_redirects' => true
            ],
        ]);
        $this->dom = new Dom();

        $this->setCryptoKey();
        $this->setTIARA();
    }

    /**
     * Get session information from Kakao
     *
     * @return object Cookies and payloads
     */
    public function authenticate()
    {
        if (Cache::has('KAKAO_AUTH')) return Cache::get('KAKAO_AUTH');

        $this->client->request('POST', config('kakao.endpoints.account') . 'weblogin/authenticate.json', [
            'headers' => ['Referer' => $this->payload['loginReferer'][0]],
            'json' => [
                'os' => 'web',
                'webview_v' => '2',
                'email' => CryptoJSAES::encrypt(config('kakao.auth.email'), $this->payload['cryptoKey']),
                'password' => CryptoJSAES::encrypt(config('kakao.auth.password'), $this->payload['cryptoKey']),
                'continue' => explode('continue=', $this->payload['loginReferer'][0])[1],
                'third' => 'false',
                'k' => 'true'
            ],
        ]);

        Cache::put('KAKAO_AUTH', (object) [
            'cookies' => $this->cookies,
            'payload' => $this->payload,
        ], 3600);

        return (object) [
            'cookies' => $this->cookies,
            'payload' => $this->payload,
        ];
    }

    /**
     * Set Encryption Key retrieved from Kakao sharer
     */
    private function setCryptoKey()
    {
        $response = $this->client->request('POST', config('kakao.endpoint.sharer') . 'talk/friends/picker/link', [
            'form_params' => [
                'app_key' => config('kakao.auth.key'),
                'validation_action' => 'default',
                'validation_params' => '{}',
                'ka' => config('kakao.client.kakao-agent'),
                'lcba' => ''
            ],
        ]);

        $this->dom->loadStr($response->getBody()->getContents());
        $this->payload['cryptoKey'] = $this->dom->find('input[name=p]')->value;
        $this->payload['loginReferer'] = $response->getHeader(RedirectMiddleware::HISTORY_HEADER);
    }

    /**
     * Imitate Kakao tiara footstep
     */
    private function setTIARA()
    {
        $this->client->request('GET', config('kakao.endpoint.tiara') . 'queen/footsteps');
    }
}
