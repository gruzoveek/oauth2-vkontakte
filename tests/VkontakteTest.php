<?php

namespace J4k\OAuth2\Client\Test\Provider;

use GuzzleHttp\Psr7\Response;
use J4k\OAuth2\Client\Provider\Vkontakte as Provider;
use JetBrains\PhpStorm\Pure;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use Mockery as m;


class VkontakteTest extends \PHPUnit_Framework_TestCase
{
    protected Provider $provider;
    protected array $defaultScopes = ['email', 'friends', 'offline'];

    protected function setUp()
    {
        $this->provider = new Provider([
            'clientId' => 'mock_client_id',
            'clientSecret' => 'mock_secret',
            'redirectUri' => 'none',
        ]);
    }
    protected function tearDown(): void
    {
        m::close();
        parent::tearDown();
    }

    #[Pure]
    protected function getMockProvider(array $options = []): Provider
    {
        return new Provider(array_merge([
            'urlAuthorize'            => 'http://example.com/authorize',
            'urlAccessToken'          => 'http://example.com/token',
            'urlResourceOwnerDetails' => 'http://example.com/user',
        ], $options));
    }

    protected function getMockAccessTokenObject(): AccessToken
    {
        return new AccessToken([
            'access_token'      => 'mock_access_token',
            'resource_owner_id' => 1,
            'refresh_token'     => 'mock_refresh_token',
            'expires'           => 0
        ]);
    }

    protected function getMockAccessToken(): string
    {
        return json_encode([
            'access_token' => 'mock_access_token',
            'expires' => 0,
            'refresh_token' => 'mock_refresh_token',
            'uid' => 42,
            'email' => 'mock_user@example.com',
        ], JSON_THROW_ON_ERROR);
    }

    protected function getMockOwner(): string
    {
        return json_encode([
            'response' => [
                [
                    'uid' => 12345,
                    'bdate' => '12.07.1980',
                    'city' => [
                        'id' => 42,
                        'title' => 'mock_city_title',
                    ],
                    'country' => [
                        'id' => 421,
                        'title' => 'UK',
                    ],
                    'domain' => 'id12345',
                    'first_name' => 'mock_first_name',
                    'friend_status' => 3,
                    'has_photo' => 1,
                    'home_town' => 'mock_home_town',
                    'is_friend' => 1,
                    'last_name' => 'mock_last_name',
                    'maiden_name' => 'mock_maiden_name',
                    'nickname' => 'mock_nickname',
                    'photo_max' => 'http::/example.com/mock/image/url.jpg?with=parameters&and=square',
                    'photo_max_orig' => 'http::/example.com/mock/image/url.jpg?with=parameters&and=max',
                    'screen_name' => 'mock_screen_name',
                    'sex' => 2,
                ],
            ],
        ], JSON_THROW_ON_ERROR);
    }

    protected function getMockUsers(): string
    {
        return json_encode([
            'response' => [
                [
                    'uid' => 12345,
                    'bdate' => '12.07.1980',
                    'city' => [
                        'id' => 42,
                        'title' => 'mock_city_title',
                    ],
                    'country' => [
                        'id' => 421,
                        'title' => 'UK',
                    ],
                    'domain' => 'id12345',
                    'first_name' => 'mock_first_name',
                    'friend_status' => 3,
                    'has_photo' => 1,
                    'home_town' => 'mock_home_town',
                    'is_friend' => 1,
                    'last_name' => 'mock_last_name',
                    'maiden_name' => 'mock_maiden_name',
                    'nickname' => 'mock_nickname',
                    'photo_max' => 'http::/example.com/mock/image/url.jpg?with=parameters&and=square',
                    'photo_max_orig' => 'http::/example.com/mock/image/url.jpg?with=parameters&and=max',
                    'screen_name' => 'mock_screen_name',
                    'sex' => 2,
                ],
                [
                    'uid' => 23456,
                    'bdate' => '12.07.1988',
                    'city' => [
                        'id' => 422,
                        'title' => 'mock_city_title_2',
                    ],
                    'country' => [
                        'id' => 4212,
                        'title' => 'UK',
                    ],
                    'domain' => 'id23456',
                    'first_name' => 'mock_first_name_2',
                    'friend_status' => 0,
                    'has_photo' => 1,
                    'home_town' => 'mock_home_town_2',
                    'is_friend' => 0,
                    'last_name' => 'mock_last_name_2',
                    'maiden_name' => 'mock_maiden_name_2',
                    'nickname' => 'mock_nickname_2',
                    'photo_max' => 'http::/example.com/mock/image/url2.jpg?with=parameters&and=square',
                    'photo_max_orig' => 'http::/example.com/mock/image/url2.jpg?with=parameters&and=max',
                    'screen_name' => 'mock_screen_name_2',
                    'sex' => 1,
                ],
            ],
        ], JSON_THROW_ON_ERROR);
    }

