<?php

namespace Tests\Feature;

use App\Livewire\Dashboard;
use App\Livewire\HallChat;
use App\Livewire\VenueChatWidget;
use App\Models\Hall;
use App\Models\HallChatMessage;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ConciergeChatbotTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('public');
    }

    public function test_concierge_routes_render_cleanly(): void
    {
        $hall = Hall::firstOrFail();

        $this->get(route('public.concierge'))
            ->assertOk()
            ->assertSee('Virtual Concierge', false);

        $this->get(route('public.venue.chat', $hall->slug))
            ->assertOk()
            ->assertSee($hall->name, false)
            ->assertSee('AI Concierge', false);
    }

    public function test_venue_chat_widget_renders_on_venue_showcase_and_whatsapp_is_removed(): void
    {
        $hall = Hall::firstOrFail();

        $response = $this->get(route('hall.showcase', $hall->slug));
        $response->assertOk();
        $response->assertSee('Ask ' . $hall->name . ' Concierge', false);
        $response->assertDontSee('https://wa.me/', false);
        $response->assertDontSee('WhatsApp Concierge', false);
    }

    public function test_venue_chat_widget_can_toggle_and_send_messages(): void
    {
        $hall = Hall::firstOrFail();

        // 1. Initial collapsed state and toggling
        $widget = Livewire::test(VenueChatWidget::class, ['hall' => $hall])
            ->assertSet('isOpen', false)
            ->call('toggleChat')
            ->assertSet('isOpen', true);

        // 2. Asking automated rent question
        $widget->call('askQuickQuestion', 'What is the hall rent and capacity?')
            ->assertHasNoErrors();

        $botReply = HallChatMessage::where('hall_id', $hall->id)
            ->where('sender_type', 'bot')
            ->latest('id')
            ->first();

        $this->assertNotNull($botReply);
        $this->assertStringContainsString('Rates & Capacity', $botReply->message);

        // 3. Sending custom message
        $widget->set('newMessage', 'Can you provide floral stage setup?')
            ->call('sendMessage')
            ->assertHasNoErrors();

        $customerMsg = HallChatMessage::where('hall_id', $hall->id)
            ->where('sender_type', 'customer')
            ->latest('id')
            ->first();

        $this->assertNotNull($customerMsg);
        $this->assertStringContainsString('floral stage setup', $customerMsg->message);

        // 4. Voice note upload
        $fakeAudio = UploadedFile::fake()->create('note.mp3', 100, 'audio/mpeg');
        $widget->set('voiceRecording', $fakeAudio)
            ->call('uploadVoice')
            ->assertHasNoErrors();

        $voiceMsg = HallChatMessage::where('hall_id', $hall->id)
            ->where('message_type', 'voice')
            ->latest('id')
            ->first();

        $this->assertNotNull($voiceMsg);
        $this->assertNotNull($voiceMsg->voice_file);
    }

    public function test_hall_admin_receives_inquiry_on_dashboard_and_replies(): void
    {
        $owner = User::where('email', 'owner@venuecloud.pk')->firstOrFail();
        $hall = Hall::where('tenant_id', $owner->tenant_id)->firstOrFail();

        $clientIp = '203.101.12.88';
        $clientSession = 'session_guest_xyz';

        // Customer sends inquiry via widget
        HallChatMessage::create([
            'hall_id' => $hall->id,
            'user_id' => null,
            'ip_address' => $clientIp,
            'session_id' => $clientSession,
            'sender_type' => 'customer',
            'message_type' => 'text',
            'message' => 'Is 15th November open for Barat?',
            'is_read' => false,
        ]);

        // Hall Owner logs in and views their Executive Dashboard
        $this->actingAs($owner);

        $dashboard = Livewire::test(Dashboard::class)
            ->assertSee('Live Guest Inquiries', false)
            ->assertSee($hall->name, false)
            ->call('openChatModal', $hall->id, $clientIp, $clientSession)
            ->assertSet('showChatModal', true)
            ->assertSet('selectedChatIp', $clientIp)
            ->set('chatReplyText', 'Yes, 15th November is available. We would love to give you a tour!')
            ->call('sendChatReply')
            ->assertHasNoErrors();

        // Verify Hall Owner's reply is saved in database
        $ownerReply = HallChatMessage::where('hall_id', $hall->id)
            ->where('sender_type', 'hall_owner')
            ->first();

        $this->assertNotNull($ownerReply);
        $this->assertStringContainsString('15th November is available', $ownerReply->message);

        // Verify it was marked as read
        $this->assertTrue((bool) HallChatMessage::where('ip_address', $clientIp)->where('sender_type', 'customer')->first()->is_read);
    }

    public function test_chatbot_provides_instant_automated_answers(): void
    {
        $hall = Hall::firstOrFail();

        // 1. Ask about rent and pricing
        Livewire::test(HallChat::class, ['slug' => $hall->slug])
            ->call('askQuickQuestion', 'What is the hall rent and guest capacity?')
            ->assertHasNoErrors();

        $botRentReply = HallChatMessage::where('hall_id', $hall->id)
            ->where('sender_type', 'bot')
            ->latest('id')
            ->first();

        $this->assertNotNull($botRentReply);
        $this->assertStringContainsString('Base Hall Rental', $botRentReply->message);
        $this->assertStringContainsString(number_format($hall->base_rent), $botRentReply->message);

        // 2. Ask about catering packages
        Livewire::test(HallChat::class, ['slug' => $hall->slug])
            ->call('askQuickQuestion', 'What royal catering packages and menus do you offer?')
            ->assertHasNoErrors();

        $botCateringReply = HallChatMessage::where('hall_id', $hall->id)
            ->where('sender_type', 'bot')
            ->latest('id')
            ->first();

        $this->assertNotNull($botCateringReply);
        $this->assertStringContainsString('Royal Catering Packages', $botCateringReply->message);
        $this->assertStringContainsString('/head', $botCateringReply->message);

        // 3. Ask about generator backup
        Livewire::test(HallChat::class, ['slug' => $hall->slug])
            ->call('askQuickQuestion', 'Do you have 100% generator backup and a bridal suite?')
            ->assertHasNoErrors();

        $botFacilityReply = HallChatMessage::where('hall_id', $hall->id)
            ->where('sender_type', 'bot')
            ->latest('id')
            ->first();

        $this->assertNotNull($botFacilityReply);
        $this->assertStringContainsString('Power Backup', $botFacilityReply->message);

        // 4. Voice message upload
        $fakeAudio = UploadedFile::fake()->create('inquiry.mp3', 250, 'audio/mpeg');

        Livewire::test(HallChat::class, ['slug' => $hall->slug])
            ->set('voiceRecording', $fakeAudio)
            ->call('uploadVoice')
            ->assertHasNoErrors();

        $voiceMsg = HallChatMessage::where('hall_id', $hall->id)
            ->where('message_type', 'voice')
            ->first();

        $this->assertNotNull($voiceMsg);
        $this->assertNotNull($voiceMsg->voice_file);
    }
}
