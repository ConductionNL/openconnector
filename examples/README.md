# Testing Admin Login Endpoint

This directory contains examples and test scripts for testing the OpenConnector admin login functionality.

## Overview

The UserController now includes comprehensive CORS support and enhanced security features for the login and user management endpoints. This enables cross-origin requests from web applications while maintaining security.

## Files

- **test-admin-login.php** - Manual test script for testing admin login with CORS
- **README.md** - This documentation file

## Quick Test Guide

### 1. Manual Testing with cURL

Test the admin login endpoint directly:

```bash
# Test login endpoint
curl -X POST http://localhost:8080/apps/openconnector/api/user/login \
  -H "Content-Type: application/json" \
  -H "Origin: https://localhost:3000" \
  -d '{"username":"admin","password":"admin"}' \
  -v

# Test OPTIONS preflight (CORS)
curl -X OPTIONS http://localhost:8080/apps/openconnector/api/user/login \
  -H "Origin: https://localhost:3000" \
  -v
```

### 2. Using the Test Script

```bash
# Run the PHP test script
php examples/test-admin-login.php
```

Make sure to update the configuration in the script:
- `$baseUrl` - Your Nextcloud installation URL
- `$adminUsername` - Admin username (usually 'admin')
- `$adminPassword` - Admin password

### 3. Running Unit Tests

```bash
# Run the UserController tests
./vendor/bin/phpunit tests/Unit/Controller/UserControllerTest.php
```

## Expected Responses

### Successful Login Response
```json
{
    "message": "Login successful",
    "user": {
        "uid": "admin",
        "displayName": "Administrator", 
        "email": "admin@example.com",
        "enabled": true,
        "isAdmin": true,
        "groups": ["admin"],
        "permissions": ["all"]
    },
    "session_created": true
}
```

### CORS Headers
The response should include these CORS headers:
```
Access-Control-Allow-Origin: https://localhost:3000
Access-Control-Allow-Methods: PUT, POST, GET, DELETE, PATCH
Access-Control-Allow-Headers: Authorization, Content-Type, Accept
```

### Error Responses

**Invalid Credentials (401)**
```json
{
    "error": "Invalid username or password"
}
```

**Validation Error (400)**
```json
{
    "error": "Username and password are required"
}
```

**Rate Limited (429)**
```json
{
    "error": "Too many login attempts",
    "retry_after": 60,
    "lockout_until": "2024-01-01T12:00:00Z"
}
```

## Testing Checklist

- [ ] Login with valid admin credentials returns 200
- [ ] Login response includes user data with admin privileges
- [ ] CORS headers are present in all responses
- [ ] OPTIONS preflight requests work correctly
- [ ] Invalid credentials return proper error
- [ ] Rate limiting works for repeated failed attempts
- [ ] Session is created and can be used for /me endpoint
- [ ] /me endpoint returns user data with CORS headers
- [ ] Security headers are present in responses

## Security Features Tested

1. **Input Validation** - Username/password sanitization
2. **Rate Limiting** - Protection against brute force attacks
3. **CORS Support** - Proper cross-origin request handling
4. **Session Management** - Secure session creation
5. **Error Handling** - Generic error messages to prevent enumeration
6. **Security Headers** - XSS and other security protections

## Troubleshooting

### Common Issues

1. **CORS Errors**: Ensure the Origin header matches expected domains
2. **404 Errors**: Check that routes are properly registered
3. **500 Errors**: Verify all dependencies are properly injected
4. **Auth Errors**: Confirm admin credentials are correct

### Debug Tips

- Enable verbose cURL output with `-v` flag
- Check Nextcloud logs for detailed error information
- Verify app is properly installed and enabled
- Test with simple credentials first (admin/admin)

## Development Notes

The UserController includes several new features:
- CORS preflight handling via `preflightedCors()` method
- Enhanced security with SecurityService integration
- Memory monitoring for performance
- Comprehensive error handling with proper HTTP status codes
- Security headers in all responses 