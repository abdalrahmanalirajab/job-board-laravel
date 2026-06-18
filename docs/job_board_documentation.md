**JOB BOARD PLATFORM**

Laravel + Vue.js Backend

Project Documentation & Team Division

**ITI — Information Technology Institute**

Team Size: 4 Members

Version 1.0 — 2025

# **1. Project Overview**

The Job Board Platform is a multi-role web application that connects employers, job candidates, and administrators. Employers register, post job listings, and manage applications. Candidates search and apply for jobs. Admins moderate content and approve job postings before they go live.

## **1.1 Tech Stack**

| **Layer** | **Technology** |
| --- | --- |
| Backend Framework | Laravel 11/12 |
| Frontend | Vue.js 3 + Axios (SPA or Blade+Vue) |
| Database | MySQL |
| Authentication | Laravel Sanctum (API tokens) |
| File Storage | Laravel Storage (local / S3) |
| Payments | Stripe / PayPal |
| Notifications | Laravel Notifications (email/DB) |
| Version Control | Git + GitHub |

# **2. User Roles & Permissions**

## **2.1 Employer**

* Register and create an employer account
* Post job listings with full details:
* Title, description, responsibilities
* Required skills and qualifications
* Salary range and benefits
* Location and work type (remote / onsite / hybrid)
* Category (programming, management, …)
* Technologies used
* Application deadline
* Edit and delete their own job listings
* Review applications: accept or reject candidates
* View analytics on job postings (bonus: e.g. number of applicants)
* Comment on job listings
* Pay after a candidate is approved (Stripe / PayPal)
* Upload company logo / branding (optional)

## **2.2 Candidate**

* Register and create a candidate account
* Search and filter jobs by:
* Keywords (title / description)
* Location
* Category / industry
* Experience level
* Salary range
* Date posted
* Apply for jobs by uploading a resume OR providing contact info
* Connect profile with LinkedIn (bonus)
* Manage profile: edit personal info, cancel or re-apply for jobs
* Receive notifications about application status (bonus)

## **2.3 Admin**

* Approve or reject job postings submitted by employers
* Monitor overall platform activity and user behaviour
* Remove inappropriate comments (bonus)

# **3. Database Design (Key Tables)**

| **Table** | **Key Columns** | **Notes** |
| --- | --- | --- |
| users | id, name, email, password, role (employer|candidate|admin), avatar | Polymorphic base |
| employer\_profiles | id, user\_id, company\_name, logo, website, description | One-to-one with users |
| candidate\_profiles | id, user\_id, resume\_path, linkedin\_url, bio, skills | One-to-one with users |
| categories | id, name, slug | Job categories |
| job\_listings | id, employer\_id, category\_id, title, description, responsibilities, skills\_required, salary\_min, salary\_max, location, work\_type, status (pending|approved|rejected), deadline | Central table |
| job\_technologies | id, job\_listing\_id, technology\_name | Many technologies per job |
| applications | id, job\_listing\_id, candidate\_id, resume\_path, contact\_email, contact\_phone, status (pending|accepted|rejected), applied\_at | Tracks all applications |
| comments | id, job\_listing\_id, user\_id, body, is\_visible | Soft-delete / visibility flag |
| notifications | Laravel default notifications table | For candidate alerts (bonus) |
| payments | id, employer\_id, application\_id, amount, provider, status, paid\_at | After candidate approved |

# **4. API Routes Summary**

## **4.1 Authentication**

| **Method** | **Endpoint** | **Description** | **Role** |
| --- | --- | --- | --- |
| POST | /api/register | Register (employer or candidate) | Public |
| POST | /api/login | Login — returns Sanctum token | Public |
| POST | /api/logout | Revoke token | Auth |
| GET | /api/user | Get current user profile | Auth |

## **4.2 Job Listings**

| **Method** | **Endpoint** | **Description** | **Role** |
| --- | --- | --- | --- |
| GET | /api/jobs | List approved jobs (search + filter) | Public |
| GET | /api/jobs/{id} | Show single job detail | Public |
| POST | /api/jobs | Create job listing | Employer |
| PUT | /api/jobs/{id} | Update job listing | Employer (owner) |
| DELETE | /api/jobs/{id} | Delete job listing | Employer (owner) |
| GET | /api/employer/jobs | List own job listings | Employer |
| GET | /api/admin/jobs/pending | List pending jobs | Admin |
| PUT | /api/admin/jobs/{id}/approve | Approve job listing | Admin |
| PUT | /api/admin/jobs/{id}/reject | Reject job listing | Admin |

