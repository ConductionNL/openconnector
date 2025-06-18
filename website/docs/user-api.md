# User API Endpoints

The User API provides JSON-based endpoints for external applications to interact with user data without requiring form posts. This API is designed to facilitate easier integration with external systems and includes comprehensive security measures to protect against XSS attacks and brute force attempts.

## Architecture

The User API follows a clean service-oriented architecture:

- **UserController**: Thin controller handling HTTP requests/responses and security
- **UserService**: Business logic for user data management and custom field handling  
- **SecurityService**: Comprehensive protection against XSS and brute force attacks
- **Cross-app compatibility**: Custom name fields (firstName, lastName, middleName) stored in 'core' namespace for access by other NextCloud apps

### Custom Fields Accessibility

The custom name fields are stored in the 'core' namespace making them accessible to other NextCloud apps:

```php
// Any NextCloud app can access these fields:
$config = \OC::$server->getConfig();
$firstName = $config->getUserValue($userId, 'core', 'firstName', '');
$lastName = $config->getUserValue($userId, 'core', 'lastName', '');
$middleName = $config->getUserValue($userId, 'core', 'middleName', '');
```

See [examples/other-app-integration.php](../../examples/other-app-integration.php) for complete integration examples.

## Security Features

🔒 **Enterprise-Grade Security Protection:**
- **Rate Limiting**: 5 attempts per 15-minute window (per user and per IP)
- **Progressive Delays**: Exponential backoff (2s → 4s → 8s → 16s → 32s → 60s max)
- **Account Lockout**: 1-hour automatic lockout after failed attempts
- **IP Blocking**: Suspicious IP addresses are temporarily blocked
- **XSS Protection**: Comprehensive input sanitization and security headers
- **Security Logging**: All authentication events are logged for monitoring

For detailed security information, see [Security Best Practices](security-best-practices.md).

## Available Endpoints

### GET /api/user/me

Retrieves the current authenticated user's information in JSON format including user groups and complete profile data.

**Authentication Required:** Yes

**Features:**
- ✅ Complete user profile information
- ✅ User groups and permissions (`groups` array contains group IDs)
- ✅ Quota information with actual usage calculation
- ✅ Complete profile fields (phone, address, website, twitter, fediverse, organisation, role, headline, biography) when set
- ✅ Smart language/locale detection with fallbacks
- ✅ Backend capabilities and restrictions
- ✅ Email verification status
- ✅ Security headers included

**Request:**
```http
GET /api/user/me
Content-Type: application/json
```

**Response (Success - 200):**
```json
{
  'uid': 'john.doe',
  'displayName': 'John Doe',
  'email': 'john.doe@example.com',
  'enabled': true,
  'quota': {
    'free': '5 GB',
    'used': 1073741824,
    'total': 5368709120,
    'relative': 20.0
  },
  'avatarScope': 'contacts',
  'lastLogin': 1640995200,
      'backend': 'Database',
    'subadmin': [],
    'groups': ['users', 'admin'],
    'language': 'en',
  'locale': 'en_US',
  'firstName': 'Ruben',           // Optional: if set in profile
  'lastName': 'van der Linde',    // Optional: if set in profile
  'middleName': '2',              // Optional: if set in profile
  'phone': '+31645536677',        // Optional: if set in profile
  'address': 'Amsterdam',         // Optional: if set in profile
  'website': 'https://example.com', // Optional: if set in profile
  'twitter': '@username',         // Optional: if set in profile
  'fediverse': '@user@mastodon.social', // Optional: if set in profile
  'organisation': 'Conduction',   // Optional: if set in profile
  'role': 'Directeur',           // Optional: if set in profile
  'headline': 'Your headline',    // Optional: if set in profile
  'biography': 'Your biography',  // Optional: if set in profile
  'backendCapabilities': {
    'displayName': true,
    'email': true,
    'password': true,
    'avatar': true
  }
}
```

**Response (Error - 401):**
```json
{
  'error': 'User not authenticated'
}
```

### PUT /api/user/me

Updates the current authenticated user's information based on the provided JSON data.

**Authentication Required:** Yes

**Request:**
```http
PUT /api/user/me
Content-Type: application/json

{
  'displayName': 'Updated Name',
  'email': 'newemail@example.com',
  'firstName': 'John',
  'lastName': 'Doe',
  'middleName': 'William',
  'language': 'nl',
  'locale': 'nl_NL'
}
```

