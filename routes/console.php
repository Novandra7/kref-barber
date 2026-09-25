<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes & Task Scheduler
|--------------------------------------------------------------------------
|
| Di sini Anda dapat mendaftarkan closure command berbasis console maupun
| jadwal (Task Scheduler) untuk berjalan secara otomatis di latar belakang.
|
*/

// Safety-net scheduler: memeriksa booking setiap 15 menit
Schedule::command('booking:send-reminders')->everyFifteenMinutes();

// Scheduler untuk memeriksa dan menandai pembayaran pending yang telah kedaluwarsa setiap 10 menit
Schedule::command('booking:expire-unpaid-payments')->everyTenMinutes();
