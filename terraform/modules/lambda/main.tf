# Archive Lambda function code
data "archive_file" "lambda_zip" {
  type        = "zip"
  source_file = "${path.module}/lambda_function.py"
  output_path = "${path.module}/lambda_function.zip"
}

# Lambda Function
resource "aws_lambda_function" "canary" {
  filename         = data.archive_file.lambda_zip.output_path
  function_name    = "canary-phase-controller"
  role             = var.lambda_role_arn
  handler          = "lambda_function.lambda_handler"
  source_code_hash = data.archive_file.lambda_zip.output_base64sha256
  runtime          = "python3.11"
  timeout          = 60
  memory_size      = 256

  vpc_config {
    subnet_ids         = var.private_subnet_ids
    security_group_ids = [var.sg_lambda_id]
  }

  environment {
    variables = {
      DB_SECRET_NAME = var.db_secret_name
      REGION         = var.region
      SNS_TOPIC_ARN  = var.sns_topic_arn
    }
  }

  tags = {
    Name = "canary-phase-controller"
  }
}

# EventBridge Rule - Run every 5 minutes
resource "aws_cloudwatch_event_rule" "canary_check" {
  name                = "canary-phase-check"
  description         = "Canary 배포 Phase 체크 (5분 간격)"
  schedule_expression = "rate(5 minutes)"
}

# EventBridge Target
resource "aws_cloudwatch_event_target" "lambda" {
  rule      = aws_cloudwatch_event_rule.canary_check.name
  target_id = "canary-lambda"
  arn       = aws_lambda_function.canary.arn
}

# Lambda Permission for EventBridge
resource "aws_lambda_permission" "allow_eventbridge" {
  statement_id  = "AllowExecutionFromEventBridge"
  action        = "lambda:InvokeFunction"
  function_name = aws_lambda_function.canary.function_name
  principal     = "events.amazonaws.com"
  source_arn    = aws_cloudwatch_event_rule.canary_check.arn
}
