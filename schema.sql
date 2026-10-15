-- ============================================================
-- Flowork Agency Software - Database Schema
-- Ye file install.php khud chalata hai, manually import
-- karne ki zaroorat NAHI hai.
-- ============================================================

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin','staff') NOT NULL DEFAULT 'staff',
  designation VARCHAR(100) NOT NULL DEFAULT '',
  phone VARCHAR(30) NOT NULL DEFAULT '',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS settings (
  skey VARCHAR(80) PRIMARY KEY,
  svalue TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS clients (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  company VARCHAR(150) NOT NULL DEFAULT '',
  phone VARCHAR(30) NOT NULL DEFAULT '',
  whatsapp VARCHAR(30) NOT NULL DEFAULT '',
  email VARCHAR(150) NOT NULL DEFAULT '',
  address TEXT,
  notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS projects (
  id INT AUTO_INCREMENT PRIMARY KEY,
  client_id INT NOT NULL,
  title VARCHAR(180) NOT NULL,
  service VARCHAR(120) NOT NULL DEFAULT '',
  description TEXT,
  total_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  deadline DATE NULL,
  status ENUM('new','in_progress','delivered','completed','cancelled') NOT NULL DEFAULT 'new',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS milestones (
  id INT AUTO_INCREMENT PRIMARY KEY,
  project_id INT NOT NULL,
  title VARCHAR(150) NOT NULL,
  amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  due_date DATE NULL,
  status ENUM('unpaid','paid') NOT NULL DEFAULT 'unpaid',
  paid_date DATE NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS invoices (
  id INT AUTO_INCREMENT PRIMARY KEY,
  invoice_no VARCHAR(40) NOT NULL UNIQUE,
  project_id INT NULL,
  milestone_id INT NULL,
  client_id INT NOT NULL,
  issue_date DATE NOT NULL,
  due_date DATE NULL,
  amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  paid_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  status ENUM('unpaid','partial','paid') NOT NULL DEFAULT 'unpaid',
  notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL,
  FOREIGN KEY (milestone_id) REFERENCES milestones(id) ON DELETE SET NULL,
  FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS payments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  invoice_id INT NULL,
  project_id INT NULL,
  client_id INT NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  method VARCHAR(40) NOT NULL DEFAULT 'Cash',
  payment_date DATE NOT NULL,
  notes VARCHAR(255) NOT NULL DEFAULT '',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE SET NULL,
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL,
  FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS expense_categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS expenses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  project_id INT NULL,
  category VARCHAR(80) NOT NULL DEFAULT 'General',
  description VARCHAR(255) NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  expense_date DATE NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS project_team (
  project_id INT NOT NULL,
  user_id INT NOT NULL,
  PRIMARY KEY (project_id, user_id),
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- Default settings ----------
INSERT INTO settings (skey, svalue) VALUES
('agency_name','Flowork'),
('tagline','Digital Marketing Agency'),
('logo_path',''),
('phone',''),
('whatsapp',''),
('email',''),
('address',''),
('currency','Rs'),
('invoice_prefix','INV'),
('invoice_next','1'),
('payment_details',''),
('thankyou_note','Thank you for choosing Flowork Digital!')
ON DUPLICATE KEY UPDATE svalue=VALUES(svalue);

-- ---------- Default expense categories ----------
INSERT INTO expense_categories (name) VALUES
('Salaries'),
('Tools & Software'),
('Advertising'),
('Posting & Content'),
('Travel'),
('Office'),
('Other')
ON DUPLICATE KEY UPDATE name=VALUES(name);
