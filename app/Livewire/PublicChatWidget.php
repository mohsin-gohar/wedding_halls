<?php

namespace App\Livewire;

use App\Models\Hall;
use Illuminate\Support\Str;
use Livewire\Component;

class PublicChatWidget extends Component
{
    public bool $isOpen = false;

    public string $newMessage = '';

    public array $messages = [];

    public function mount(): void
    {
        $this->initWelcomeMessage();
    }

    public function initWelcomeMessage(): void
    {
        $totalHalls = Hall::where('is_active', true)->count();
        $this->messages = [
            [
                'sender' => 'bot',
                'message' => "Assalamu Alaikum! Riwaj Grand Pakistan mein aap ka khush-aamdeed (Riwayat bhi, Jiddat bhi)!\n\nMain **Riwaj Grand AI Concierge** hoon. Main aap ko Pakistan ke top luxury banquets, wedding marquees, shift booking timings, catering packages aur platform registration ke baray mein mukammal information de sakta hoon.\n\nAap mujh se kuch bhi pooch saktay hain!",
                'time' => now()->format('h:i A'),
            ],
        ];
    }

    public function toggleChat(): void
    {
        $this->isOpen = ! $this->isOpen;
        if ($this->isOpen) {
            $this->dispatch('publicChatOpened');
        }
    }

    public function openChat(): void
    {
        $this->isOpen = true;
        $this->dispatch('publicChatOpened');
    }

    public function closeChat(): void
    {
        $this->isOpen = false;
    }

    public function resetConversation(): void
    {
        $this->initWelcomeMessage();
        $this->newMessage = '';
        $this->dispatch('publicChatOpened');
    }

    public function askQuickQuestion(string $question): void
    {
        $this->newMessage = $question;
        $this->sendMessage();
    }

    public function sendMessage(): void
    {
        $userText = trim($this->newMessage);
        if (empty($userText)) {
            return;
        }

        $this->validate([
            'newMessage' => 'required|string|min:1|max:1500',
        ]);

        // 1. Record User Message
        $this->messages[] = [
            'sender' => 'user',
            'message' => $userText,
            'time' => now()->format('h:i A'),
        ];

        $this->newMessage = '';

        // 2. Generate Intelligent Knowledge-Base Response
        $botReply = $this->generateAiReply($userText);

        $this->messages[] = [
            'sender' => 'bot',
            'message' => $botReply,
            'time' => now()->format('h:i A'),
        ];

        $this->dispatch('publicChatUpdated');
    }

