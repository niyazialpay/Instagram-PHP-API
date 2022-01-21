<?php

namespace niyazialpay\Instagram;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class Instagram
{
    const GRAPH_URL = 'https://graph.instagram.com/';

    /**
     * The API OAuth URL
     */
    const API_OAUTH_URL = 'https://api.instagram.com/oauth/authorize';

    /**
     * The OAuth token URL
     */
    const API_OAUTH_TOKEN_URL = 'https://api.instagram.com/oauth/access_token';

    private static string $_apikey;

    /**
     * The Instagram OAuth API secret
     *
     * @var string
     */
    private static string $_apisecret;

    /**
     * The callback URL
     *
     * @var string
     */
    private static string $_callbackurl;

    /**
     * The user access token
     *
     * @var string
     */
    private static string $_accesstoken;

    /**
     * Available scopes
     *
     * @var array
     */
    private static array $_scopes = ['user_media', 'user_profile'];

    /**
     * Default constructor
     *
     * @param array|string $config Instagram configuration data
     * @return void
     * @throws Exception
     */
    public static function config(array|string $config) {
        if (true === is_array($config)) {
            // if you want to access user data
            self::setApiKey($config['apiKey']);
            self::setApiSecret($config['apiSecret']);
            self::setApiCallback($config['apiCallback']);
        } else if (true === is_string($config)) {
            // if you only want to access public data
            self::setApiKey($config);
        } else {
            throw new Exception("Error: __construct() - Configuration data is missing.");
        }
    }

    /**
     * Generates the OAuth login URL
     *
     * @param array [optional] $scope       Requesting additional permissions
     * @return string                       Instagram OAuth login URL
     * @throws Exception
     */
    public static function getLoginUrl($scope = ['user_profile']): string
    {
        if (is_array($scope) && count(array_intersect($scope, self::$_scopes)) === count($scope)) {
            return self::API_OAUTH_URL . '?client_id=' . self::getApiKey() . '&redirect_uri=' . urlencode(self::getApiCallback()) . '&scope=' . implode('+', $scope) . '&response_type=code';
        } else {
            throw new Exception("Error: getLoginUrl() - The parameter isn't an array or invalid scope permissions used.");
        }
    }

    /**
     * @throws GuzzleException
     */
    public static function getLongLivedToken($access_token){
        return self::makeAction('access_token', [
            'grant_type' => 'ig_exchange_token',
            'client_secret'   => self::getApiSecret(),
            'access_token' => $access_token
        ]);
    }

    /**
     * @throws GuzzleException
     */
    public static function RefreshToken($access_token){
        return self::makeAction('refresh_access_token', [
            'grant_type' => 'ig_refresh_token',
            'access_token' => $access_token
        ]);
    }

    /**
     * Access Token Setter
     *
     * @param object|string $data
     * @return void
     */
    public static function setAccessToken(object|string $data) {
        (true === is_object($data)) ? $token = $data->access_token : $token = $data;
        self::$_accesstoken = $token;
    }

    /**
     * Access Token Getter
     *
     * @return string
     */
    public static function getAccessToken(): string
    {
        return self::$_accesstoken;
    }

    /**
     * API-key Setter
     *
     * @param string $apiKey
     * @return void
     */
    public static function setApiKey(string $apiKey) {
        self::$_apikey = $apiKey;
    }

    /**
     * API Key Getter
     *
     * @return string
     */
    public static function getApiKey(): string
    {
        return self::$_apikey;
    }

    /**
     * API Secret Setter
     *
     * @param string $apiSecret
     * @return void
     */
    public static function setApiSecret(string $apiSecret) {
        self::$_apisecret = $apiSecret;
    }

    /**
     * API Secret Getter
     *
     * @return string
     */
    public static function getApiSecret(): string
    {
        return self::$_apisecret;
    }

    /**
     * API Callback URL Setter
     *
     * @param string $apiCallback
     * @return void
     */
    public static function setApiCallback(string $apiCallback) {
        self::$_callbackurl = $apiCallback;
    }

    /**
     * API Callback URL Getter
     *
     * @return string
     */
    public static function getApiCallback(): string
    {
        return self::$_callbackurl;
    }

    /**
     * @throws GuzzleException
     */
    public static function getUserMedia($id, $limit = 10): array
    {

        return self::userMedias($id, $limit);
    }

    /**
     * @throws GuzzleException
     */
    public static function getUserMediaFromHashtag($id, $hashtag, $limit = 10): array
    {
        return self::userMedias($id, $limit, $hashtag);
    }

    /**
     * @throws GuzzleException
     */
    private static function userMedias($id, $limit=20, $hashtag=null): array
    {
        $medias_response = self::makeAction($id, [
            'fields' => 'media',
            'access_token' => self::getAccessToken()
        ]);
        $i = 0;
        $posts_response = [];
        foreach ($medias_response->media->data as $media) {
            if ($i < $limit) {
                $insta_media = self::makeAction($media->id, [
                    'fields' => 'thumbnail_url,media_url,permalink,media_type,caption',
                    'access_token' => self::getAccessToken()
                ]);
                if($hashtag){
                    try{
                        if($insta_media?->caption){
                            if (str_contains($insta_media->caption, $hashtag)) {
                                $posts_response[] = $insta_media;
                            }
                        }
                    }
                    catch (Exception $e){}
                }
            }
            $i++;
        }
        return $posts_response;
    }

    /**
     * Get the OAuth data of a user by the returned callback code
     *
     * @param string $code OAuth2 code variable (after a successful login)
     * @param boolean [optional] $token     If it's true, only the access token will be returned
     * @return mixed
     * @throws Exception
     * @throws GuzzleException
     */
    public static function getOAuthToken(string $code, $token = false): mixed
    {
        $apiData = [
            'grant_type'      => 'authorization_code',
            'client_id'       => self::getApiKey(),
            'client_secret'   => self::getApiSecret(),
            'redirect_uri'    => self::getApiCallback(),
            'code'            => $code
        ];

        $result = self::OAuthAction(query: $apiData);
        return (false === $token) ? $result : $result->access_token;
    }

    /**
     * @throws GuzzleException
     */
    private static function OAuthAction($query=null){
        $client = new Client();
        $resp = $client->post(self::API_OAUTH_TOKEN_URL, [
            'headers' => [
                'Accept'     => 'application/json'
            ],
            'form_params' => $query
        ]);

        return json_decode($resp->getBody());
    }

    /**
     * @throws GuzzleException
     */
    private static function makeAction($uri, $query)
    {
        $client = new Client([
            'base_uri' => self::GRAPH_URL
        ]);

        $resp = $client->get($uri, [
            'query' => $query
        ]);

        return json_decode($resp->getBody());
    }
}
