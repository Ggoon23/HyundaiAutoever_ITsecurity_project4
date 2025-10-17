-- OTA Database Schema
-- MySQL 8.0
-- Character Set: UTF8MB4

-- Create devices table
CREATE TABLE IF NOT EXISTS devices (
    device_id VARCHAR(128) PRIMARY KEY,
    secret VARCHAR(128) NOT NULL,
    status ENUM('active', 'blocked') NOT NULL DEFAULT 'active',
    note VARCHAR(255),
    last_seen_at DATETIME(6),
    created_at DATETIME(6) DEFAULT CURRENT_TIMESTAMP(6),
    updated_at DATETIME(6) DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create releases table
CREATE TABLE IF NOT EXISTS releases (
    update_id VARCHAR(128) PRIMARY KEY,
    ecu VARCHAR(64) NOT NULL,
    min_version_lt VARCHAR(32),
    region_csv VARCHAR(256),
    artifact_id VARCHAR(128) NOT NULL,
    target_version VARCHAR(32) NOT NULL,
    s3_bucket VARCHAR(128) NOT NULL,
    s3_key VARCHAR(512) NOT NULL,
    size_bytes BIGINT,
    sha256_hex VARCHAR(64),
    created_at DATETIME(6) DEFAULT CURRENT_TIMESTAMP(6)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create reports table
CREATE TABLE IF NOT EXISTS reports (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    ts DATETIME(6) NOT NULL,
    update_id VARCHAR(128) NOT NULL,
    vin VARCHAR(64) NOT NULL,
    ecu VARCHAR(64) NOT NULL,
    phase ENUM('download', 'install', 'verify', 'done', 'failed') NOT NULL,
    percent INT,
    installed_version VARCHAR(32),
    error VARCHAR(255),
    client_ip VARCHAR(64)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create inquiries table (1x INV website contact form)
CREATE TABLE IF NOT EXISTS inquiries (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    company VARCHAR(200),
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(50) NOT NULL,
    category ENUM('product', 'technical', 'sales', 'partnership', 'other') NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    image_path VARCHAR(512),
    status ENUM('pending', 'in_progress', 'completed') DEFAULT 'pending',
    created_at DATETIME(6) DEFAULT CURRENT_TIMESTAMP(6),
    updated_at DATETIME(6) DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;