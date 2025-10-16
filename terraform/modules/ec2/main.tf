# Data source for Amazon Linux 2023 AMI
data "aws_ami" "amazon_linux_2023" {
  most_recent = true
  owners      = ["amazon"]

  filter {
    name   = "name"
    values = ["al2023-ami-*-x86_64"]
  }

  filter {
    name   = "virtualization-type"
    values = ["hvm"]
  }
}

# Launch Template
resource "aws_launch_template" "ota" {
  name_prefix   = "ota-server-"
  image_id      = data.aws_ami.amazon_linux_2023.id
  instance_type = "t2.micro"

  iam_instance_profile {
    name = var.instance_profile_name
  }

  vpc_security_group_ids = [var.sg_ec2_id]

  user_data = base64encode(templatefile("${path.module}/../../scripts/user-data.sh", {
    db_secret_name = var.db_secret_name
    region         = var.region
  }))

  tag_specifications {
    resource_type = "instance"
    tags = {
      Name = "ota-server"
    }
  }

  lifecycle {
    create_before_destroy = true
  }
}

# Auto Scaling Group
resource "aws_autoscaling_group" "ota" {
  name                = "ota-asg"
  vpc_zone_identifier = var.public_subnet_ids
  min_size            = 1
  max_size            = 2
  desired_capacity    = 1
  health_check_type   = "ELB"
  health_check_grace_period = 300

  launch_template {
    id      = aws_launch_template.ota.id
    version = "$Latest"
  }

  target_group_arns = [var.target_group_arn]

  tag {
    key                 = "Name"
    value               = "ota-server"
    propagate_at_launch = true
  }
}

# Auto Scaling Policy - Target Tracking
resource "aws_autoscaling_policy" "cpu_target" {
  name                   = "ota-cpu-target"
  autoscaling_group_name = aws_autoscaling_group.ota.name
  policy_type            = "TargetTrackingScaling"

  target_tracking_configuration {
    predefined_metric_specification {
      predefined_metric_type = "ASGAverageCPUUtilization"
    }
    target_value = 70.0
  }
}
