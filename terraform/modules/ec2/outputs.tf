output "launch_template_id" {
  description = "Launch Template ID"
  value       = aws_launch_template.ota.id
}

output "autoscaling_group_name" {
  description = "Auto Scaling Group Name"
  value       = aws_autoscaling_group.ota.name
}

output "autoscaling_group_arn" {
  description = "Auto Scaling Group ARN"
  value       = aws_autoscaling_group.ota.arn
}
