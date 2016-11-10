<?php namespace freelancer\OAuth2\Client\Provider;

use UnexpectedValueException;
use Psr\Http\Message\ResponseInterface;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\GenericResourceOwner;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;

class FreelancerIdentity extends AbstractProvider {
    /**
     * scopes, prompt and advanced_scopes can be modified by
     * $options array on construct
     */
    public $scopes = ['basic','advanced'];
    public $prompt = ['select_account', 'consent'];
    public $advanced_scopes = [];

    private $separator = ' ';
    private $responseCode = 'error_code';
    private $responseError = 'message';
    private $ownerId = 'email';
    /**
     * @throws UnexpectedValueException
     */
    public function __construct($options = []) {
        parent::__construct($options);

        if (!isset($options['baseUri'])) {
            throw new UnexpectedValueException('Base URI required.');
        } else {
            $this->baseUri = $options['baseUri'];
        }
    }

    /**
     * by default user needs to select account then consent on requested scopes
     * explicitly define it in prompt will force user to go through these steps
     *
     * select_account step is optional and can be skip by remove it from prompt
     * consent step is optional if client is white-listed (FLN, WAR, Escrow) or
     * bearer token exist and all scopes granted previously
     *
     * advanced scopes is required if you want to use freelancer API on user's behave
     * you will need to select advanced scopes when you create your app
     *
     * @return array
     */
    protected function getAuthorizationParameters(array $options) {
        $options = parent::getAuthorizationParameters($options);
        $options += ['prompt' => implode($this->separator, $this->prompt)];
        $options += ['advanced_scopes' => implode($this->separator, $this->advanced_scopes)];
        return $options;
    }

    protected function getScopeSeparator() {
        return $this->separator;
    }

    public function getBaseAuthorizationUrl() {
        return $this->baseUri.'/oauth/authorise';
    }

    public function getBaseAccessTokenUrl(array $params) {
        return $this->baseUri.'/oauth/token';
    }

    public function getResourceOwnerDetailsUrl(\League\OAuth2\Client\Token\AccessToken $token) {
        return $this->baseUri.'/oauth/me';
    }

    /**
     * all requests made by getAuthenticatedRequest() with $token passed in
     * will have bearer token set in their headers
     */
    protected function getAuthorizationHeaders($token = null) {
        if ($token) {
            return ['Authorization' => 'Bearer '.$token->getToken()];
        } else {
            return [];
        }
    }

    /**
     * Returns the default scopes used by this provider
     * unless specific scopes pass in on constructor
     *
     * @return array
     */
    public function getDefaultScopes() {
        return $this->scopes;
    }

    /**
     * @throws IdentityProviderException when response contains error/exception
     */
    protected function checkResponse(ResponseInterface $response, $data) {
        if (!empty($data[$this->responseCode])) {
            $error = $data[$this->responseError];
            throw new IdentityProviderException($error, 0, $data);
        }
    }

    protected function createResourceOwner(array $response, AccessToken $token) {
        return new GenericResourceOwner($response, $response[$this->ownerId]);
    }
}
