<?php

use App\Http\Middleware\SetTeamUrlDefaults;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withCommands([
        __DIR__.'/../app/Console/Commands',
    ])
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            SetTeamUrlDefaults::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('status:check-services')->everyMinute()->withoutOverlapping();
        $schedule->command('status:discord-snapshot --auto')->everyFiveMinutes()->withoutOverlapping();
        $schedule->command('app:update --auto')->everyFifteenMinutes()->withoutOverlapping();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
