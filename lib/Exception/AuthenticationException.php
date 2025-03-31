<?php

/**
 * This file is part of the OpenConnector app.
 *
 * @category  Exception
 * @package   OpenConnector
 * @author    Conduction Development Team <dev@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://OpenConnector.app
 * @version   GIT: <git-id>
 */
namespace OCA\OpenConnector\Exception;

use Exception;

/**
 * Authentication Exception.
 *
 * Exception for storing authentication exceptions with details about the authentication failure.
 *
 * @category  Exception
 * @package   OpenConnector
 * @author    Conduction Development Team <dev@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://OpenConnector.app
 */
class AuthenticationException extends Exception
{

    /**
     * Details about the authentication failure.
     *
     * @var array Array containing detailed error information
     *
     * @psalm-var array<string, mixed>
     */
    private readonly array $details;


    /**
     * Constructor for the AuthenticationException.
     *
     * @param string $message The exception message
     * @param array  $details The details describing why an authentication failed
     *
     * @psalm-param array<string, mixed> $details
     *
     * @return void
     */
    public function __construct(string $message, array $details)
    {
        $this->details = $details;
        parent::__construct($message);

    }//end __construct()


    /**
     * Retrieves the details to display them.
     *
     * @return array The details array
     *
     * @psalm-return array<string, mixed>
     */
    public function getDetails(): array
    {
        return $this->details;

    }//end getDetails()


}//end class