    private function generateAiReply(string $input): string
    {
        $lower = Str::lower($input);

        // Greetings (using word boundaries for short tokens like 'hi', 'hey' so words like 'shift' don't false-match)
        if (preg_match('/\b(hi|hey|hello|salam|assalam|aoa|alaikum)\b|assalamu/i', $lower)) {
            return "Wa Alaikum Assalam wa Rahmatullahi wa Barakatuh!\n\nKhush-aamdeed! Main Riwaj Grand ka official AI Concierge hoon (Riwayat bhi, Jiddat bhi). Main aap ki madad ke liye har waqt online hoon. Aap mujh se:\n- 🏛️ Top Venues & Cities (Lahore, Karachi, Islamabad)\n- 📅 Shift Timings & Booking Process\n- 🍽️ Catering Packages & Bawarchi Menus\n- 📝 Wedding Hall Listing (Vendor SaaS)\nke baray mein pooch saktay hain.";
        }

        // Shifts & Timings
        if (preg_match('/\b(timing|timings|shift|shifts|hours|afternoon|evening|waqt|baje|slot)\b/i', $lower)) {
            return "Riwaj Grand ke tamaam partner venues 2 standard operational shifts mein book hotay hain:\n\n1. ☀️ **Afternoon Shift:** 1:00 PM se 4:30 PM tak (Mehndi, Dholki & Daytime Luncheon).\n2. 🌄 **Evening Shift:** 7:30 PM se 11:30 PM tak (Barat, Walima & Gala Receptions).\n\n⚡ **Single-Host Exclusivity:** Har shift mein poora hall sirf aik hi family ke liye exclusive hota hai, taake concurrency safe aur pur-waqar function ho.";
        }

        // Booking & Advance Payment
        if (preg_match('/\b(book|booking|advance|reserve|reservation|advance kitna|process|security|hold)\b/i', $lower)) {
            return "Riwaj Grand par shift reservation ka tareeqa bohot aasan hai:\n\n1. Kisi bhi hall ke page par ja kar **'RESERVE EVENT SHIFT'** button click karein.\n2. Date aur preferred shift (Afternoon ya Evening) select karein.\n3. Apni guest count aur catering menu tier chunein.\n4. **25% Advance:** Initial reservation lock karne ke liye 25% advance token darkaar hota hai, jis se shift guarantee ke sath lock ho jati hai aur double-booking ka zero risk hota hai.\n5. Official digital receipt aur quotation foran generate ho jati hai.";
        }

        // Vendor Onboarding / Hall Registration (Checked before generic hall search)
        if (preg_match('/\b(vendor|owner|register|list|add hall|partner|saas|admin|tenant|registration)\b/i', $lower)) {
            return "Kya aap wedding hall, marquee, ya event complex ke owner hain?\n\nRiwaj Grand Pakistan aap ko complete Cloud ERP deta hai:\n- Shift Booking Matrix (Zero double-booking guarantee)\n- Daily Kharcha & Generator Fuel Ledger\n- Staff Attendance & Payroll Generator\n- Kitchen KOT & WhatsApp RSVP Automation\n\n🎉 **Free Launch Access:** Abhi join karne par **30 Days Complimentary Access** milta hai! Yahan se register karein: [List Your Wedding Hall](/register).";
        }

        // Venues, Cities & Search
        if (preg_match('/\b(venue|venues|hall|halls|lahore|karachi|islamabad|rawalpindi|faisalabad|marquee|banquet|complex|location)\b/i', $lower)) {
            return "Riwaj Grand par Pakistan ke top verified banquets aur royal marquees listed hain:\n\n- **Lahore:** Gulberg III, DHA Phase 5, Raiwind Road, Canal Road aur Cantt.\n- **Karachi:** Clifton, KDA, Shahrah-e-Faisal aur Malir Cantt.\n- **Islamabad & Rawalpindi:** Club Road, Expressway, aur Rawalpindi Saddar.\n\nAap hamari [Explore Venues](/venues) directory mein ja kar city, capacity (200 se 1,500+ guests), aur date ke mutabiq filter kar saktay hain!";
        }

        // Catering, Food & Bawarchi
        if (preg_match('/\b(catering|food|khana|menu|menus|biryani|qorma|bbq|per head|bawarchi|sweet|dessert|price per head)\b/i', $lower)) {
            return "Hamare partner halls mein standard se le kar royal executive catering packages dastyab hain:\n\n- **Silver Package (~Rs 1,450/head):** Chicken Biryani, Chicken Qorma, Naan, Raita/Salad, Kheer/Gulab Jamun.\n- **Gold Royal Package (~Rs 2,250/head):** Mutton Qorma, Chicken Tikka/Sajji, Biryani, Live Tandoor, Kashmiri Chai, Desserts.\n- **Imperial Diamond (~Rs 3,200+/head):** Live BBQ Counter, Continental Soup, Mutton Pulao, Fish Fry, Custom Dessert Buffet.\n\nHar menu tier ke sath 100% generator backup aur climate control AC shamil hota hai.";
        }

        // Contact, Office & Helpline
        if (preg_match('/\b(contact|phone|number|helpline|call|address|office|location|email|help)\b/i', $lower)) {
            return "Aap Riwaj Grand Head Office se direct rabta kar saktay hain:\n\n📍 **Headquarters:** Plot 42, Main Boulevard, Gulberg III, Lahore, Pakistan\n📞 **UAN Helpline:** +92 42 35789000 (Mon-Sat, 10 AM – 8 PM)\n📧 **Inquiries Email:** inquiries@riwajgrand.pk\n\nAap hamare [Contact Us Page](/contact) par bhi apna paigham chor saktay hain.";
        }

        // Difference between Venue Chat and Public Chat
        if (preg_match('/\b(human|manager|hall manager|direct talk|venue chat|owner se baat)\b/i', $lower)) {
            return "Riwaj Grand par 2 mukhtalif chat options hain:\n\n1. **Riwaj Grand Chatbot (Yahan):** Main platform-wide AI guide hoon jo website, rules, cities aur packages ke baray mein help karta hoon.\n2. **Venue Desk Live Chat:** Jab aap kisi specific hall ke page (jaise Imperial Banquet) par jatay hain, wahan us hall ke front-desk manager se direct baat kar saktay hain aur live voice note bhi bhej saktay hain!";
        }

        // Thank you / Shukriya
        if (preg_match('/\b(shukriya|thanks|thank you|jazakallah|meherbani|great|good)\b/i', $lower)) {
            return 'Bohat shukriya! Riwaj Grand par aap ka aitmaad hamara fakhr hai. Agar aap ko kisi venue ya date availability ke baray mein mazeed poochna ho, toh main yahin dastyab hoon. Have a wonderful day!';
        }

        // Default Intelligent Fallback
        return "Main aap ke sawal ko behtar samajhne ki koshish kar raha hoon.\n\nAap mujh se in topics ke baray mein pooch saktay hain:\n- 🏛️ **'Lahore ke top banquets dikhayein'**\n- 📅 **'Shift timings kya hain?'**\n- 💳 **'Booking process aur advance policy kya hai?'**\n- 🍽️ **'Catering menu packages batayein'**\n- 📝 **'Apna hall kaise register karein?'**\n\nAap in mein se kisi bhi topic par pooch saktay hain!";
    }

    public function render()
    {
        return view('livewire.public-chat-widget');
    }
}
