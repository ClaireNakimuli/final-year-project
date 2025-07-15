-- Create jobs table if it doesn't exist
CREATE TABLE IF NOT EXISTS jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    company VARCHAR(255) NOT NULL,
    type ENUM('Full-time', 'Part-time', 'Contract', 'Remote') NOT NULL,
    location VARCHAR(255) NOT NULL,
    experience VARCHAR(100) NOT NULL,
    salary VARCHAR(100),
    description TEXT NOT NULL,
    requirements TEXT,
    tags VARCHAR(255),
    status ENUM('active', 'inactive', 'draft') DEFAULT 'active',
    posted_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (posted_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci; 