# Sales Control API

<div align="center">

![PHP](https://img.shields.io/badge/PHP-8.4-777BB4?style=for-the-badge&logo=php&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-9.0-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![Redis](https://img.shields.io/badge/Redis-7.0-DC382D?style=for-the-badge&logo=redis&logoColor=white)
![Docker](https://img.shields.io/badge/Docker-24.0-2496ED?style=for-the-badge&logo=docker&logoColor=white)
![JWT](https://img.shields.io/badge/JWT-Auth-000000?style=for-the-badge&logo=jsonwebtokens&logoColor=white)

Sales control system with automated email reporting

[Features](#features) •
[Installation](#installation) •
[API](#api-endpoints) •
[Tests](#tests)

</div>

---

## About

**Sales Control** is an API developed in Laravel for managing sales, sellers, and reports. The system provides JWT authentication, automated daily email reports, and a scalable architecture based on design patterns.

### Key Features

- JWT authentication with refresh token
- Automated daily reports sent via email at 11:00 PM
- Layered architecture (Repository + Service Pattern)
- Test coverage (Unit + Feature + Integration)
- Dockerized environment
- Redis caching for performance
- Queue system for asynchronous processing
- MailHog for email testing in development

---

## Features

### Authentication
- Login/Logout with JWT
- Automatic token refresh
- Protected routes
- User management

### Seller Management
- Complete CRUD operations
- Data validation
- Paginated and filtered listings
- Soft deletes

### Sales Control
- Sales registration
- Automatic commission calculation (8.5%)
- Sales history per seller
- Date and seller filters
- Statistics and aggregations

### Automated Reports
- Daily dispatch at 11:00 PM (America/Sao_Paulo timezone)
- Individual reports per seller
- Daily metrics (sales count, amounts, commissions)
- Responsive HTML template
- Average value per sale
- Asynchronous processing via queue
- Administrator reports

### Technical Features
- Cache with automatic invalidation
- Data Transfer Objects (DTOs)
- Dependency Injection
- Custom Service Providers
- Structured logging
- Centralized error handling

---

## Technologies

This project was developed with the following technologies:

| Technology | Version | Description |
|------------|---------|-------------|
| [PHP](https://www.php.net/) | 8.4 | Programming language |
| [Laravel](https://laravel.com/) | 12.x | PHP framework |
| [MySQL](https://www.mysql.com/) | 9.0 | Relational database |
| [Redis](https://redis.io/) | 7.0 | Cache and queue broker |
| [Docker](https://www.docker.com/) | 24.0 | Containerization |
| [Docker Compose](https://docs.docker.com/compose/) | 2.x | Container orchestration |
| [JWT Auth](https://github.com/tymondesigns/jwt-auth) | 2.2 | JWT authentication |
| [MailHog](https://github.com/mailhog/MailHog) | Latest | Email testing tool |
| [PHPUnit](https://phpunit.de/) | 11.5 | Testing framework |

---

## Requirements

- PHP 8.4+
- Composer
- MySQL 9+
- Redis 7+

---

## Installation

### 1. Clone the repository

```bash
git clone <repository-url>
cd sales-control
```

### 2. Configure the environment

Copy the example file and adjust the variables:

```bash
cp .env.example .env
```

### 3. Start Docker containers

```bash
docker-compose up -d
```

This will start the following services:

| Service | Port | Description |
|---------|------|-------------|
| **app** | 80 | Laravel application |
| **mysql** | 3306 | MySQL database |
| **redis** | 6379 | Cache and queue |
| **mailhog** | 8025 | Email web interface |
| **mailhog-smtp** | 1025 | SMTP server |

### 4. Install dependencies

```bash
docker exec -it sales-control-api composer install
```

### 5. Generate application keys

```bash
# Laravel application key
docker exec -it sales-control-api php artisan key:generate

# JWT authentication key
docker exec -it sales-control-api php artisan jwt:secret
```

### 6. Run migrations

```bash
docker exec -it sales-control-api php artisan migrate
```

### 7. (Optional) Seed test data

```bash
docker exec -it sales-control-api php artisan db:seed --class=SalesReportTestSeeder
```

### Installation Verification

Access http://localhost to verify the application is running.

To view test emails, access: **http://localhost:8025**

---

## Configuration

### Main Environment Variables

Edit the `.env` file as needed:

```env
# Application
APP_NAME="Sales Control"
APP_ENV=local
APP_DEBUG=true
APP_TIMEZONE=America/Sao_Paulo

# Database
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=sales_control
DB_USERNAME=root
DB_PASSWORD=root

# Cache and Queue
CACHE_STORE=redis
QUEUE_CONNECTION=redis
REDIS_HOST=redis
REDIS_PORT=6379

# Email (Development with MailHog)
MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="noreply@salescontrol.com"
MAIL_FROM_NAME="Sales Control"

# JWT
JWT_TTL=60
JWT_REFRESH_TTL=20160

# Sales Configuration
SALE_COMMISSION_RATE=0.085  # 8.5% commission
```

### Email Configuration for Production

To use a real SMTP provider in production, update:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
```

---

## Usage

### Starting the Application

```bash
# Start all services
docker-compose up -d

# View logs in real-time
docker-compose logs -f app

# Stop all services
docker-compose down
```

### Daily Reports System

#### Automatic Dispatch

Reports are sent automatically at 11:00 PM (America/Sao_Paulo timezone) daily. The Laravel scheduler manages this automatically.

#### Manual Dispatch

```bash
# Today's reports
docker exec -it sales-control-api php artisan reports:send-daily-sales

# Reports for a specific date
docker exec -it sales-control-api php artisan reports:send-daily-sales 2025-10-26

# Administrator reports
docker exec -it sales-control-api php artisan reports:send-daily-admin
```

#### Process the Queue

Emails are sent asynchronously. Start the queue worker:

```bash
docker exec -it sales-control-api php artisan queue:work
```

#### View Emails (MailHog)

During development, all emails are captured by MailHog:

**Web Interface:** http://localhost:8025

### Monitoring

```bash
# View logs in real-time (requires Laravel Pail)
docker exec -it sales-control-api php artisan pail

# Check scheduled tasks
docker exec -it sales-control-api php artisan schedule:list

# List failed jobs
docker exec -it sales-control-api php artisan queue:failed

# Retry failed jobs
docker exec -it sales-control-api php artisan queue:retry all
```

---

## API Endpoints

### Authentication

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| `POST` | `/api/v1/auth/login` | User login | No |
| `POST` | `/api/v1/auth/logout` | User logout | Yes |
| `POST` | `/api/v1/auth/refresh` | Refresh token | Yes |
| `GET` | `/api/v1/auth/me` | Authenticated user data | Yes |

#### Login Example

```bash
curl -X POST http://localhost/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@example.com",
    "password": "password"
  }'
```

**Response:**
```json
{
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "token_type": "bearer",
  "expires_in": 3600
}
```

### Users

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| `GET` | `/api/v1/users` | List all users | Yes |
| `POST` | `/api/v1/users` | Create new user | Yes |
| `GET` | `/api/v1/users/{id}` | Show specific user | Yes |
| `PUT` | `/api/v1/users/{id}` | Update user | Yes |
| `DELETE` | `/api/v1/users/{id}` | Delete user | Yes |

### Sellers

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| `GET` | `/api/v1/sellers` | List all sellers | Yes |
| `POST` | `/api/v1/sellers` | Create new seller | Yes |
| `GET` | `/api/v1/sellers/{id}` | Show specific seller | Yes |
| `PUT` | `/api/v1/sellers/{id}` | Update seller | Yes |
| `DELETE` | `/api/v1/sellers/{id}` | Delete seller | Yes |

#### Seller Creation Example

```bash
curl -X POST http://localhost/api/v1/sellers \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {your-token}" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com"
  }'
```

### Sales

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| `GET` | `/api/v1/sales` | List all sales | Yes |
| `POST` | `/api/v1/sales` | Create new sale | Yes |
| `GET` | `/api/v1/sales/{id}` | Show specific sale | Yes |
| `PUT` | `/api/v1/sales/{id}` | Update sale | Yes |
| `DELETE` | `/api/v1/sales/{id}` | Delete sale | Yes |

#### Sale Creation Example

```bash
curl -X POST http://localhost/api/v1/sales \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {your-token}" \
  -d '{
    "seller_id": 1,
    "amount": 1500.00,
    "date": "2025-10-26"
  }'
```

**Note:** Commission is calculated automatically (8.5% of the amount).

---

## Tests

The project includes test coverage (Unit, Feature, and Integration).

### Run All Tests

```bash
docker exec -it sales-control-api php artisan test
```

### Tests with Coverage

```bash
docker exec -it sales-control-api php artisan test --coverage
```

## License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.

