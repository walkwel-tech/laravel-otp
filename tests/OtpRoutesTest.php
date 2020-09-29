<?php

/*
 * @copyright 2018 Hilmi Erdem KEREN
 * @license MIT
 */

namespace WalkwelTech\Otp;

use WalkwelTech\Otp\Http\Controllers\OtpController;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use Mockery as M;
use PHPUnit\Framework\TestCase;

/** @covers \WalkwelTech\Otp\OtpRoutes */
class OtpRoutesTest extends TestCase
{
    public function testRegister(): void
    {
        // This test makes no sense.
        // Just for coverage =)

        $app = new Container();

        $mock = M::mock();

        $app->bind('router', function () use ($mock) {
            return $mock;
        });

        Facade::setFacadeApplication($app);

        $mock->shouldReceive('resource')
            ->once()->with('otp', OtpController::class, [
                'only'       => ['create', 'store'],
                'prefix'     => 'otp',
            ])->andReturnSelf();

        $mock->shouldReceive('middleware')
            ->once()->with(['web', 'auth']);

        $this->assertNull(OtpRoutes::register());

        M::close();
    }
}
