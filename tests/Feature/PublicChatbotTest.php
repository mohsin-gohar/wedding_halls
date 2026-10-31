<?php

namespace Tests\Feature;

use App\Livewire\PublicChatWidget;
use App\Models\Hall;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PublicChatbotTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_public_chat_widget_renders_on_general_pages(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('eventhub-public-ai-chatbot')
            ->assertSee('EventHub Chatbot');

        $this->get(route('public.venues'))
            ->assertOk()
            ->assertSee('eventhub-public-ai-chatbot')
            ->assertSee('EventHub Chatbot');
    }

    public function test_public_chat_widget_is_hidden_on_venue_showcase(): void
    {
        $hall = Hall::where('is_active', true)->firstOrFail();

        $this->get(route('hall.showcase', $hall->slug))
            ->assertOk()
            ->assertSee('venue-concierge-chat-widget')
            ->assertDontSee('eventhub-public-ai-chatbot');
    }

    public function test_public_chat_can_answer_platform_questions(): void
    {
        $component = Livewire::test(PublicChatWidget::class)
            ->assertSet('isOpen', false)
            ->call('openChat')
            ->assertSet('isOpen', true);

        // Test greetings
        $component->set('newMessage', 'Assalamu Alaikum')
            ->call('sendMessage')
            ->assertSee('Wa Alaikum Assalam');

        // Test shift timings
        $component->set('newMessage', 'Shift timings kya hain?')
            ->call('sendMessage')
            ->assertSee('Afternoon Shift')
            ->assertSee('Evening Shift');

        // Test booking process
        $component->set('newMessage', 'Booking process aur advance kitna hai?')
            ->call('sendMessage')
            ->assertSee('25% Advance');

        // Test venue listing
        $component->set('newMessage', 'Apna hall kaise register karein?')
            ->call('sendMessage')
            ->assertSee('List Your Wedding Hall');
    }
}
