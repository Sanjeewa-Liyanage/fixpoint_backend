<?php
class DefaultPasswordGenerator
{
    /**
     * Generate a default password.
     * Includes uppercase, lowercase, numbers, and special characters.
     *
     * @param int $length
     * @return string
     */
    public static function generate($length = 12)
    {
        $upper = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lower = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $special = '!@#$%^&*()-_=+[]{}|;:,.<>?';

        // Ensure at least one character from each set
        $password = [
            $upper[random_int(0, strlen($upper) - 1)],
            $lower[random_int(0, strlen($lower) - 1)],
            $numbers[random_int(0, strlen($numbers) - 1)],
            $special[random_int(0, strlen($special) - 1)],
        ];

        $all = $upper . $lower . $numbers . $special;
        for ($i = 4; $i < $length; $i++) {
            $password[] = $all[random_int(0, strlen($all) - 1)];
        }

        // Shuffle to randomize character positions
        shuffle($password);

        return implode('', $password);
    }
}