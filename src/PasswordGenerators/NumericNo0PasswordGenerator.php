<?php

/*
 * @copyright 2018 Hilmi Erdem KEREN
 * @license MIT
 */

namespace WalkwelTech\Otp\PasswordGenerators;

use WalkwelTech\Otp\PasswordGeneratorInterface;
use Exception;

/**
 * Class NumericNo0PasswordGenerator.
 */
class NumericNo0PasswordGenerator extends NumericPasswordGenerator implements PasswordGeneratorInterface
{
    /**
     * Generate a numeric password with no zeroes.
     *
     * @param int $length
     *
     * @return string
     */
    public function generate(int $length): string
    {
        return (string) str_replace(0, $this->getRandomDigitWithNo0(), (string) parent::generate($length));
    }

    /**
     * Generate a random digit with no zeroes.
     *
     * @return int
     */
    private function getRandomDigitWithNo0()
    {
        try {
            $int = random_int(1, 9);
        } catch (Exception $e) {
            $int = rand(1, 9);
        }

        return $int;
    }
}
