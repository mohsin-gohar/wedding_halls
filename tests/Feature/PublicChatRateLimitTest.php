<?php

namespace Tests\Feature;

use App\Livewire\HallChat;
use App\Models\Hall;
use App\Models\HallChatMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PublicChatRateLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_public_hall_chat_limits_message_writes_per_visitor_and_hall(): void
    {
        $hall = Hall::publicVisible()->firstOrFail();

        $page = Livewire::test(HallChat::class, ['slug' => $hall->slug]);

        for ($i = 0; $i < 10; $i++) {
            $page->set('newMessage', 'Availability question number '.$i)
                ->call('sendMessage')
                ->assertHasNoErrors();
        }

        $page->set('newMessage', 'Rate limit overflow')
            ->call('sendMessage')
            ->assertHasErrors(['newMessage']);

        $this->assertSame(10, HallChatMessage::query()
            ->where('hall_id', $hall->id)
            ->where('sender_type', 'customer')
            ->count());
    }
}
