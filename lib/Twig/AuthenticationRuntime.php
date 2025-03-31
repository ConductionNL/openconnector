<?php
/**
 * OpenConnector Authentication Runtime
 *
 * This file contains the runtime class for Twig authentication functions
 * in the OpenConnector application.
 *
 * @category  Twig
 * @package   OpenConnector
 * @author    Conduction Development Team <dev@conductio.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git-id>
 * @link      https://OpenConnector.app
 */

namespace OCA\OpenConnector\Twig;

use Adbar\Dot;
use GuzzleHttp\Exception\GuzzleException;
use OCA\OpenConnector\Db\Source;
use OCA\OpenConnector\Service\AuthenticationService;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * Class AuthenticationRuntime
 *
 * This class implements the RuntimeExtensionInterface for providing authentication functions to Twig templates.
 *
 * @package   OCA\OpenConnector\Twig
 * @category  Twig
 * @author    Conduction Development Team <dev@conductio.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   1.0.0
 */
class AuthenticationRuntime implements RuntimeExtensionInterface
{


    /**
     * Constructor
     *
     * @param AuthenticationService $authService Service for authentication operations
     *
     * @return void
     */
    public function __construct(
        private readonly AuthenticationService $authService,
    ) {

    }//end __construct()


    /**
     * Add an oauth token to the configuration
     *
     * @param Source $source The source containing authentication configuration
     *
     * @return string The OAuth token
     * @throws GuzzleException When an HTTP error occurs during token acquisition
     */
    public function oauthToken(Source $source): string
    {
        $configuration = new Dot($source->getConfiguration(), true);

        $authConfig = $configuration->get('authentication');

        return $this->authService->fetchOAuthTokens(
            configuration: $authConfig
        );

    }//end oauthToken()


    /**
     * Add a decos non-oauth token to the configuration
     *
     * @param Source $source The source containing authentication configuration
     *
     * @return string The Decos token
     * @throws GuzzleException When an HTTP error occurs during token acquisition
     */
    public function decosToken(Source $source): string
    {
        $configuration = new Dot($source->getConfiguration(), true);

        $authConfig = $configuration->get('authentication');

        return $this->authService->fetchDecosToken(
            configuration: $authConfig
        );

    }//end decosToken()


    /**
     * Add a JWT token to the configuration
     *
     * @param Source $source The source containing authentication configuration
     *
     * @return string The JWT token
     * @throws GuzzleException When an HTTP error occurs during token acquisition
     */
    public function jwtToken(Source $source): string
    {
        $configuration = new Dot($source->getConfiguration(), true);

        $authConfig = $configuration->get('authentication');

        return $this->authService->fetchJWTToken(
            configuration: $authConfig
        );

    }//end jwtToken()


}//end class
