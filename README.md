# PHP Library. Scheduler

Database driven scheduler.

## Usage ##

```
\Sinevia\Scheduler::configure(['pdo' => db()->getPdo()]);

(new \Sinevia\Scheduler)->run();
```

## Helpers ##

- {DIR}

The {DIR} occurrence in a command will be substituted with the current project folder absolute path

For instance: 
php {DIR}/cron/mails-archive.php will be converted to php /var/www/your-project/cron/mails-archive.php

## Screenshots ##

<img src="Screenshot.png" />
