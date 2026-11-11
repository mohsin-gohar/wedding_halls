<?php

namespace App\Livewire;

use App\Models\Hall;
use App\Models\HallChatMessage;
use App\Models\Package;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.public', ['title' => 'Concierge Chatbot â€” Riwaj Grand'])]
class HallChat extends Component
{
    use WithFileUploads;

    public ?Hall $hall = null;

    public string $newMessage = '';

    public $voiceRecording = null;

    public string $visitorIp = '';

    public string $visitorSession = '';

    public ?string $selectedCustomerIp = null;

    public ?string $selectedCustomerSession = null;

    public array $chatParticipants = [];

    public array $messages = [];

    public function mount(?string $slug = null, ?string $id = null)
    {
        $identifier = $slug ?? $id;

        if (! $identifier) {
            $this->hall = Hall::with(['venue', 'tenant'])
                ->where('is_active', true)
                ->whereHas('tenant', fn ($t) => $t->where('is_active', true)->where('verification_status', 'APPROVED'))
                ->first();

            if (! $this->hall) {
                $this->hall = Hall::with(['venue', 'tenant'])->where('is_active', true)->first();
            }

            if (! $this->hall) {
                abort(404, 'No active venues available for concierge chat.');
            }
        } else {
            $this->hall = Hall::with(['venue', 'tenant'])
                ->where(function ($q) use ($identifier) {
                    $q->where('slug', $identifier);
                    if (str_contains($identifier, '-')) {
                        $q->orWhere('id', $identifier);
                    }
                })
                ->firstOrFail();

            if (! $this->hall->is_active) {
                abort(404, 'This venue space is currently inactive.');
            }

            $user = Auth::user();
            $isStaffForHall = $user && ($user->isSuperAdmin() || $user->tenant_id === $this->hall->tenant_id);
            if (! $isStaffForHall && (! $this->hall->tenant || ! $this->hall->tenant->is_active || $this->hall->tenant->verification_status !== 'APPROVED')) {
                abort(404, 'Venue is pending verification or currently unavailable.');
            }
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

        // 1. Persist customer message
        HallChatMessage::create([
            'hall_id' => $this->hall->id,
            'user_id' => $user?->id,
            'ip_address' => $this->visitorIp,
            'session_id' => $this->visitorSession,
            'sender_type' => $isHallOwner ? 'hall_owner' : 'customer',
            'message_type' => 'text',
            'message' => $userText,
            'voice_file' => null,
            'is_read' => false,
        ]);

        $this->newMessage = '';

        // 2. If sent by customer/guest, generate an instant AI Concierge Bot response
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
            if ($this->selectedCustomerIp && $this->selectedCustomerSession) {
                $this->messages = HallChatMessage::getConversation(
                    $this->hall->id,
                    $this->selectedCustomerIp,
                    $this->selectedCustomerSession
                );
            }
        }

        $this->dispatch('messageSent');
    }

