output "ec2_role_arn" {
  description = "EC2 IAM Role ARN"
  value       = aws_iam_role.ec2.arn
}

output "ec2_instance_profile_name" {
  description = "EC2 Instance Profile Name"
  value       = aws_iam_instance_profile.ec2.name
}

output "lambda_role_arn" {
  description = "Lambda IAM Role ARN"
  value       = aws_iam_role.lambda.arn
}
