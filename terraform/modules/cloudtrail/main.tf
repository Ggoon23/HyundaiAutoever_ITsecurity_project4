# Data source for current account
data "aws_caller_identity" "current" {}

# S3 Bucket for CloudTrail Logs
resource "aws_s3_bucket" "cloudtrail" {
  bucket        = "ota-cloudtrail-logs-${data.aws_caller_identity.current.account_id}"
  force_destroy = true

  tags = {
    Name = "ota-cloudtrail-logs"
  }
}

# S3 Bucket Policy for CloudTrail
resource "aws_s3_bucket_policy" "cloudtrail" {
  bucket = aws_s3_bucket.cloudtrail.id

  policy = jsonencode({
    Version = "2012-10-17"
    Statement = [
      {
        Sid    = "AWSCloudTrailAclCheck"
        Effect = "Allow"
        Principal = {
          Service = "cloudtrail.amazonaws.com"
        }
        Action   = "s3:GetBucketAcl"
        Resource = aws_s3_bucket.cloudtrail.arn
      },
      {
        Sid    = "AWSCloudTrailWrite"
        Effect = "Allow"
        Principal = {
          Service = "cloudtrail.amazonaws.com"
        }
        Action   = "s3:PutObject"
        Resource = "${aws_s3_bucket.cloudtrail.arn}/*"
        Condition = {
          StringEquals = {
            "s3:x-amz-acl" = "bucket-owner-full-control"
          }
        }
      }
    ]
  })
}

# S3 Bucket Lifecycle Configuration
resource "aws_s3_bucket_lifecycle_configuration" "cloudtrail" {
  bucket = aws_s3_bucket.cloudtrail.id

  rule {
    id     = "archive-old-logs"
    status = "Enabled"

    transition {
      days          = 90
      storage_class = "GLACIER"
    }

    expiration {
      days = 365
    }
  }
}

# CloudTrail
resource "aws_cloudtrail" "ota" {
  name                          = "ota-trail"
  s3_bucket_name                = aws_s3_bucket.cloudtrail.id
  include_global_service_events = true
  is_multi_region_trail         = false
  enable_log_file_validation    = true

  event_selector {
    read_write_type           = "All"
    include_management_events = true

    data_resource {
      type   = "AWS::S3::Object"
      values = ["${var.firmware_bucket_arn}/*"]
    }
  }

  insight_selector {
    insight_type = "ApiCallRateInsight"
  }

  tags = {
    Name = "ota-trail"
  }

  depends_on = [aws_s3_bucket_policy.cloudtrail]
}

# EventBridge Rule - Suspicious S3 Activity
resource "aws_cloudwatch_event_rule" "s3_suspicious" {
  name        = "ota-suspicious-s3-access"
  description = "S3 DeleteObject/DeleteBucket 감지"

  event_pattern = jsonencode({
    source      = ["aws.s3"]
    detail-type = ["AWS API Call via CloudTrail"]
    detail = {
      eventName = ["DeleteObject", "DeleteBucket", "PutBucketPolicy"]
    }
  })
}

resource "aws_cloudwatch_event_target" "s3_suspicious" {
  rule      = aws_cloudwatch_event_rule.s3_suspicious.name
  target_id = "SendToSNS"
  arn       = var.sns_security_topic_arn
}

resource "aws_sns_topic_policy" "s3_suspicious" {
  arn = var.sns_security_topic_arn

  policy = jsonencode({
    Version = "2012-10-17"
    Statement = [
      {
        Effect = "Allow"
        Principal = {
          Service = "events.amazonaws.com"
        }
        Action   = "SNS:Publish"
        Resource = var.sns_security_topic_arn
      }
    ]
  })
}

# EventBridge Rule - EC2 Terminate Attempt
resource "aws_cloudwatch_event_rule" "ec2_terminate" {
  name        = "ota-unauthorized-ec2-action"
  description = "EC2 Terminate/Stop 감지"

  event_pattern = jsonencode({
    source      = ["aws.ec2"]
    detail-type = ["AWS API Call via CloudTrail"]
    detail = {
      eventName = ["TerminateInstances", "StopInstances"]
    }
  })
}

resource "aws_cloudwatch_event_target" "ec2_terminate" {
  rule      = aws_cloudwatch_event_rule.ec2_terminate.name
  target_id = "SendToSNS"
  arn       = var.sns_security_topic_arn
}

# EventBridge Rule - RDS Modification
resource "aws_cloudwatch_event_rule" "rds_modification" {
  name        = "ota-rds-modification"
  description = "RDS 삭제/수정 감지"

  event_pattern = jsonencode({
    source      = ["aws.rds"]
    detail-type = ["AWS API Call via CloudTrail"]
    detail = {
      eventName = ["DeleteDBInstance", "ModifyDBInstance", "DeleteDBSnapshot"]
    }
  })
}

resource "aws_cloudwatch_event_target" "rds_modification" {
  rule      = aws_cloudwatch_event_rule.rds_modification.name
  target_id = "SendToSNS"
  arn       = var.sns_security_topic_arn
}
