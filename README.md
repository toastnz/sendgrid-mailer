# SilverStripe SendGrid Mailer

Simple mailer module that uses SendGrid API to send emails.

## Requirements

* silverstripe/cms ^4.3.x
* silverstripe/framework ^4.3.x
* sendgrid/sendgrid ^7.3

## Installation

```bash
composer require toastnz/sendgrid-mailer
```

## Configuration

Add the following to your `app.yml`:

```yaml
Toast\SSSendGrid\SendGridMailer:
  api_key: 'YOUR_SENDGRID_API_KEY'
  default_email: 'from@example.com' # Required for password reset form
```

## Usage

Simply use the Email class provided by SilverStripe framework:

```php
$email = new SilverStripe\Control\Email\Email();
$email->setFrom('from@example.com', 'John Doe');
$email->setTo('to@example.com', 'Jane Doe');
$email->setSubject('This is a test email');
$email->setBody('Hello there, this was sent using SendGrid');
$email->addAttachment('path/to/file.pdf', 'document.pdf');
$email->send();
```
