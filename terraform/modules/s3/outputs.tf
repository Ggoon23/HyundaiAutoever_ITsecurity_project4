output "bucket_name" {
  description = "S3 Bucket Name"
  value       = aws_s3_bucket.firmware.id
}

output "bucket_arn" {
  description = "S3 Bucket ARN"
  value       = aws_s3_bucket.firmware.arn
}

output "bucket_domain_name" {
  description = "S3 Bucket Domain Name"
  value       = aws_s3_bucket.firmware.bucket_domain_name
}