## **4.3 Applications**

| **Method** | **Endpoint** | **Description** | **Role** |
| --- | --- | --- | --- |
| POST | /api/jobs/{id}/apply | Apply for a job | Candidate |
| DELETE | /api/applications/{id} | Cancel application | Candidate |
| GET | /api/candidate/applications | List own applications | Candidate |
| GET | /api/employer/applications | List received applications | Employer |
| PUT | /api/applications/{id}/accept | Accept an application | Employer |
| PUT | /api/applications/{id}/reject | Reject an application | Employer |

## **4.4 Comments & Payments**

| **Method** | **Endpoint** | **Description** | **Role** |
| --- | --- | --- | --- |
| GET | /api/jobs/{id}/comments | List comments for a job | Public |
| POST | /api/jobs/{id}/comments | Add a comment | Auth |
| DELETE | /api/comments/{id} | Remove comment | Admin / Owner |
| POST | /api/payments/checkout | Initiate payment after approval | Employer |
| POST | /api/payments/webhook | Stripe/PayPal webhook | System |

# **5. Team Division (4 Members)**

The project is divided into four modules. Each member owns their module end-to-end (migrations, models, controllers, requests, resources, tests).

## **👤 Member 1 — Auth, Roles & User Profiles**

### **Responsibility**

Foundation of the entire application. All other modules depend on this work being done first.

### **Tasks**

* Install and configure Laravel Sanctum
* Create users migration with role column (enum: employer, candidate, admin)
* Create employer\_profiles and candidate\_profiles migrations & models
* AuthController: register (with role), login, logout, me
* ProfileController: show and update employer/candidate profiles
* File upload for avatar and company logo
* Form Request validation classes for all auth actions
* API Resources: UserResource, EmployerProfileResource, CandidateProfileResource
* Role-based middleware (EnsureIsEmployer, EnsureIsCandidate, EnsureIsAdmin)
* Seeders: AdminSeeder, sample employer, sample candidate

### **Deliverable Files**

| **File/Folder** | **Description** |
| --- | --- |
| database/migrations/ | users, employer\_profiles, candidate\_profiles |
| app/Models/ | User, EmployerProfile, CandidateProfile |
| app/Http/Controllers/Api/AuthController.php | Register, login, logout, me |
| app/Http/Controllers/Api/ProfileController.php | View/update profiles |
| app/Http/Requests/ | RegisterRequest, LoginRequest, UpdateProfileRequest |
| app/Http/Resources/ | UserResource, EmployerProfileResource, CandidateProfileResource |
| app/Http/Middleware/ | EnsureIsEmployer, EnsureIsCandidate, EnsureIsAdmin |
| database/seeders/ | AdminSeeder, UserSeeder |

## **👤 Member 2 — Job Listings & Categories**

### **Responsibility**

Core content of the platform. Handles everything related to creating, browsing, filtering, and approving job posts.

### **Tasks**

* Create categories, job\_listings, job\_technologies migrations & models
* JobListingController (employer): CRUD for own listings
* JobListingController (public): index with search/filter, show
* AdminJobController: list pending, approve, reject
* Search and filtering: keywords, location, category, work\_type, salary range, date posted
* Status flow: pending → approved / rejected
* File upload for company logo on job listing (optional)
* Form Request validation for job creation and update
* API Resources: CategoryResource, JobListingResource, JobListingDetailResource
* Seeders: CategorySeeder, JobListingSeeder (sample data)

### **Deliverable Files**

| **File/Folder** | **Description** |
| --- | --- |
| database/migrations/ | categories, job\_listings, job\_technologies |
| app/Models/ | Category, JobListing, JobTechnology |
| app/Http/Controllers/Api/JobListingController.php | Public + employer CRUD |
| app/Http/Controllers/Api/Admin/JobListingController.php | Admin approve/reject |
| app/Http/Requests/ | StoreJobListingRequest, UpdateJobListingRequest |
| app/Http/Resources/ | CategoryResource, JobListingResource, JobListingDetailResource |
| database/seeders/ | CategorySeeder, JobListingSeeder |

## **👤 Member 3 — Applications & Comments**

### **Responsibility**

Handles the application lifecycle (candidate applies → employer accepts/rejects) and the comments system.

