output "rds_secret_arn" {
  description = "RDS Credentials Secret ARN"
  value       = aws_secretsmanager_secret.rds_credentials.arn
}

output "rds_secret_name" {
  description = "RDS Credentials Secret Name"
  value       = aws_secretsmanager_secret.rds_credentials.name
}

output "vendor_b_secret_arn" {
  description = "Vendor B API Key Secret ARN"
  value       = aws_secretsmanager_secret.vendor_b.arn
}
