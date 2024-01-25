# Vkontakte OAuth2 client provider

This package is fork of [j4k/oauth2-vkontakte](https://github.com/j4k/oauth2-vkontakte) and provides [Vkontakte](https://vk.com) integration for [OAuth2 Client](https://github.com/thephpleague/oauth2-client) by the League. Requires php8.

## Installation

```sh
composer require gruzoveek/oauth2-vkontakte
```

## Configuration

```php
$provider = new J4k\OAuth2\Client\Provider\Vkontakte([
    'clientId'     => '1234567',
    'clientSecret' => 's0meRe4lLySEcRetC0De',
    'redirectUri'  => 'https://example.org/oauth-endpoint',
    'scopes'       => ['email', 'offline', 'friends'],
]);
```

## Authorization

```php
// Authorize if needed
if (PHP_SESSION_NONE === session_status()) session_start();
$isSessionActive = PHP_SESSION_ACTIVE === session_status();
$code            = !empty($_GET['code'])  ? $_GET['code']  : null;
$state           = !empty($_GET['state']) ? $_GET['state'] : null;
$sessionState    = 'oauth2state';

// No code – get some
if (!$code) {
    $authUrl = $provider->getAuthorizationUrl();
    if ($isSessionActive) $_SESSION[$sessionState] = $provider->getState();
    // Redirect user to VK
    header("Location: $authUrl");
    die();
}

// Anti-CSRF
elseif ($isSessionActive && (empty($state) || ($state !== $_SESSION[$sessionState]))) {
    unset($_SESSION[$sessionState]);
    throw new \RuntimeException('Invalid state');
}

// Exchange code to access_token
else {
    try {
        $providerAccessToken = $provider->getAccessToken('authorization_code', ['code' => $code]);
        // Yay, got it!
        var_dump([
            'access_token'  => $providerAccessToken->getToken(),
            'expires'       => $providerAccessToken->getExpires(),
            'user_id'       => $providerAccessToken->getValues()['user_id'],
            'email'         => $providerAccessToken->getValues()['email'], // Only for "email" scope
        ]);
    }
    catch (IdentityProviderException $e) {
        // Log error
        error_log($e->getMessage());
    }
}
```

## Helper methods

### Public
```php
$provider->usersGet([1234, 56789]); // => \J4k\OAuth2\Client\Provider\User[]
$provider->friendsGet(23456);        // => \J4k\OAuth2\Client\Provider\User[]
```

### With additional data
```php
$providerAccessToken = new \League\OAuth2\Client\Token\AccessToken(['access_token' => 'iAmAccessTokenString']);
$provider->usersGet([1234, 56789], $providerAccessToken); // => \J4k\OAuth2\Client\Provider\User[]
$provider->friendsGet(23456, $providerAccessToken);        // => \J4k\OAuth2\Client\Provider\User[]
```

## Contributions

Contributions are very welcome. Please submit a PR