    public function uploadVoice()
    {
        $this->validate([
            'voiceRecording' => 'required|file|mimes:mp3,wav,ogg,m4a|max:5120',
        ]);

        $user = Auth::user();
        $isHallOwner = $user && ($user->isSuperAdmin() || $user->tenant_id === $this->hall->tenant_id);

        $path = $this->voiceRecording->store('hall-voice-messages/'.$this->hall->id, 'public');

        HallChatMessage::create([
            'hall_id' => $this->hall->id,
            'user_id' => $user?->id,
            'ip_address' => $this->visitorIp,
            'session_id' => $this->visitorSession,
            'sender_type' => $isHallOwner ? 'hall_owner' : 'customer',
            'message_type' => 'voice',
            'message' => 'Voice message',
            'voice_file' => $path,
            'is_read' => false,
        ]);

        $this->voiceRecording = null;

        if (! $isHallOwner) {
            $botReply = "ðŸŽ™ï¸ Thank you for your voice note! Our wedding concierge desk at {$this->hall->name} has received it. A venue manager will listen to your recording and get back to you shortly.";
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
            if ($this->selectedCustomerIp && $this->selectedCustomerSession) {
                $this->messages = HallChatMessage::getConversation(
                    $this->hall->id,
                    $this->selectedCustomerIp,
                    $this->selectedCustomerSession
                );
            }
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

            return "ðŸ›ï¸ **{$hall->name} â€” Rates & Capacity:**\n\n"
                ."â€¢ **Base Hall Rental:** PKR {$formattedRent} per event shift (Afternoon or Evening).\n"
                ."â€¢ **Guest Capacity:** Accommodates from {$minCap} up to {$maxCap} guests.\n"
                ."â€¢ **Included:** Central AC, 100% generator backup, private bridal suite, and parking.\n\n"
                .'Would you like to review our royal catering packages or check live date availability?';
        }

        // 2. Catering & Food Menus
        if (preg_match('/(food|cater|menu|package|dish|biryani|qorma|khana|dastarkhwan|per head|sweet|dessert)/i', $lower)) {
            $packages = Package::where('tenant_id', $hall->tenant_id)->where('is_active', true)->take(3)->get();
            if ($packages->isNotEmpty()) {
                $lines = [];
                foreach ($packages as $pkg) {
                    $rate = number_format($pkg->per_head_price);
                    $lines[] = "â€¢ **{$pkg->name}:** PKR {$rate}/head (Min {$pkg->min_guests} guests)";
                }
                $pkgList = implode("\n", $lines);

                return "ðŸ½ï¸ **Royal Catering Packages at {$hall->name}:**\n\n"
                    ."{$pkgList}\n\n"
                    .'All packages feature authentic Moghlai recipes, live tandoori naan, salad bar, desserts, and dedicated waiter service. You can also customize your preferred menu items during reservation!';
            }

            return "ðŸ½ï¸ **Catering Service at {$hall->name}:**\n\n"
                .'We provide premium catering packages with traditional degi biryani, chicken handi, live tandoor, and royal sweets. Custom menu tastings can also be arranged with our executive chef!';
        }

        // 3. Shift Timings, Dates & Booking
        if (preg_match('/(date|available|avail|book|reserve|slot|shift|calendar|december|january|february|march|april|november|october|tarikh|din)/i', $lower)) {
            return "ðŸ“… **Shift Hours & Reservation Process:**\n\n"
                ."â€¢ **Afternoon Shift:** 12:00 PM â€“ 04:00 PM (Ideal for Mehndi / Nikkah / Luncheons)\n"
                ."â€¢ **Evening Shift:** 07:00 PM â€“ 11:30 PM (Barat / Walima Reception)\n"
                ."â€¢ **Full Day:** 11:00 AM â€“ 11:30 PM (Exclusive Takeover)\n\n"
                ."To reserve an available slot instantly, you can click **'Reserve Event Shift'** on the showcase page and pay token advance deposit at front desk rates!";
        }

        // 4. Amenities: Generator, AC, Bridal Suite, Stage Decor
        if (preg_match('/(ac|air condition|chiller|generator|light|bijli|bridal|stage|decor|flower|sound|parking|valet)/i', $lower)) {
            $acText = $hall->has_air_conditioning ? 'High-Tonnage Central Chiller Air Conditioning' : 'Standard cooling ventilation';
            $genText = $hall->has_generator_backup ? '100% Heavy-Duty Backup Generator (Seamless Transfer)' : 'Standard Power';
            $bridalText = $hall->has_bridal_room ? 'Private VIP Bridal Suite with dressing vanity and restroom' : 'Available on request';

            return "âœ¨ **Facilities & Luxury Amenities at {$hall->name}:**\n\n"
                ."â€¢ âš¡ **Power Backup:** {$genText}\n"
                ."â€¢ â„ï¸ **Climate Control:** {$acText}\n"
                ."â€¢ ðŸ‘° **Bridal Suite:** {$bridalText}\n"
                ."â€¢ ðŸš— **Guest Parking:** On-site secure parking & valet assistance\n"
                .'â€¢ ðŸŒ¸ **Stage & Decor:** Bespoke thematic stage decor and fresh floral styling are available through our services catalog.';
        }

        // 5. Contact Human / Venue Manager
        if (preg_match('/(human|owner|manager|call|phone|number|baat|contact|agent|talk|person)/i', $lower)) {
            return "ðŸ“ž **Venue Manager Alerted:**\n\n"
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
