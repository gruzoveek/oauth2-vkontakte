<?php

namespace J4k\OAuth2\Client\Provider;

use InvalidArgumentException;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use Psr\Http\Message\ResponseInterface;


class Vkontakte extends AbstractProvider
{
    protected string $baseOAuthUri = 'https://oauth.vk.com';
    protected string $baseUri      = 'https://api.vk.com/method';
    protected string $version      = '5.199';
    protected ?string $language    = null;

    /** @see https://vk.com/dev/permissions */
    public array $scopes = [
        'email',
        //'friends',
        //'offline',
        //'photos',
        //'wall',
        //'ads',
        //'audio',
        //'docs',
        'groups',
        //'market',
        //'messages',
        //'nohttps',
        //'notes',
        //'notifications',
        //'notify',
        //'pages',
        //'stats',
        //'status',
        //'video',
    ];
    /** @see https://new.vk.com/dev/fields */
    public array $userFields = [
        'bdate',
        'city',
        'country',
        'domain',
        'first_name',
        'friend_status',
        'has_photo',
        'home_town',
        'id',
        'is_friend',
        'last_name',
        'maiden_name',
        'nickname',
        'photo_max',
        'photo_max_orig',
        'screen_name',
        'sex',
        //'about',
        //'activities',
        //'blacklisted',
        //'blacklisted_by_me',
        //'books',
        //'can_post',
        //'can_see_all_posts',
        //'can_see_audio',
        //'can_send_friend_request',
        //'can_write_private_message',
        //'career',
        //'common_count',
        //'connections',
        //'contacts',
        //'crop_photo',
        //'counters',
        //'deactivated',
        //'education',
        //'exports',
        //'followers_count',
        //'games',
        //'has_mobile',
        //'hidden',
        //'interests',
        //'is_favorite',
        //'is_hidden_from_feed',
        //'last_seen',
        //'military',
        //'movies',
        //'occupation',
        //'online',
        //'personal',
        //'photo_100',
        //'photo_200',
        //'photo_200_orig',
        //'photo_400_orig',
        //'photo_50',
        //'photo_id',
        //'quotes',
        //'relation',
        //'relatives',
        //'schools',
        //'site',
        //'status',
        //'timezone',
        //'tv',
        //'universities',
        //'verified',
        //'wall_comments',
    ];

    public function setLanguage(string $language): static
    {
        $this->language = $language;

        return $this;
    }

    public function getBaseAuthorizationUrl(): string
    {
        return $this->baseOAuthUri . '/authorize';
    }
    public function getBaseAccessTokenUrl(array $params): string
    {
        return $this->baseOAuthUri . '/access_token';
    }
    public function getResourceOwnerDetailsUrl(AccessToken $token): string
    {
        $params = [
            'fields'       => $this->userFields,
            'access_token' => $token->getToken(),
            'v'            => $this->version,
            'lang'         => $this->language
        ];
        $query  = $this->buildQueryString($params);

        return $this->baseUri . '/users.get?' . $query;
    }

    protected function getDefaultScopes(): array
    {
        return $this->scopes;
    }

    protected function checkResponse(ResponseInterface $response, $data): void
    {
        // Metadata info
        $contentTypeRaw = $response->getHeader('Content-Type');
        $contentTypeArray = explode(';', reset($contentTypeRaw));
        $contentType = reset($contentTypeArray);
        // Response info
        $responseCode    = $response->getStatusCode();
        $responseMessage = $response->getReasonPhrase();
        // Data info
        $error            = !empty($data['error']) ? $data['error'] : null;
        $errorCode        = !empty($error['error_code']) ? $error['error_code'] : $responseCode;
        $errorDescription = !empty($data['error_description']) ? $data['error_description'] : null;
        $errorMessage     = !empty($error['error_msg']) ? $error['error_msg'] : $errorDescription;
        $message          = $errorMessage ?: $responseMessage;

        // Request/meta validation
        if (399 < $responseCode) {
            throw new IdentityProviderException($message, $responseCode, $data);
        }

        // Content validation
        if ('application/json' !== $contentType) {
            throw new IdentityProviderException($message, $responseCode, $data);
        }
        if ($error) {
            throw new IdentityProviderException($errorMessage, $errorCode, $data);
        }
    }

    protected function createResourceOwner(array $responseArray, AccessToken $token): User
    {
        $response   = reset($responseArray['response']);
        $additional = $token->getValues();
        if (!empty($additional['email'])) {
            $response['email'] = $additional['email'];
        }
        if (!empty($additional['user_id'])) {
            $response['id'] = $additional['user_id'];
        }

        return new User($response);
    }

    /** @see https://vk.com/dev/users.get */
    public function usersGet(array $ids = [], AccessToken $token = null, array $params = []): array
    {
        if (empty($ids) && !$token) {
            throw new InvalidArgumentException('Some of parameters usersIds OR access_token are required');
        }

        $default = [
            'user_ids'     => implode(',', $ids),
            'fields'       => $this->userFields,
            'access_token' => $token?->getToken(),
            'v'            => $this->version,
            'lang'         => $this->language
        ];
        $params  = array_merge($default, $params);
        $query   = $this->buildQueryString($params);
        $url     = "$this->baseUri/users.get?$query";

        $response   = $this->getResponse($this->createRequest(static::METHOD_GET, $url, $token, []))['response'];
        $users      = !empty($response['items']) ? $response['items'] : $response;
        $array2user = static function ($userData) {
            return new User($userData);
        };

        return array_map($array2user, $users);
    }

    /** @see https://vk.com/dev/friends.get */
    public function friendsGet($userId, AccessToken $token = null, array $params = []): array
    {
        $default = [
            'user_id'      => $userId,
            'fields'       => $this->userFields,
            'access_token' => $token?->getToken(),
            'v'            => $this->version,
            'lang'         => $this->language
        ];
        $params  = array_merge($default, $params);
        $query   = $this->buildQueryString($params);
        $url     = "$this->baseUri/friends.get?$query";

        $response     = $this->getResponse($this->createRequest(static::METHOD_GET, $url, $token, []))->getBody()->getContents();
        $friends      = json_decode($response, true)['response'];
        $array2friend = static function ($friendData) {
            if (is_numeric($friendData)) {
                $friendData = ['id' => $friendData];
            }

            return new User($friendData);
        };

        return array_map($array2friend, $friends);
    }

    /** @see https://dev.vk.com/ru/method/groups.getById */
    public function groupsGet($userId, AccessToken $token = null, array $params = []): array
    {
        $default = [
            'user_id'      => $userId,
            'fields'       => $this->userFields,
            'access_token' => $token?->getToken(),
            'v'            => $this->version,
            'lang'         => $this->language
        ];
        $params  = array_merge($default, $params);
        $query   = $this->buildQueryString($params);
        $url     = "$this->baseUri/groups.getById?$query";

        $response = $this->getResponse($this->createRequest(static::METHOD_GET, $url, $token, []))->getBody()->getContents();
        return json_decode($response, true)['response'];
    }
}
