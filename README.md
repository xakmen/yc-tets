# Engagement Email Campaign

Yii2 console command for sending simple engagement emails.

## Usage

~~~bash
php yii campaign/send-engagement-emails [options]
~~~

## Options

- `--dryRun=1` (default)  
  Only shows how many emails would be sent, nothing is delivered.
- `--dryRun=0`  
  Sends emails (stored in `runtime/mail/` when `useFileTransport=true`).
- `--hours=24`  
  Time window in hours to check user activity (default: 24).
- `--batch=5000`  
  Batch size for fetching users from MySQL (default: 5000).

## Examples

### Dry-run for last 24h
~~~bash
php yii campaign/send-engagement-emails --dryRun=1
~~~

### Send emails for last 12h with batch size 1000
~~~bash
php yii campaign/send-engagement-emails --hours=12 --batch=1000 --dryRun=0
~~~
