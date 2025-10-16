output "alb_dns_name" {
  description = "ALB DNS Name"
  value       = module.alb.alb_dns_name
}

output "rds_endpoint" {
  description = "RDS Endpoint"
  value       = module.rds.db_instance_endpoint
}

output "s3_bucket_name" {
  description = "S3 Firmware Bucket Name"
  value       = module.s3.bucket_name
}

output "ec2_asg_name" {
  description = "EC2 Auto Scaling Group Name"
  value       = module.ec2.autoscaling_group_name
}

output "lambda_function_name" {
  description = "Lambda Function Name"
  value       = module.lambda.lambda_function_name
}

output "sns_alerts_topic_arn" {
  description = "SNS Alerts Topic ARN"
  value       = module.sns.alerts_topic_arn
}

output "rds_secret_name" {
  description = "RDS Credentials Secret Name"
  value       = module.secrets_manager.rds_secret_name
}
