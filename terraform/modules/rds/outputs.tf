output "db_instance_endpoint" {
  description = "RDS Instance Endpoint"
  value       = aws_db_instance.ota.endpoint
}

output "db_instance_id" {
  description = "RDS Instance ID"
  value       = aws_db_instance.ota.id
}

output "db_instance_address" {
  description = "RDS Instance Address"
  value       = aws_db_instance.ota.address
}

output "db_instance_port" {
  description = "RDS Instance Port"
  value       = aws_db_instance.ota.port
}

output "db_password" {
  description = "RDS Master Password"
  value       = random_password.rds.result
  sensitive   = true
}
