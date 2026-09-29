# PortGen - Dynamic Portfolio Builder

![PortGen Demo](demo.png)

PortGen is an automated web-based application built using PHP and MySQL that allows users to quickly build, edit, and manage personalized portfolio websites. It features user authentication, a form-based dynamic builder, and persistent database storage.

## Features
- **User Authentication:** Secure signup, login, and session tracking (`signup.php`, `login.php`).
- **Dynamic Portfolio Builder:** Form-driven creation and editing tool (`builder.php`, `edit-portfolio.php`).
- **Dashboard & Portfolio Viewer:** View saved portfolio entries and individual profile pages (`my-portfolios.php`, `view.php`).
- **Data Persistence:** Relational database logging and record management via MySQL (`db.php`).

## Tech Stack
- **Backend:** PHP
- **Database:** MySQL
- **Frontend:** HTML5, CSS3 (`style.css`), JavaScript

## Setup & Local Installation

1. **Clone the Repository:**
   ```bash
   git clone [https://github.com/Ashad-001/portgen.git](https://github.com/Ashad-001/portgen.git)

2. Server Configuration (XAMPP):

Move the project directory to your web server root (e.g., C:\xampp\htdocs\portgen).

Start Apache and MySQL in the XAMPP Control Panel.

Import or create the database portgen_db in phpMyAdmin.

Ensure local database configuration in db.php matches your local server credentials:
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'portgen_db';

