# Browser-Based Authentication with OpenConnector API

This guide explains how to authenticate with the OpenConnector API from a web browser using session cookies and AJAX/fetch requests.

## Overview

The OpenConnector API supports two authentication methods:
1. **Session-based authentication** (recommended for browsers)
2. **HTTP Basic Authentication** (recommended for server-to-server)

For browser applications, session-based authentication using cookies is the preferred method as it's more secure and follows web standards.

## The Authentication Flow

### 1. Login Process

When you make a POST request to `/api/user/login`, the server:
- Validates credentials
- Creates a session
- Returns session cookies in the response headers
- These cookies are automatically stored by the browser

### 2. Authenticated Requests

For subsequent API calls:
- The browser automatically includes session cookies
- The server recognizes the session and authenticates the user
- No need to manually handle tokens or credentials

## Key Requirements for Browser Authentication

### 1. CORS Configuration

The server must be configured to:
- Allow credentials in cross-origin requests
- Set `Access-Control-Allow-Credentials: true`
- Specify exact origins (not wildcards when using credentials)

### 2. Fetch Configuration

All requests must include:
```javascript
credentials: 'include'  // This tells the browser to include cookies
```

### 3. Same-Site Cookie Handling

Nextcloud sets cookies with `SameSite=Lax`, which works for most scenarios but may require additional configuration for cross-site requests.

## React Authentication Examples

### Basic Authentication Hook

```javascript
import { useState, useEffect, createContext, useContext } from 'react';

// Create authentication context
const AuthContext = createContext();

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
};

// Authentication provider component
export const AuthProvider = ({ children, apiBaseUrl }) => {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  // Check if user is already logged in when component mounts
  useEffect(() => {
    checkAuthStatus();
  }, []);

  const checkAuthStatus = async () => {
    try {
      const response = await fetch(`${apiBaseUrl}/api/user/me`, {
        method: 'GET',
        credentials: 'include', // Include cookies
        headers: {
          'Content-Type': 'application/json',
        },
      });

      if (response.ok) {
        const userData = await response.json();
        setUser(userData);
      } else {
        setUser(null);
      }
    } catch (err) {
      console.error('Auth check failed:', err);
      setUser(null);
    } finally {
      setLoading(false);
    }
  };

  const login = async (username, password) => {
    setLoading(true);
    setError(null);

    try {
      const response = await fetch(`${apiBaseUrl}/api/user/login`, {
        method: 'POST',
        credentials: 'include', // Include cookies
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ username, password }),
      });

      if (response.ok) {
        const result = await response.json();
        setUser(result.user);
        return { success: true, user: result.user };
      } else {
        const errorData = await response.json();
        setError(errorData.error || 'Login failed');
        return { success: false, error: errorData.error };
      }
    } catch (err) {
      const errorMessage = 'Login request failed';
      setError(errorMessage);
      return { success: false, error: errorMessage };
    } finally {
      setLoading(false);
    }
  };

  const logout = async () => {
    try {
      // Note: You might want to implement a logout endpoint
      // For now, we'll just clear the local state
      setUser(null);
      
      // Optional: Call a logout endpoint if available
      // await fetch(`${apiBaseUrl}/api/user/logout`, {
      //   method: 'POST',
      //   credentials: 'include',
      // });
    } catch (err) {
      console.error('Logout failed:', err);
    }
  };

  const value = {
    user,
    loading,
    error,
    login,
    logout,
    checkAuthStatus,
    isAuthenticated: !!user,
  };

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
};
```

### Login Component

```javascript
import React, { useState } from 'react';
import { useAuth } from './AuthProvider';

const LoginForm = () => {
  const [username, setUsername] = useState('');
  const [password, setPassword] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);
  const { login, error, loading } = useAuth();

  const handleSubmit = async (e) => {
    e.preventDefault();
    setIsSubmitting(true);

    const result = await login(username, password);
    
    if (result.success) {
      console.log('Login successful:', result.user);
      // Redirect or update UI as needed
    } else {
      console.error('Login failed:', result.error);
    }
    
    setIsSubmitting(false);
  };

  return (
    <form onSubmit={handleSubmit} className="login-form">
      <div className="form-group">
        <label htmlFor="username">Username:</label>
        <input
          id="username"
          type="text"
          value={username}
          onChange={(e) => setUsername(e.target.value)}
          required
          disabled={isSubmitting || loading}
        />
      </div>
      
      <div className="form-group">
        <label htmlFor="password">Password:</label>
        <input
          id="password"
          type="password"
          value={password}
          onChange={(e) => setPassword(e.target.value)}
          required
          disabled={isSubmitting || loading}
        />
      </div>

      {error && (
        <div className="error-message">
          {error}
        </div>
      )}

      <button 
        type="submit" 
        disabled={isSubmitting || loading}
        className="login-button"
      >
        {isSubmitting ? 'Logging in...' : 'Login'}
      </button>
    </form>
  );
};

export default LoginForm;
```

### Authenticated API Requests

