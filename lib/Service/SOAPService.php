<?php

namespace OCA\OpenConnector\Service;

use DOMDocument;
use DOMXPath;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use OCA\OpenConnector\Db\Source;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Soap\Engine\Engine;
use Soap\Engine\SimpleEngine;
use Soap\ExtSoapEngine\AbusedClient;
use Soap\ExtSoapEngine\ExtSoapOptions;
use Soap\ExtSoapEngine\ExtSoapDriver;
use Soap\ExtSoapEngine\Transport\ExtSoapClientTransport;
use Soap\ExtSoapEngine\Transport\TraceableTransport;
use Soap\ExtSoapEngine\Wsdl\InMemoryWsdlProvider;
use Soap\ExtSoapEngine\Wsdl\TemporaryWsdlLoaderProvider;
use Soap\Psr18Transport\Psr18Transport;
use Soap\Psr18Transport\Wsdl\Psr18Loader;
use Soap\Wsdl\Loader\StreamWrapperLoader;
use stdClass;
use Symfony\Component\Config\Definition\Exception\Exception;

/**
 * This class contains a basic SOAP client for communicating with SOAP Sources using a WSDL
 *
 * It manages the execution of SOAP requests using the Guzzle HTTP client for performing the actual HTTP requests.
 */
class SOAPService
{
	/**
	 * @var Client The GuzzleClient used by the SOAP engine
	 */
    private Client $client;

	/**
	 * @var Psr18Transport The PSR-18 transport layer of the SOAP engine
	 */
	private Psr18Transport $transport;

	/**
	 * Constructor
	 *
	 * @param CookieJar $cookieJar A cookie jar to pass on cookies between SOAP requests.
	 */
	public function __construct(private readonly CookieJar $cookieJar) {
	}

	/**
	 * Setup an SOAP engine for a source.
	 *
	 * @param Source $source The source to call.
	 * @param array $passedConfig The config to setup the HTTP client with.
	 *
	 * @return Engine The resulting soap engine.
	 * @throws \SoapFault
	 */
    public function setupEngine(Source $source, array $passedConfig): Engine {

        $config = $source->getConfiguration();

        if (isset($config['wsdl']) === false) {
            throw new Exception('No wsdl provided');
        }

		$passedConfig['cookies'] = $this->cookieJar;

        $this->client = new Client($passedConfig);
        $wsdl = $config['wsdl'];
        unset($passedConfig['wsdl']);
        try {
            $engine = new SimpleEngine(
                $driver = ExtSoapDriver::createFromClient(
                    $soap = $client = AbusedClient::createFromOptions(
                        ExtSoapOptions::defaults($wsdl, [
                            'cache_wsdl' => WSDL_CACHE_NONE,
                            'trace' => true,
                            'location' => $source->getLocation(),
							'soap_version' => SOAP_1_2
                        ])
                            ->withWsdlProvider(new TemporaryWsdlLoaderProvider(new Psr18Loader($this->client, new HttpFactory())))
                            ->disableWsdlCache()
                    )
                ),
				$this->transport = Psr18Transport::createForClient($this->client),
//                $transport = new TraceableTransport(
//                    $client,
//                    new ExtSoapClientTransport($client)
//                )
            );
        } catch (\SoapFault $fault) {
            throw $fault;
        }

        return $engine;
    }

	/**
	 * Parse an XML snippet with its own dynamic XSD
	 *
	 * @param string $xmlString The XML split in two parts: the XSD and the data to parse.
	 *
	 * @return \SimpleXMLElement The resulting XML element.
	 */
	private function parseDynamicXsd (string $xmlString): \SimpleXMLElement
	{
		// @TODO: This is awfully specific, to be replaced by a more generic fix for faulty XSD.
		$xmlString = '<any>'.str_replace('NewDataSet', 'DocumentElement', $xmlString).'</any>';

		$dom = new DOMDocument();
		$dom->loadXML($xmlString);

		// 3. OPTIONAL: Validate against schema in the XML itself (or use an external .xsd file)
		libxml_use_internal_errors(true);
		if ($dom->schemaValidateSource($xmlString) === true) {
		} else {
			libxml_clear_errors();
		}

		// 4. Parse the data inside diffgram
		$simpleXml = simplexml_load_string($xmlString, 'SimpleXMLElement', 0,
			'diffgr', true);

		// The diffgram will be under the 'diffgram' namespace
		$namespaces = $simpleXml->getNamespaces(true);
		$diffgram = $simpleXml->children($namespaces['diffgr'])->diffgram;

		// Or just get the DocumentElement directly
		$documentElement = $simpleXml->xpath('//DocumentElement')[0];

		return $documentElement->QueryExecResult;
	}

	/**
	 * Call a soap source with provided configuration.
	 *
	 * @param Source $source The SOAP source to call.
	 * @param string $soapAction The SOAPAction to call (most comparable to an endpoint in REST).
	 * @param array $config The configuration to use when calling the source.
	 *
	 * @return Response The resulting response.
	 * @throws \SoapFault
	 */
    public function callSoapSource(Source $source, string $soapAction, array $config): Response
    {

        $body = json_decode(json: $config['body'], associative: true);
        unset($config['body']);

        libxml_set_external_entity_loader(static function ($public, $system) {
            return $system;
        });
        /**
         * @var $engine Engine
         */
        $engine = $this->setupEngine(source: $source, passedConfig: $config);

        // In SOAP the endpoint is decided by the WSDL, however, the SOAP method can be derived from the endpoint property of the call.
        $result = $engine->request($soapAction, $body);

		// @TODO: This must be replaced by an generic detector of fields that should be parsed in the parseDynamicXsd-function.
		if(isset($result->{'QueryExecute2Result'}) === true && isset($result->{'QueryExecute2Result'}->any) === true) {

			$result->{'QueryExecute2Result'} = $this->parseDynamicXsd($result->{'QueryExecute2Result'}->any);
		}

		// @TODO: The detection of binary data fields should be dynamic
		if(isset($result->FileBytes) === true && json_encode($result) === false) {
			$result->FileBytes = base64_encode($result->FileBytes);
		}

        libxml_set_external_entity_loader(static function () {
            return null;
        });

        return new Response(status: 200, body: json_encode($result));


        //return json_encode($result);
    }
}
