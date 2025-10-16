output "sg_alb_id" {
  description = "ALB Security Group ID"
  value       = aws_security_group.alb.id
}

output "sg_ec2_id" {
  description = "EC2 Security Group ID"
  value       = aws_security_group.ec2.id
}

output "sg_rds_id" {
  description = "RDS Security Group ID"
  value       = aws_security_group.rds.id
}

output "sg_lambda_id" {
  description = "Lambda Security Group ID"
  value       = aws_security_group.lambda.id
}
