variable "vpc_id" {
  description = "VPC ID"
  type        = string
}

variable "private_subnet_ids" {
  description = "Private subnet IDs"
  type        = list(string)
}

variable "sg_rds_id" {
  description = "RDS Security Group ID"
  type        = string
}
