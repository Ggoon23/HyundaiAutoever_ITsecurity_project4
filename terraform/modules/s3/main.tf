# Data source for current account
data "aws_caller_identity" "current" {}

# S3 Bucket for Firmware
resource "aws_s3_bucket" "firmware" {
  bucket = "ota-firmware-bucket-${data.aws_caller_identity.current.account_id}"

  tags = {
    Name = "ota-firmware-bucket"
  }
}

# Bucket Versioning
resource "aws_s3_bucket_versioning" "firmware" {
  bucket = aws_s3_bucket.firmware.id

  versioning_configuration {
    status = "Enabled"
  }
}

# Server-Side Encryption
resource "aws_s3_bucket_server_side_encryption_configuration" "firmware" {
  bucket = aws_s3_bucket.firmware.id

  rule {
    apply_server_side_encryption_by_default {
      sse_algorithm = "AES256"
    }
  }
}

# Public Access Block
resource "aws_s3_bucket_public_access_block" "firmware" {
  bucket = aws_s3_bucket.firmware.id

  block_public_acls       = true
  block_public_policy     = true
  ignore_public_acls      = true
  restrict_public_buckets = true
}

# Lifecycle Configuration
resource "aws_s3_bucket_lifecycle_configuration" "firmware" {
  bucket = aws_s3_bucket.firmware.id

  rule {
    id     = "archive-old-versions"
    status = "Enabled"

    noncurrent_version_transition {
      noncurrent_days = 90
      storage_class   = "GLACIER"
    }

    noncurrent_version_expiration {
      noncurrent_days = 180
    }
  }
}

# Bucket Policy - Deny insecure transport
resource "aws_s3_bucket_policy" "firmware" {
  bucket = aws_s3_bucket.firmware.id

  policy = jsonencode({
    Version = "2012-10-17"
    Statement = [
      {
        Sid    = "DenyInsecureTransport"
        Effect = "Deny"
        Principal = "*"
        Action = "s3:*"
        Resource = [
          aws_s3_bucket.firmware.arn,
          "${aws_s3_bucket.firmware.arn}/*"
        ]
        Condition = {
          Bool = {
            "aws:SecureTransport" = "false"
          }
        }
      }
    ]
  })
}

# Create folder structure with .keep files
resource "aws_s3_object" "image_repo_targets" {
  bucket  = aws_s3_bucket.firmware.id
  key     = "image-repo/targets/.keep"
  content = ""
}

resource "aws_s3_object" "image_repo_metadata" {
  bucket  = aws_s3_bucket.firmware.id
  key     = "image-repo/metadata/.keep"
  content = ""
}

resource "aws_s3_object" "director_repo_metadata" {
  bucket  = aws_s3_bucket.firmware.id
  key     = "director-repo/metadata/.keep"
  content = ""
}

resource "aws_s3_object" "certificates" {
  bucket  = aws_s3_bucket.firmware.id
  key     = "certificates/.keep"
  content = ""
}

resource "aws_s3_object" "public_keys" {
  bucket  = aws_s3_bucket.firmware.id
  key     = "public-keys/.keep"
  content = ""
}
