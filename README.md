# AWS EC2 Load Test + ALB + Auto Scaling Demo

This PHP project reproduces the simple AWS Load Test page shown in the reference screenshots.

## What it demonstrates

- EC2 Instance ID from the EC2 Instance Metadata Service
- Availability Zone from EC2 metadata
- CPU-load test page at `/load.php`
- Automatic refresh every 5 seconds
- Suitable for an Application Load Balancer Target Group
- Suitable for an Auto Scaling Group

## 1. Install Apache + PHP on Ubuntu EC2

```bash
sudo apt update
sudo apt install -y apache2 php php-curl
sudo systemctl enable --now apache2
```

## 2. Copy the project

Copy `index.php` and `load.php` into:

```bash
sudo cp index.php /var/www/html/
sudo cp load.php /var/www/html/
```

Then:

```bash
sudo systemctl restart apache2
```

Open:

```text
http://EC2_PUBLIC_IP/
```

## 3. ALB

Create an Application Load Balancer:

- Internet-facing
- Listener: HTTP 80
- Target Group: HTTP 80
- Health check path: `/`

The EC2 security group should allow HTTP 80 from the ALB security group.

## 4. Auto Scaling

Create an AMI from the working EC2, then a Launch Template using that AMI.

Create an Auto Scaling Group:

```text
Minimum: 1
Desired: 1
Maximum: 3
```

Attach the ALB Target Group.

Use Target Tracking:

```text
Average CPU utilization: 60%
```

When `/load.php` creates CPU pressure, CloudWatch/Auto Scaling can scale out additional EC2 instances.

## Important

For production, use HTTPS on the ALB and a proper application health endpoint. The displayed CPU percentage in this demo is intentionally presentation-oriented; use CloudWatch `CPUUtilization` for the actual Auto Scaling metric.
