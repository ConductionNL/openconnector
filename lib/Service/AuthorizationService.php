<?php

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
use OCA\OpenConnector\Db\Consumer;
use OCA\OpenConnector\Db\ConsumerMapper;
use OCA\OpenConnector\Exception\AuthenticationException;
use OCP\IUserManager;
use OCP\IUserSession;

/**
 * Service class for handling authorization on incoming calls.
 */
class AuthorizationService
{
	const HMAC_ALGORITHMS = ['HS256', 'HS384', 'HS512'];
	const PKCS1_ALGORITHMS = ['RS256', 'RS384', 'RS512'];
	const PSS_ALGORITHMS = ['PS256', 'PS384', 'PS512'];


	/**
	 * @param IUserManager $userManager
	 * @param IUserSession $userSession
	 * @param ConsumerMapper $consumerMapper
	 */
	public function __construct(
		private readonly IUserManager   $userManager,
		private readonly IUserSession   $userSession,
		private readonly ConsumerMapper $consumerMapper,
	)
	{
	}

	/**
	 * Find the issuer (consumer) for the request.
	 *
	 * @param string $issuer The issuer from the JWT token.
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
	}

	/**
	 * Check if the headers of a JWT token are valid.
	 *
	 * @param JWS $token The unserialized token.
	 * @return void
	 */
	private function checkHeaders(JWS $token): void
	{
		$headerChecker = new HeaderCheckerManager(
			checkers: [
				new AlgorithmChecker(array_merge(self::HMAC_ALGORITHMS, self::PKCS1_ALGORITHMS, self::PSS_ALGORITHMS))
			],
			tokenTypes: [new JWSTokenSupport()]);

		$headerChecker->check(jwt: $token, index: 0);

	}

	/**
	 * Get the Json Web Key for a public key combined with an algorithm.
	 *
	 * @param string $publicKey The public key to create a JWK for
	 * @param string $algorithm The algorithm deciding how the key should be defined.
	 * @return JWKSet The resulting JWK-set.
	 * @throws AuthenticationException
	 */
	private function getJWK(string $publicKey, string $algorithm): JWKSet
	{

		if (in_array(needle: $algorithm, haystack: self::HMAC_ALGORITHMS) === true) {
			return new JWKSet([
				JWKFactory::createFromSecret(
					secret: $publicKey,
					additional_values: ['alg' => $algorithm, 'use' => 'sig'])
			]);
		} else if (in_array(needle: $algorithm, haystack: self::PKCS1_ALGORITHMS) === true
			|| in_array(needle: $algorithm, haystack: self::PSS_ALGORITHMS) === true
		) {
			$stamp = microtime() . getmypid();
			$filename = "/var/tmp/publickey-$stamp";
			file_put_contents($filename, base64_decode($publicKey));
			$jwk = new JWKSet([JWKFactory::createFromKeyFile(file: $filename)]);
			unlink($filename);
			return $jwk;
		}
		throw new AuthenticationException(message: 'The token algorithm is not supported', details: ['algorithm' => $algorithm]);
	}

	/**
	 * Validate data in the payload.
	 *
	 * @param array $payload The payload of the JWT token.
	 * @return void
	 * @throws AuthenticationException
	 */
	public function validatePayload(array $payload): void
	{
		$now = new DateTime();

		if (isset($payload['iat']) === true) {
			$iat = new DateTime('@' . $payload['iat']);
		} else {
			throw new AuthenticationException(message: 'The token has no time of creation', details: ['iat' => null]);
		}

		if (isset($payload['exp']) === true) {
			$exp = new DateTime('@' . $payload['exp']);
		} else {
			$exp = clone $iat;
			$exp->modify('+1 Hour');
		}

		if ($exp->diff($now)->format('%R') === '+') {
			throw new AuthenticationException(message: 'The token has expired', details: ['iat' => $iat->getTimestamp(), 'exp' => $exp->getTimestamp(), 'time checked' => $now->getTimestamp()]);
		}
	}

	/**
	 * Checks if authorization header contains a valid JWT token.
	 *
	 * @param string $authorization The authorization header.
	 * @return void
	 * @throws AuthenticationException
	 */
	public function authorize(string $authorization): void
	{
		$token = substr(string: $authorization, offset: strlen('Bearer '));

		if ($token === '') {
			throw new AuthenticationException(message: 'No token has been provided', details: []);
		}

		$algorithmManager = new AlgorithmManager([
			new HS256(),
			new HS384(),
			new HS256(),
			new RS256(),
			new RS384(),
			new RS512(),
			new PS256(),
			new PS384(),
			new PS512()
		]);
		$verifier = new JWSVerifier($algorithmManager);
		$serializerManager = new JWSSerializerManager([new CompactSerializer()]);


		$jws = $serializerManager->unserialize(input: $token);

		try {
			$this->checkHeaders($jws);
		} catch (InvalidHeaderException $exception) {
			throw new AuthenticationException(message: 'The token could not be validated', details: ['reason' => $exception->getMessage()]);
		}

		$payload = json_decode(json: $jws->getPayload(), associative: true);
		$issuer = $this->findIssuer(issuer: $payload['iss']);

		$publicKey = $issuer->getAuthorizationConfiguration()['publicKey'];
		$algorithm = $issuer->getAuthorizationConfiguration()['algorithm'];

		$jwkSet = $this->getJWK(publicKey: $publicKey, algorithm: $algorithm);

		if ($verifier->verifyWithKeySet(jws: $jws, jwkset: $jwkSet, signatureIndex: 0) === false) {
			throw new AuthenticationException(message: 'The token could not be validated', details: ['reason' => 'The token does not match the public key']);
		}
		$this->validatePayload($payload);
		$this->userSession->setUser($this->userManager->get($issuer->getUserId()));
	}
}
