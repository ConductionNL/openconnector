<?php

/**
 * Service class for handling authentication on other services.
 *
 * This service provides various authentication methods for external services,
 * including OAuth Client Credentials, Password Grant, JWT token generation and more.
 *
 * @category  Service
 * @package   OpenConnector
 * @author    Conduction Development Team <dev@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: 1.0.0
 * @link      https://OpenConnector.app
 */

namespace OCA\OpenConnector\Service;

use DateTime;
use GuzzleHttp\Client;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Core\JWK;
use Jose\Component\KeyManagement\JWKFactory;
use Jose\Component\Signature\Algorithm\HS256;
use Jose\Component\Signature\Algorithm\HS384;
use Jose\Component\Signature\Algorithm\HS512;
use Jose\Component\Signature\Algorithm\PS256;
use Jose\Component\Signature\Algorithm\RS256;
use Jose\Component\Signature\Algorithm\RS384;
use Jose\Component\Signature\Algorithm\RS512;
use Jose\Component\Signature\JWSBuilder;
use Jose\Component\Signature\Serializer\CompactSerializer;
use Jose\Component\Signature\Serializer\JSONFlattenedSerializer;
use OAuthException;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

/**
 * Service class for handling authentication on other services.
 */
class AuthenticationService
{
    /**
     * Required parameters for Client Credentials grant type.
     *
     * @var string[]
     */
    public const REQUIRED_PARAMETERS_CLIENT_CREDENTIALS = [
        'grant_type',
        'scope',
        'authentication',
        'client_id',
        'client_secret',
    ];

    /**
     * Required parameters for Password grant type.
     *
     * @var string[]
     */
    public const REQUIRED_PARAMETERS_PASSWORD = [
        'grant_type',
        'scope',
        'authentication',
        'username',
        'password',
    ];

    /**
     * Required parameters for JWT token creation.
     *
     * @var string[]
     */
    public const REQUIRED_PARAMETERS_JWT = [
        'payload',
        'secret',
        'algorithm',
    ];

    /**
     * Twig environment for templating.
     *
     * @var Environment
     */
    private Environment $twig;


    /**
     * Setting up the class with required service.
     *
     * @param ArrayLoader $loader The ArrayLoader for Twig.
     *
     * @return void
     */
    public function __construct(
        ArrayLoader $loader
    ) {
        $this->twig = new Environment(loader: $loader);

    }//end __construct()


    /**
     * Create call options for OAuth with Client Credentials.
     *
     * @param array $configuration Configuration array for authentication.
     *
     * @return array The call options for OAuth with Client Credentials.
     *
     * @throws BadRequestException When required parameters are missing.
     *
     * @psalm-param  array<string, mixed> $configuration
     * @psalm-return array<string, mixed>
     */
    private function createClientCredentialConfig(array $configuration): array
    {
        $diff = array_diff(self::REQUIRED_PARAMETERS_CLIENT_CREDENTIALS, array_keys($configuration));
        if ($diff !== []) {
            throw new BadRequestException('Some required parameters are not set: ['.implode(',', $diff).']');
        }

        $callConfig = [
            'form_params' => [
                'grant_type' => $configuration['grant_type'],
                'scope'      => $configuration['scope'],
            ],
        ];

        if ($configuration['authentication'] === 'body') {
            $callConfig['form_params']['client_id']     = $configuration['client_id'];
            $callConfig['form_params']['client_secret'] = $configuration['client_secret'];
        } else if ($configuration['authentication'] === 'basic_auth') {
            $callConfig['auth'] = [
                'username' => $configuration['client_id'],
                'password' => $configuration['client_secret'],
            ];
        }

        if (isset($configuration['client_assertion_type']) === true
            && $configuration['client_assertion_type'] === 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer'
        ) {
            $callConfig['form_params']['client_assertion_type'] = $configuration['client_assertion_type'];
            $callConfig['form_params']['client_assertion']      = $this->fetchJWTToken(
                [
                    'algorithm' => 'PS256',
                    'secret'    => $configuration['private_key'],
                    'x5t'       => $configuration['x5t'],
                    'payload'   => $configuration['payload'],
                ]
            );
        }

        return $callConfig;

    }//end createClientCredentialConfig()


