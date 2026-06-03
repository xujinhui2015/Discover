<?php

/*
 * // +----------------------------------------------------------------------
 * // | erp
 * // +----------------------------------------------------------------------
 * // | Copyright (c) 2006~2020 erp All rights reserved.
 * // +----------------------------------------------------------------------
 * // | Licensed ( LICENSE-1.0.0 )
 * // +----------------------------------------------------------------------
 * // | Author: yxx <1365831278@qq.com>
 * // +----------------------------------------------------------------------
 */

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule
             ->command('check:inventory-status')
             ->everyMinute();

        // 每天凌晨清理 Telescope 监控记录，仅保留最近一个月，避免 telescope_entries 表过大。
        // Telescope 在 require-dev，生产环境未安装，无法使用 telescope:prune 命令，故直接清理数据表。
        $schedule
             ->call(function () {
                 if (! \Schema::hasTable('telescope_entries')) {
                     return;
                 }

                 $before = now()->subMonth();

                 // 分批删除，避免大表一次性删除锁表；关联的 telescope_entries_tags 由外键级联清理。
                 do {
                     $deleted = \DB::table('telescope_entries')
                         ->where('created_at', '<', $before)
                         ->limit(1000)
                         ->delete();
                 } while ($deleted > 0);
             })
             ->name('prune-telescope-entries')
             ->dailyAt('02:00')
             ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
