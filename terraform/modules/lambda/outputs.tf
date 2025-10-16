output "lambda_function_arn" {
  description = "Lambda Function ARN"
  value       = aws_lambda_function.canary.arn
}

output "lambda_function_name" {
  description = "Lambda Function Name"
  value       = aws_lambda_function.canary.function_name
}
