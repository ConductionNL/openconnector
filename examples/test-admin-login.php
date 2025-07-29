<?php

/**
 * Manual test script for admin login endpoint
 *
 * This script demonstrates how to test the login endpoint with admin credentials.
 * It can be used for manual testing and verification of the login functionality.
 *
 * @category  Example
 * @package   OpenConnector
 * @author    Conduction <info@conduction.nl>
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   1.0.0
 * @link      https://github.com/ConductionNL/openconnector
 */

// Configuration for testing
$baseUrl = 'http://localhost:8080/apps/openconnector'; // Adjust to your Nextcloud URL
$adminUsername = 'admin'; // Default admin username
$adminPassword = 'admin'; // Replace with actual admin password

/**
 * Test the login endpoint with admin credentials
 *
 * This function demonstrates how to make a login request to the API
 * and handle the response including CORS headers.
 *
 * @param string $baseUrl The base URL of the OpenConnector app
 * @param string $username The admin username
 * @param string $password The admin password
 * @return array The response data
 */
function testAdminLogin(string $baseUrl, string $username, string $password): array
{
    $loginUrl = $baseUrl . '/api/user/login';
    
    // Prepare login data
    $loginData = [
        'username' => $username,
        'password' => $password
    ];
    
    // Initialize cURL session
    $ch = curl_init();
    
    // Set cURL options
    curl_setopt_array($ch, [
        CURLOPT_URL => $loginUrl,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($loginData),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json',
            'Origin: https://localhost:3000', // Test CORS
        ],
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false, // For local testing only
        CURLOPT_TIMEOUT => 30,
        CURLOPT_VERBOSE => true
    ]);
    
    // Execute the request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    
    // Check for cURL errors
    if (curl_error($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new \Exception("cURL Error: $error");
    }
    
    curl_close($ch);
    
    // Separate headers and body
    $headers = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    
    // Parse headers
    $headerLines = explode("\n", $headers);
    $parsedHeaders = [];
    foreach ($headerLines as $line) {
        if (strpos($line, ':') !== false) {
            [$key, $value] = explode(':', $line, 2);
            $parsedHeaders[trim($key)] = trim($value);
        }
    }
    
    // Decode JSON body
    $bodyData = json_decode($body, true);
    
    return [
        'http_code' => $httpCode,
        'headers' => $parsedHeaders,
        'body' => $bodyData,
        'raw_response' => $response
    ];
}

/**
 * Test the /me endpoint after login
 *
 * This function tests the /me endpoint to verify the session was created
 * and the user data is accessible.
 *
 * @param string $baseUrl The base URL of the OpenConnector app
 * @param string $sessionCookie The session cookie from login
 * @return array The response data
 */
function testMeEndpoint(string $baseUrl, string $sessionCookie = ''): array
{
    $meUrl = $baseUrl . '/api/user/me';
    
    // Initialize cURL session
    $ch = curl_init();
    
    // Set cURL options
    curl_setopt_array($ch, [
        CURLOPT_URL => $meUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json',
            'Origin: https://localhost:3000', // Test CORS
        ],
        CURLOPT_COOKIE => $sessionCookie,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false, // For local testing only
        CURLOPT_TIMEOUT => 30
    ]);
    
    // Execute the request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    
    // Check for cURL errors
    if (curl_error($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new \Exception("cURL Error: $error");
    }
    
    curl_close($ch);
    
    // Separate headers and body
    $headers = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    
    // Decode JSON body
    $bodyData = json_decode($body, true);
    
    return [
        'http_code' => $httpCode,
        'body' => $bodyData,
        'raw_response' => $response
    ];
}

/**
 * Main test execution
 */
try {
    echo "Testing OpenConnector Admin Login Endpoint\n";
    echo "==========================================\n\n";
    
    // Test admin login
    echo "1. Testing admin login...\n";
    $loginResponse = testAdminLogin($baseUrl, $adminUsername, $adminPassword);
    
    echo "HTTP Status Code: " . $loginResponse['http_code'] . "\n";
    
    // Check CORS headers
    $corsHeaders = [
        'Access-Control-Allow-Origin',
        'Access-Control-Allow-Methods',
        'Access-Control-Allow-Headers'
    ];
    
    echo "\nCORS Headers:\n";
    foreach ($corsHeaders as $header) {
        if (isset($loginResponse['headers'][$header])) {
            echo "  $header: " . $loginResponse['headers'][$header] . "\n";
        }
    }
    
    echo "\nResponse Body:\n";
    if ($loginResponse['body']) {
        echo json_encode($loginResponse['body'], JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "No JSON response body\n";
    }
    
    // Test session validation with /me endpoint
    if ($loginResponse['http_code'] === 200) {
        echo "\n2. Testing /me endpoint...\n";
        
        // Extract session cookie if available
        $sessionCookie = '';
        if (isset($loginResponse['headers']['Set-Cookie'])) {
            $sessionCookie = $loginResponse['headers']['Set-Cookie'];
        }
        
        $meResponse = testMeEndpoint($baseUrl, $sessionCookie);
        echo "HTTP Status Code: " . $meResponse['http_code'] . "\n";
        echo "Response Body:\n";
        if ($meResponse['body']) {
            echo json_encode($meResponse['body'], JSON_PRETTY_PRINT) . "\n";
        } else {
            echo "No JSON response body\n";
        }
    }
    
    echo "\nTest completed successfully!\n";
    
} catch (\Exception $e) {
    echo "Test failed: " . $e->getMessage() . "\n";
    exit(1);
}

/**
 * Usage instructions
 */
echo "\n";
echo "Usage Instructions:\n";
echo "===================\n";
echo "1. Update the \$baseUrl variable to match your Nextcloud installation\n";
echo "2. Update the \$adminUsername and \$adminPassword with valid admin credentials\n";
echo "3. Run this script: php examples/test-admin-login.php\n";
echo "4. Check the output for successful login and CORS headers\n\n";

echo "Expected successful response:\n";
echo "- HTTP Status Code: 200\n";
echo "- CORS headers should be present\n";
echo "- Response should contain user data with admin privileges\n";
echo "- Session should be created for subsequent requests\n"; 