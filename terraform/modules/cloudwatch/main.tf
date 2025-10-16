# CloudWatch Log Groups
resource "aws_cloudwatch_log_group" "ec2" {
  name              = "/aws/ec2/ota-server"
  retention_in_days = 14

  tags = {
    Name = "ota-ec2-logs"
  }
}

resource "aws_cloudwatch_log_group" "alb" {
  name              = "/aws/alb/ota-alb"
  retention_in_days = 14

  tags = {
    Name = "ota-alb-logs"
  }
}

resource "aws_cloudwatch_log_group" "rds" {
  name              = "/aws/rds/instance/ota-mysql/error"
  retention_in_days = 14

  tags = {
    Name = "ota-rds-logs"
  }
}

resource "aws_cloudwatch_log_group" "lambda" {
  name              = "/aws/lambda/canary-controller"
  retention_in_days = 14

  tags = {
    Name = "ota-lambda-logs"
  }
}

# EC2 Alarms
resource "aws_cloudwatch_metric_alarm" "ec2_cpu" {
  alarm_name          = "ota-ec2-high-cpu"
  comparison_operator = "GreaterThanThreshold"
  evaluation_periods  = 2
  metric_name         = "CPUUtilization"
  namespace           = "AWS/EC2"
  period              = 300
  statistic           = "Average"
  threshold           = 80
  alarm_description   = "EC2 CPU 사용률이 80%를 초과했습니다"
  alarm_actions       = [var.sns_topic_arn]

  dimensions = {
    AutoScalingGroupName = var.asg_name
  }
}

resource "aws_cloudwatch_metric_alarm" "ec2_status" {
  alarm_name          = "ota-ec2-status-check"
  comparison_operator = "GreaterThanThreshold"
  evaluation_periods  = 2
  metric_name         = "StatusCheckFailed"
  namespace           = "AWS/EC2"
  period              = 60
  statistic           = "Maximum"
  threshold           = 0
  alarm_description   = "EC2 Status Check 실패"
  alarm_actions       = [var.sns_topic_arn]

  dimensions = {
    AutoScalingGroupName = var.asg_name
  }
}

# ALB Alarms
resource "aws_cloudwatch_metric_alarm" "alb_unhealthy" {
  alarm_name          = "ota-alb-unhealthy-hosts"
  comparison_operator = "GreaterThanThreshold"
  evaluation_periods  = 2
  metric_name         = "UnHealthyHostCount"
  namespace           = "AWS/ApplicationELB"
  period              = 60
  statistic           = "Maximum"
  threshold           = 0
  alarm_description   = "ALB에 Unhealthy Host 존재"
  alarm_actions       = [var.sns_topic_arn]

  dimensions = {
    LoadBalancer = var.alb_arn_suffix
    TargetGroup  = var.target_group_arn_suffix
  }
}

resource "aws_cloudwatch_metric_alarm" "alb_response_time" {
  alarm_name          = "ota-alb-high-response-time"
  comparison_operator = "GreaterThanThreshold"
  evaluation_periods  = 2
  metric_name         = "TargetResponseTime"
  namespace           = "AWS/ApplicationELB"
  period              = 300
  statistic           = "Average"
  threshold           = 2
  alarm_description   = "ALB 응답 시간이 2초를 초과했습니다"
  alarm_actions       = [var.sns_topic_arn]

  dimensions = {
    LoadBalancer = var.alb_arn_suffix
  }
}

resource "aws_cloudwatch_metric_alarm" "alb_5xx" {
  alarm_name          = "ota-alb-5xx-errors"
  comparison_operator = "GreaterThanThreshold"
  evaluation_periods  = 1
  metric_name         = "HTTPCode_Target_5XX_Count"
  namespace           = "AWS/ApplicationELB"
  period              = 300
  statistic           = "Sum"
  threshold           = 10
  alarm_description   = "ALB 5xx 에러가 10건을 초과했습니다"
  alarm_actions       = [var.sns_topic_arn]

  dimensions = {
    LoadBalancer = var.alb_arn_suffix
  }
}

# RDS Alarms
resource "aws_cloudwatch_metric_alarm" "rds_cpu" {
  alarm_name          = "ota-rds-high-cpu"
  comparison_operator = "GreaterThanThreshold"
  evaluation_periods  = 2
  metric_name         = "CPUUtilization"
  namespace           = "AWS/RDS"
  period              = 300
  statistic           = "Average"
  threshold           = 80
  alarm_description   = "RDS CPU 사용률이 80%를 초과했습니다"
  alarm_actions       = [var.sns_topic_arn]

  dimensions = {
    DBInstanceIdentifier = var.db_instance_id
  }
}

resource "aws_cloudwatch_metric_alarm" "rds_storage" {
  alarm_name          = "ota-rds-low-storage"
  comparison_operator = "LessThanThreshold"
  evaluation_periods  = 1
  metric_name         = "FreeStorageSpace"
  namespace           = "AWS/RDS"
  period              = 300
  statistic           = "Average"
  threshold           = 2147483648 # 2GB in bytes
  alarm_description   = "RDS 여유 공간이 2GB 미만입니다"
  alarm_actions       = [var.sns_topic_arn]

  dimensions = {
    DBInstanceIdentifier = var.db_instance_id
  }
}

resource "aws_cloudwatch_metric_alarm" "rds_connections" {
  alarm_name          = "ota-rds-high-connections"
  comparison_operator = "GreaterThanThreshold"
  evaluation_periods  = 2
  metric_name         = "DatabaseConnections"
  namespace           = "AWS/RDS"
  period              = 300
  statistic           = "Average"
  threshold           = 50
  alarm_description   = "RDS 연결 수가 50을 초과했습니다"
  alarm_actions       = [var.sns_topic_arn]

  dimensions = {
    DBInstanceIdentifier = var.db_instance_id
  }
}

# CloudWatch Dashboard
resource "aws_cloudwatch_dashboard" "ota_main" {
  dashboard_name = "ota-main-dashboard"

  dashboard_body = jsonencode({
    widgets = [
      {
        type = "metric"
        properties = {
          metrics = [
            ["AWS/EC2", "CPUUtilization", { stat = "Average" }],
            ["AWS/ApplicationELB", "TargetResponseTime", { stat = "Average" }],
            ["AWS/RDS", "CPUUtilization", { stat = "Average" }]
          ]
          period = 300
          stat   = "Average"
          region = "ap-northeast-2"
          title  = "System Overview"
        }
      }
    ]
  })
}
