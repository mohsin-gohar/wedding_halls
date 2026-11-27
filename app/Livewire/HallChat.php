<?php

namespace App\Livewire;

use App\Models\Hall;
use App\Models\HallChatMessage;
use App\Models\Package;
use App\Support\ShiftWindows;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.public', ['title' => 'Concierge Chatbot — Riwaj Grand'])]
class HallChat extends Component
{
    use WithFileUploads;

    #[Locked]\n    public ?Hall $hall = null;

    public string $newMessage = '';

    public $voiceRecording = null;

    #[Locked]\n    public string $visitorIp = '';

    #[Locked]\n    public string $visitorSession = '';

    public ?string $selectedCustomerIp = null;

    public ?string $selectedCustomerSession = null;

    public array $chatParticipants = [];

    public array $messages = [];

    public function mount(?string $slug = null, ?string $id = null)
    {
        $identifier = $slug ?? $id;

        // Concierge chat is a public surface: only centrally visible halls may
        // be resolved, with no fallback to an arbitrary active hall.
        $hallQuery = Hall::publicVisible()->with(['venue', 'tenant']);

        if (! $identifier) {
            $this->hall = $hallQuery->first();

            if (! $this->hall) {
                abort(404, 'No active venues available for concierge chat.');
            }
        } else {
            $this->hall = $hallQuery
                ->where(function ($q) use ($identifier) {
                    $q->where('halls.slug', $identifier);
                    if (str_contains($identifier, '-')) {
                        $q->orWhere('halls.id', $identifier);
                    }
                })
                ->firstOrFail();
        }

        $this->visitorIp = request()->ip() ?? '127.0.0.1';
        $this->visitorSession = session()->getId();

        $user = Auth::user();
        if ($user && ($user->isSuperAdmin() || $user->tenant_id === $this->hall->tenant_id)) {
            $this->chatParticipants = HallChatMessage::getChatParticipants($this->hall->id);
        } else {
            $this->messages = HallChatMessage::getConversation(
                $this->hall->id,
                $this->visitorIp,
                $this->visitorSession
            );
        }
    }

    public function askQuickQuestion(string $question): void
    {
        $this->newMessage = $question;
        $this->sendMessage();
    }

    public function sendMessage()
    {
        $this->validate([
            'newMessage' => 'required|string|min:1|max:2000',
        ]);

        $user = Auth::user();
        $isHallOwner = $user && ($user->isSuperAdmin() || $user->tenant_id === $this->hall->tenant_id);
        $userText = trim($this->newMessage);

        // A staff reply must be addressed to an explicitly selected guest
        // thread. Never fall back to the staff member's own visitor identity.
        if ($isHallOwner && (! $this->selectedCustomerIp || ! $this->selectedCustomerSession)) {
            $this->addError('newMessage', 'Select a guest conversation before replying.');

            return;
        }

        $targetIp = $isHallOwner ? $this->selectedCustomerIp : $this->visitorIp;
        $targetSession = $isHallOwner ? $this->selectedCustomerSession : $this->visitorSession;

        // 1. Persist the message into the selected conversation.
        HallChatMessage::create([
            'hall_id' => $this->hall->id,
            'user_id' => $user?->id,
            'ip_address' => $targetIp,
            'session_id' => $targetSession,
            'sender_type' => $isHallOwner ? 'hall_owner' : 'customer',
            'message_type' => 'text',
            'message' => $userText,
            'voice_file' => null,
            'is_read' => false,
        ]);

        $this->newMessage = '';

        // 2. Guests get an instant bot reply; staff replies stay in the guest thread.
        if (! $isHallOwner) {
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

            $this->messages = HallChatMessage::getConversation(
                $this->hall->id,
                $this->visitorIp,
                $this->visitorSession
            );
        } else {
            $this->messages = HallChatMessage::getConversation(
                $this->hall->id,
                $targetIp,
                $targetSession
            );
            $this->chatParticipants = HallChatMessage::getChatParticipants($this->hall->id);
        }

        $this->dispatch('messageSent');
    }

