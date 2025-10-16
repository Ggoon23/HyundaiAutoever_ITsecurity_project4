terraform {
  required_version = ">= 1.5"

  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "~> 5.0"
    }
    random = {
      source  = "hashicorp/random"
      version = "~> 3.5"
    }
  }

  # State 백엔드 (선택사항)
  # backend "s3" {
  #   bucket = "ota-terraform-state"
  #   key    = "ota/terraform.tfstate"
  #   region = "ap-northeast-2"
  # }
}

provider "aws" {
  region = var.region

  default_tags {
    tags = {
      Project     = "OTA-System"
      Environment = var.environment
      ManagedBy   = "Terraform"
    }
  }
}

# VPC 모듈
module "vpc" {
  source = "./modules/vpc"

  vpc_cidr             = var.vpc_cidr
  availability_zones   = var.availability_zones
  public_subnet_cidrs  = var.public_subnet_cidrs
  private_subnet_cidrs = var.private_subnet_cidrs
}

# Security Groups 모듈
module "security_groups" {
  source = "./modules/security-groups"

  vpc_id       = module.vpc.vpc_id
  admin_ssh_ip = var.admin_ssh_ip
}

# S3 모듈
module "s3" {
  source = "./modules/s3"
}

# IAM 모듈
module "iam" {
  source = "./modules/iam"

  firmware_bucket_name = module.s3.bucket_name
}

# RDS 모듈
module "rds" {
  source = "./modules/rds"

  vpc_id             = module.vpc.vpc_id
  private_subnet_ids = module.vpc.private_subnet_ids
  sg_rds_id          = module.security_groups.sg_rds_id
}

# Secrets Manager 모듈
module "secrets_manager" {
  source = "./modules/secrets-manager"

  db_instance_address = module.rds.db_instance_address
  db_instance_port    = module.rds.db_instance_port
  db_password         = module.rds.db_password
}

# SNS 모듈
module "sns" {
  source = "./modules/sns"

  email_address = var.alert_email
}

# ALB 모듈
module "alb" {
  source = "./modules/alb"

  vpc_id            = module.vpc.vpc_id
  public_subnet_ids = module.vpc.public_subnet_ids
  sg_alb_id         = module.security_groups.sg_alb_id
  certificate_arn   = var.certificate_arn
}

# EC2 모듈
module "ec2" {
  source = "./modules/ec2"

  vpc_id                = module.vpc.vpc_id
  public_subnet_ids     = module.vpc.public_subnet_ids
  sg_ec2_id             = module.security_groups.sg_ec2_id
  instance_profile_name = module.iam.ec2_instance_profile_name
  target_group_arn      = module.alb.target_group_arn
  db_secret_name        = module.secrets_manager.rds_secret_name
  region                = var.region
}

# Lambda 모듈
module "lambda" {
  source = "./modules/lambda"

  vpc_id             = module.vpc.vpc_id
  private_subnet_ids = module.vpc.private_subnet_ids
  sg_lambda_id       = module.security_groups.sg_lambda_id
  lambda_role_arn    = module.iam.lambda_role_arn
  db_secret_name     = module.secrets_manager.rds_secret_name
  sns_topic_arn      = module.sns.alerts_topic_arn
  region             = var.region
}

# CloudWatch 모듈
module "cloudwatch" {
  source = "./modules/cloudwatch"

  asg_name                = module.ec2.autoscaling_group_name
  alb_arn_suffix          = module.alb.alb_arn_suffix
  target_group_arn_suffix = module.alb.target_group_arn_suffix
  db_instance_id          = module.rds.db_instance_id
  sns_topic_arn           = module.sns.alerts_topic_arn
}

# CloudTrail 모듈
module "cloudtrail" {
  source = "./modules/cloudtrail"

  firmware_bucket_arn    = module.s3.bucket_arn
  sns_security_topic_arn = module.sns.security_topic_arn
}
