output "alb_arn" {
  description = "ALB ARN"
  value       = aws_lb.ota.arn
}

output "alb_dns_name" {
  description = "ALB DNS Name"
  value       = aws_lb.ota.dns_name
}

output "alb_zone_id" {
  description = "ALB Zone ID"
  value       = aws_lb.ota.zone_id
}

output "target_group_arn" {
  description = "Target Group ARN"
  value       = aws_lb_target_group.ota.arn
}

output "alb_arn_suffix" {
  description = "ALB ARN Suffix for CloudWatch"
  value       = aws_lb.ota.arn_suffix
}

output "target_group_arn_suffix" {
  description = "Target Group ARN Suffix for CloudWatch"
  value       = aws_lb_target_group.ota.arn_suffix
}
