<?php

namespace App\Livewire;

use App\Models\Hall;
use App\Models\HallChatMessage;
use App\Models\Package;
use App\Support\ShiftWindows;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\On;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

class VenueChatWidget extends Component
{
    use WithFileUploads;

    #[Locked]
    public Hall $hall;

    public bool $isOpen = false;

    public string $newMessage = '';

    public $voiceRecording = null;

    #[Locked]
    public string $visitorIp = '';

    #[Locked]
    public string $visitorSession = '';

    public array $messages = [];

    /**
     * Human-Only Live Chat Mode:
     * When true, canned automated bot answers are muted.
     * The customer's inquiries are recorded in the database, acknowledged once by front-desk,
     * and answered directly by the human hall manager from /dashboard.
     */
    public bool $humanOnlyMode = true;

    public function mount(Hall $hall)
    {
        $this->hall = $hall;
        $this->visitorIp = request()->ip() ?? '127.0.0.1';
        $this->visitorSession = session()->getId();
        $this->loadConversation();
    }

    #[On('toggleChat')]
    public function toggleChat(): void
    {
        $this->isOpen = ! $this->isOpen;
        if ($this->isOpen) {
            $this->loadConversation();
            $this->dispatch('chatOpened');
        } else {
            $this->dispatch('chatClosed');
        }
    }

    #[On('openVenueChat')]
    public function openChat(): void
    {
        $this->isOpen = true;
        $this->loadConversation();
        $this->dispatch('chatOpened');
    }

    public function closeChat(): void
    {
        $this->isOpen = false;
        $this->dispatch('chatClosed');
    }

    public function resetConversation(): void
    {
        $this->messages = [];
        $this->newMessage = '';
    }

    public function loadConversation(): void
    {
        $this->messages = HallChatMessage::getConversation(
            $this->hall->id,
            $this->visitorIp,
            $this->visitorSession
        );
    }

    public function askQuickQuestion(string $question): void
    {
        $this->newMessage = $question;
        $this->sendMessage();
    }

    private function hitChatRateLimit(string $action, int $maximum, int $decaySeconds): bool
    {
        $user = Auth::user();
        $identity = $user
            ? 'user:'.$user->id
            : 'guest:'.hash('sha256', request()->ip().'|'.session()->getId());
        $key = 'hall-chat:'.$this->hall->id.':'.$identity.':'.$action;

        if (RateLimiter::tooManyAttempts($key, $maximum)) {
            return true;
        }

        RateLimiter::hit($key, $decaySeconds);

        return false;
    }

    public function sendMessage(): void
    {
        if ($this->hitChatRateLimit('message', 10, 60)) {
            $this->addError('newMessage', 'Too many chat messages. Please wait a minute.');

            return;
        }

        $userText = trim($this->newMessage);
        if (empty($userText)) {
            return;
        }

        $this->validate([
            'newMessage' => 'required|string|min:1|max:2000',
        ]);

        $user = Auth::user();

        // 1. Persist guest message tied to this specific hall & tenant
        HallChatMessage::create([
            'hall_id' => $this->hall->id,
            'user_id' => $user?->id,
            'ip_address' => $this->visitorIp,
            'session_id' => $this->visitorSession,
            'sender_type' => 'customer',
            'message_type' => 'text',
            'message' => $userText,
            'voice_file' => null,
            'is_read' => false,
        ]);

        $this->newMessage = '';

        // 2. Generate an automated reply only when bots are enabled. In
        // human-only mode the guest message stays queued for staff review.
        if (! $this->humanOnlyMode) {
            $botReply = $this->generateBotReply($userText);

            HallChatMessage::create([
                'hall_id' => $this->hall->id,
                'user_id' => null,
                'ip_address' => $this->visitorIp,
                'session_id' => $this->visitorSession,
                'sender_type' => 'bot',
                'message_type' => 'text',
                'message' => $botReply,
                'voice_file' => null,
                'is_read' => true,
            ]);
        }

        $this->loadConversation();
        $this->dispatch('messageSent');
    }