```javascript
import { useAuth } from './AuthProvider';

const ApiService = (apiBaseUrl) => {
  // Generic function for making authenticated API requests
  const makeAuthenticatedRequest = async (endpoint, options = {}) => {
    const defaultOptions = {
      credentials: 'include', // Always include cookies
      headers: {
        'Content-Type': 'application/json',
        ...options.headers,
      },
    };

    const response = await fetch(`${apiBaseUrl}${endpoint}`, {
      ...defaultOptions,
      ...options,
    });

    if (!response.ok) {
      throw new Error(`API request failed: ${response.status} ${response.statusText}`);
    }

    return response.json();
  };

  return {
    // Get user info
    getMe: () => makeAuthenticatedRequest('/api/user/me'),
    
    // Update user info
    updateMe: (userData) => makeAuthenticatedRequest('/api/user/me', {
      method: 'PUT',
      body: JSON.stringify(userData),
    }),

    // Example: Get some other data
    getData: (endpoint) => makeAuthenticatedRequest(endpoint),
    
    // Example: Post data
    postData: (endpoint, data) => makeAuthenticatedRequest(endpoint, {
      method: 'POST',
      body: JSON.stringify(data),
    }),
  };
};

// Component using the API service
const UserProfile = () => {
  const { user, isAuthenticated } = useAuth();
  const [profile, setProfile] = useState(null);
  const [loading, setLoading] = useState(false);
  const api = ApiService('http://localhost:8080'); // Your API base URL

  useEffect(() => {
    if (isAuthenticated) {
      loadProfile();
    }
  }, [isAuthenticated]);

  const loadProfile = async () => {
    setLoading(true);
    try {
      const profileData = await api.getMe();
      setProfile(profileData);
    } catch (error) {
      console.error('Failed to load profile:', error);
    } finally {
      setLoading(false);
    }
  };

  if (!isAuthenticated) {
    return <div>Please log in to view your profile.</div>;
  }

  if (loading) {
    return <div>Loading profile...</div>;
  }

  return (
    <div className="user-profile">
      <h2>User Profile</h2>
      {profile && (
        <div>
          <p><strong>Username:</strong> {profile.uid}</p>
          <p><strong>Display Name:</strong> {profile.displayName}</p>
          <p><strong>Email:</strong> {profile.email}</p>
          <p><strong>Organizations:</strong> {profile.organisations?.length || 0}</p>
        </div>
      )}
    </div>
  );
};

export default UserProfile;
```

### Complete App Example

```javascript
import React from 'react';
import { AuthProvider, useAuth } from './AuthProvider';
import LoginForm from './LoginForm';
import UserProfile from './UserProfile';

const AppContent = () => {
  const { isAuthenticated, user, logout, loading } = useAuth();

  if (loading) {
    return <div>Loading...</div>;
  }

  return (
    <div className="app">
      <header>
        <h1>OpenConnector Demo</h1>
        {isAuthenticated && (
          <div className="user-info">
            Welcome, {user?.displayName || user?.uid}!
            <button onClick={logout} className="logout-button">
              Logout
            </button>
          </div>
        )}
      </header>

      <main>
        {isAuthenticated ? (
          <UserProfile />
        ) : (
          <LoginForm />
        )}
      </main>
    </div>
  );
};

const App = () => {
  return (
    <AuthProvider apiBaseUrl="http://localhost:8080">
      <AppContent />
    </AuthProvider>
  );
};

export default App;
```

## Common Issues and Solutions

### 1. CORS Errors

If you see CORS errors, ensure:
- The server sets `Access-Control-Allow-Credentials: true`
- The server specifies the exact origin (not `*`)
- Your requests include `credentials: 'include'`

### 2. Cookies Not Being Sent

Check that:
- You're using `credentials: 'include'` in all fetch requests
- The domain/port matches between your app and API
- Cookies aren't being blocked by browser security settings

### 3. Session Not Persisting

Verify that:
- The login endpoint properly establishes a session
- Session cookies have the correct path and domain
- The server session storage is working

### 4. Development vs Production

For development with different ports:
```javascript
// Development configuration
const API_BASE_URL = 'http://localhost:8080';

// Ensure CORS is properly configured on the server
// for requests from http://localhost:3000 (or your dev server port)
```

For production:
```javascript
// Production configuration
const API_BASE_URL = ''; // Use relative URLs or same domain
```

## Testing the Authentication

You can test the authentication flow using browser developer tools:

1. **Open Network tab** in DevTools
2. **Make a login request** - you should see:
   - POST to `/api/user/login`
   - Response includes `Set-Cookie` headers
3. **Make a `/me` request** - you should see:
   - GET to `/api/user/me`
   - Request includes `Cookie` header
   - Response returns user data

## Security Considerations

1. **Always use HTTPS in production** to protect session cookies
2. **Set secure cookie flags** on the server for production
3. **Implement proper CSRF protection** if needed
4. **Consider cookie expiration** and session timeout
5. **Handle authentication errors gracefully**

## Example Server CORS Configuration

The server should return these headers for browser authentication:

```
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS
Access-Control-Allow-Headers: Content-Type, Authorization
```

Note: Replace `http://localhost:3000` with your actual frontend URL. 