    protected function getMockErrorFlat(): string
    {
        return json_encode([
            'error' => 'mock_error_message',
            'error_description' => 'mock_error_description',
        ], JSON_THROW_ON_ERROR);
    }

    protected function getMockErrorTree(): string
    {
        return json_encode([
            'error' => [
                'error_code' => 123,
                'error_msg' => 'mock_error_message',
            ],
        ], JSON_THROW_ON_ERROR);
    }

    public function testAuthorizationUrl(): void
    {
        $url = $this->provider->getAuthorizationUrl();
        $uri = parse_url($url);
        parse_str($uri['query'], $query);

        static::assertArrayHasKey('client_id', $query);
        static::assertArrayHasKey('redirect_uri', $query);
        static::assertArrayHasKey('state', $query);
        static::assertArrayHasKey('scope', $query);
        static::assertArrayHasKey('response_type', $query);
        static::assertArrayHasKey('approval_prompt', $query);
        static::assertNotNull($this->provider->getState());
    }

    public function testUrlAccessToken(): void
    {
        $url = $this->provider->getBaseAccessTokenUrl([]);
        $uri = parse_url($url);

        static::assertEquals('/access_token', $uri['path']);
    }

    public function testResourceOwnerDetailsUrlNotContainLanguage(): void
    {
        $url = $this->provider->getResourceOwnerDetailsUrl($this->getMockAccessTokenObject());
        $uri = parse_url($url);
        parse_str($uri['query'], $params);

        static::assertArrayNotHasKey('lang', $params);
    }

    public function testResourceOwnerDetailsUrlLanguage(): void
    {
        $this->provider->setLanguage('en');
        $url = $this->provider->getResourceOwnerDetailsUrl($this->getMockAccessTokenObject());
        $uri = parse_url($url);
        parse_str($uri['query'], $params);

        static::assertArrayHasKey('lang', $params);
        static::assertEquals('en', $params['lang']);
    }
    public function testScopes()
    {
        static::assertEquals($this->defaultScopes, $this->provider->scopes);
    }

    public function testCheckResponseSuccess(): void
    {
        $response = m::mock(Response::class);
        $response->shouldReceive('getBody')->andReturn($this->getMockOwner());
        $response->shouldReceive('getHeader')->andReturn(['Content-Type' => 'application/json; encoding=utf-8']);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $response->shouldReceive('getReasonPhrase')->andReturn('OK');

        $provider      = $this->getMockProvider();
        $reflection    = new \ReflectionClass(get_class($provider));
        $checkResponse = $reflection->getMethod('checkResponse');
        $checkResponse->setAccessible(true);

        static::assertNull($checkResponse->invokeArgs($provider, [$response, []]));
    }

    public function testCheckResponseErrorFlat(): void
    {
        $response      = m::mock(Response::class);
        $response->shouldReceive('getHeader')->andReturn(['Content-Type' => 'application/json; encoding=utf-8']);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $response->shouldReceive('getReasonPhrase')->andReturn('OK');

        $provider      = $this->getMockProvider();
        $reflection    = new \ReflectionClass(get_class($provider));
        $checkResponse = $reflection->getMethod('checkResponse');
        $checkResponse->setAccessible(true);

        $response->shouldReceive('getBody')->andReturn($this->getMockErrorFlat());
        $this->setExpectedException(IdentityProviderException::class, 'mock_error_description');
        $checkResponse->invokeArgs($provider, [$response, json_decode($this->getMockErrorFlat(), true, 512, JSON_THROW_ON_ERROR)]);
    }

    public function testCheckResponseErrorTree(): void
    {
        $response      = m::mock(Response::class);
        $response->shouldReceive('getHeader')->andReturn(['Content-Type' => 'application/json; encoding=utf-8']);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $response->shouldReceive('getReasonPhrase')->andReturn('OK');

        $provider      = $this->getMockProvider();
        $reflection    = new \ReflectionClass(get_class($provider));
        $checkResponse = $reflection->getMethod('checkResponse');
        $checkResponse->setAccessible(true);

        $response->shouldReceive('getBody')->andReturn($this->getMockErrorTree());
        $this->setExpectedException(IdentityProviderException::class, 'mock_error_message', 123);
        $checkResponse->invokeArgs($provider, [$response, json_decode($this->getMockErrorTree(), true, 512, JSON_THROW_ON_ERROR)]);
    }
}
