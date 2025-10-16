-- OTA Database Schema
-- MySQL 8.0
-- Character Set: UTF8MB4

class DeviceStatusEnum(str, enum.Enum):
    active = "active"
    blocked = "blocked"

class DeviceORM(Base):
    __tablename__ = "devices"
    device_id = Column(String(128), primary_key=True)
    secret = Column(String(128), nullable=False)
    status = Column(Enum(DeviceStatusEnum), nullable=False, default=DeviceStatusEnum.active)
    note = Column(String(255))
    last_seen_at = Column(DateTime(fsp=6))
    created_at = Column(DateTime(fsp=6), server_default=func.now())
    updated_at = Column(DateTime(fsp=6), server_default=func.now(), onupdate=func.now())

class ReleaseORM(Base):
    __tablename__ = "releases"
    update_id = Column(String(128), primary_key=True)
    ecu = Column(String(64), nullable=False)
    min_version_lt = Column(String(32))
    region_csv = Column(String(256))
    artifact_id = Column(String(128), nullable=False)
    target_version = Column(String(32), nullable=False)
    s3_bucket = Column(String(128), nullable=False)
    s3_key = Column(String(512), nullable=False)
    size_bytes = Column(BigInteger)
    sha256_hex = Column(String(64))
    created_at = Column(DateTime(fsp=6), server_default=func.now())

class ReportORM(Base):
    __tablename__ = "reports"
    id = Column(BigInteger, primary_key=True, autoincrement=True)
    ts = Column(DateTime(fsp=6), nullable=False)
    update_id = Column(String(128), nullable=False)
    vin = Column(String(64), nullable=False)
    ecu = Column(String(64), nullable=False)
    phase = Column(Enum("download","install","verify","done","failed", name="phase_enum"), nullable=False)
    percent = Column(Integer)
    installed_version = Column(String(32))
    error = Column(String(255))
    client_ip = Column(String(64))
```