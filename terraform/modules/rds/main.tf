# Random password for RDS
resource "random_password" "rds" {
  length  = 32
  special = true
}

# DB Subnet Group
resource "aws_db_subnet_group" "ota" {
  name       = "ota-db-subnet-group"
  subnet_ids = var.private_subnet_ids

  tags = {
    Name = "ota-db-subnet-group"
  }
}

# RDS MySQL Instance
resource "aws_db_instance" "ota" {
  identifier           = "ota-mysql"
  engine               = "mysql"
  engine_version       = "8.0.35"
  instance_class       = "db.t3.micro"
  allocated_storage    = 20
  max_allocated_storage = 50
  storage_type         = "gp3"
  storage_encrypted    = true

  db_name  = "ota_db"
  username = "ota_admin"
  password = random_password.rds.result

  multi_az               = false
  publicly_accessible    = false
  vpc_security_group_ids = [var.sg_rds_id]
  db_subnet_group_name   = aws_db_subnet_group.ota.name

  backup_retention_period = 3
  backup_window          = "03:00-04:00"
  maintenance_window     = "sun:04:00-sun:05:00"

  enabled_cloudwatch_logs_exports = ["error", "general", "slowquery"]

  skip_final_snapshot       = false
  final_snapshot_identifier = "ota-mysql-final-snapshot-${formatdate("YYYY-MM-DD-hhmm", timestamp())}"

  parameter_group_name = aws_db_parameter_group.ota.name

  tags = {
    Name        = "ota-mysql"
    Environment = "development"
  }

  lifecycle {
    ignore_changes = [final_snapshot_identifier]
  }
}

# DB Parameter Group for MySQL
resource "aws_db_parameter_group" "ota" {
  name   = "ota-mysql-params"
  family = "mysql8.0"

  parameter {
    name  = "character_set_server"
    value = "utf8mb4"
  }

  parameter {
    name  = "collation_server"
    value = "utf8mb4_unicode_ci"
  }

  parameter {
    name  = "max_connections"
    value = "100"
  }

  tags = {
    Name = "ota-mysql-parameter-group"
  }
}
