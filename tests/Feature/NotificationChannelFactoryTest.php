<?php

namespace Tests\Feature;

use App\Domain\Notification\EmailChannel;
use App\Domain\Notification\NotificationChannel;
use App\Domain\Notification\NotificationChannelFactory;
use App\Domain\Notification\NotificationPreference;
use App\Domain\Notification\PushChannel;
use App\Domain\Notification\SlackChannel;
use App\Domain\Notification\SmsChannel;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class NotificationChannelFactoryTest extends TestCase
{
    /**
     * @return array<string, array{NotificationPreference, class-string<NotificationChannel>}>
     */
    public static function preferenceProvider(): array
    {
        return [
            'email' => [NotificationPreference::Email, EmailChannel::class],
            'sms' => [NotificationPreference::Sms, SmsChannel::class],
            'slack' => [NotificationPreference::Slack, SlackChannel::class],
            'push' => [NotificationPreference::Push, PushChannel::class],
        ];
    }

    #[DataProvider('preferenceProvider')]
    public function test_it_returns_the_correct_channel_for_each_preference(
        NotificationPreference $preference,
        string $expectedChannel,
    ): void {
        $channel = app(NotificationChannelFactory::class)->make($preference);

        $this->assertInstanceOf($expectedChannel, $channel);
        $this->assertInstanceOf(NotificationChannel::class, $channel);
    }

    /**
     * Fails when a new enum case is added without a row in preferenceProvider(),
     * so the provider can't silently fall behind the enum.
     */
    public function test_every_preference_case_is_covered_by_the_provider(): void
    {
        $covered = array_values(array_map(
            fn (array $row): NotificationPreference => $row[0],
            self::preferenceProvider(),
        ));

        $this->assertEqualsCanonicalizing(NotificationPreference::cases(), $covered);
    }
}
