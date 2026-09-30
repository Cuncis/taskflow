<?php

namespace Tests\Feature;

use App\Domain\Notification\SlackChannel;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
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
}