    public function uploadVoice(): void
    {
        if ($this->hitChatRateLimit('voice', 5, 600)) {
            $this->addError('voiceRecording', 'Too many voice messages. Please wait before sending another.');

            return;
        }

        $this->validate([
            'voiceRecording' => 'required|file|mimetypes:audio/mpeg,audio/mp4,audio/ogg,audio/wav,audio/m4a,audio/webm|mimes:mp3,wav,ogg,m4a,webm|max:5120', // 5MB
        ]);

        $user = Auth::user();
        $path = $this->voiceRecording->store('hall-voice-messages/'.$this->hall->id, 'public');

        HallChatMessage::create([
            'hall_id' => $this->hall->id,
            'user_id' => $user?->id,
            'ip_address' => $this->visitorIp,
            'session_id' => $this->visitorSession,
            'sender_type' => 'customer',
            'message_type' => 'voice',
            'message' => 'Voice message',
            'voice_file' => $path,
            'is_read' => false,
        ]);

        $this->voiceRecording = null;

        if (! $this->humanOnlyMode) {
            $botReply = "🎙️ Shukriya! Aap ka voice message {$this->hall->name} ke concierge desk ko receive ho gaya hai. Hamara venue manager live sun kar foran yahan reply karega.";
            HallChatMessage::create([
                'hall_id' => $this->hall->id,
                'user_id' => null,
                'ip_address' => $this->visitorIp,
                'session_id' => $this->visitorSession,
                'sender_type' => 'bot',
                'message_type' => 'text',
                'message' => $botReply,
                'voice_file' => null,
                'is_read' => true,
            ]);
        }

        $this->loadConversation();
        $this->dispatch('messageSent');
    }

