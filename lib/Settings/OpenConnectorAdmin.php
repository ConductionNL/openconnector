<?php
/**
 * OpenConnector Admin Settings
 *
 * This file contains the admin settings implementation for the OpenConnector app.
 *
 * @category  Settings
 * @package   OpenConnector
 * @author    Conduction Development Team <dev@conductio.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git-id>
 * @link      https://OpenConnector.app
 */

namespace OCA\OpenConnector\Settings;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\IConfig;
use OCP\IL10N;
use OCP\Settings\ISettings;

/**
 * Admin settings for OpenConnector
 *
 * @package   OCA\OpenConnector\Settings
 * @category  Settings
 * @author    Conduction Development Team <dev@conductio.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   1.0.0
 */
class OpenConnectorAdmin implements ISettings
{

    /**
     * Localization service
     *
     * @var IL10N
     */
    private IL10N $l;

    /**
     * Configuration service
     *
     * @var IConfig
     */
    private IConfig $config;


    /**
     * Constructor for the OpenConnectorAdmin class
     *
     * @param IConfig $config The configuration service
     * @param IL10N   $l      The localization service
     *
     * @return void
     */
    public function __construct(IConfig $config, IL10N $l)
    {
        $this->config = $config;
        $this->l      = $l;

    }//end __construct()


    /**
     * Returns the admin settings form
     *
     * @return TemplateResponse The template response containing the settings form
     */
    public function getForm(): TemplateResponse
    {
        $parameters = [
            'mySetting' => $this->config->getSystemValue('open_connector_setting', true),
        ];

        return new TemplateResponse('openconnector', 'settings/admin', $parameters, '');

    }//end getForm()


    /**
     * Returns the section ID
     *
     * @return string The section ID
     */
    public function getSection(): string
    {
        // Name of the previously created section.
        return 'openconnector';

    }//end getSection()


    /**
     * Returns the section priority
     *
     * @return int whether the form should be rather on the top or bottom of
     * the admin section. The forms are arranged in ascending order of the
     * priority values. It is required to return a value between 0 and 100.
     *
     * E.g.: 70
     */
    public function getPriority(): int
    {
        return 10;

    }//end getPriority()


}//end class
