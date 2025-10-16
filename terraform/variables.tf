variable "region" {
  description = "AWS Region"
  type        = string
  default     = "ap-northeast-2"
}

variable "environment" {
  description = "Environment (development/production)"
  type        = string
  default     = "development"
}

variable "vpc_cidr" {
  description = "VPC CIDR block"
  type        = string
  default     = "10.0.0.0/16"
}

variable "availability_zones" {
  description = "Availability Zones"
  type        = list(string)
  default     = ["ap-northeast-2a", "ap-northeast-2b"]
}

variable "public_subnet_cidrs" {
  description = "Public subnet CIDR blocks"
  type        = list(string)
  default     = ["10.0.1.0/24", "10.0.2.0/24"]
}

variable "private_subnet_cidrs" {
  description = "Private subnet CIDR blocks"
  type        = list(string)
  default     = ["10.0.11.0/24", "10.0.12.0/24"]
}

variable "admin_ssh_ip" {
  description = "Admin SSH access IP (CIDR)"
  type        = string
  default     = "0.0.0.0/0" # 실제 IP로 변경 필요
}

variable "alert_email" {
  description = "Alert email address"
  type        = string
  default     = "xogoon1325@gmail.com"
}

variable "certificate_arn" {
  description = "ACM Certificate ARN for ALB HTTPS"
  type        = string
  default     = "" # ACM에서 사전 생성 필요
}

variable "ec2_key_name" {
  description = "EC2 Key Pair name for SSH access"
  type        = string
  default     = "project4"
}