**Response (Success - 200):**
```json
{
  'uid': 'john.doe',
  'displayName': 'Updated Name',
  'email': 'newemail@example.com',
  'enabled': true,
  'quota': {
    'free': '5 GB',
    'used': 1073741824,
    'total': 5368709120,
    'relative': 20.0
  },
  'avatarScope': 'contacts',
  'lastLogin': 1640995200,
  'backend': 'Database',
  'subadmin': [],
  'groups': ['users', 'admin'],
  'language': 'nl',
  'locale': 'nl_NL',
  'firstName': 'John',            // Optional: if set in profile
  'lastName': 'Doe',              // Optional: if set in profile
  'middleName': 'William',        // Optional: if set in profile
  'phone': '+31645536677',        // Optional: if set in profile
  'address': 'Amsterdam',         // Optional: if set in profile
  'website': 'https://example.com', // Optional: if set in profile
  'twitter': '@username',         // Optional: if set in profile
  'fediverse': '@user@mastodon.social', // Optional: if set in profile
  'organisation': 'Conduction',   // Optional: if set in profile
  'role': 'Directeur',           // Optional: if set in profile
  'headline': 'Your headline',    // Optional: if set in profile
  'biography': 'Your biography',  // Optional: if set in profile
  'backendCapabilities': {
    'displayName': true,
    'email': true,
    'password': true,
    'avatar': true
  }
}
```

**Response (Error - 401):**
```json
{
  'error': 'User not authenticated'
}
```

**Updatable Fields:**
- `displayName`: User's display name (if backend allows)
- `email`: User's email address (if backend allows)
- `password`: User's password (if backend allows)
- `firstName`: User's first name (stored in core namespace, accessible to other apps)
- `lastName`: User's last name (stored in core namespace, accessible to other apps)
- `middleName`: User's middle name (stored in core namespace, accessible to other apps)
- `phone`: User's phone number (stored in AccountManager)
- `address`: User's address (stored in AccountManager)
- `website`: User's website URL (stored in AccountManager)
- `twitter`: User's Twitter handle (stored in AccountManager)
- `fediverse`: User's Fediverse handle (stored in AccountManager)
- `organisation`: User's organization (stored in AccountManager)
- `role`: User's role/job title (stored in AccountManager)
- `headline`: User's headline (stored in AccountManager)
- `biography`: User's biography (stored in AccountManager)
- `language`: User's preferred language
- `locale`: User's locale setting

### POST /api/user/login

Securely authenticates a user using username/email and password combination with comprehensive protection against brute force attacks and XSS.

**Authentication Required:** No (Public endpoint - creates authentication)

**🔒 Security Features:**
- ✅ Input validation and sanitization (XSS protection)
- ✅ Rate limiting: 5 attempts per 15-minute window (per user and IP)
- ✅ Progressive delays: Exponential backoff (2s → 4s → 8s → 16s → 32s → 60s max)
- ✅ Account lockout: 1-hour automatic lockout after threshold
- ✅ IP blocking: Suspicious IP addresses temporarily blocked
- ✅ Security event logging for monitoring
- ✅ Comprehensive security headers

**Request:**
```http
POST /api/user/login
Content-Type: application/json

{
  'username': 'john.doe',
  'password': 'secretpassword'
}
```

**Response (Success - 200):**
```json
{
  'message': 'Login successful',
  'user': {
    'uid': 'john.doe',
    'displayName': 'John Doe',
    'email': 'john.doe@example.com',
    'enabled': true,
    'quota': {
      'free': '5 GB',
      'used': 1073741824,
      'total': 5368709120,
      'relative': 20.0
    },
    'avatarScope': 'contacts',
    'lastLogin': 1640995200,
    'backend': 'Database',
    'subadmin': [],
    'groups': ['users', 'admin'],
    'language': 'en',
    'locale': 'en_US',
    'firstName': 'John',            // Optional: if set in profile
    'lastName': 'Doe',              // Optional: if set in profile
    'middleName': 'William',        // Optional: if set in profile
    'phone': '+31645536677',        // Optional: if set in profile
    'address': 'Amsterdam',         // Optional: if set in profile
    'website': 'https://example.com', // Optional: if set in profile
    'twitter': '@username',         // Optional: if set in profile
    'fediverse': '@user@mastodon.social', // Optional: if set in profile
    'organisation': 'Conduction',   // Optional: if set in profile
    'role': 'Directeur',           // Optional: if set in profile
    'headline': 'Your headline',    // Optional: if set in profile
    'biography': 'Your biography',  // Optional: if set in profile
    'backendCapabilities': {
      'displayName': true,
      'email': true,
      'password': true,
      'avatar': true
    }
  },
  'session_created': true
}
```

**Response (Rate Limited - 429):**
```json
{
  'error': 'Too many login attempts. Please wait before trying again.',
  'retry_after': 8,
  'lockout_until': null
}
```

