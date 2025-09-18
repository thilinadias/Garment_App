CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL, email VARCHAR(150) NOT NULL, username VARCHAR(100) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL, role ENUM('admin','manager','staff') NOT NULL DEFAULT 'staff', phone VARCHAR(50), avatar VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS factories (
  id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(150) NOT NULL, contact_person VARCHAR(100), phone VARCHAR(50), email VARCHAR(150), address VARCHAR(255), notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS styles ( id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL UNIQUE ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS jobs (
  id INT AUTO_INCREMENT PRIMARY KEY, job_number VARCHAR(100) NOT NULL UNIQUE, style_id INT NULL, cut_date DATE NULL, cut_qty INT NULL,
  factory_id INT NULL, out_date DATE NULL, out_qty INT NULL, date_count INT NULL,
  status ENUM('New','In Progress','On Hold','Canceled','Finished','Billed') NOT NULL DEFAULT 'New', notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NULL DEFAULT NULL,
  KEY idx_jobs_status (status), KEY idx_jobs_factory (factory_id), KEY idx_jobs_style (style_id),
  CONSTRAINT fk_jobs_factory FOREIGN KEY (factory_id) REFERENCES factories(id) ON DELETE SET NULL,
  CONSTRAINT fk_jobs_style FOREIGN KEY (style_id) REFERENCES styles(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS job_photos (
  id INT AUTO_INCREMENT PRIMARY KEY, job_id INT NOT NULL, filename VARCHAR(255) NOT NULL, uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_job_photos_job (job_id), CONSTRAINT fk_job_photos_job FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS settings (`key` VARCHAR(64) PRIMARY KEY, `value` TEXT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS app_logs (
  id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NULL, action VARCHAR(50) NOT NULL, entity_type VARCHAR(50), entity_id INT, details TEXT, ip VARCHAR(45),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, KEY idx_logs_action (action), KEY idx_logs_created (created_at), KEY idx_logs_user (user_id),
  CONSTRAINT fk_logs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS chart_configs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(120) NOT NULL,
  dim ENUM('status','factory','style','month') NOT NULL,
  metric ENUM('jobs','cut_qty','out_qty') NOT NULL,
  type ENUM('bar','pie','doughnut','line') NOT NULL DEFAULT 'bar',
  sort_order INT NOT NULL DEFAULT 100,
  created_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_chart_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
