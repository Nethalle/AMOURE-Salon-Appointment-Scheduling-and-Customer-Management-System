# Project Skills

Skills applied in **AMOURÉ SALON: Salon Appointment Scheduling and Customer Management System**, and where each one appears in the project.

Back to [README](README.md)

---

## Technical skills

| Skill | Where it is applied |
| --- | --- |
| **HTML5** | Page templates in `frontend/views/`: forms, tables, dashboards, email template |
| **CSS3** | `frontend/assets/css/style.css`: salon branding, layout, responsive pages |
| **JavaScript** | `frontend/assets/js/app.js`: form hints, filters, confirm dialogs |
| **PHP (backend development)** | Controllers, services and core files in `backend/` |
| **MySQL database design** | `database/schema.sql`: 9 related tables, keys, unique constraints and indexes |
| **SQL queries** | Overlap check, daily-limit count, search filters, monthly reports |
| **PDO and prepared statements** | `backend/core/db.php` and every query in the system |
| **Frontend / backend separation** | `frontend/` displays data only; `backend/` holds all logic and SQL |
| **Routing** | `index.php` sends every request to the right controller |
| **Authentication** | Login, logout, registration with `password_hash()` and sessions |
| **Role-based access control** | Admin, staff and customer each see only their allowed pages and records |
| **Email integration** | Booking confirmation, cancellation and reminder emails sent through PHPMailer and SMTP, with every send logged |
| **Scheduled tasks** | `backend/jobs/send_reminders.php` run hourly by Task Scheduler or cron |
| **Dependency management** | Composer installs PHPMailer |
| **Web security** | Protection against SQL injection, XSS and CSRF; blocked access to backend files |
| **Input validation** | Server-side checks on dates, times, emails, ratings and required fields |
| **Reporting** | Daily and monthly summaries, popular services, average ratings |
| **Version control** | Git and GitHub for tracking changes |
| **Technical documentation** | README with architecture, database and request-flow diagrams (Mermaid) |

---

## Problem-solving skills

| Skill | How it is shown |
| --- | --- |
| **Problem identification** | Found a real salon problem: double bookings, scattered records, missed appointments |
| **Problem analysis** | Listed causes, effects, the current manual process and its limitations |
| **Solution design** | Turned each problem into a system feature (see the table below) |
| **Business rules design** | Defined clear rules for overlaps, daily limit, statuses, reminders and reviews |
| **Testing** | Wrote and ran manual test cases for every major rule and role |

### From problem to feature

| Problem | Feature that solves it |
| --- | --- |
| Two customers booked with the same stylist at the same time | Overlap check before saving a booking |
| Salon accepts more bookings than it can handle | Daily appointment limit |
| Customers unsure whether their booking was confirmed or cancelled | Confirmation and cancellation emails |
| Customers forget their appointments | Email reminders |
| Cancelled bookings not updated | Appointment status tracking that frees cancelled slots |
| Customer details and history hard to find | Customer profiles with appointment history, search and filtering |
| Counting appointments by hand | Dashboard and reports |
| No organized customer feedback | Ratings and reviews approved by the administrator |

---

## Course objectives met

| Course objective | How this project meets it |
| --- | --- |
| Identify a specific problem | Salon bookings recorded by hand across many channels |
| Analyze causes, effects and needs | Problem analysis in the project proposal and README |
| Design a suitable Web Management System | Appointment scheduling and customer management system |
| Identify users and functions | Administrator, staff and customer roles with defined permissions |
| Apply HTML5, CSS, PHP, MySQL and other web technologies | Full stack listed above, plus JavaScript, PHPMailer and Composer |
| Demonstrate problem-solving, creativity and logical thinking | Business rules, booking checks, and automatic confirmation, cancellation and reminder emails |
