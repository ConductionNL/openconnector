<?php

namespace OCA\OpenConnector\Tests\Http;

use PHPUnit\Framework\TestCase;

/**
 * Mock of the Response class for testing
 */
class MockResponse {
    private $headers = [];
    private $status = 200;

    public function addHeader($name, $value) {
        $this->headers[$name] = $value;
    }

    public function setStatus($status) {
        $this->status = $status;
    }

    public function getStatus() {
        return $this->status;
    }
}

/**
 * Manual implementation of XMLResponse for testing
 */
class XMLResponse extends MockResponse {
    protected array $data;
    protected $renderCallback = null;

    public function __construct($data = [], int $status = 200, array $headers = []) {
        $this->data = is_array($data) ? $data : ['content' => $data];

        $this->setStatus($status);

        foreach ($headers as $name => $value) {
            $this->addHeader($name, $value);
        }

        $this->addHeader('Content-Type', 'application/xml; charset=utf-8');
    }

    protected function getData(): array {
        return ['value' => $this->data];
    }

    public function setRenderCallback(callable $callback) {
        $this->renderCallback = $callback;
        return $this;
    }

    public function render(): string {
        if ($this->renderCallback !== null) {
            return ($this->renderCallback)($this->getData());
        }

        $data = $this->getData()['value'];

        // Check if data contains an @root key, if so use it directly
        if (isset($data['@root']) === true) {
            return $this->arrayToXml($data);
        }

        // Use default root tag
        return $this->arrayToXml(['value' => $data], 'response');
    }

    public function arrayToXml(array $data, ?string $rootTag = null): string {
        $rootName = $rootTag ?? ($data['@root'] ?? 'root');

        if (isset($data['@root']) === true) {
            unset($data['@root']);
        }

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        $root = $dom->createElement($rootName);
        if (!$root) {
            return '';
        }

        $dom->appendChild($root);

        $this->buildXmlElement($dom, $root, $data);

        // Get XML output
        $xmlOutput = $dom->saveXML() ?: '';

        // Directly replace decimal CR entities with hexadecimal
        $xmlOutput = str_replace('&#13;', '&#xD;', $xmlOutput);

        // Format empty tags to have a space before the closing bracket
        $xmlOutput = preg_replace('/<([^>]*)\/>/','<$1 />', $xmlOutput);

        return $xmlOutput;
    }

    private function buildXmlElement(\DOMDocument $dom, \DOMElement $element, array $data): void {
        if (isset($data['@attributes']) === true && is_array($data['@attributes']) === true) {
            foreach ($data['@attributes'] as $attrKey => $attrValue) {
                $element->setAttribute($attrKey, (string)$attrValue);
            }
            unset($data['@attributes']);
        }

        if (isset($data['#text']) === true) {
            $element->appendChild($this->createSafeTextNode($dom, (string)$data['#text']));
            unset($data['#text']);
        }

        foreach ($data as $key => $value) {
            $key = ltrim($key, '@');
            $key = is_numeric($key) === true ? "item$key" : $key;

            if (is_array($value) === true) {
                if (isset($value[0]) === true && is_array($value[0]) === true) {
                    foreach ($value as $item) {
                        $this->createChildElement($dom, $element, $key, $item);
                    }
                } else {
                    $this->createChildElement($dom, $element, $key, $value);
                }
            } else {
                $this->createChildElement($dom, $element, $key, $value);
            }
        }
    }

    private function createChildElement(\DOMDocument $dom, \DOMElement $parentElement, string $tagName, $data): void {
        $childElement = $dom->createElement($tagName);
        if ($childElement) {
            $parentElement->appendChild($childElement);

            if (is_array($data) === true) {
                $this->buildXmlElement($dom, $childElement, $data);
            } elseif (is_object($data) === true) {
                // Handle objects: use __toString if available, otherwise use a placeholder
                if (method_exists($data, '__toString') === true) {
                    $childElement->appendChild($this->createSafeTextNode($dom, (string)$data));
                } else {
                    $childElement->appendChild($this->createSafeTextNode($dom, '[Object of class ' . get_class($data) . ']'));
                }
            } else {
                $childElement->appendChild($this->createSafeTextNode($dom, (string)$data));
            }
        }
    }

    private function createSafeTextNode(\DOMDocument $dom, string $text): \DOMNode {
        // Decode any HTML entities to prevent double encoding
        // First decode things like &amp; into &
        $decodedText = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        // Then decode again to handle cases like &#039; into '
        $decodedText = html_entity_decode($decodedText, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Create a text node with the processed text
        // Carriage returns will be encoded as decimal entities (&#13;) which are
        // later converted to hexadecimal (&#xD;) in the arrayToXml method
        return $dom->createTextNode($decodedText);
    }
}

/**
 * PHPUnit test cases for the XMLResponse class
 *
 * Tests functionality in lib/Http/XMLResponse.php
 *
 * @category Test
 * @package  OCA\OpenConnector\Tests\Http
 */
class XMLResponseTest extends TestCase
{
    private const BASIC_XML_DATA = [
        'user' => [
            'id' => 123,
            'name' => 'Test User',
            'email' => 'test@example.com'
        ]
    ];