**Response (Account Locked - 429):**
```json
{
  'error': 'Account temporarily locked due to too many failed login attempts',
  'retry_after': null,
  'lockout_until': 1705334622
}
```

**Response (IP Blocked - 429):**
```json
{
  'error': 'IP address temporarily blocked due to suspicious activity',
  'retry_after': null,
  'lockout_until': 1705334622
}
```

**Response (Invalid Input - 400):**
```json
{
  'error': 'Username and password are required'
}
```

**Response (Invalid Credentials - 401):**
```json
{
  'error': 'Invalid username or password'
}
```

**Response (Account Disabled - 401):**
```json
{
  'error': 'Account is disabled'
}
```

**Response (System Error - 500):**
```json
{
  'error': 'Login failed due to a system error'
}
```

**Security Headers Included in All Responses:**
- `X-Frame-Options: DENY` - Prevents clickjacking
- `X-Content-Type-Options: nosniff` - Prevents MIME sniffing
- `X-XSS-Protection: 1; mode=block` - Browser XSS protection
- `Referrer-Policy: strict-origin-when-cross-origin` - Controls referrer info
- `Content-Security-Policy: default-src 'none'; frame-ancestors 'none';` - Restricts resource loading
- `Cache-Control: no-store, no-cache, must-revalidate, private` - Prevents caching

## Error Handling

All endpoints return appropriate HTTP status codes with comprehensive security considerations:

- **200 OK**: Request successful
- **400 Bad Request**: Invalid request parameters or malformed input
- **401 Unauthorized**: Authentication required, failed, or account disabled
- **429 Too Many Requests**: Rate limit exceeded, account locked, or IP blocked
- **500 Internal Server Error**: Server error occurred

**Security-Enhanced Error Responses:**

All error responses include:
- An 'error' field with a descriptive (but not revealing) message
- Security headers to prevent XSS and other attacks
- Rate limiting information when applicable (retry_after, lockout_until)
- Generic error messages to prevent information disclosure

**Rate Limiting Information:**
- `retry_after`: Seconds to wait before next attempt (progressive delay)
- `lockout_until`: Unix timestamp when lockout expires (account/IP lockout)

## Usage Examples

### JavaScript/Fetch Example

```javascript
// Get current user information
async function getCurrentUser() {
  try {
    const response = await fetch('/api/user/me', {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json'
      }
    });
    
    if (response.ok) {
      const userData = await response.json();
      console.log('Current user:', userData);
      return userData;
    } else {
      const error = await response.json();
      console.error('Error:', error.error);
    }
  } catch (error) {
    console.error('Request failed:', error);
  }
}

// Update user information
async function updateUser(updates) {
  try {
    const response = await fetch('/api/user/me', {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(updates)
    });
    
    if (response.ok) {
      const userData = await response.json();
      console.log('User updated:', userData);
      return userData;
    } else {
      const error = await response.json();
      console.error('Update failed:', error.error);
    }
  } catch (error) {
    console.error('Request failed:', error);
  }
}

// Login user
async function loginUser(username, password) {
  try {
    const response = await fetch('/api/user/login', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        username: username,
        password: password
      })
    });
    
    if (response.ok) {
      const result = await response.json();
      console.log('Login successful:', result.message);
      console.log('User data:', result.user);
      return result;
    } else {
      const error = await response.json();
      console.error('Login failed:', error.error);
    }
  } catch (error) {
    console.error('Request failed:', error);
  }
}
```

### cURL Examples

```bash
# Get current user information
curl -X GET 'https://your-nextcloud.com/api/user/me' \
  -H 'Content-Type: application/json' \
  -b 'cookies.txt'

# Update user information
curl -X PUT 'https://your-nextcloud.com/api/user/me' \
  -H 'Content-Type: application/json' \
  -b 'cookies.txt' \
  -d '{
    "displayName": "New Display Name",
    "email": "newemail@example.com"
  }'

# Login user
curl -X POST 'https://your-nextcloud.com/api/user/login' \
  -H 'Content-Type: application/json' \
  -c 'cookies.txt' \
  -d '{
    "username": "john.doe",
    "password": "secretpassword"
  }'
```

## Security Considerations

- The login endpoint is public but should be used over HTTPS in production
- Session management is handled automatically by NextCloud
- User updates are restricted based on backend capabilities
- All user data is validated before processing
- Sensitive operations require proper authentication

## Notes

- The API follows RESTful conventions
- All responses are in JSON format
- User capabilities depend on the NextCloud backend configuration
- Groups and subadmin information require additional services and are currently returned as empty arrays
- Quota calculations are based on available NextCloud user data 