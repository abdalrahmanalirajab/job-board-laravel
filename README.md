# Job Board Platform

A multi-role job board web application built with **Laravel 13** + **Sanctum**. Connects employers, job candidates, and administrators with full payment processing and real-time notifications.

## Project Overview

This platform allows employers to post job listings that go through an admin approval workflow. Candidates can browse approved listings, apply, and receive real-time notifications about their application status. Employers can accept or reject applications and pay a hiring fee via Stripe. Admins oversee all listings and platform analytics.

---

## Tech Stack

| Layer         | Technology                         |
|---------------|------------------------------------|
| Backend       | Laravel 13 (PHP 8.2+)              |
| Auth          | Laravel Sanctum (token-based)      |
| Database      | MySQL (SQLite for local dev)       |
| Payments      | Stripe                             |
| Notifications | Laravel Queued Notifications (mail + database) |
| Frontend      | Vue.js (SPA — separate repo)       |
| Assets        | Vite                               |

---

## Team Members

| Member   | Module                                        |
|----------|-----------------------------------------------|
| Member 1 | Authentication & User Roles                   |
| Member 2 | Job Listings & Categories                     |
| Member 3 | Applications & Comments                       |
| Member 4 | Payments, Notifications & Analytics           |

---

## Requirements

- PHP 8.2+
- Composer
- MySQL (or SQLite for development)
- Node.js & NPM
- A Stripe account (test keys are fine)

---

## Installation

```bash
# 1. Clone the repository
git clone <repository-url>
cd job-board-laravel

# 2. Install PHP dependencies
composer install

# 3. Copy environment file and generate app key
cp .env.example .env
php artisan key:generate

# 4. Run database migrations and seeders
php artisan migrate --seed

# 5. Create storage symlink (for file uploads)
php artisan storage:link

# 6. Start the development server
php artisan serve
```

---

## Default Credentials

| Role      | Email                  | Password |
|-----------|------------------------|----------|
| Admin     | admin@jobboard.com     | password |
| Employer  | employer@jobboard.com  | password |
| Candidate | candidate@jobboard.com | password |

---

## Environment Variables

Key variables (see `.env.example` for the full list):

| Variable                     | Description                          |
|------------------------------|--------------------------------------|
| `STRIPE_KEY`                 | Stripe publishable key               |
| `STRIPE_SECRET`              | Stripe secret key                    |
| `STRIPE_WEBHOOK_SECRET`      | Stripe webhook signing secret        |
| `SANCTUM_STATEFUL_DOMAINS`   | SPA domains for Sanctum cookie auth  |
| `MAIL_MAILER`                | Mail driver (e.g. `log`, `smtp`)     |
| `QUEUE_CONNECTION`           | Queue driver (`database` recommended)|

---

## User Roles & Permissions

| Action                          | Admin | Employer | Candidate |
|---------------------------------|:-----:|:--------:|:---------:|
| Register / Login                |  ✅   |    ✅    |    ✅     |
| Browse approved job listings    |  ✅   |    ✅    |    ✅     |
| Create / edit / delete own jobs |  ❌   |    ✅    |    ❌     |
| Approve / reject job listings   |  ✅   |    ❌    |    ❌     |
| Apply for jobs                  |  ❌   |    ❌    |    ✅     |
| View / manage received apps     |  ❌   |    ✅    |    ❌     |
| Accept / reject applications    |  ❌   |    ✅    |    ❌     |
| Initiate payment                |  ❌   |    ✅    |    ❌     |
| View own notifications          |  ✅   |    ✅    |    ✅     |
| View employer analytics         |  ❌   |    ✅    |    ❌     |
| View platform analytics         |  ✅   |    ❌    |    ❌     |

---

## Stripe Payment Flow

* **Step 1**: Employer accepts candidate application.
* **Step 2**: Employer calls `POST /api/payments/checkout` with `application_id`.
* **Step 3**: Frontend receives `client_secret` and `stripe_publishable_key`.
* **Step 4**: Frontend uses Stripe.js to confirm payment.
* **Step 5**: Stripe calls webhook `POST /api/payments/stripe/webhook` → payment is marked `completed` (or `failed`).

## Test Cards

Use the following Stripe test credentials:
- **Success**: `4242 4242 4242 4242`
- **Fail**: `4000 0000 0000 0002`
- **3D Secure**: `4000 0025 0000 3155`

