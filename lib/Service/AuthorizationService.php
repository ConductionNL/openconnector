<?php
/**
 * OpenConnector Authorization Service
 *
 * This file contains the service for handling authentication and authorization
 * in the OpenConnector application.
 *
 * @category  Service
 * @package   OpenConnector
 * @author    NextCloud Development Team <dev@nextcloud.com>
 * @copyright 2023 NextCloud GmbH
 * @license   AGPL-3.0 https://www.gnu.org/licenses/agpl-3.0.en.html
 * @version   GIT: <git-id>
 * @link      https://nextcloud.com
 */

namespace OCA\OpenConnector\Service;

use DateTime;
use Jose\Component\Checker\AlgorithmChecker;
use Jose\Component\Checker\HeaderCheckerManager;
use Jose\Component\Checker\InvalidHeaderException;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Core\JWKSet;
use Jose\Component\KeyManagement\JWKFactory;
use Jose\Component\Signature\Algorithm\HS256;
use Jose\Component\Signature\Algorithm\HS384;
use Jose\Component\Signature\Algorithm\PS256;
use Jose\Component\Signature\Algorithm\PS384;
use Jose\Component\Signature\Algorithm\PS512;
use Jose\Component\Signature\Algorithm\RS256;
use Jose\Component\Signature\Algorithm\RS384;
use Jose\Component\Signature\Algorithm\RS512;
use Jose\Component\Signature\JWS;
use Jose\Component\Signature\JWSTokenSupport;
use Jose\Component\Signature\JWSVerifier;
use Jose\Component\Signature\Serializer\CompactSerializer;
use Jose\Component\Signature\Serializer\JWSSerializerManager;
use OCP\IRequest;
use OC\AppFramework\Middleware\Security\Exceptions\SecurityException;
use OCP\AppFramework\Http\Attribute\CORS;
use OCP\AppFramework\Http\Response;
use OCP\Authentication\Token\IProvider;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\ISession;
use OCP\IUserManager;
use OCP\IUserSession;
use OCA\OAuth2\Db\AccessTokenMapper;
use OCA\OAuth2\Db\Client;
use OCA\OpenConnector\Db\Consumer;
use OCA\OpenConnector\Db\ConsumerMapper;
use OCA\OpenConnector\Exception\AuthenticationException;

/**
 * Service class for handling authorization on incoming calls.
 */
class AuthorizationService
{
    const HMAC_ALGORITHMS  = [
        'HS256',
        'HS384',
        'HS512',
    ];
    const PKCS1_ALGORITHMS = [
        'RS256',
        'RS384',
        'RS512',
    ];
    const PSS_ALGORITHMS   = [
        'PS256',
        'PS384',
        'PS512',
    ];


    /**
     * Constructor for the AuthorizationService
     *
     * @param IUserManager   $userManager    User manager service
     * @param IUserSession   $userSession    User session service
     * @param ConsumerMapper $consumerMapper Consumer mapper service
     * @param IGroupManager  $groupManager   Group manager service
     * @param IProvider      $tokenProvider  Token provider service
     *
     * @return void
     */
    public function __construct(
        private readonly IUserManager $userManager,
        private readonly IUserSession $userSession,
        private readonly ConsumerMapper $consumerMapper,
        private readonly IGroupManager $groupManager,
        private readonly IProvider $tokenProvider,
    ) {

    }//end __construct()


    /**
     * Find the issuer (consumer) for the request.
     *
     * @param string $issuer The issuer from the JWT token.
     *
     * @return Consumer The consumer for the JWT token.
     * @throws AuthenticationException Thrown if no issuer was found.
     */
    private function findIssuer(string $issuer): Consumer
    {
        $consumers = $this->consumerMapper->findAll(filters: ['name' => $issuer]);

        if (count($consumers) === 0) {
            throw new AuthenticationException(message: 'The issuer was not found', details: ['iss' => $issuer]);
        }

        return $consumers[0];

    }//end findIssuer()


    /**
     * Check if the headers of a JWT token are valid.
     *
     * @param JWS $token The unserialized token.
     *
     * @return void
     */
    private function checkHeaders(JWS $token): void
    {
        $checkers = [new AlgorithmChecker(array_merge(self::HMAC_ALGORITHMS, self::PKCS1_ALGORITHMS, self::PSS_ALGORITHMS))];

        $headerChecker = new HeaderCheckerManager(
            checkers: $checkers,
            tokenTypes: [new JWSTokenSupport()]
        );

        $headerChecker->check(jwt: $token, index: 0);

    }//end checkHeaders()


