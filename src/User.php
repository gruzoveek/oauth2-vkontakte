<?php

namespace J4k\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;


/**
 * @see     https://vk.com/dev/fields
 *
 * @package J4k\OAuth2\Client\Provider
 */
class User implements ResourceOwnerInterface
{
    protected array $response;


    public function __construct(array $response)
    {
        $this->response = $response;
    }


    public function toArray(): array
    {
        return $this->response;
    }

    public function getId(): int
    {
        return (int) ($this->getField('uid') ?: $this->getField('id'));
    }

    protected function getField(string $key): mixed
    {
        return !empty($this->response[$key]) ? $this->response[$key] : null;
    }

    /** @return string|null DD.MM.YYYY */
    public function getBirthday(): ?string
    {
        return $this->getField('bdate');
    }

    /** @return array [id =>, title => string] */
    public function getCity(): array
    {
        return $this->getField('city');
    }

    /** @return array [id =>, title => string] */
    public function getCountry(): array
    {
        return $this->getField('country');
    }

    public function getDomain(): string
    {
        return $this->getField('domain');
    }

    public function getFirstName(): string
    {
        return $this->getField('first_name');
    }

    /** @return int 0|1|2|3 => nobody|resquest_sent|incoming_request|friends */
    public function getFriendStatus(): int
    {
        return $this->getField('friend_Status');
    }

    public function isHasPhoto(): bool
    {
        return (bool)$this->getField('has_photo');
    }

    public function getHomeTown(): string
    {
        return $this->getField('home_town');
    }

    public function isFriend(): bool
    {
        return (bool)$this->getField('is_friend');
    }

    public function getLastName(): string
    {
        return $this->getField('last_name');
    }

    public function getMaidenName(): string
    {
        return $this->getField('maiden_name');
    }

    public function getNickname(): string
    {
        return $this->getField('nickname');
    }

    public function getPhotoMax(): string
    {
        return $this->getField('photo_max');
    }

    public function getPhotoMaxOrig(): string
    {
        return $this->getField('photo_max_orig');
    }

    public function getScreenName(): string
    {
        return $this->getField('screen_name');
    }
    /** @return int 1|2 => woman|man */
    public function getSex(): int
    {
        return $this->getField('sex');
    }

    public function getEmail(): string
    {
        return $this->getField('email');
    }
}