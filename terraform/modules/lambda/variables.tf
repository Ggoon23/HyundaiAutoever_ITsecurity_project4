variable "vpc_id" {
  description = "VPC ID"
  type        = string
}

variable "private_subnet_ids" {
  description = "Private subnet IDs"
  type        = list(string)
}

variable "sg_lambda_id" {
  description = "Lambda Security Group ID"
  type        = string
}

variable "lambda_role_arn" {
  description = "Lambda IAM Role ARN"
  type        = string
}

variable "db_secret_name" {
  description = "RDS Secret Name"
  type        = string
}

variable "sns_topic_arn" {
  description = "SNS Topic ARN for alerts"
  type        = string
}

variable "region" {
  description = "AWS Region"
  type        = string
}
