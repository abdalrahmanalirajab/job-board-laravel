# Job Board Platform

A multi-role job board web application built with Laravel 13 + Sanctum. Connects employers, job candidates, and administrators.

## Features

- **Role-based authentication** — Admin, Employer, and Candidate accounts
- **Job listings** — Employers create, edit, and manage job posts
- **Search & filtering** — Filter jobs by keyword, location, category, work type, experience level, and salary range
- **Approval workflow** — Admin approves or rejects job listings before they go live
- **Applications** — Candidates apply with resume or contact info; employers accept or reject
- **Comments** — Discuss job listings; admin can hide inappropriate comments
- **Payments** — Stripe integration for employer payments on accepted applications
- **Notifications** — Candidates receive database notifications on application status changes
- **Analytics** — Employers view job statistics; admin views platform overview

## Requirements

- PHP ^8.3
- Composer
- SQLite (default) or MySQL / PostgreSQL
- Node.js & NPM (for frontend assets via Vite)

## Installation

```bash
# Clone the repository
git clone <repository-url>
cd job-board-laravel

# Install PHP dependencies
composer install

# Copy environment file and generate app key
cp .env.example .env
php artisan key:generate

# Run database migrations and seeders
php artisan migrate --seed

# Create storage symlink (for file uploads)
php artisan storage:link

# Install and build frontend assets (optional)
npm install
npm run build
```

## Default Credentials

| Role      | Email                  | Password |
|-----------|------------------------|----------|
| Admin     | admin@jobboard.com     | password |
| Employer  | employer@jobboard.com  | password |
| Candidate | candidate@jobboard.com | password |

## API Endpoints

### Authentication
| Method | Endpoint              | Description        |
|--------|-----------------------|--------------------|
| POST   | `/api/register`       | Register new user  |
| POST   | `/api/login`          | Login              |
| POST   | `/api/logout`         | Logout             |
| GET    | `/api/user`           | Current user       |
| POST   | `/api/send-reset-link`| Send password reset|
| POST   | `/api/reset`          | Reset password     |

### Profile
| Method | Endpoint          | Description          |
|--------|-------------------|----------------------|
| GET    | `/api/profile`    | View profile         |
| PUT    | `/api/profile`    | Update profile       |

### Job Listings (Public)
| Method | Endpoint              | Description              |
|--------|-----------------------|--------------------------|
| GET    | `/api/jobs`           | List approved jobs       |
| GET    | `/api/jobs/{id}`      | Show job details         |
| GET    | `/api/categories`     | List categories          |
| GET    | `/api/categories/{id}`| Show category with jobs  |

### Job Listings (Employer)
| Method | Endpoint                     | Description              |
|--------|------------------------------|--------------------------|
| GET    | `/api/employer/jobs`         | List own jobs            |
| POST   | `/api/employer/jobs`         | Create job listing       |
| GET    | `/api/employer/jobs/{id}`    | Show own job             |
| PUT    | `/api/employer/jobs/{id}`    | Update job listing       |
| DELETE | `/api/employer/jobs/{id}`    | Delete job listing       |

### Job Listings (Admin)
| Method | Endpoint                         | Description          |
|--------|----------------------------------|----------------------|
| GET    | `/api/admin/jobs`                | List all jobs        |
| PUT    | `/api/admin/jobs/{id}/approve`   | Approve job listing  |
| PUT    | `/api/admin/jobs/{id}/reject`    | Reject job listing   |

### Applications
| Method | Endpoint                              | Description                    |
|--------|---------------------------------------|--------------------------------|
| POST   | `/api/jobs/{id}/apply`                | Apply for a job (candidate)    |
| DELETE | `/api/applications/{id}`              | Cancel application (candidate) |
| GET    | `/api/candidate/applications`         | List own applications          |
| GET    | `/api/employer/applications`          | List received applications     |
| PUT    | `/api/applications/{id}/accept`       | Accept application (employer)  |
| PUT    | `/api/applications/{id}/reject`       | Reject application (employer)  |

### Comments
| Method | Endpoint                    | Description                    |
|--------|-----------------------------|--------------------------------|
| GET    | `/api/jobs/{id}/comments`   | List comments for a job        |
| POST   | `/api/jobs/{id}/comments`   | Add a comment                  |
| DELETE | `/api/comments/{id}`        | Delete/hide comment            |

### Payments & Notifications
| Method | Endpoint                              | Description                      |
|--------|---------------------------------------|----------------------------------|
| POST   | `/api/payments/checkout`              | Initiate payment (employer)      |
| POST   | `/api/payments/webhook`               | Stripe webhook                   |
| GET    | `/api/notifications`                  | List notifications               |
| GET    | `/api/notifications/unread-count`     | Unread notification count        |
| PUT    | `/api/notifications/{id}/read`        | Mark notification as read        |
| PUT    | `/api/notifications/read-all`         | Mark all notifications as read   |

### Analytics
| Method | Endpoint                          | Description                 |
|--------|-----------------------------------|-----------------------------|
| GET    | `/api/analytics/jobs`             | Employer job stats          |
| GET    | `/api/admin/analytics/overview`   | Admin platform overview     |

## Environment Variables

Key variables (see `.env.example` for full list):

| Variable              | Description                     |
|-----------------------|---------------------------------|
| `STRIPE_KEY`          | Stripe publishable key          |
| `STRIPE_SECRET`       | Stripe secret key               |
| `STRIPE_WEBHOOK_SECRET`| Stripe webhook signing secret  |
| `PAYPAL_CLIENT_ID`    | PayPal client ID                |
| `PAYPAL_CLIENT_SECRET`| PayPal client secret            |
| `SANCTUM_STATEFUL_DOMAINS`| SPA domains for Sanctum    |

## Running the Application

```bash
# Start the development server
php artisan serve

# Run queue worker (for notifications)
php artisan queue:work
```

## Running Tests

```bash
php artisan test
```

## Project Structure

- `app/Http/Controllers/Api/` — API controllers grouped by feature
- `app/Http/Requests/` — Form request validation classes
- `app/Http/Resources/` — API resource transformers
- `app/Models/` — Eloquent models
- `app/Policies/` — Authorization policies
- `app/Notifications/` — Notification classes
- `app/Http/Middleware/` — Role-based middleware
- `database/migrations/` — Database migrations
- `database/seeders/` — Database seeders
- `routes/api.php` — All API route definitions

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