    /**
     * Get the Json Web Key for a public key combined with an algorithm.
     *
     * @param string $publicKey The public key to create a JWK for
     * @param string $algorithm The algorithm deciding how the key should be defined.
     *
     * @return JWKSet The resulting JWK-set.
     * @throws AuthenticationException If algorithm is not supported
     */
    private function getJWK(string $publicKey, string $algorithm): JWKSet
    {
        if (in_array(needle: $algorithm, haystack: self::HMAC_ALGORITHMS) === true) {
            $jwk = JWKFactory::createFromSecret(
                secret: $publicKey,
                additional_values: [
                    'alg' => $algorithm,
                    'use' => 'sig',
                ]
            );

            return new JWKSet([$jwk]);
        } else if (in_array(needle: $algorithm, haystack: self::PKCS1_ALGORITHMS) === true
            || in_array(needle: $algorithm, haystack: self::PSS_ALGORITHMS) === true
        ) {
            $stamp    = microtime().getmypid();
            $filename = "/var/tmp/publickey-$stamp";
            file_put_contents($filename, base64_decode($publicKey));
            $jwk = new JWKSet([JWKFactory::createFromKeyFile(file: $filename)]);
            unlink($filename);
            return $jwk;
        }//end if

        throw new AuthenticationException(
            message: 'The token algorithm is not supported',
            details: ['algorithm' => $algorithm]
        );

    }//end getJWK()


    /**
     * Validate data in the payload.
     *
     * @param array $payload The payload of the JWT token.
     *
     * @return void
     * @throws AuthenticationException If validation fails
     */
    public function validatePayload(array $payload): void
    {
        $now = new DateTime();

        if (isset($payload['iat']) === true) {
            $iat = new DateTime('@'.$payload['iat']);
        } else {
            throw new AuthenticationException(
                message: 'The token has no time of creation',
                details: ['iat' => null]
            );
        }

        if (isset($payload['exp']) === true) {
            $exp = new DateTime('@'.$payload['exp']);
        } else {
            $exp = clone $iat;
            $exp->modify('+1 Hour');
        }

        if ($exp->diff($now)->format('%R') === '+') {
            throw new AuthenticationException(
                message: 'The token has expired',
                details: [
                    'iat'          => $iat->getTimestamp(),
                    'exp'          => $exp->getTimestamp(),
                    'time checked' => $now->getTimestamp(),
                ]
            );
        }

    }//end validatePayload()


    /**
     * Checks if authorization header contains a valid JWT token.
     *
     * @param string $authorization The authorization header.
     *
     * @return void
     * @throws AuthenticationException If token validation fails
     */
    public function authorizeJwt(string $authorization): void
    {
        $token = substr(string: $authorization, offset: strlen('Bearer '));

        if ($token === '' || $token === null) {
            throw new AuthenticationException(message: 'No token has been provided', details: []);
        }

        $algorithms = [
            new HS256(),
            new HS384(),
            new HS256(),
            new RS256(),
            new RS384(),
            new RS512(),
            new PS256(),
            new PS384(),
            new PS512(),
        ];

        $algorithmManager  = new AlgorithmManager($algorithms);
        $verifier          = new JWSVerifier($algorithmManager);
        $serializerManager = new JWSSerializerManager([new CompactSerializer()]);

        $jws = $serializerManager->unserialize(input: $token);

        try {
            $this->checkHeaders($jws);
        } catch (InvalidHeaderException $exception) {
            throw new AuthenticationException(
                message: 'The token could not be validated',
                details: ['reason' => $exception->getMessage()]
            );
        }

        $payload = json_decode(json: $jws->getPayload(), associative: true);
        if (isset($payload['iss']) === false || empty($payload['iss']) === true) {
            throw new AuthenticationException(
                message: 'The token could not be validated',
                details: ['reason' => 'No issuer mentioned']
            );
        }

        $issuer = $this->findIssuer(issuer: $payload['iss']);

        $publicKey = $issuer->getAuthorizationConfiguration()['publicKey'];
        $algorithm = $issuer->getAuthorizationConfiguration()['algorithm'];

        $jwkSet = $this->getJWK(publicKey: $publicKey, algorithm: $algorithm);

        if ($verifier->verifyWithKeySet(jws: $jws, jwkset: $jwkSet, signatureIndex: 0) === false) {
            throw new AuthenticationException(
                message: 'The token could not be validated',
                details: ['reason' => 'The token does not match the public key']
            );
        }

        $this->validatePayload($payload);
        $this->userSession->setUser($this->userManager->get($issuer->getUserId()));

    }//end authorizeJwt()


