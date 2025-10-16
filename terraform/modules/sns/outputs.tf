output "alerts_topic_arn" {
  description = "SNS Alerts Topic ARN"
  value       = aws_sns_topic.alerts.arn
}

output "security_topic_arn" {
  description = "SNS Security Topic ARN"
  value       = aws_sns_topic.security.arn
}
