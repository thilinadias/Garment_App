# Garment App — Docker (Final)
**Made with ❤️ by Thilina Dias**

This package is built from your `garment_app_Final.zip` and is ready to run.

## Quick start
```bash
cp .env.example .env
docker compose up -d --build      # or: docker-compose up -d --build
```

- App:       `http://<host>:8080/`
- First run: `http://<host>:8080/install.php`  → should print **OK**
- Login:     `admin@example.com / admin123` (change after login)
- pMA:       `http://<host>:8081/`   (server: `db`; creds from `.env`)

## Notes
- To bind to a specific host IP, set `HOST_IP` in `.env` (e.g., 192.168.172.183).
- Your original `config/db.php` (if existed) was backed up to `config/db.original.php`.
- Uploads persist in volume `app_uploads` and DB in `db_data`.
