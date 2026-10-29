# AquaTrack

A PHP and MySQL water-monitoring dashboard for managing incidents, sensors, telemetry, work orders, and audit logs.

## Requirements

- PHP with PDO MySQL enabled
- MySQL or MariaDB
- Apache (for example, XAMPP)

## Setup

1. Create a MySQL database and provision it with the tables expected by the application. A complete schema dump is not included in this repository.
2. Import [`database/auth_schema.sql`](./database/auth_schema.sql) into the database used by the app (`aquatrack_db` locally, or your selected InfinityFree database) to create the account table.
3. For local development, the default connection uses `localhost` and the `aquatrack_db` database. For InfinityFree, copy `config/database.local.example.php` to `config/database.local.php`, then enter the full database name shown in the InfinityFree panel and your MySQL password. The local configuration file is ignored by Git and should never be committed. You can also configure `AQUATRACK_DB_HOST`, `AQUATRACK_DB_PORT`, `AQUATRACK_DB_NAME`, `AQUATRACK_DB_USERNAME`, and `AQUATRACK_DB_PASSWORD` as server environment variables; these take precedence over the local file.
4. Place the project in your web server's document root and open `login.php` in a browser. Use **Create an account** to register the first account.

## Sessions and transactions

- `includes/auth.php` starts a hardened PHP session, and all dashboard pages require a signed-in session.
- Incident reporting runs in its own PDO transaction.
- Incident updates run in their own PDO transaction.
- Incident deletion runs in its own PDO transaction.

Each incident transaction commits on success and rolls back on a database error. Registration and login use password hashes and CSRF-protected forms; logout is a CSRF-protected POST request.
