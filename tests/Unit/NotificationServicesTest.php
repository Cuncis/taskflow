<?php

namespace Tests\Unit;

use App\Domain\Notification\NotificationChannel;
use App\Domain\Notification\NotificationChannelFactory;
use App\Domain\Notification\NotificationPreference;
use App\Domain\Notification\SmsChannel;
use App\Domain\Notification\UrgentAlertService;
use App\Domain\Notification\UserNotifier;
use App\Models\User;
use Mockery;
use Tests\TestCase;

class NotificationServicesTest extends TestCase
{
    public function test_user_notifier_sends_through_the_channel_matching_the_users_preference(): void
    {
        $user = new User(['email' => 'sam@example.test']);
        $user->notification_preference = NotificationPreference::Sms;

        $channel = Mockery::mock(NotificationChannel::class);
        $channel->shouldReceive('send')->once()->with('sam@example.test', 'Hello');

        $factory = Mockery::mock(NotificationChannelFactory::class);
        $factory->shouldReceive('make')->once()->with(NotificationPreference::Sms)->andReturn($channel);

        (new UserNotifier($factory))->notify($user, 'Hello');
    }

    public function test_urgent_alert_service_prefixes_the_message(): void
    {
        $channel = Mockery::mock(NotificationChannel::class);
        $channel->shouldReceive('send')->once()->with('ops@example.test', 'URGENT: Server down');

        (new UrgentAlertService($channel))->alert('ops@example.test', 'Server down');
    }

    public function test_urgent_alerts_are_delivered_by_sms_via_the_contextual_binding(): void
    {
        $this->assertInstanceOf(SmsChannel::class, $this->resolveUrgentChannel());
    }

    private function resolveUrgentChannel(): NotificationChannel
    {
        $service = app(UrgentAlertService::class);

        return (new \ReflectionProperty($service, 'notificationChannel'))->getValue($service);
    }
}
