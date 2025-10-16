# SNS Topic for General Alerts
resource "aws_sns_topic" "alerts" {
  name         = "ota-alerts"
  display_name = "OTA System Alerts"

  tags = {
    Name = "ota-alerts"
  }
}

# SNS Topic for Security Alerts
resource "aws_sns_topic" "security" {
  name         = "ota-cloudtrail-alerts"
  display_name = "OTA CloudTrail Alerts"

  tags = {
    Name = "ota-cloudtrail-alerts"
  }
}

# Email Subscription for Alerts
resource "aws_sns_topic_subscription" "alerts_email" {
  topic_arn = aws_sns_topic.alerts.arn
  protocol  = "email"
  endpoint  = var.email_address
}

# Email Subscription for Security Alerts
resource "aws_sns_topic_subscription" "security_email" {
  topic_arn = aws_sns_topic.security.arn
  protocol  = "email"
  endpoint  = var.email_address
}
