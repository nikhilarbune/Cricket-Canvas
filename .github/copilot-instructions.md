Based on the project structure and database schema, let's plan the admin features and file structure:

```
admin/
├── functions/
│   ├── payment_functions.php
│   ├── tournament_functions.php
│   ├── user_functions.php
│   └── report_functions.php
├── includes/
│   ├── admin_header.php
│   ├── admin_footer.php
│   └── admin_sidebar.php
├── pages/
│   ├── payments/
│   │   ├── index.php
│   │   ├── verify.php
│   │   └── report.php
│   ├── tournaments/
│   │   ├── index.php
│   │   ├── view.php
│   │   └── manage.php
│   ├── users/
│   │   ├── index.php
│   │   ├── players.php
│   │   └── organizers.php
│   └── reports/
│       ├── tournament_report.php
│       ├── payment_report.php
│       └── user_report.php
└── index.php
```

## Admin Features and File Structure for Cricket Canvas

### 1. Authentication and Access Control
- `admin/auth/login.php` - Admin login page
- `admin/auth/logout.php` - Admin logout handler
- `admin/middleware/auth_check.php` - Admin authentication middleware

### 2. Dashboard
- `index.php` - Main admin dashboard showing key statistics
    - Total tournaments
    - Active players
    - Recent payments
    - Upcoming matches

### 3. Tournament Management
- `admin/tournaments/index.php` - List all tournaments
- `admin/tournaments/view.php` - View tournament details
- `admin/tournaments/approve.php` - Approve/reject tournaments
- `admin/tournaments/reports.php` - Tournament statistics

### 4. User Management

#### Players
- `admin/users/players/index.php` - List all players
- `admin/users/players/view.php` - View player details
- `admin/users/players/status.php` - Manage player status

#### Organizers
- `admin/users/organizers/index.php` - List all organizers
- `admin/users/organizers/view.php` - View organizer details
- `admin/users/organizers/approve.php` - Approve/reject organizers

### 5. Payment Management
- `admin/payments/index.php` - List all payments
- `admin/payments/verify.php` - Verify payment proofs
- `admin/payments/report.php` - Payment reports and analytics

### 6. Match Management
- `admin/matches/index.php` - List all matches
- `admin/matches/view.php` - View match details
- `admin/matches/update.php` - Update match status

### 7. Reports and Analytics
- `admin/reports/tournament_stats.php` - Tournament statistics
- `admin/reports/payment_analytics.php` - Payment analytics
- `admin/reports/user_activity.php` - User activity reports
- `admin/reports/export.php` - Export reports in CSV/PDF

### 8. Support Files

#### Includes
- `admin/includes/header.php` - Admin panel header
- `admin/includes/footer.php` - Admin panel footer
- `admin/includes/sidebar.php` - Admin navigation sidebar

#### Functions
- `tournament_functions.php`
- `user_functions.php`
- `payment_functions.php`
- `report_functions.php`

#### Assets
- `admin/assets/css/admin-style.css`
- `admin/assets/js/admin-scripts.js`

### Database Tables Used
- `users`
- `tournaments`
- `matches`
- `payments`
- `teams`
- `tournament_teams`
- `match_scores`

This structure provides a complete admin system for managing the cricket tournament platform with proper separation of concerns and organized file structure.