# Random password for Vendor B API Key
resource "random_password" "vendor_b_api_key" {
  length  = 64
  special = false
}

# RDS Credentials Secret
resource "aws_secretsmanager_secret" "rds_credentials" {
  name                    = "ota/rds/credentials"
  recovery_window_in_days = 7

  tags = {
    Name = "ota-rds-credentials"
  }
}

# RDS Credentials Secret Version
resource "aws_secretsmanager_secret_version" "rds_credentials" {
  secret_id = aws_secretsmanager_secret.rds_credentials.id
  secret_string = jsonencode({
    username = "ota_admin"
    password = var.db_password
    engine   = "mysql"
    host     = var.db_instance_address
    port     = var.db_instance_port
    dbname   = "ota_db"
  })
}

# Vendor B API Key Secret
resource "aws_secretsmanager_secret" "vendor_b" {
  name                    = "ota/api-keys/vendor-b"
  recovery_window_in_days = 7

  tags = {
    Name = "ota-vendor-b-api-key"
  }
}

# Vendor B API Key Secret Version
resource "aws_secretsmanager_secret_version" "vendor_b" {
  secret_id = aws_secretsmanager_secret.vendor_b.id
  secret_string = jsonencode({
    api_key    = random_password.vendor_b_api_key.result
    created_at = timestamp()
    expires_at = timeadd(timestamp(), "2160h") # 90 days
  })

  lifecycle {
    ignore_changes = [secret_string]
  }
}
