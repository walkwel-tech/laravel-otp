<?php

/*
 * @copyright 2018 Hilmi Erdem KEREN
 * @license MIT
 */

namespace WalkwelTech\Otp;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Mockery as M;
use PHPUnit\Framework\TestCase;

if (! \function_exists('\WalkwelTech\Otp\config')) {
    function config($key)
    {
        global $testerClass;

        return $testerClass::$functions->config($key);
    }
}

/** @covers \WalkwelTech\Otp\Token */
class TokenTest extends TestCase
{
    public static $functions;

    /**
     * @var Token
     */
    private $token;

    public function setUp(): void
    {
        Carbon::setTestNow(new Carbon('2018-11-06 00:00:00'));

        $this->token = new Token(
            1,
            'foo',
            'bar',
            10,
            Carbon::now(),
            Carbon::now()
        );

        static::$functions = M::mock();
        global $testerClass;

        $testerClass = self::class;
    }

    public function tearDown(): void
    {
        M::close();
    }

    public function testRefresh(): void
    {
        Carbon::setTestNow(new Carbon('2018-11-06 00:00:01'));

        $this->persistShouldBeCalled();

        $this->token->refresh();

        $this->assertSame(11, $this->token->expiryTime());
    }

    public function testExpiresAt(): void
    {
        $this->assertSame('2018-11-06 00:00:10', $this->token->expiresAt()->toDateTimeString());
    }

    public function testItDoesNotConstructWithNullAuthenticableId(): void
    {
        $this->expectException(\LogicException::class);

        new Token(
            null,
            'foo',
            'bar',
            10,
            Carbon::now(),
            Carbon::now()
        );
    }

    public function testAuthenticableId(): void
    {
        $this->assertSame(1, $this->token->authenticableId());
    }

    public function testExpiryTime(): void
    {
        $this->assertSame(10, $this->token->expiryTime());
    }

    public function testPlainText(): void
    {
        $this->assertSame('bar', $this->token->plainText());
    }

    public function testCreate(): void
    {
        Carbon::setTestNow(new Carbon('2018-11-06 00:00:00'));

        $this::$functions->shouldReceive('config')
            ->once()->with('otp.table')
            ->andReturn($tableName = 'foes');

        $this::$functions->shouldReceive('config')
            ->once()->with('otp.expires')
            ->andReturn($expiryTimeMins = 10);

        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('table')->with($tableName)->once()->andReturnSelf();
        DB::shouldReceive('updateOrInsert')
            ->once()
            ->with([
                'authenticable_id' => $authenticableId = 1,
                'cipher_text'      => $cipherText = 'foo',
            ], [
                'authenticable_id' => $authenticableId,
                'expiry_time'      => $expiryTimeMins * 60,
                'cipher_text'      => $cipherText,
                'created_at'       => '2018-11-06 00:00:00',
                'updated_at'       => '2018-11-06 00:00:00',
            ])
            ->andReturn(true);
        DB::shouldReceive('commit')->once();

        $newToken = $this->token::create(
            1,
            'foo',
            'bar'
        );

        $this->assertInstanceOf(TokenInterface::class, $newToken);
    }

    public function testPersistenceShouldHandleErrors(): void
    {
        $this->expectException(\RuntimeException::class);

        Carbon::setTestNow(new Carbon('2018-11-06 00:00:00'));

        $this::$functions->shouldReceive('config')
            ->once()->with('otp.table')
            ->andReturn($tableName = 'foes');

        $this::$functions->shouldReceive('config')
            ->once()->with('otp.expires')
            ->andReturn($expiryTimeMins = 10);

        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('table')->with($tableName)->once()->andReturnSelf();
        DB::shouldReceive('updateOrInsert')
            ->once()
            ->with([
                'authenticable_id' => $authenticableId = 1,
                'cipher_text'      => $cipherText = 'foo',
            ], [
                'authenticable_id' => $authenticableId,
                'expiry_time'      => $expiryTimeMins * 60,
                'cipher_text'      => $cipherText,
                'created_at'       => '2018-11-06 00:00:00',
                'updated_at'       => '2018-11-06 00:00:00',
            ])
            ->andThrow(\RuntimeException::class);

        DB::shouldReceive('rollBack')->once();

        $newToken = $this->token::create(
            1,
            'foo',
            'bar'
        );

        $this->assertInstanceOf(TokenInterface::class, $newToken);
    }