    public function uploadVoice()
    {
        $this->validate([
            'voiceRecording' => 'required|file|mimetypes:audio/mpeg,audio/mp4,audio/ogg,audio/wav,audio/m4a,audio/webm|mimes:mp3,wav,ogg,m4a,webm|max:5120', // 5MB
        ]);

        $user = Auth::user();
        $isHallOwner = $user && ($user->isSuperAdmin() || $user->tenant_id === $this->hall->tenant_id);

        if ($isHallOwner && (! $this->selectedCustomerIp || ! $this->selectedCustomerSession)) {
            $this->addError('voiceRecording', 'Select a guest conversation before sending a voice reply.');

            return;
        }

        $targetIp = $isHallOwner ? $this->selectedCustomerIp : $this->visitorIp;
        $targetSession = $isHallOwner ? $this->selectedCustomerSession : $this->visitorSession;

        $path = $this->voiceRecording->store('hall-voice-messages/'.$this->hall->id, 'public');

        HallChatMessage::create([
            'hall_id' => $this->hall->id,
            'user_id' => $user?->id,
            'ip_address' => $targetIp,
            'session_id' => $targetSession,
            'sender_type' => $isHallOwner ? 'hall_owner' : 'customer',
            'message_type' => 'voice',
            'message' => 'Voice message',
            'voice_file' => $path,
            'is_read' => false,
        ]);

        $this->voiceRecording = null;

        if (! $isHallOwner) {
            $botReply = "🎙️ Thank you for your voice note! Our wedding concierge desk at {$this->hall->name} has received it. A venue manager will listen to your recording and get back to you shortly.";
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

            $this->messages = HallChatMessage::getConversation(
                $this->hall->id,
                $this->visitorIp,
                $this->visitorSession
            );
        } else {
            $this->messages = HallChatMessage::getConversation(
                $this->hall->id,
                $targetIp,
                $targetSession
            );
            $this->chatParticipants = HallChatMessage::getChatParticipants($this->hall->id);
        }

        $this->dispatch('messageSent');
    }

    /**
     * Generate an intelligent, context-aware AI Concierge response tailored to this hall.
     */
    protected function generateBotReply(string $input): string
    {
        $lower = strtolower($input);
        $hall = $this->hall;

        // 1. Rent, Pricing & Capacity
        if (preg_match('/(rent|price|cost|rate|charge|fee|paisa|pese|kitna|budget|pkr|rs|capacity|guests|bande|log)/i', $lower)) {
            $formattedRent = number_format($hall->base_rent);
            $minCap = $hall->min_capacity ?: 100;
            $maxCap = $hall->max_capacity ?: 800;

            return "🏛️ **{$hall->name} — Rates & Capacity:**\n\n"
                ."• **Base Hall Rental:** PKR {$formattedRent} per event shift (Afternoon or Evening).\n"
                ."• **Guest Capacity:** Accommodates from {$minCap} up to {$maxCap} guests.\n"
                ."• **Included:** Central AC, 100% generator backup, private bridal suite, and parking.\n\n"
                .'Would you like to review our royal catering packages or check live date availability?';
        }

        // 2. Catering & Food Menus
        if (preg_match('/(food|cater|menu|package|dish|biryani|qorma|khana|dastarkhwan|per head|sweet|dessert)/i', $lower)) {
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
                    .'All packages feature authentic Moghlai recipes, live tandoori naan, salad bar, desserts, and dedicated waiter service. You can also customize your preferred menu items during reservation!';
            }

            return "🍽️ **Catering Service at {$hall->name}:**\n\n"
                .'We provide premium catering packages with traditional degi biryani, chicken handi, live tandoor, and royal sweets. Custom menu tastings can also be arranged with our executive chef!';
        }

        // 3. Shift Timings, Dates & Booking
        if (preg_match('/(date|available|avail|book|reserve|slot|shift|calendar|december|january|february|march|april|november|october|tarikh|din)/i', $lower)) {
            $storedWindows = $hall->tenant?->shift_windows;
            $shiftSummary = ShiftWindows::summary($storedWindows);
            $fullDay = ShiftWindows::labelForSlot($storedWindows, 'FULL_DAY');

            return "📅 **Shift Hours & Reservation Process:**\n\n"
                ."• **Operational Shifts:** {$shiftSummary}\n"
                ."• **Full Day (exclusive takeover):** {$fullDay}\n\n"
                ."To request an available shift, click **'Reserve Event Shift'** on the showcase page and our banquet team will confirm availability with you!";
        }

