
## 🏛️ Department of the Interior and Local Government
### Santa Cruz, Laguna - Road Clearing Operations

<p align="center">
<img src="https://img.shields.io/badge/Laravel-11.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel">
<img src="https://img.shields.io/badge/PHP-8.2-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP">
<img src="https://img.shields.io/badge/Tailwind_CSS-4.0-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white" alt="Tailwind">
<img src="https://img.shields.io/badge/DaisyUI-Latest-5A0EF8?style=for-the-badge" alt="DaisyUI">
</p>

---

## 📖 About DILG-RC System

DILG-RC is a comprehensive road clearing violation reporting and monitoring system designed for the Department of the Interior and Local Government (DILG) in Santa Cruz, Laguna. The system enables efficient reporting, verification, tracking, and resolution of road clearing violations across 26 barangays.

### ✅ Key Features

- **🏛️ Role-Based Authentication** - DILG Admin and Barangay Staff with distinct access levels
- **📊 Real-Time Analytics** - Comprehensive violation statistics and performance metrics
- **🏘️ Barangay Performance Ranking** - Transparent ranking system with resolution rates
- **📈 Status Transparency Timeline** - Complete audit trail of report status changes
- **📄 Printable Reports** - Professional auto-generated reports for compliance
- **🎯 Smart Filtering** - Role-based data filtering and barangay assignment
- **📱 Responsive Design** - Modern Tailwind CSS + DaisyUI interface with DILG yellow/gold theme
- **🗺️ GIS Boundary Map** - Interactive Leaflet.js map with barangay boundaries
- **🔒 Anonymous Citizen Reporting** - Privacy-focused reporting with tracking ID system

---
## 🛠️ Installation & Setup

```bash
# Clone repository
git clone <repository-url>
cd DILG-RC

# Install dependencies
composer install
npm install

# Leaflet.js is already installed locally
# No need to download separately - it's in public/js/ and public/css/

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Run migrations
php artisan migrate

# Seed database (includes 27 test accounts)
php artisan db:seed

# Start development server
php artisan serve

# Login credentials (see ACCOUNTS_CREDENTIALS.md)
# DILG Admin: admin@dilg.gov.ph / password
# Barangay Staff: {barangay-slug}@barangay.dilg.gov.ph / password
```

---


## 📄 License

This system is developed for the Department of the Interior and Local Government (DILG) - Santa Cruz, Laguna. Built on Laravel framework which is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

---

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).


# DILG-RC System

## Collaborating on the Repository

Welcome to the DILG-RC System project. This guide explains how collaborators can clone, pull, and push changes to the repository using Git and GitHub.

---

## Prerequisites

Before starting, make sure you have:

* A GitHub account
* Git installed on your computer
* Visual Studio Code (recommended)
* Accepted the GitHub collaborator invitation from the repository owner

To verify Git is installed, open a terminal and run:

```bash
git --version
```

---

# Step 1: Clone the Repository

Open **PowerShell**, **Command Prompt**, or the **VS Code Terminal**.

Navigate to the folder where you want to save the project.

Example:

```bash
cd C:\Users\YourName\Desktop
```

Clone the repository:

```bash
git clone https://github.com/OWNER_USERNAME/REPOSITORY_NAME.git
```

Example:

```bash
git clone https://github.com/username/dilg-rc-system.git
```

Move into the project folder:

```bash
cd dilg-rc-system
```

Open the project in VS Code:

```bash
code .
```

---

# Step 2: Configure Git (First Time Only)

Run these commands once on your computer.

```bash
git config --global user.name "Your Name"
```

```bash
git config --global user.email "your-email@example.com"
```

Verify your configuration:

```bash
git config --global --list
```

---

# Step 3: Get the Latest Changes

Before starting any work, always update your local copy.

```bash
git checkout main
```

```bash
git pull origin main
```

---

# Step 4: Create Your Own Branch

Create a new branch for the feature or task you are working on.

Example:

```bash
git checkout -b feature-report-module
```

Other examples:

```bash
git checkout -b feature-dashboard
```

```bash
git checkout -b fix-login
```

```bash
git checkout -b feature-mobile-api
```

---

# Step 5: Make Your Changes

Edit the project files using Visual Studio Code.

When finished, check the modified files:

```bash
git status
```

---

# Step 6: Stage Your Changes

Add all modified files:

```bash
git add .
```

Verify that the files are staged:

```bash
git status
```

---

# Step 7: Commit Your Changes

Write a clear and meaningful commit message.

Example:

```bash
git commit -m "Added report validation"
```

Other examples:

```bash
git commit -m "Updated dashboard design"
```

```bash
git commit -m "Fixed login bug"
```

---

# Step 8: Push Your Branch

The first time you push your branch:

```bash
git push -u origin feature-report-module
```

After the first push, you only need:

```bash
git push
```

---

# Step 9: Create a Pull Request

1. Open the repository on GitHub.
2. Click **Compare & pull request**.
3. Verify that the base branch is **main**.
4. Enter a descriptive title.
5. Click **Create pull request**.

The repository owner will review your changes before merging them into the main branch.

---

# Daily Workflow

Every time you begin working:

```bash
git checkout main
```

```bash
git pull origin main
```

Switch back to your branch:

```bash
git checkout feature-report-module
```

Merge the latest updates from the main branch:

```bash
git merge main
```

After making changes:

```bash
git add .
git commit -m "Describe your changes"
git push
```

---

# Useful Git Commands

Check current branch:

```bash
git branch
```

View project status:

```bash
git status
```

View commit history:

```bash
git log --oneline
```

List remote repositories:

```bash
git remote -v
```

Fetch updates without merging:

```bash
git fetch
```

---

# Best Practices

* Always pull the latest changes before starting work.
* Create a separate branch for each feature or bug fix.
* Write clear and meaningful commit messages.
* Push your work regularly.
* Create a Pull Request instead of pushing directly to the `main` branch.
* Resolve merge conflicts carefully before submitting a Pull Request.

---

# Repository Workflow

```
Clone Repository
        │
        ▼
Pull Latest Changes
        │
        ▼
Create Feature Branch
        │
        ▼
Develop Feature
        │
        ▼
git add .
        │
        ▼
git commit
        │
        ▼
git push
        │
        ▼
Create Pull Request
        │
        ▼
Repository Owner Reviews
        │
        ▼
Merge into Main
```

---

## Repository Owner

The repository owner is responsible for reviewing Pull Requests, resolving merge conflicts when necessary, and merging approved changes into the `main` branch.

