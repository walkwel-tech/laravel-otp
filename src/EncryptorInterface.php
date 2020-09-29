<?php

/*
 * @copyright 2018 Hilmi Erdem KEREN
 * @license MIT
 */

namespace WalkwelTech\Otp;

interface EncryptorInterface
{
    public function encrypt(string $plainText): string;
}
