# Zazagram

Zazagram is now configured to use a MySQL database instead of JSON files.

## Run locally with Docker

Make sure Docker Desktop is running, then from this folder run:

```bash
docker compose up --build
```

Open the app here:

```text
http://localhost:8082
```

Open phpMyAdmin here:

```text
http://localhost:8083
```

phpMyAdmin login:

```text
Server: db
Username: zazagram
Password: zazagram
Database: zazagram
```

Root login, optional:

```text
Username: root
Password: root
```

## What changed

- `includes/db.php` now connects to MySQL with PDO.
- The old `db_read()` and `db_write()` functions still exist, so the existing pages and API files keep working.
- `database/init.sql` creates the MySQL tables and imports the existing sample JSON data.
- `docker-compose.yml` starts the PHP app, MySQL, and phpMyAdmin.
- The web app is exposed on port `8080`.

## Reset the database

Docker only imports `database/init.sql` when the database volume is first created. To reset everything and import the seed data again:

```bash
docker compose down -v
docker compose up --build
```
