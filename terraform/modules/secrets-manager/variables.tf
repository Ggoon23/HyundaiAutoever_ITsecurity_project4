variable "db_instance_address" {
  description = "RDS Instance Address"
  type        = string
}

variable "db_instance_port" {
  description = "RDS Instance Port"
  type        = number
}

variable "db_password" {
  description = "RDS Master Password"
  type        = string
  sensitive   = true
}