---

## API Documentation Overview

### Authentication
| Method | Endpoint               | Description         |
|--------|------------------------|---------------------|
| POST   | `/api/register`        | Register new user   |
| POST   | `/api/login`           | Login               |
| POST   | `/api/logout`          | Logout              |
| GET    | `/api/user`            | Current user        |
| POST   | `/api/send-reset-link` | Send password reset |
| POST   | `/api/reset`           | Reset password      |

### Profile
| Method | Endpoint        | Description   |
|--------|-----------------|---------------|
| GET    | `/api/profile`  | View profile  |
| PUT    | `/api/profile`  | Update profile|

### Job Listings (Public)
| Method | Endpoint               | Description              |
|--------|------------------------|--------------------------|
| GET    | `/api/jobs`            | List approved jobs        |
| GET    | `/api/jobs/{id}`       | Show job details          |
| GET    | `/api/categories`      | List categories           |
| GET    | `/api/categories/{id}` | Show category with jobs   |

### Job Listings (Employer)
| Method | Endpoint                  | Description         |
|--------|---------------------------|---------------------|
| GET    | `/api/employer/jobs`      | List own jobs       |
| POST   | `/api/employer/jobs`      | Create job listing  |
| GET    | `/api/employer/jobs/{id}` | Show own job        |
| PUT    | `/api/employer/jobs/{id}` | Update job listing  |
| DELETE | `/api/employer/jobs/{id}` | Delete job listing  |

### Job Listings (Admin)
| Method | Endpoint                       | Description         |
|--------|--------------------------------|---------------------|
| GET    | `/api/admin/jobs`              | List all jobs       |
| PUT    | `/api/admin/jobs/{id}/approve` | Approve job listing |
| PUT    | `/api/admin/jobs/{id}/reject`  | Reject job listing  |

### Applications
| Method | Endpoint                           | Description                    |
|--------|------------------------------------|--------------------------------|
| POST   | `/api/jobs/{id}/apply`             | Apply for a job (candidate)    |
| DELETE | `/api/applications/{id}`           | Cancel application (candidate) |
| GET    | `/api/candidate/applications`      | List own applications          |
| GET    | `/api/employer/applications`       | List received applications     |
| PUT    | `/api/applications/{id}/accept`    | Accept application (employer)  |
| PUT    | `/api/applications/{id}/reject`    | Reject application (employer)  |

### Payments
| Method | Endpoint                         | Description                  |
|--------|----------------------------------|------------------------------|
| POST   | `/api/payments/checkout`         | Initiate payment (employer)  |
| GET    | `/api/employer/payments`         | List own payments (employer) |
| POST   | `/api/payments/stripe/webhook`   | Stripe webhook (no auth)     |

### Notifications
| Method | Endpoint                            | Description                    |
|--------|-------------------------------------|--------------------------------|
| GET    | `/api/notifications`                | List notifications             |
| GET    | `/api/notifications/unread-count`   | Unread notification count      |
| PUT    | `/api/notifications/{id}/read`      | Mark notification as read      |
| PUT    | `/api/notifications/mark-all-read`  | Mark all notifications as read |

### Analytics
| Method | Endpoint                         | Description                |
|--------|----------------------------------|----------------------------|
| GET    | `/api/employer/analytics`        | Employer overview stats    |
| GET    | `/api/employer/analytics/{id}`   | Stats for a specific job   |
| GET    | `/api/admin/analytics/overview`  | Admin platform overview    |

---

## Running the Application

```bash
# Start the development server
php artisan serve

# Run queue worker (for queued notifications)
php artisan queue:work

# Run all tests
php artisan test
```

---

## Project Structure

```
app/
├── Http/
│   ├── Controllers/Api/       # API controllers grouped by feature
│   │   ├── Admin/             # Admin-only controllers
│   │   └── Employer/          # Employer-only controllers
│   ├── Middleware/            # Role-based middleware
│   ├── Requests/              # Form request validation
│   └── Resources/             # API resource transformers
├── Models/                    # Eloquent models
├── Notifications/             # Queued notification classes
└── Policies/                  # Authorization policies
database/
├── migrations/                # Database migrations
└── seeders/                   # Database seeders
routes/
└── api.php                    # All API route definitions
```

---

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
