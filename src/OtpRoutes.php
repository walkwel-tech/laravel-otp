<?php

/*
 * @copyright 2018 Hilmi Erdem KEREN
 * @license MIT
 */

namespace WalkwelTech\Otp;

use WalkwelTech\Otp\Http\Controllers\OtpController;
use Illuminate\Support\Facades\Route;

class OtpRoutes
{
    /**
     * Binds the Passport routes into the controller.
     */
    public static function register(): void
    {
        Route::resource('otp', OtpController::class, [
            'only'       => ['create', 'store'],
            'prefix'     => 'otp',
        ])->middleware(['web', 'auth']);

        Route::get('/otp/resend', [OtpController::class, 'resend'])
            ->middleware(['web', 'auth'])
            ->name('otp.resend');
    }
}