        // 4. Amenities: Generator, AC, Bridal Suite, Stage Decor
        if (preg_match('/(ac|air condition|chiller|generator|light|bijli|bridal|stage|decor|flower|sound|parking|valet)/i', $lower)) {
            $acText = $hall->has_air_conditioning ? 'High-Tonnage Central Chiller Air Conditioning' : 'Standard cooling ventilation';
            $genText = $hall->has_generator_backup ? '100% Heavy-Duty Backup Generator (Seamless Transfer)' : 'Standard Power';
            $bridalText = $hall->has_bridal_room ? 'Private VIP Bridal Suite with dressing vanity and restroom' : 'Available on request';

            return "✨ **Facilities & Luxury Amenities at {$hall->name}:**\n\n"
                ."• ⚡ **Power Backup:** {$genText}\n"
                ."• ❄️ **Climate Control:** {$acText}\n"
                ."• 👰 **Bridal Suite:** {$bridalText}\n"
                ."• 🚗 **Guest Parking:** On-site secure parking & valet assistance\n"
                .'• 🌸 **Stage & Decor:** Bespoke thematic stage decor and fresh floral styling are available through our services catalog.';
        }

        // 5. Contact Human / Venue Manager
        if (preg_match('/(human|owner|manager|call|phone|number|baat|contact|agent|talk|person)/i', $lower)) {
            return "📞 **Venue Manager Alerted:**\n\n"
                ."Your conversation has been sent to the front-desk management console of {$hall->name}. Please reply with your **phone number and event date**, and our wedding coordinator will contact you directly!";
        }

        // 6. Greetings
        if (preg_match('/(salam|hi|hello|aoa|assalam|hey|morning|evening)/i', $lower)) {
            return "Assalamu Alaikum! Welcome to **{$hall->name} AI Concierge**.\n\n"
                ."I am your 24/7 virtual banquet assistant. How can I help make your dream wedding seamless today?\n\n"
                .'Feel free to ask about our **hall rent, catering menus, available dates, or facilities**!';
        }

        // 7. Default Friendly Assistance
        return "Thank you for reaching out to **{$hall->name} Concierge**!\n\n"
            ."I can instantly help you with:\n"
            ."1ï¸âƒ£ **Hall Rent & Capacity** (Base rent & seating limits)\n"
            ."2ï¸âƒ£ **Catering Packages** (Per-head menus & tastings)\n"
            ."3ï¸âƒ£ **Shift Timings & Dates** (Afternoon vs Evening slots)\n"
            ."4ï¸âƒ£ **Generator & Bridal Suite Facilities**\n"
            ."5ï¸âƒ£ **Connect with Venue Manager**\n\n"
            .'What would you like to know more about?';
    }

    public function setSelectedCustomer(string $ip, string $session)
    {
        $user = Auth::user();
        $isHallOwner = $user && ($user->isSuperAdmin() || $user->tenant_id === $this->hall->tenant_id);
        abort_unless($isHallOwner, 403, 'Only authorized venue staff can open guest chat threads.');

        $this->selectedCustomerIp = $ip;
        $this->selectedCustomerSession = $session;
        $this->messages = HallChatMessage::getConversation(
            $this->hall->id,
            $ip,
            $session
        );
        HallChatMessage::markAsRead(
            $this->hall->id,
            $ip,
            $session
        );
    }

    public function render()
    {
        $user = Auth::user();
        $isHallOwner = $user && ($user->isSuperAdmin() || $user->tenant_id === $this->hall->tenant_id);

        return view('livewire.hall-chat', [
            'hall' => $this->hall,
            'messages' => $this->messages,
            'chatParticipants' => $this->chatParticipants,
            'selectedCustomerIp' => $this->selectedCustomerIp,
            'selectedCustomerSession' => $this->selectedCustomerSession,
            'isHallOwner' => $isHallOwner,
            'visitorIp' => $this->visitorIp,
            'visitorSession' => $this->visitorSession,
        ]);
    }
}
