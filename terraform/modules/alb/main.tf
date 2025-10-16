# Target Group
resource "aws_lb_target_group" "ota" {
  name     = "ota-tg"
  port     = 80
  protocol = "HTTP"
  vpc_id   = var.vpc_id

  health_check {
    enabled             = true
    healthy_threshold   = 2
    unhealthy_threshold = 3
    timeout             = 5
    interval            = 30
    path                = "/health"
    protocol            = "HTTP"
    matcher             = "200"
  }

  deregistration_delay = 30

  tags = {
    Name = "ota-target-group"
  }
}

# Application Load Balancer
resource "aws_lb" "ota" {
  name               = "ota-alb"
  internal           = false
  load_balancer_type = "application"
  security_groups    = [var.sg_alb_id]
  subnets            = var.public_subnet_ids

  enable_deletion_protection       = false
  enable_http2                     = true
  enable_cross_zone_load_balancing = true

  tags = {
    Name = "ota-alb"
  }
}

# HTTP Listener (Redirect to HTTPS)
resource "aws_lb_listener" "http" {
  load_balancer_arn = aws_lb.ota.arn
  port              = 80
  protocol          = "HTTP"

  default_action {
    type = "redirect"

    redirect {
      port        = "443"
      protocol    = "HTTPS"
      status_code = "HTTP_301"
    }
  }
}

# HTTPS Listener (only if certificate_arn is provided)
resource "aws_lb_listener" "https" {
  count = var.certificate_arn != "" ? 1 : 0

  load_balancer_arn = aws_lb.ota.arn
  port              = 443
  protocol          = "HTTPS"
  ssl_policy        = "ELBSecurityPolicy-TLS-1-2-2017-01"
  certificate_arn   = var.certificate_arn

  default_action {
    type             = "forward"
    target_group_arn = aws_lb_target_group.ota.arn
  }
}

# Path-based Routing Rules
resource "aws_lb_listener_rule" "firmware" {
  count = var.certificate_arn != "" ? 1 : 0

  listener_arn = aws_lb_listener.https[0].arn
  priority     = 100

  action {
    type             = "forward"
    target_group_arn = aws_lb_target_group.ota.arn
  }

  condition {
    path_pattern {
      values = ["/firmware/*"]
    }
  }
}

resource "aws_lb_listener_rule" "metadata" {
  count = var.certificate_arn != "" ? 1 : 0

  listener_arn = aws_lb_listener.https[0].arn
  priority     = 101

  action {
    type             = "forward"
    target_group_arn = aws_lb_target_group.ota.arn
  }

  condition {
    path_pattern {
      values = ["/metadata/*"]
    }
  }
}

resource "aws_lb_listener_rule" "vehicle" {
  count = var.certificate_arn != "" ? 1 : 0

  listener_arn = aws_lb_listener.https[0].arn
  priority     = 102

  action {
    type             = "forward"
    target_group_arn = aws_lb_target_group.ota.arn
  }

  condition {
    path_pattern {
      values = ["/vehicle/*"]
    }
  }
}

resource "aws_lb_listener_rule" "deploy" {
  count = var.certificate_arn != "" ? 1 : 0

  listener_arn = aws_lb_listener.https[0].arn
  priority     = 103

  action {
    type             = "forward"
    target_group_arn = aws_lb_target_group.ota.arn
  }

  condition {
    path_pattern {
      values = ["/deploy/*"]
    }
  }
}
