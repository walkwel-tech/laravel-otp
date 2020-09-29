<?php

/*
 * @copyright 2018 Hilmi Erdem KEREN
 * @license MIT
 */

namespace WalkwelTech\Otp;

use Illuminate\Notifications\Messages\MailMessage;
use Mockery as M;
use PHPUnit\Framework\TestCase;

if (! \function_exists('\WalkwelTech\Otp\config')) {
    function config($key)
    {
        global $testerClass;

        return $testerClass::$functions->config($key);
    }
}

/**
 * @covers \WalkwelTech\Otp\TokenNotification
 */
class TokenNotificationTest extends TestCase
{
    public static $functions;

    /**
     * @var TokenInterface
     */
    private $token;

    /**
     * @var TokenNotification
     */
    private $notification;

    public function setUp(): void
    {
        global $testerClass;
        $testerClass = self::class;

        $this->token = M::mock(TokenInterface::class);
        $this::$functions = M::mock();
        $this->notification = new TokenNotification($this->token);
    }

    public function tearDown(): void
    {
        M::close();

        global $testerClass;
        $testerClass = null;

        parent::tearDown();
    }

    public function testToMail(): void
    {
        $this::$functions->shouldReceive('config')
            ->once()->with('app.name')
            ->andReturn($appName = 'Laravel');

        $this->token->shouldReceive('plainText')
            ->once()
            ->andReturn($plainText = 'foo');

        $mailMessage = $this->notification->toMail();
        $this->assertInstanceOf(MailMessage::class, $mailMessage);
        $this->assertSame($appName.' One Time Password', $mailMessage->subject);
        $this->assertSame([
            'Somebody recently requested for a one-time password in behalf of you.',
            'You can enter the following reset code: '.$plainText,
            "If you didn't request the password, simply ignore this message.",
        ], $mailMessage->introLines);
    }

    public function testViaReturnsDefaultChannels(): void
    {
        $this::$functions->shouldReceive('config')
            ->once()->with('otp.default_channels')
            ->andReturn('mail,sms,acme_sms');

        $this->assertSame(['mail', 'sms', 'acme_sms'], $this->notification->via(null));
    }

    public function testViaReturnsNotifiablePreference(): void
    {
        $notifiable = new class() {
            public function otpChannels()
            {
                return ['mail', 'sms'];
            }
        };

        $this->assertSame(['mail', 'sms'], $this->notification->via($notifiable));
    }

    public function testTokenNotificationMacro(): void
    {
        $testedThis = null;

        $this->notification::macro('acme', function () use (&$testedThis) {
            $testedThis = $this;
        });

        $this->notification->acme();

        $this->assertInstanceOf(TokenNotification::class, $testedThis);
    }

    public function testToSms(): void
    {
        $this->token->shouldReceive('plainText')
            ->once()
            ->andReturn($plainText = 'foo');

        $this->assertSame(
            'Somebody recently requested a one-time password.'
            ." You can enter the following reset code: {$plainText}"
            .' If you didn\'t request the password, simply ignore this message.', $this->notification->toSms()
        );
    }

    public function testItIsSerializable(): void
    {
        $this::$functions->shouldReceive('config')
            ->once()->with('otp.expires')
            ->andReturn('60');

        $tokenNotification = new TokenNotification(new FakeToken2(1, 'foo'));
        $tokenNotification::macro('acmeSms', function () {
            return 'foo';
        });
        $serialized = serialize($tokenNotification);
        $tokenNotification = unserialize($serialized);

        $this->assertEquals('foo', $tokenNotification->acmeSms());
    }
}

class FakeToken2 extends Token implements TokenInterface
{
    public static function retrieveByAttributes(array $attributes): ?TokenInterface
    {
        return OtpServiceTest::$functions->retrieveByAttributes($attributes);
    }

    protected function persist(): bool
    {
        return true;
    }
}
