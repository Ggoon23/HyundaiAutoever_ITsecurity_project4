variable "asg_name" {
  description = "Auto Scaling Group Name"
  type        = string
}

variable "alb_arn_suffix" {
  description = "ALB ARN Suffix"
  type        = string
}

variable "target_group_arn_suffix" {
  description = "Target Group ARN Suffix"
  type        = string
}

variable "db_instance_id" {
  description = "RDS Instance ID"
  type        = string
}

variable "sns_topic_arn" {
  description = "SNS Topic ARN for alarms"
  type        = string
}