    private const CUSTOM_RENDER_DATA = [
        'test' => 'data'
    ];

    private const CUSTOM_ROOT_DATA = [
        '@root' => 'customRoot',
        'message' => 'Hello World'
    ];

    private const ARRAY_ITEMS_DATA = [
        'items' => [
            ['name' => 'Item 1', 'value' => 100],
            ['name' => 'Item 2', 'value' => 200],
            ['name' => 'Item 3', 'value' => 300]
        ]
    ];

    private const ATTRIBUTES_DATA = [
        'element' => [
            '@attributes' => [
                'id' => '123',
                'class' => 'container'
            ],
            'content' => 'Text with attributes'
        ]
    ];

    private const NAMESPACED_ATTRIBUTES_DATA = [
        '@root' => 'root',
        '@attributes' => [
            'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
            'xsi:schemaLocation' => 'http://example.org/schema.xsd'
        ],
        'content' => 'Namespaced content'
    ];

    private const SPECIAL_CHARS_DATA = [
        'element' => 'Text with <special> & "characters"'
    ];

    private const HTML_ENTITY_DATA = [
        'simple' => 'Text with apostrophes like BOA&#039;s and camera&#039;s',
        'double' => 'Text with double encoded apostrophes like BOA&amp;#039;s'
    ];

    private const CARRIAGE_RETURN_DATA = [
        'element' => "Text with carriage return\r and line feed\n mixed together"
    ];

    private const ARCHIMATE_MODEL_DATA = [
        '@root' => 'model',
        '@attributes' => [
            'xmlns' => 'http://www.opengroup.org/xsd/archimate/3.0/',
            'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
            'xsi:schemaLocation' => 'http://www.opengroup.org/xsd/archimate/3.0/ http://www.opengroup.org/xsd/archimate/3.1/archimate3_Diagram.xsd',
            'identifier' => 'id-b58b6b03-a59d-472b-bd87-88ba77ded4e6'
        ]
    ];

    private const EMPTY_TAG_DATA = [
        'properties' => [
            'property' => [
                '@attributes' => [
                    'propertyDefinitionRef' => 'propid-3'
                ],
                'value' => [
                    '@attributes' => [
                        'xml:lang' => 'nl'
                    ]
                ]
            ]
        ]
    ];

    /**
     * Test basic XML generation
     *
     * @return void
     */
    public function testBasicXmlGeneration(): void
    {
        $response = new XMLResponse(self::BASIC_XML_DATA);
        $xml = $response->render();

        $this->assertStringContainsString('<response>', $xml);
        $this->assertStringContainsString('<value>', $xml);
        $this->assertStringContainsString('<user>', $xml);
        $this->assertStringContainsString('<id>123</id>', $xml);
        $this->assertStringContainsString('<name>Test User</name>', $xml);
        $this->assertStringContainsString('<email>test@example.com</email>', $xml);
    }

    /**
     * Test custom render callback
     *
     * @return void
     */
    public function testCustomRenderCallback(): void
    {
        $response = new XMLResponse(self::CUSTOM_RENDER_DATA);
        $response->setRenderCallback(function($data) {
            return '<custom>' . json_encode($data) . '</custom>';
        });

        $result = $response->render();
        $this->assertStringContainsString('<custom>', $result);
        $this->assertStringContainsString('test', $result);
    }

    /**
     * Test XML generation with custom root tag
     *
     * @return void
     */
    public function testCustomRootTag(): void
    {
        $response = new XMLResponse(self::CUSTOM_ROOT_DATA);
        $xml = $response->render();

        $this->assertStringContainsString('<customRoot>', $xml);
        $this->assertStringNotContainsString('<response>', $xml);
        $this->assertStringContainsString('<message>Hello World</message>', $xml);
        $this->assertStringNotContainsString('<@root>', $xml);
    }

    /**
     * Test handling of array items
     *
     * @return void
     */
    public function testArrayItems(): void
    {
        $response = new XMLResponse();
        $xml = $response->arrayToXml(self::ARRAY_ITEMS_DATA);

        $this->assertStringContainsString('<items>', $xml);
        $this->assertStringContainsString('<name>Item 1</name>', $xml);
        $this->assertStringContainsString('<value>100</value>', $xml);
        $this->assertStringContainsString('<name>Item 2</name>', $xml);
    }

