<?php

namespace Tests\Unit;

use App\Services\OutboundEmailDeliveryClassifier;
use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class OutboundEmailDeliveryClassifierTest extends TestCase
{
    #[DataProvider('permanentMessagesProvider')]
    public function test_detects_permanent_recipient_failures(string $message): void
    {
        $this->assertTrue(
            OutboundEmailDeliveryClassifier::isPermanentRecipientFailure(new Exception($message)),
            'Expected permanent: '.$message
        );
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function permanentMessagesProvider(): array
    {
        return [
            'smtp_550' => ['Expected response code "250" but got code "550", with message "550 5.1.1 User unknown".'],
            'rfc_551' => ['551 User not local; please try <other@host>'],
            'mailbox_unavailable' => ['Mailbox unavailable for this recipient.'],
            'relay_denied' => ['554 5.7.1 Relay access denied'],
            'invalid_recipient' => ['Invalid recipient address syntax'],
        ];
    }

    public function test_detects_permanent_in_exception_chain(): void
    {
        $inner = new Exception('550 5.1.1 bad mailbox');
        $outer = new Exception('Transport error', 0, $inner);

        $this->assertTrue(OutboundEmailDeliveryClassifier::isPermanentRecipientFailure($outer));
    }

    public function test_transient_errors_are_not_permanent(): void
    {
        $this->assertFalse(OutboundEmailDeliveryClassifier::isPermanentRecipientFailure(
            new Exception('Connection timed out while reading from socket')
        ));

        $this->assertFalse(OutboundEmailDeliveryClassifier::isPermanentRecipientFailure(
            new Exception('Expected response code "250" but got code "421", with message "421 Service not available"')
        ));
    }
}
