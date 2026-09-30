<?php

namespace Tests\Feature;

use App\Domain\Notification\SlackChannel;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class SlackChannelTest extends TestCase
{
    public function test_it_sends_a_slack_message_via_http(): void
    {
        Http::fake();

        (new SlackChannel)->send('#general', 'New task created');

        Http::assertSent(fn (Request $request) => $request->url() === 'https://hooks.slack.example.com/services/fake-webhook-url'
            && $request['text'] === 'New task created'
            && $request['channel'] === '#general');
        Http::assertSentCount(1);
    }

    public function test_it_works_with_a_specific_faked_response(): void
    {
        Http::fake([
            'hooks.slack.example.com/*' => Http::response(['ok' => true], 200),
        ]);

        (new SlackChannel)->send('#general', 'hello');

        Http::assertSent(fn (Request $request) => $request['text'] === 'hello');
    }

    public function test_it_logs_a_warning_and_does_not_throw_when_slack_returns_500(): void
    {
        Log::spy();
        Http::fake(['*' => Http::response(null, 500)]);

        // Must not throw: a Slack outage cannot be allowed to break the caller.
        (new SlackChannel)->send('#general', 'New task created');

        Http::assertSentCount(1);
        Log::shouldHaveReceived('warning')
            ->once()
            ->with('Slack notification failed', [
                'channel' => '#general',
                'status' => 500,
                'body' => '',
            ]);
    }

    public function test_it_does_not_log_a_warning_when_slack_succeeds(): void
    {
        Log::spy();
        Http::fake(['*' => Http::response('ok', 200)]);

        (new SlackChannel)->send('#general', 'New task created');

        Log::shouldNotHaveReceived('warning');
    }
}
