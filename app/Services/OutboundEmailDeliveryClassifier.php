<?php

namespace App\Services;

use Throwable;

/**
 * Classifies mail transport errors so we skip bad recipients (no retries)
 * and only retry likely-transient failures.
 */
class OutboundEmailDeliveryClassifier
{
    /**
     * True when the error almost certainly means the message will never deliver
     * to this recipient (bad address, mailbox gone, policy rejection at RCPT).
     */
    public static function isPermanentRecipientFailure(Throwable $e): bool
    {
        $message = strtolower($e->getMessage().' '.self::chainMessages($e));

        if ($message === '') {
            return false;
        }

        // RFC 5321 / common SMTP permanent codes in exception text
        $patterns = [
            '/\b550\b/',
            '/\b551\b/',
            '/\b553\b/',
            '/\b554\b/',
            '/\b5\.1\.1\b/',
            '/\b5\.1\.2\b/',
            '/\b5\.1\.3\b/',
            '/\b5\.5\.0\b/',
            '/\b5\.5\.1\b/',
            '/\b5\.5\.2\b/',
            '/recipient address rejected/',
            '/relay access denied/',
            '/user unknown/',
            '/unknown user/',
            '/no such user/',
            '/mailbox unavailable/',
            '/invalid mailbox/',
            '/invalid recipient/',
            '/does not exist/',
            '/address rejected/',
            '/not a valid/',
            '/bad destination/',
            '/undeliverable/',
            '/no mailbox/',
            '/mailbox not found/',
            '/recipient rejected/',
            '/unrouteable address/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $message) === 1) {
                return true;
            }
        }

        return false;
    }

    private static function chainMessages(Throwable $e): string
    {
        $parts = [];
        $prev = $e->getPrevious();
        while ($prev instanceof Throwable) {
            $parts[] = $prev->getMessage();
            $prev = $prev->getPrevious();
        }

        return implode(' ', $parts);
    }
}