### **Tasks**

* Create applications and comments migrations & models
* ApplicationController (candidate): apply (upload resume or contact info), cancel, list own
* ApplicationController (employer): list received, accept, reject
* Resume file upload via Laravel Storage (PDF)
* CommentController: list, create, delete (admin can delete any)
* Prevent duplicate applications (one per candidate per job)
* Policy: candidates can only cancel pending applications; employers own their jobs
* Form Request validation for application and comment actions
* API Resources: ApplicationResource, CommentResource
* Seeders: ApplicationSeeder, CommentSeeder

### **Deliverable Files**

| **File/Folder** | **Description** |
| --- | --- |
| database/migrations/ | applications, comments |
| app/Models/ | Application, Comment |
| app/Http/Controllers/Api/ApplicationController.php | Apply, cancel, list, accept, reject |
| app/Http/Controllers/Api/CommentController.php | List, add, delete comments |
| app/Http/Requests/ | StoreApplicationRequest, StoreCommentRequest |
| app/Http/Resources/ | ApplicationResource, CommentResource |
| app/Policies/ | ApplicationPolicy, CommentPolicy |
| database/seeders/ | ApplicationSeeder, CommentSeeder |

## **👤 Member 4 — Payments, Notifications & Analytics**

### **Responsibility**

Handles payment integration, bonus features (notifications, analytics), and overall project integration/testing.

### **Tasks**

* Create payments migration & model
* PaymentController: initiate checkout, handle Stripe/PayPal webhook
* Integrate Stripe SDK (stripe/stripe-php) or PayPal SDK
* Trigger payment after employer accepts a candidate (only once)
* NotificationController: send and list notifications for candidates (bonus)
* Laravel Notification classes: ApplicationAccepted, ApplicationRejected
* Analytics endpoint: job post stats for employer (total applicants, accepted, rejected) (bonus)
* Admin: remove comments endpoint (bonus)
* Write .env.example with all required keys documented
* API Resources: PaymentResource, NotificationResource
* Write README.md for the project

### **Deliverable Files**

| **File/Folder** | **Description** |
| --- | --- |
| database/migrations/ | payments, notifications (Laravel default) |
| app/Models/ | Payment |
| app/Http/Controllers/Api/PaymentController.php | Checkout + webhook |
| app/Notifications/ | ApplicationAccepted, ApplicationRejected |
| app/Http/Controllers/Api/AnalyticsController.php | Job post stats (bonus) |
| app/Http/Resources/ | PaymentResource, NotificationResource |
| .env.example | All env keys documented |
| README.md | Project setup and documentation |

# **6. Team Summary Table**

| **Member** | **Module** | **Key Models** | **Priority** |
| --- | --- | --- | --- |
| Member 1 | Auth, Roles & Profiles | User, EmployerProfile, CandidateProfile | 🔴 Highest — do first |
| Member 2 | Job Listings & Categories | Category, JobListing, JobTechnology | 🟠 High — start after Member 1 |
| Member 3 | Applications & Comments | Application, Comment | 🟡 Medium — start after Member 2 |
| Member 4 | Payments, Notifications & Analytics | Payment, Notification | 🟢 Can start partially in parallel |

# **7. Recommended Team Workflow**

## **7.1 Git Branching Strategy**

Use a feature-branch workflow on GitHub:

* main — production-ready code only
* develop — integration branch; all features merge here first
* feature/auth — Member 1's branch
* feature/jobs — Member 2's branch
* feature/applications — Member 3's branch
* feature/payments — Member 4's branch

Merge order: Member 1 merges first → Member 2 merges → Member 3 merges → Member 4 merges.

## **7.2 Pull Request Rules**

* Never push directly to main or develop
* Every PR must be reviewed by at least one other team member
* PR title format: [MODULE] short description (e.g. [Auth] Add Sanctum login endpoint)
* Resolve all merge conflicts locally before opening PR

## **7.3 Naming Conventions**

| **Item** | **Convention** | **Example** |
| --- | --- | --- |
| Database tables | snake\_case plural | job\_listings, employer\_profiles |
| Models | PascalCase singular | JobListing, EmployerProfile |
| Controllers | PascalCase + Controller | JobListingController |
| Routes | kebab-case | /api/job-listings/{id} |
| Branches | feature/short-name | feature/auth, feature/jobs |
| Env keys | UPPER\_SNAKE\_CASE | STRIPE\_SECRET\_KEY |