    protected function generateBotReply(string $input): string
    {
        $lower = strtolower(trim($input));
        $hall = $this->hall;
        $formattedRent = number_format($hall->base_rent);
        $minCap = $hall->min_capacity ?: 100;
        $maxCap = $hall->max_capacity ?: 800;

        // 1. Warm Greetings: hi, hello, salam, aoa, hey, etc.
        if (preg_match('/^(hi|hello|hey|salam|aoa|assalam|assalam-o-alaikum|assalamu alaikum|helo|hy|salamalikum|kese ho|kaise ho)(\b|\s|\!|\?|$)/i', $lower) || in_array($lower, ['hi', 'hello', 'hey', 'salam', 'aoa', 'assalam', 'helo', 'hy'])) {
            return "Walaikum Assalam wa Rahmatullah! Welcome to **{$hall->name}** VIP Desk.\n\n"
                .'Main aap ki kya madad kar sakta hoon? Aap mujh se hall rent, guest capacity, catering packages ya available dates ke baray mein be-jhijhak pooch sakte hain!';
        }

        // 2. Goodbyes / Gratitude: bye, shukriya, thanks, jazakallah, ok, theek hai, allah hafiz
        if (preg_match('/(bye|shukriya|thanks|thank you|jazakallah|allah hafiz|alvida|ok|theek|thk|done|good night)/i', $lower)) {
            return "Bohat shukriya! **{$hall->name}** ki taraf se aap ka din khushgawar guzray. Agar mazeed kuch poochna ho to hum 24/7 hazir hain! 🌸";
        }

        // 3. Rent, Pricing & Capacity: rent, price, cost, rate, kitna, budget, capacity, guests
        if (preg_match('/(rent|price|cost|rate|charge|fee|paisa|pese|kitna|budget|pkr|rs|capacity|guests|bande|log|aadmi)/i', $lower)) {
            return "🏛️ **{$hall->name} Rates & Capacity:**\n\n"
                ."• **Base Hall Rental:** PKR {$formattedRent} per event shift (Afternoon ya Evening).\n"
                ."• **Seating Capacity:** {$minCap} se {$maxCap} guests araam se accommodate hotay hain.\n"
                ."• **Included Facilities:** Central Chiller AC, 100% heavy generator power backup, private bridal suite aur secure parking.\n\n"
                .'Aap kis date ya month ke liye booking plan kar rahe hain? Manager ko bhi alert bhej diya gaya hai taake aap ko best discounted package offer kiya ja sakay!';
        }

        // 4. Catering & Food Menus: food, cater, menu, package, biryani, khana, dish, per head
        if (preg_match('/(food|cater|menu|package|dish|biryani|qorma|khana|dastarkhwan|per head|sweet|dessert|tasting)/i', $lower)) {
            $packages = Package::where('tenant_id', $hall->tenant_id)->where('is_active', true)->take(3)->get();
            if ($packages->isNotEmpty()) {
                $lines = [];
                foreach ($packages as $pkg) {
                    $rate = number_format($pkg->per_head_price);
                    $lines[] = "• **{$pkg->name}:** PKR {$rate}/head (Min {$pkg->min_guests} guests)";
                }
                $pkgList = implode("\n", $lines);

                return "🍽️ **Royal Catering Packages at {$hall->name}:**\n\n"
                    ."{$pkgList}\n\n"
                    .'Tamam packages me shahi degi recipes, live tandoori naan, salad bar, desserts aur professional waiter service shamil hai. Free menu tasting bhi arrange ki ja sakti hai!';
            }

            return "🍽️ **Royal Catering Service at {$hall->name}:**\n\n"
                .'Hamaray executive bawarchi traditional degi biryani, live tandoor, chicken handi, fresh salads aur shahi sweets provide kartay hain (PKR 1,800 se PKR 3,200/head tak packages available hain). Aap ke kitne guests mutawaqqo hain?';
        }

        // 5. Shift Timings, Dates & Booking
        if (preg_match('/(date|available|avail|book|reserve|slot|shift|timing|calendar|december|january|february|march|april|november|october|tarikh|din|waqt|time)/i', $lower)) {
            $shiftSummary = ShiftWindows::summary($hall->tenant?->shift_windows);
            $fullDay = ShiftWindows::labelForSlot($hall->tenant?->shift_windows, 'FULL_DAY');

            return "📅 **Shift Timings & Booking Details:**\n\n"
                ."• **Operational Shifts:** {$shiftSummary}\n"
                ."• **Full Day (exclusive takeover):** {$fullDay}\n\n"
                .'Aap kis specific tarikh ya month ke liye shift dekh rahe hain? Yahan bataiye, manager live dashboard par availability check kar ke slot lock karwa dega!';
        }

        // 6. Amenities: Generator, AC, Bridal Suite, Stage Decor
        if (preg_match('/(ac|air condition|chiller|generator|light|bijli|load shedding|bridal|stage|decor|flower|sound|parking|valet)/i', $lower)) {
            $acText = $hall->has_air_conditioning ? 'High-Tonnage Central Chiller Air Conditioning' : 'Standard cooling ventilation';
            $genText = $hall->has_generator_backup ? '100% Heavy-Duty Backup Generator (Seamless Transfer)' : 'Standard Power';
            $bridalText = $hall->has_bridal_room ? 'Private VIP Bridal Suite with personal vanity' : 'Available on request';

            return "✨ **Facilities & Amenities at {$hall->name}:**\n\n"
                ."• ⚡ **Power Backup:** {$genText}\n"
                ."• ❄️ **Climate Control:** {$acText}\n"
                ."• 👰 **Bridal Suite:** {$bridalText}\n"
                ."• 🚗 **Guest Parking:** On-site secure parking & valet assistance\n"
                .'• 🌸 **Stage & Decor:** Bespoke thematic stage decor mojood hai.';
        }

        // 7. Contact Human / Venue Manager / Phone Number
        if (preg_match('/(human|owner|manager|call|phone|number|baat|contact|agent|talk|person|rabta)/i', $lower)) {
            return "📞 **Venue Manager Alerted:**\n\n"
                ."Aap ki inquiry **{$hall->name}** ke front-desk console par pohanch chuki hai. Meherbani farma kar apna **Phone Number** aur function date share farma dain, manager foran aap se call par rabta kar le ga!";
        }

        // 8. Default Friendly Assistance
        return "Shukriya! Aap ka paigham **{$hall->name}** ke front desk par receive ho chuka hai.\n\n"
            .'Main aap ki mazeed kya madad kar sakta hoon? Aap mujh se **hall rent, catering menu, available dates ya facilities** ke baray me pooch sakte hain. Hamara venue manager bhi live dashboard se aap ke sath connected hai!';
    }

    public function render()
    {
        return view('livewire.venue-chat-widget');
    }
}
