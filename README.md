# PHP Library. Scheduler

Database driven scheduler


## Usage ##

```
\Sinevia\Scheduler::configure(['pdo' => db()->getPdo()]);

(new \Sinevia\Scheduler)->run();
```
