# 🚀 Laravel Error Logging with Elasticsearch

<div align="center">

![Laravel](https://img.shields.io/badge/Laravel-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![Elasticsearch](https://img.shields.io/badge/Elasticsearch-005571?style=for-the-badge&logo=elasticsearch&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)

[![Medium](https://img.shields.io/badge/Medium-12100E?style=for-the-badge&logo=medium&logoColor=white)](https://medium.com/@murilolivorato)
[![GitHub Stars](https://img.shields.io/github/stars/murilolivorato/laravel_sso?style=social)](https://github.com/murilolivorato/laravel_sso/stargazers)

</div>

This implementation automatically captures every error in your Laravel application and stores it in Elasticsearch, providing powerful search capabilities, real-time monitoring, and comprehensive error analytics that will transform how you debug and maintain your applications.

<p align="center">
<img src="https://cdn-images-1.medium.com/max/800/1*ELqsEByJyJtdRFdI_SRH8g.png" alt="Error Logging Dashboard" width="800"/>
</p>

# 🏷️ This file is part of a series of articles on Medium

> - [📖 **How to Integrate Elasticsearch with Laravel: A Step-by-Step Guide**](https://medium.com/@murilolivorato/how-to-integrate-elasticsearch-with-laravel-a-step-by-step-guide-e98f0cec7d9a)
> - [Using Elasticsearch for Effective Laravel Error Logging](https://medium.com/@murilolivorato/using-elasticsearch-for-effective-laravel-error-logging-b7d34bf0db90)
> - [Mastering Exception Handling in Laravel 12: Centralized Error Management Guide](https://medium.com/@murilolivorato/mastering-exception-handling-in-laravel-12-centralized-error-management-guide-95df500cb4ba)
## 📋 Table of Contents

- [✨ Features](#-features)
- [🔧 Prerequisites](#-prerequisites)
- [🚀 Installation](#-installation)
- [⚙️ Configuration](#️-configuration)
- [💻 Usage](#-usage)
- [🛡️ Security Features](#️-security-features)
- [📊 Monitoring & Analytics](#-monitoring--analytics)
- [🔍 API Reference](#-api-reference)
- [⭐ Best Practices](#-best-practices)
- [🔧 Troubleshooting](#-troubleshooting)
- [🤝 Contributing](#-contributing)
- [📚 Related Articles](#-related-articles)

## ✨ Features

### 🔍 Comprehensive Error Tracking
- 🎯 **Automatic Exception Handling** - Catches and processes all Laravel exceptions
- 🏷️ **Smart Error Categorization** - Automatically tags errors by type, status, environment
- 📝 **Request Context Capture** - Logs complete request information including headers, parameters, and user context
- ⚡ **Performance Metrics** - Tracks response time and memory usage for each error

### 🛡️ Security-First Design
- 🔒 **Data Sanitization** - Automatically removes sensitive data (passwords, tokens, API keys)
- 🚫 **Filtered Stack Traces** - Removes sensitive function arguments from traces
- 🛣️ **Path Sanitization** - Strips system paths from file references
- 🛡️ **Header Protection** - Redacts authentication and session headers

### 📊 Advanced Analytics
- 🔎 **Real-time Search** - Powerful Elasticsearch queries for error analysis
- 📈 **Error Trends** - Track error patterns over time
- 👥 **User Impact Analysis** - Identify which users are affected by errors
- 🌍 **Environment Comparison** - Compare error rates across environments

### 🎯 Exception-Specific Handling
- ✅ **Authentication Exceptions** - Secure handling of auth failures
- ✅ **Validation Exceptions** - Detailed validation error tracking
- ✅ **Model Not Found Exceptions** - Resource not found handling
- ✅ **Authorization Exceptions** - Permission error management
- ✅ **HTTP Exceptions** - HTTP status code tracking
- ✅ **Database Query Exceptions** - SQL error monitoring
- ✅ **Custom Exception Support** - Extensible for custom exceptions

## 🔧 Prerequisites

- 🐘 PHP 8.2 or higher
- 🎯 Laravel 11.x (compatible with 10.x)
- 🔍 Elasticsearch 8.x
- 📦 Composer for dependency management

## 🚀 Installation

```bash
# Install Elasticsearch package
composer require elasticsearch/elasticsearch

2. **Laravel Configuration**
   - Add Elasticsearch package
   - Configure environment variables
   - Set up service provider

3. **Index Design**
   Create an Elasticsearch index with the following structure:
   ```json
   {
     "mappings": {
       "properties": {
         "type": { "type": "keyword" },
         "message": { "type": "text" },
         "status": { "type": "integer" },
         "uri": { "type": "keyword" },
         "method": { "type": "keyword" },
         "user": { "type": "object" },
         "ip": { "type": "ip" },
         "trace": { "type": "text" },
         "timestamp": { "type": "date" },
         "environment": { "type": "keyword" },
         "file": { "type": "keyword" },
         "line": { "type": "integer" },
         "request_data": { "type": "object" },
         "headers": { "type": "object" },
         "response_time": { "type": "float" },
         "memory_usage": { "type": "integer" },
         "tags": { "type": "keyword" }
       }
     }
   }
   ```

## ⚙️ Configuration

### Environment Variables
```env
ELASTICSEARCH_HOST=http://localhost:9200
ELASTICSEARCH_USERNAME=elastic
ELASTICSEARCH_PASSWORD=your_secure_password
```

### Service Configuration
```php
'elk' => [
    'host_ip' => env('ELASTICSEARCH_HOST', 'http://localhost:9200'),
    'username' => env('ELASTICSEARCH_USERNAME', 'elastic'),
    'password' => env('ELASTICSEARCH_PASSWORD', ''),
],
```

## 💻 Usage Example

### Sample Error Log Entry
```json
{
   "_index": "error-logs",
   "_type": "_doc",
   "_id": "a7b8c9d0e1f2g3h4i5j6k7l8m9n0o1p2",
   "_score": 1.0,
   "_source": {
      "id": "d5e8a7b2c9f4e6a1b3d7f8e2c5a9b4d6",
      "type": "ValidationException",
      "message": "The email field is required.",
      "status": 422,
      "uri": "/api/users",
      "method": "POST",
      "user": {
         "id": "12345",
         "type": "sanctum_user",
         "name": "John Doe",
         "email": "john.doe@example.com"
      },
      "ip": "192.168.1.100",
      "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36",
      "trace": "[{\"file\":\"/app/Http/Controllers/UserController.php\",\"line\":45,\"function\":\"validate\",\"class\":\"Illuminate\\\\Http\\\\Request\"},{\"file\":\"/app/Http/Controllers/UserController.php\",\"line\":23,\"function\":\"store\",\"class\":\"App\\\\Http\\\\Controllers\\\\UserController\"},{\"file\":\"/vendor/laravel/framework/src/Illuminate/Routing/Controller.php\",\"line\":54,\"function\":\"callAction\"}]",
      "timestamp": "2025-06-02T14:32:15.847Z",
      "environment": "production",
      "file": "/app/Http/Requests/CreateUserRequest.php",
      "line": 28,
      "context": {
         "request_id": "req_9f8e7d6c5b4a3210fedcba0987654321",
         "session_id": "sess_abcd1234efgh5678ijkl9012mnop3456",
         "correlation_id": "corr_xyz789abc123def456ghi789jkl012"
      },
      "request_data": {
         "input": {
            "name": "John Doe",
            "phone": "+1234567890",
            "password": "[REDACTED]"
         },
         "query": {
            "source": "mobile_app",
            "version": "1.2.3"
         },
         "files": []
      },
      "headers": {
         "host": ["api.example.com"],
         "user-agent": ["Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36"],
         "accept": ["application/json"],
         "content-type": ["application/json"],
         "authorization": ["[REDACTED]"],
         "x-requested-with": ["XMLHttpRequest"],
         "x-api-version": ["v1"],
         "cookie": ["[REDACTED]"]
      },
      "response_time": 245.67,
      "memory_usage": 8388608,
      "tags": [
         "production",
         "validationexception",
         "post",
         "client_error",
         "api",
         "authenticated"
      ]
   }
}
```


   

## Best Practices

1. **Security**
   - Never log sensitive data
   - Implement proper data sanitization
   - Use secure authentication
   - Limit stack trace information
   - Filter request data


## ⭐ Best Practices

### 🔒 Security
- 🚫 Never log sensitive data
- 🛡️ Implement proper data sanitization
- 🔐 Use secure authentication
- 📝 Limit stack trace information
- 🧹 Filter request data

### ⚡ Performance
- 📊 Implement proper indexing
- 📦 Use bulk operations when possible
- 🔄 Implement connection pooling
- ⚠️ Add proper error handling
- 🔄 Include fallback mechanisms

### 🔧 Maintenance
- 📅 Implement index lifecycle management
- 📊 Set up proper monitoring
- 💾 Create backup strategies
- 📝 Document error patterns
- 🔍 Regular security audits

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.


<div align="center">
  <h3>⭐ Star This Repository ⭐</h3>
  <p>Your support helps us improve and maintain this project!</p>
  <a href="https://github.com/murilolivorato/laravel_sso/stargazers">
    <img src="https://img.shields.io/github/stars/murilolivorato/laravel_sso?style=social" alt="GitHub Stars">
  </a>
</div>



