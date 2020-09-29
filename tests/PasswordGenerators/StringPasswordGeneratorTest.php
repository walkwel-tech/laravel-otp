<?php

/*
 * @copyright 2018 Hilmi Erdem KEREN
 * @license MIT
 */

namespace WalkwelTech\Otp\PasswordGenerators;

use WalkwelTech\Otp\PasswordGeneratorInterface;
use Mockery as M;
use PHPUnit\Framework\TestCase;

/** @covers \WalkwelTech\Otp\PasswordGenerators\StringPasswordGenerator */
class StringPasswordGeneratorTest extends TestCase
{
    /**
     * @var M\Mock
     */
    public static $functions;

    /**
     * @var PasswordGeneratorInterface
     */
    private $passwordGenerator;

    public function setUp(): void
    {
        self::$functions = M::mock();

        global $testerClass;
        $testerClass = self::class;

        $this->passwordGenerator = new StringPasswordGenerator();
    }

    public function tearDown(): void
    {
        M::close();
    }

    public function testGenerate(): void
    {
        $password = $this->passwordGenerator->generate(7);
        $this->assertSame(7, strlen($password));
    }
}