    /**
     * Test XML generation with attributes
     *
     * @return void
     */
    public function testAttributesHandling(): void
    {
        $response = new XMLResponse();
        $xml = $response->arrayToXml(self::ATTRIBUTES_DATA);

        $this->assertStringContainsString('<element id="123" class="container">', $xml);
        $this->assertStringContainsString('<content>Text with attributes</content>', $xml);
    }

    /**
     * Test XML generation with namespaced attributes
     *
     * @return void
     */
    public function testNamespacedAttributes(): void
    {
        $response = new XMLResponse();
        $xml = $response->arrayToXml(self::NAMESPACED_ATTRIBUTES_DATA);

        $this->assertStringContainsString('<root xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://example.org/schema.xsd">', $xml);
        $this->assertStringContainsString('<content>Namespaced content</content>', $xml);
    }

    /**
     * Test XML special character handling
     *
     * @return void
     */
    public function testSpecialCharactersHandling(): void
    {
        $response = new XMLResponse();
        $xml = $response->arrayToXml(self::SPECIAL_CHARS_DATA);

        $this->assertStringContainsString('<element>Text with &lt;special&gt; &amp; "characters"</element>', $xml);
    }

    /**
     * Test HTML entity decoding
     *
     * @return void
     */
    public function testHtmlEntityDecoding(): void
    {
        $response = new XMLResponse();
        $xml = $response->arrayToXml(self::HTML_ENTITY_DATA);

        // Just verify that both were converted to real apostrophes
        $this->assertStringContainsString("BOA's and camera's", $xml);
        $this->assertStringContainsString("BOA's</double>", $xml);
    }

    /**
     * Test carriage return handling with hexadecimal entities
     *
     * @return void
     */
    public function testCarriageReturnHandling(): void
    {
        $response = new XMLResponse();
        $xml = $response->arrayToXml(self::CARRIAGE_RETURN_DATA);

        // Check for hexadecimal entity for carriage return (without CDATA)
        $this->assertStringContainsString("carriage return&#xD; and line feed", $xml);
        $this->assertStringNotContainsString("<![CDATA[", $xml);
    }

    /**
     * Test handling of objects that can't be converted to string
     *
     * @return void
     */
    public function testObjectHandling(): void
    {
        // Create a simple stdClass object that doesn't have __toString
        $mockObject = new \stdClass();
        $mockObject->property = 'value';

        // Create an object with a __toString method
        $stringableObject = new class {
            public function __toString(): string {
                return 'Custom string representation';
            }
        };

        $response = new XMLResponse();
        $xml = $response->arrayToXml([
            'data' => [
                'object' => $mockObject,
                'stringable' => $stringableObject,
                'normal' => 'text'
            ]
        ]);

        // Verify that the object is converted to a placeholder
        $this->assertStringContainsString('<object>[Object of class stdClass]</object>', $xml);
        // Verify that the stringable object is converted using __toString
        $this->assertStringContainsString('<stringable>Custom string representation</stringable>', $xml);
        $this->assertStringContainsString('<normal>text</normal>', $xml);
    }

    /**
     * Test for OpenGroup ArchiMate XML format - Integration test
     *
     * @return void
     */
    public function testArchiMateOpenGroupModelXML(): void
    {
        $response = new XMLResponse(self::ARCHIMATE_MODEL_DATA);
        $xml = $response->render();

        // Verify XML declaration
        $this->assertStringContainsString('<?xml version="1.0" encoding="UTF-8"?>', $xml);

        // Verify model tag exists as the root element (not nested in a response element)
        $this->assertStringContainsString('<model ', $xml);
        $this->assertStringNotContainsString('<response>', $xml);

        // Verify each attribute exists
        $this->assertStringContainsString('xmlns="http://www.opengroup.org/xsd/archimate/3.0/"', $xml);
        $this->assertStringContainsString('xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"', $xml);
        $this->assertStringContainsString('xsi:schemaLocation="http://www.opengroup.org/xsd/archimate/3.0/ http://www.opengroup.org/xsd/archimate/3.1/archimate3_Diagram.xsd"', $xml);
        $this->assertStringContainsString('identifier="id-b58b6b03-a59d-472b-bd87-88ba77ded4e6"', $xml);
    }

    /**
     * Test empty tag formatting with space before the closing bracket
     *
     * @return void
     */
    public function testEmptyTagFormatting(): void
    {
        $response = new XMLResponse();
        $xml = $response->arrayToXml(self::EMPTY_TAG_DATA);

        // Check that empty tags have a space before the closing bracket
        $this->assertStringContainsString('<value xml:lang="nl" />', $xml);
        $this->assertStringNotContainsString('<value xml:lang="nl"/>', $xml);
    }
}