# **8. GitHub Repository Setup (Step by Step)**

## **Step 1 — Create the Repository on GitHub**

1. Go to https://github.com and log in with your account.
2. Click the green "New" button (top-left) or go to https://github.com/new.
3. Fill in Repository name: job-board-laravel
4. Set visibility to Private (so only your team sees it).
5. Check "Add a README file".
6. Choose .gitignore template: Laravel.
7. Click "Create repository".

## **Step 2 — Add Your Team as Collaborators**

1. In the new repo, go to Settings → Collaborators → Add people.
2. Add each team member by their GitHub username.
3. Set their role to "Write" so they can push branches.
4. Each collaborator accepts the invitation email.

## **Step 3 — Clone & Initialize the Laravel Project (Member 1 does this)**

Run these commands in your terminal:

# Clone the repo

git clone https://github.com/YOUR\_USERNAME/job-board-laravel.git

cd job-board-laravel

# Create a new Laravel project inside it

composer create-project laravel/laravel .

# Install Sanctum

composer require laravel/sanctum

php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"

# Copy .env and generate app key

cp .env.example .env

php artisan key:generate

# Create develop branch and push

git checkout -b develop

git add .

git commit -m "Initial Laravel project setup"

git push origin develop

## **Step 4 — Each Member Creates Their Feature Branch**

After cloning the repo, each member runs:

git checkout develop

git pull origin develop

git checkout -b feature/YOUR-MODULE-NAME

| **Member** | **Command** |
| --- | --- |
| Member 1 | git checkout -b feature/auth |
| Member 2 | git checkout -b feature/jobs |
| Member 3 | git checkout -b feature/applications |
| Member 4 | git checkout -b feature/payments |

## **Step 5 — Daily Workflow**

1. Work on your files in your feature branch.
2. Commit often with clear messages: git commit -m "[Auth] Add register endpoint"
3. Push your branch: git push origin feature/auth
4. When done, open a Pull Request on GitHub from your branch into develop.
5. Ask a teammate to review and approve the PR before merging.

## **Step 6 — Protect the main and develop Branches**

1. Go to Settings → Branches → Add branch protection rule.
2. Branch name pattern: main → enable "Require a pull request before merging".
3. Repeat for develop.

# **9. Environment Variables (.env)**

Member 4 is responsible for maintaining .env.example. Required keys:

| **Key** | **Description** |
| --- | --- |
| APP\_NAME | Job Board |
| APP\_URL | http://localhost |
| DB\_DATABASE | job\_board |
| DB\_USERNAME | root |
| DB\_PASSWORD | (your password) |
| SANCTUM\_STATEFUL\_DOMAINS | localhost,127.0.0.1 |
| FILESYSTEM\_DISK | public (for resumes and logos) |
| STRIPE\_KEY | Stripe publishable key |
| STRIPE\_SECRET | Stripe secret key |
| STRIPE\_WEBHOOK\_SECRET | Stripe webhook signing secret |
| PAYPAL\_CLIENT\_ID | PayPal client ID (if using PayPal) |
| PAYPAL\_CLIENT\_SECRET | PayPal client secret |
| MAIL\_MAILER | smtp (for notifications) |
| MAIL\_HOST | smtp.mailtrap.io (dev) or real SMTP |
| MAIL\_USERNAME | Mailtrap/SMTP username |
| MAIL\_PASSWORD | Mailtrap/SMTP password |

# **10. Project Milestones**

| **Week** | **Milestone** | **Owner** |
| --- | --- | --- |
| Week 1 | Project setup, repo created, develop branch ready | Member 1 |
| Week 1-2 | Auth module complete: register, login, profiles, middleware | Member 1 |
| Week 2-3 | Job Listings module: CRUD, filtering, admin approve/reject | Member 2 |
| Week 3-4 | Applications module: apply, cancel, accept/reject | Member 3 |
| Week 3-4 | Comments module: add, list, delete | Member 3 |
| Week 4-5 | Payments integration: Stripe/PayPal checkout + webhook | Member 4 |
| Week 5 | Notifications (bonus), analytics (bonus) | Member 4 |
| Week 5-6 | Integration testing, bug fixes, README | All |
| Week 6 | Final review and merge to main | All |

**End of Documentation**

ITI Job Board Platform — Laravel + Vue.js | 2025
