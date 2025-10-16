-- MySQL 데이터베이스 스키마

-- vehicles 테이블
CREATE TABLE vehicles (
    vehicle_id VARCHAR(50) PRIMARY KEY,
    vin VARCHAR(17) UNIQUE NOT NULL,
    model VARCHAR(50),
    current_fw_version VARCHAR(20),
    last_update TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- firmware_metadata 테이블
CREATE TABLE firmware_metadata (
    firmware_id INT AUTO_INCREMENT PRIMARY KEY,
    version VARCHAR(20) UNIQUE NOT NULL,
    checksum VARCHAR(64) NOT NULL,
    file_size BIGINT,
    s3_path VARCHAR(500),
    signature TEXT,
    release_date TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- deployment_history 테이블
CREATE TABLE deployment_history (
    deployment_id INT AUTO_INCREMENT PRIMARY KEY,
    firmware_version VARCHAR(20),
    start_time TIMESTAMP NULL DEFAULT NULL,
    end_time TIMESTAMP NULL DEFAULT NULL,
    target_vehicles TEXT,
    status VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- rollback_history 테이블
CREATE TABLE rollback_history (
    rollback_id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id VARCHAR(50),
    from_version VARCHAR(20),
    to_version VARCHAR(20),
    rollback_time TIMESTAMP NULL DEFAULT NULL,
    reason TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- canary_deployments 테이블
CREATE TABLE canary_deployments (
    canary_id INT AUTO_INCREMENT PRIMARY KEY,
    deployment_id INT,
    phase INT,
    target_percentage INT,
    success_count INT DEFAULT 0,
    fail_count INT DEFAULT 0,
    status VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (deployment_id) REFERENCES deployment_history(deployment_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- vehicle_groups 테이블
CREATE TABLE vehicle_groups (
    group_id VARCHAR(50) PRIMARY KEY,
    group_name VARCHAR(100),
    vehicle_list TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- director_targets 테이블
CREATE TABLE director_targets (
    target_id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id VARCHAR(50),
    target_version VARCHAR(20),
    metadata_json TEXT,
    signature TEXT,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- director_metadata 테이블
CREATE TABLE director_metadata (
    metadata_id INT AUTO_INCREMENT PRIMARY KEY,
    metadata_type VARCHAR(50),
    content TEXT,
    signature TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- vehicle_status 테이블
CREATE TABLE vehicle_status (
    vehicle_id VARCHAR(50) PRIMARY KEY,
    is_parked TINYINT(1),  -- MySQL의 BOOLEAN은 TINYINT(1)로 구현됨
    battery_level INT,
    network_quality VARCHAR(20),
    last_heartbeat TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- audit_logs 테이블
CREATE TABLE audit_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actor VARCHAR(100),
    action VARCHAR(100),
    target VARCHAR(200),
    result VARCHAR(50),
    details TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 인덱스 생성
CREATE INDEX idx_vehicles_vin ON vehicles(vin);
CREATE INDEX idx_firmware_version ON firmware_metadata(version);
CREATE INDEX idx_deployment_status ON deployment_history(status);
CREATE INDEX idx_audit_timestamp ON audit_logs(timestamp);
CREATE INDEX idx_vehicle_status_heartbeat ON vehicle_status(last_heartbeat);
