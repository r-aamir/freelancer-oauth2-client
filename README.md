# OAuth 2.0 Client

This package makes it simple to integrate your application with freelancer OAuth 2.0 service providers.

## Requirements

The following versions of PHP are supported.

* PHP 5.5
* PHP 5.6
* PHP 7.0
* HHVM

## Usage

### Initialize Freelancer OAuth2 Provider

```php
try {
    $provider = new FreelancerIdentity([
        'clientId' => '<your-client-id>',
        'clientSecret' => '<your-client-secret>',
        'redirectUri' => '<your-client-redirect-uri>',
        'baseUri' => '<freelancer-service-provider-base-uri>'
    ]);
} catch (UnexpectedValueException $e) {
    // Failed to initialize freelancer identity provider
    exit($e->getMessage());
}
```

### Authorization Code Grant

The authorization code grant type is the most common grant type used when authenticating users with a third-party service. This grant type utilizes a client (this library), a server (the service provider), and a resource owner (the user with credentials to a protected—or owned—resource) to request access to resources owned by the user. This is often referred to as 3-legged OAuth, since there are three parties involved.

```php
// Check given error
if (isset($_GET['error'])) {
    exit($_GET['error']);
} elseif (!isset($_GET['code'])) {
    // If we don't have an authorization code then get one
    // Fetch the authorization URL from the provider; this returns the
    // urlAuthorize option and generates and applies any necessary parameters
    // (e.g. state).
    $authorizationUrl = $provider->getAuthorizationUrl();

    // Redirect the user to the authorization URL.
    header('Location: ' . $authorizationUrl);
    exit;
} else {
    try {
        // Try to get an access token using the authorization code grant.
        $accessToken = $provider->getAccessToken('authorization_code', [
            'code' => $_GET['code']
        ]);

        // We have an access token, which we may use in authenticated
        // requests against the service provider's API.
        echo $accessToken->getToken() . "<br>";
        echo $accessToken->getRefreshToken() . "<br>";
        echo $accessToken->getExpires() . "<br>";
        echo ($accessToken->hasExpired() ? 'expired' : 'not expired') . "<br>";

        // Using the access token, we may look up details about the
        // resource owner.
        $resourceOwner = $provider->getResourceOwner($accessToken);
        var_export($resourceOwner);

        // The provider provides a way to get an authenticated API request for
        // the service, using the access token
        $request = $provider->getAuthenticatedRequest(
            'GET',
            $provider->baseUri.'/api/v1/user/profile_image',
            $accessToken
        );
        $response = $provider->getResponse($request);
        var_export($response);
    } catch (IdentityProviderException $e) {
        // Failed to get the access token or user details.
        exit($e->getMessage());
    }
}
```

### Refreshing a Token

Once your application is authorized, you can refresh an expired token using a refresh token rather than going through the entire process of obtaining a brand new token. To do so, simply reuse this refresh token from your data store to request a refresh.

```php
$existingAccessToken = getAccessTokenFromYourDataStore();

if ($existingAccessToken->hasExpired()) {
    $newAccessToken = $provider->getAccessToken('refresh_token', [
        'refresh_token' => $existingAccessToken->getRefreshToken()
    ]);

    // Purge old access token and store new access token to your data store.
}
```

### Client Credentials Grant

When your application is acting on its own behalf to access resources it controls/owns in a service provider, it may use the client credentials grant type. This is best used when the credentials for your application are stored privately and never exposed (e.g. through the web browser, etc.) to end-users. This grant type functions similarly to the resource owner password credentials grant type, but it does not request a user's username or password. It uses only the client ID and secret issued to your client by the service provider.

Unlike earlier examples, the following does not work against a functioning demo service provider. It is provided for the sake of example only.

``` php
try {

    // Try to get an access token using the client credentials grant.
    $accessToken = $provider->getAccessToken('client_credentials');

} catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {

    // Failed to get the access token
    exit($e->getMessage());

}
```

## Install

Via Composer

``` bash
$ composer require sydefz/freelancer-oauth2-client
```

## License

The MIT License (MIT). Please see [License File](https://github.com/sydefz/freelancer-oauth2-client/blob/master/LICENSE) for more information.
