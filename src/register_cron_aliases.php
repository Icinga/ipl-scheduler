<?php

use Cron\CronExpression;

$loader = function () use (&$loader) {
    if (! CronExpression::supportsAlias('@minutely')) {
        CronExpression::registerAlias('@minutely', '* * * * *');
    }

    if (! CronExpression::supportsAlias('@quarterly')) {
        CronExpression::registerAlias('@quarterly', '0 0 1 */3 *');
    }

    spl_autoload_unregister($loader);
    unset($loader);
};

spl_autoload_register($loader, true, true);