    /**
     * Authorize user based on basic authentication
     *
     * @param string $header The authorization header given in the request
     * @param array  $users  The users allowed to be authenticated according to the rule
     * @param array  $groups The groups allowed to be authenticated according to the rule
     *
     * @return void
     * @throws AuthenticationException If authentication fails
     */
    public function authorizeBasic(string $header, array $users, array $groups): void
    {
        $header = substr(string: $header, offset: strlen('Basic '));
        $decode = base64_decode($header);
        [
            $username,
            $password,
        ]       = explode(separator: ':', string: $decode);

        $user = $this->userManager->checkPassword(loginName: $username, password: $password);

        if ($user === false) {
            throw new AuthenticationException(message: 'Invalid username or password', details: []);
        }

        // @TODO: This code can be enabled once the frontend can properly set users and usergroups
        // $userInAllowedUsers = array_intersect($users, [$user->getUID(), $user->getEMailAddress()]) !== [];
        //
        // $userGroups = array_map(function(IGroup $group) {
        // return $group->getGID();
        // }, $this->groupManager->getUserGroups($user));
        //
        // $userInAllowedGroups = array_intersect($groups, $userGroups) !== [];
        //
        // if($userInAllowedUsers === false && $userInAllowedGroups === false) {
        // throw new AuthenticationException(
        //     message: 'Not authorized', 
        //     details: ['reason' => 'The selected user is not allowed to login on this endpoint']
        // );
        // }
        $this->userSession->setUser($user);

    }//end authorizeBasic()


    /**
     * Authorize user based on OAuth
     *
     * @param string $header The authorization header used
     * @param array  $users  The users allowed to be authenticated according to the rule
     * @param array  $groups The groups allowed to be authenticated according to the rule
     *
     * @return void
     * @throws AuthenticationException If authorization fails
     */
    public function authorizeOAuth(string $header, array $users, array $groups): void
    {
        if (str_starts_with($header, 'Bearer') === false) {
            throw new AuthenticationException(
                message: 'Invalid method',
                details: ['reason' => 'The authentication method you are using is not allowed on this resource.']
            );
        }

        if ($this->userSession->isLoggedIn() === false) {
            throw new AuthenticationException(
                message: 'Not authorized',
                details: ['reason' => 'The token you used has either expired or was not recognized as a valid token']
            );
        }

        $user = $this->userSession->getUser();

        if ($user === false) {
            throw new AuthenticationException(message: 'Invalid token', details: []);
        }

        // @TODO: This code can be enabled once the frontend can properly set users and usergroups
        // $userInAllowedUsers = array_intersect($users, [$user->getUID(), $user->getEMailAddress()]) !== [];
        // $userGroups = array_map(function(IGroup $group) {
        // return $group->getGID();
        // }, $this->groupManager->getUserGroups($user));
        // $userInAllowedGroups = array_intersect($groups, $userGroups) !== [];
    }//end authorizeOAuth()


    /**
     * Add CORS headers to controller result
     *
     * @param IRequest $request  The incoming request
     * @param Response $response The outgoing response
     *
     * @return Response The updated response
     * @throws SecurityException If CORS configuration violates security rules
     */
    public function corsAfterController(IRequest $request, Response $response)
    {
        // Only react if it's a CORS request and if the request sends origin.
        if (isset($request->server['HTTP_ORIGIN']) === true) {
            // Allow credentials headers must not be true or CSRF is possible otherwise.
            foreach ($response->getHeaders() as $header => $value) {
                if (strtolower($header) === 'access-control-allow-credentials'
                    && strtolower(trim($value)) === 'true'
                ) {
                    $msg = 'Access-Control-Allow-Credentials must not be set to true in order to prevent CSRF';
                    throw new SecurityException($msg);
                }
            }

            $origin = $request->server['HTTP_ORIGIN'];
            $response->addHeader('Access-Control-Allow-Origin', $origin);
        }

        return $response;

    }//end corsAfterController()


    /**
     * Authorize user based on APIkey
     *
     * @param string $header The authorization header used
     * @param array  $keys   The array of keys configured on the rule
     *
     * @return void
     * @throws AuthenticationException If API key is invalid
     */
    public function authorizeApiKey(string $header, array $keys): void
    {
        if (array_key_exists(key: $header, array: $keys) === false) {
            throw new AuthenticationException(message: 'Invalid API key', details: []);
        }

        $user = $this->userManager->get(uid: $keys[$header]);

        if ($user === null) {
            throw new AuthenticationException(message: 'Invalid API key', details: []);
        }

        $this->userSession->setUser(user: $user);

    }//end authorizeApiKey()


}//end class
