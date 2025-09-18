<?php
// Minimal installer / health-check.
// IMPORTANT: load the bootstrap so h(), app_setting(), and $pdo exist.
require __DIR__ . '/config/app.php';

$ddl = [
  "CREATE TABLE IF NOT EXISTS settings (
     id INT AUTO_INCREMENT PRIMARY KEY,
     k VARCHAR(100) UNIQUE, v TEXT,
     updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
  "CREATE TABLE IF NOT EXISTS users (
     id INT AUTO_INCREMENT PRIMARY KEY,
     name VARCHAR(120) NOT NULL,
     email VARCHAR(190) NOT NULL UNIQUE,
     password_hash VARCHAR(255) NOT NULL,
     role ENUM('admin','manager','staff') NOT NULL DEFAULT 'admin',
     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
  "CREATE TABLE IF NOT EXISTS factories (
     id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(160) NOT NULL,
     address TEXT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
  "CREATE TABLE IF NOT EXISTS styles (
     id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(120) NOT NULL UNIQUE,
     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
  "CREATE TABLE IF NOT EXISTS jobs (
     id INT AUTO_INCREMENT PRIMARY KEY,
     job_number VARCHAR(80) NOT NULL, style_id INT NULL, cut_date DATE NULL, cut_qty INT DEFAULT 0,
     factory_id INT NULL, out_date DATE NULL, out_qty INT DEFAULT 0, date_count INT DEFAULT 0,
     status ENUM('New','In progress','On hold','Canceled','Finished','Billed') DEFAULT 'New',
     notes TEXT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
     updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
     INDEX(job_number), INDEX(cut_date), INDEX(out_date),
     CONSTRAINT fk_jobs_style FOREIGN KEY(style_id) REFERENCES styles(id) ON DELETE SET NULL,
     CONSTRAINT fk_jobs_factory FOREIGN KEY(factory_id) REFERENCES factories(id) ON DELETE SET NULL
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
  "CREATE TABLE IF NOT EXISTS job_photos (
     id INT AUTO_INCREMENT PRIMARY KEY, job_id INT NOT NULL,
     filename VARCHAR(255) NOT NULL, path VARCHAR(255) NULL,
     uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
     CONSTRAINT fk_photos_job FOREIGN KEY(job_id) REFERENCES jobs(id) ON DELETE CASCADE
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
  "CREATE TABLE IF NOT EXISTS app_logs (
     id BIGINT AUTO_INCREMENT PRIMARY KEY, user_id INT NULL,
     action VARCHAR(255) NOT NULL, meta JSON NULL, ip VARCHAR(64) NULL,
     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX(user_id)
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
  "CREATE TABLE IF NOT EXISTS chart_configs (
     id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(160) NOT NULL,
     type ENUM('pie','doughnut','bar','line') NOT NULL DEFAULT 'pie',
     query_json JSON NOT NULL, on_dashboard TINYINT(1) NOT NULL DEFAULT 0,
     created_by INT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
];

try { foreach ($ddl as $sql) { $pdo->exec($sql); } }
catch (Throwable $e) { http_response_code(500); echo "DB/DDL ERROR: ".h($e->getMessage()); exit; }

try {
  $count = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
  if ($count === 0) {
    $hash = password_hash('admin123', PASSWORD_DEFAULT);
    $st = $pdo->prepare("INSERT INTO users (name,email,password_hash,role) VALUES (?,?,?,?)");
    $st->execute(['Admin','admin@example.com',$hash,'admin']);
  }
} catch (Throwable $e) { http_response_code(500); echo "SEED ERROR: ".h($e->getMessage()); exit; }

echo "OK";