    public function testToNotification(): void
    {
        $this->assertInstanceOf(TokenNotification::class, $this->token->toNotification());
        $this->assertSame($this->token, $this->token->toNotification()->token);
    }

    public function testCreatedAt(): void
    {
        $this->assertSame('2018-11-06 00:00:00', $this->token->createdAt()->toDateTimeString());
    }

    public function testUpdatedAt(): void
    {
        $this->assertSame('2018-11-06 00:00:00', $this->token->updatedAt()->toDateTimeString());
    }

    public function testExpired(): void
    {
        Carbon::setTestNow(new Carbon('2018-11-06 00:00:11'));

        $this->assertTrue($this->token->expired());

        Carbon::setTestNow(new Carbon('2018-11-06 00:00:05'));

        $this->assertFalse($this->token->expired());
    }

    public function testExtend(): void
    {
        $this->persistShouldBeCalled();

        $this->token->extend(1);

        $this->assertSame(11, $this->token->expiryTime());
    }

    public function testItCastsToString(): void
    {
        $this->assertSame('foo', (string) $this->token);
    }

    public function testRetrieveByAttributesCanReturnEmptyResults(): void
    {
        $this::$functions->shouldReceive('config')
            ->once()->with('otp.table')
            ->andReturn($tableName = 'foes');

        DB::shouldReceive('table')->with($tableName)->once()->andReturnSelf();
        DB::shouldReceive('first')->once()->andReturnNull();

        $result = $this->token->retrieveByAttributes([]);
        $this->assertNull($result);
    }

    public function testRetrieveByAttributes(): void
    {
        $this::$functions->shouldReceive('config')
            ->once()->with('otp.table')
            ->andReturn($tableName = 'foes');

        DB::shouldReceive('table')->with($tableName)->once()->andReturnSelf();
        DB::shouldReceive('where')->once()->with('foo', 'bar')->andReturnSelf();
        DB::shouldReceive('where')->once()->with('baz', 'qux')->andReturnSelf();
        DB::shouldReceive('first')->once()->andReturn((object) [
            'authenticable_id' => 'foo',
            'cipher_text'      => 'bar',
            'expiry_time'      => 10,
            'created_at'       => '2018-11-06 00:00:00',
            'updated_at'       => '2018-11-06 00:00:00',
        ]);

        $result = $this->token->retrieveByAttributes(['foo' => 'bar', 'baz' => 'qux']);
        $this->assertInstanceOf(TokenInterface::class, $result);
    }

    public function testCipherText(): void
    {
        $this->assertSame('foo', (string) $this->token);
    }

    public function testTimeLeft(): void
    {
        Carbon::setTestNow(new Carbon('2018-11-06 00:00:05'));

        $this->assertSame(5, $this->token->timeLeft());
    }

    public function testRevoke(): void
    {
        $this->persistShouldBeCalled();

        $this->token->revoke();

        $this->assertSame(0, $this->token->expiryTime());
    }

    public function testInvalidate(): void
    {
        $this->persistShouldBeCalled();

        $this->token->invalidate();

        $this->assertSame(0, $this->token->expiryTime());
    }

    private function persistShouldBeCalled(): void
    {
        $this::$functions->shouldReceive('config')
            ->once()->with('otp.table')
            ->andReturn($tableName = 'foes');

        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('table')->with($tableName)->once()->andReturnSelf();
        DB::shouldReceive('updateOrInsert')->once()->andReturn(true);
        DB::shouldReceive('commit')->once();
    }
}