    /**
     * Create call options for OAuth with Password Credentials.
     *
     * @param array $configuration Configuration array for authentication.
     *
     * @return array The call options for OAuth with Password Credentials.
     *
     * @throws BadRequestException When required parameters are missing.
     *
     * @psalm-param  array<string, mixed> $configuration
     * @psalm-return array<string, mixed>
     */
    private function createPasswordConfig(array $configuration): array
    {
        $diff = array_diff(self::REQUIRED_PARAMETERS_PASSWORD, array_keys($configuration));
        if ($diff !== []) {
            throw new BadRequestException('Some required parameters are not set: ['.implode(',', $diff).']');
        }

        $callConfig = [
            'form_params' => [
                'grant_type' => $configuration['grant_type'],
                'scope'      => $configuration['scope'],
            ],
        ];

        if ($configuration['authentication'] === 'body') {
            $callConfig['form_params']['username'] = $configuration['username'];
            $callConfig['form_params']['password'] = $configuration['password'];
        } else if ($configuration['authentication'] === 'basic_auth') {
            $callConfig['auth'] = [
                'username' => $configuration['username'],
                'password' => $configuration['password'],
            ];
        }

        return $callConfig;

    }//end createPasswordConfig()


    /**
     * Requests an OAuth Access Token with predefined configuration.
     *
     * @param array $configuration The configuration for the OAuth call.
     *
     * @return string The resulting access token.
     *
     * @throws BadRequestException Thrown if the configuration is not compatible with OAuth.
     * @throws \GuzzleHttp\Exception\GuzzleException Thrown if the token endpoint does not respond with an access token.
     *
     * @todo Convert GuzzleException to another error.
     *
     * @psalm-param array<string, mixed> $configuration
     */
    public function fetchOAuthTokens(array $configuration): string
    {
        if (isset($configuration['grant_type']) === false) {
            throw new BadRequestException('Grant type not set, cannot request token');
        }

        if (isset($configuration['tokenUrl']) === false) {
            throw new BadRequestException('Token URL not set, cannot request token');
        }

        switch ($configuration['grant_type']) {
            case 'client_credentials':
                $callConfig = $this->createClientCredentialConfig($configuration);
                break;
            case 'password':
                $callConfig = $this->createPasswordConfig($configuration);
                break;
            default:
                throw new BadRequestException('Grant type not supported');
        }

        $client   = new Client();
        $response = $client->post($configuration['tokenUrl'], $callConfig);
        $result   = json_decode($response->getBody()->getContents(), true);

        if (isset($configuration['tokenLocation']) === true) {
            return $result[$configuration['tokenLocation']];
        }

        return $result['access_token'];

    }//end fetchOAuthTokens()


    /**
     * Fetch an access token from the DeCOS non-implementation of OAuth 2.0.
     *
     * @param array $configuration The configuration of the source.
     *
     * @return string The access token.
     *
     * @throws \GuzzleHttp\Exception\GuzzleException When there is an issue with the HTTP request.
     *
     * @psalm-param array<string, mixed> $configuration
     */
    public function fetchDecosToken(array $configuration): string
    {
        $url           = $configuration['tokenUrl'];
        $tokenLocation = $configuration['tokenLocation'];
        unset($configuration['tokenUrl']);

        $callConfig['json'] = $configuration;

        $client   = new Client();
        $response = $client->post($url, $callConfig);
        $result   = json_decode($response->getBody()->getContents(), true);

        if (isset($tokenLocation) === true) {
            return $result[$tokenLocation];
        }

        return $result['token'];

    }//end fetchDecosToken()


    /**
     * Get RSA key for RS and PS (asymmetrical) encryption.
     *
     * @param array $configuration The configuration containing the secret key.
     *
     * @return JWK|null The JSON Web Key or null if creation fails.
     *
     * @throws Exception When there's an issue with key creation.
     *
     * @psalm-param array<string, mixed> $configuration
     */
    private function getRSJWK(array $configuration): ?JWK
    {
        $stamp    = microtime().getmypid();
        $filename = "/var/tmp/privatekey-$stamp";
        file_put_contents($filename, base64_decode($configuration['secret']));
        $jwk = null;
        try {
            $jwk = JWKFactory::createFromKeyFile(
                $filename,
                null,
                ['use' => 'sig']
            );
        } catch (Exception $exception) {
            throw $exception;
        }

        unlink($filename);

        return $jwk;

    }//end getRSJWK()


    /**
     * Get HS key for HS (symmetrical) encryption.
     *
     * @param array $configuration The configuration containing the secret key.
     *
     * @return JWK|null The JSON Web Key or null if creation fails.
     *
     * @psalm-param array<string, mixed> $configuration
     */
    private function getHSJWK(array $configuration): ?JWK
    {
        return JWKFactory::createFromSecret(
            $configuration['secret'],
            [
                'alg' => $configuration['algorithm'],
                'use' => 'sig',
            ]
        );

    }//end getHSJWK()


    /**
     * Get the JWT payload from configuration.
     *
     * @param array $configuration The configuration containing the payload.
     *
     * @return array The JWT payload.
     *
     * @psalm-param  array<string, mixed> $configuration
     * @psalm-return array<string, mixed>
     */
    private function getJWTPayload(array $configuration): array
    {
        if (isset($configuration['payload']) === true) {
            return $configuration['payload'];
        }

        return [];

    }//end getJWTPayload()


    /**
     * Get the JSON Web Key based on algorithm type.
     *
     * @param array $configuration The configuration for the JWT.
     *
     * @return JWK|null The JSON Web Key object or null if not supported.
     *
     * @psalm-param array<string, mixed> $configuration
     */
    private function getJWK(array $configuration): ?JWK
    {
        if (in_array($configuration['algorithm'], ['HS256', 'HS384', 'HS512']) === true) {
            return $this->getHSJWK($configuration);
        } else if (in_array($configuration['algorithm'], ['RS256', 'RS384', 'RS512', 'PS256']) === true) {
            return $this->getRSJWK($configuration);
        }

        return null;

    }//end getJWK()


    /**
     * Generate a JWT token.
     *
     * @param array       $payload   The payload to include in the token.
     * @param JWK         $jwk       The JSON Web Key to sign with.
     * @param string      $algorithm The algorithm to use for signing.
     * @param string|null $x5t       Optional X.509 certificate thumbprint.
     *
     * @return string The generated JWT token.
     *
     * @psalm-param array<string, mixed> $payload
     */
    private function generateJWT(array $payload, JWK $jwk, string $algorithm, ?string $x5t=null): string
    {
        // Build the algorithm manager with the required algorithm.
        $algorithmManager = new AlgorithmManager(
            [
                new HS256(),
                new HS384(),
                new HS512(),
                new RS256(),
                new RS384(),
                new RS512(),
                new PS256(),
            ]
        );

        // Create the token header.
        $header = [
            'alg' => $algorithm,
            'typ' => 'JWT',
        ];

        // Add x5t if provided (used for some Microsoft services).
        if ($x5t !== null) {
            $header['x5t'] = $x5t;
        }

        // Create the JWS Builder.
        $jwsBuilder = new JWSBuilder($algorithmManager);

        // Create the token.
        $jws = $jwsBuilder
            ->create()
            ->withPayload(json_encode($payload))
            ->addSignature($jwk, $header)
            ->build();

        // Serialize the token for delivery.
        $serializer = new CompactSerializer();

        return $serializer->serialize($jws, 0);

    }//end generateJWT()


    /**
     * Fetch a JWT token based on configuration.
     *
     * @param array $configuration The configuration for the JWT token.
     *
     * @return string The JWT token.
     *
     * @throws BadRequestException When required parameters are missing.
     *
     * @psalm-param array<string, mixed> $configuration
     */
    public function fetchJWTToken(array $configuration): string
    {
        $diff = array_diff(self::REQUIRED_PARAMETERS_JWT, array_keys($configuration));
        if ($diff !== []) {
            throw new BadRequestException('Some required parameters are not set: ['.implode(',', $diff).']');
        }

        $payload = $this->getJWTPayload($configuration);
        $jwk     = $this->getJWK($configuration);

        if ($jwk === null) {
            throw new BadRequestException('Could not create JWK from given secret');
        }

        $x5t = null;
        if (isset($configuration['x5t']) === true) {
            $x5t = $configuration['x5t'];
        }

        return $this->generateJWT($payload, $jwk, $configuration['algorithm'], $x5t);

    }//end fetchJWTToken()


}//end class
