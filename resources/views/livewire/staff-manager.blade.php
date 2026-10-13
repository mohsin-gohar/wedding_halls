<div class="max-w-7xl mx-auto space-y-8">
    
    <!-- Header -->
    <div class="bg-white dark:bg-[#171513] border border-[#C6A15B]/50 gold-foil-border p-6 sm:p-8 flex flex-col sm:flex-row sm:items-center justify-between gap-6 shadow-xl transition-colors duration-200">
        <div class="space-y-1.5">
            <div class="inline-flex items-center gap-2 px-2.5 py-0.5 border border-[#C6A15B]/40 bg-[#C6A15B]/10 text-[#8F7239] dark:text-[#C6A15B] text-[10px] font-mono uppercase tracking-widest font-bold">
                Role-Based Access Control
            </div>
            <h1 class="font-serif-luxury text-2xl sm:text-3xl text-[#171513] dark:text-white font-bold tracking-tight">
                Staff &amp; Team Management
            </h1>
            <p class="text-xs text-stone-600 dark:text-stone-300 max-w-xl font-normal leading-relaxed">
                Manage operational managers, accountants, chefs, and front-desk receptionists for {{ $tenant->name ?? 'your venue' }}.
            </p>
        </div>

        <div class="flex items-center gap-4">
            <div class="text-right hidden sm:block">
                <span class="text-[10px] uppercase font-mono tracking-wider text-stone-500 dark:text-stone-400 block font-semibold">Staff Limit</span>
                <span class="text-sm font-bold font-mono text-[#171513] dark:text-white">
                    {{ $currentStaffCount }} / {{ $maxStaff === -1 ? 'Unlimited' : $maxStaff }} Seats
                </span>
            </div>
            <button wire:click="openCreateModal" class="lux-btn lux-btn--gold lux-btn--sm">
                + Add Staff Member
            </button>
        </div>
    </div>

    <!-- Feedback / Alerts -->
    @if($feedbackMessage)
        <div class="p-4 bg-[#171513]/90 border border-[#C6A15B]/60 text-[#C6A15B] text-xs font-semibold flex justify-between items-center shadow-lg">
            <span>✓ {{ $feedbackMessage }}</span>
            <button wire:click="$set('feedbackMessage', null)" class="text-[#8F7239] font-bold">&times;</button>
        </div>
    @endif

    @if($errorMessage)
        <div class="p-4 bg-[#171513]/90 border border-[#C6A15B]/60 text-[#F8F5EF] text-xs font-semibold flex justify-between items-center shadow-lg">
            <span>⚠️ {{ $errorMessage }}</span>
            <button wire:click="$set('errorMessage', null)" class="text-[#171513] font-bold">&times;</button>
        </div>
    @endif

    <!-- Team Members Table -->
    <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 shadow-sm dark:shadow-xl overflow-hidden transition-colors duration-200">
        <div class="px-6 py-4 border-b border-[#E7E5E4] dark:border-white/10 bg-[#F8F5EF] dark:bg-[#171513] flex justify-between items-center">
            <h3 class="font-serif-luxury text-sm font-bold uppercase tracking-wider text-[#171513] dark:text-white">
                Authorized Team Members ({{ $currentStaffCount }})
            </h3>
            <span class="text-xs text-stone-500 dark:text-stone-400 font-mono">
                Tenant ID: {{ substr(auth()->user()->tenant_id, 0, 8) }}...
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-[#E7E5E4] dark:border-white/10 bg-[#F8F5EF] dark:bg-[#171513] text-[#78716C] dark:text-[#C6A15B] uppercase font-mono tracking-wider text-[10px]">
                        <th class="py-3 px-6 font-bold">Staff Member</th>
                        <th class="py-3 px-6 font-bold">Email / Phone</th>
                        <th class="py-3 px-6 font-bold">System Role</th>
                        <th class="py-3 px-6 font-bold">Access Permissions</th>
                        <th class="py-3 px-6 font-bold text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E7E5E4] dark:divide-white/10 text-stone-800 dark:text-stone-200 font-medium">
                    @foreach($staffMembers as $member)
                        <tr class="hover:bg-[#F8F5EF] dark:hover:bg-[#171513]/70 transition">
                            <td class="py-4 px-6">
                                <div class="font-bold text-[#171513] dark:text-white text-sm">{{ $member->name }}</div>
                                @if($member->id === auth()->id())
                                    <span class="text-[9px] uppercase tracking-wider bg-stone-200 dark:bg-white/10 text-stone-700 dark:text-stone-300 px-1.5 py-0.5 font-mono font-bold mt-0.5 inline-block">
                                        Current User (You)
                                    </span>
                                @endif
                            </td>
                            <td class="py-4 px-6 space-y-0.5">
                                <div class="font-mono text-stone-700 dark:text-stone-300">{{ $member->email }}</div>
                                <div class="text-stone-500 dark:text-stone-400 text-[11px] font-mono">{{ $member->phone ?? '—' }}</div>
                            </td>
                            <td class="py-4 px-6">
                                @if($member->role === 'ROLE_TENANT_OWNER')
                                    <span class="px-2.5 py-1 bg-[#C6A15B]/20 dark:bg-[#171513]/85 text-[#8F7239] dark:text-[#C6A15B] border border-[#C6A15B]/60 dark:border-[#C6A15B]/50 font-bold font-mono text-[10px] uppercase">
                                        👑 Business Owner
                                    </span>
                                @elseif($member->role === 'ROLE_MANAGER')
                                    <span class="px-2.5 py-1 bg-[#C6A15B]/15 dark:bg-[#171513]/85 text-[#8F7239] dark:text-[#C6A15B] border border-[#C6A15B]/60 dark:border-[#C6A15B]/50 font-bold font-mono text-[10px] uppercase">
                                        👔 General Manager
                                    </span>
                                @elseif($member->role === 'ROLE_ACCOUNTANT')
                                    <span class="px-2.5 py-1 bg-[#C6A15B]/15 dark:bg-[#171513]/85 text-[#8F7239] dark:text-[#C6A15B] border border-[#C6A15B]/60 dark:border-[#C6A15B]/50 font-bold font-mono text-[10px] uppercase">
                                        📊 Finance &amp; Accountant
                                    </span>
                                @elseif($member->role === 'ROLE_CHEF')
                                    <span class="px-2.5 py-1 bg-[#C6A15B]/20 dark:bg-[#171513]/85 text-[#8F7239] dark:text-[#C6A15B] border border-[#C6A15B]/60 dark:border-[#C6A15B]/50 font-bold font-mono text-[10px] uppercase">
                                        👨‍🍳 Executive Chef
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 bg-stone-100 dark:bg-white/10 text-stone-800 dark:text-stone-200 border border-stone-300 dark:border-white/15 font-bold font-mono text-[10px] uppercase">
                                        🛎️ Front Desk Reception
                                    </span>
                                @endif
                            </td>
                            <td class="py-4 px-6 text-[11px] text-stone-600 dark:text-stone-400">
                                @if($member->role === 'ROLE_TENANT_OWNER')
                                    <span>Full Administrative Control &amp; Banking</span>
                                @elseif($member->role === 'ROLE_MANAGER')
                                    <span>Halls, Bookings, Packages, CRM &amp; Staff</span>
                                @elseif($member->role === 'ROLE_ACCOUNTANT')
                                    <span>Invoices, Payments, Expenses &amp; P&amp;L Reports</span>
                                @elseif($member->role === 'ROLE_CHEF')
                                    <span>Kitchen KOT Slips, Raw Ingredients &amp; Menus</span>
                                @else
                                    <span>Shift Calendar, Booking Creation, Inquiries</span>
                                @endif
                            </td>
                            <td class="py-4 px-6 text-right whitespace-nowrap space-x-2 font-mono">
                                <button wire:click="openEditModal({{ $member->id }})" 
                                        class="text-[#171513] dark:text-stone-200 hover:text-[#C6A15B] font-semibold text-xs transition border border-[#E7E5E4] dark:border-white/15 hover:border-[#C6A15B] px-2.5 py-1 bg-white dark:bg-[#171513]">
                                    Edit
                                </button>
                                @if($member->id !== auth()->id() && !$member->isTenantOwner())
                                    <button wire:click="removeStaffMember({{ $member->id }})" 
                                            wire:confirm="Are you certain you wish to revoke all access for {{ $member->name }}?"
                                            class="text-[#171513] dark:text-[#F8F5EF] hover:text-[#171513] font-semibold text-xs transition border border-[#C6A15B]/40 dark:border-[#171513]/40 hover:border-[#C6A15B]/60 px-2.5 py-1 bg-[#171513]/8 dark:bg-[#171513]/85">
                                        Remove
                                    </button>
                                @else
                                    <span class="text-stone-400 dark:text-stone-500 font-mono text-[11px]">Protected</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Create Staff Modal -->
    @if($isCreating)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md overflow-y-auto">
            <div class="bg-white dark:bg-[#171513] border border-[#C6A15B]/60 gold-foil-border max-w-lg w-full p-6 sm:p-8 space-y-6 shadow-2xl text-stone-800 dark:text-stone-200 transition-colors">
                <div class="flex justify-between items-start border-b border-[#E7E5E4] dark:border-white/15 pb-4">
                    <div>
                        <span class="text-[10px] font-mono uppercase tracking-widest text-[#8F7239] dark:text-[#C6A15B] font-bold block">
                            Staff Onboarding
                        </span>
                        <h2 class="font-serif-luxury text-xl text-[#171513] dark:text-white font-bold mt-1">Add Team Member</h2>
                    </div>
                    <button wire:click="closeCreateModal" class="text-stone-400 hover:text-stone-700 dark:hover:text-white text-2xl leading-none">&times;</button>
                </div>

                <form wire:submit="createStaffMember" class="space-y-4 text-xs font-mono">
                    <div>
                        <label class="block text-xs uppercase font-bold text-[#171513] dark:text-[#C6A15B] mb-1">Full Name *</label>
                        <input type="text" wire:model="name" placeholder="e.g. Tariq Mehmood" class="w-full bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/15 focus:border-[#C6A15B] text-[#171513] dark:text-white py-2.5 px-3.5 outline-none font-sans">
                        @error('name') <span class="text-[#171513] dark:text-[#F8F5EF] text-[11px] mt-0.5 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs uppercase font-bold text-[#171513] dark:text-[#C6A15B] mb-1">Official Email *</label>
                            <input type="email" wire:model="email" placeholder="staff@banquet.com" class="w-full bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/15 focus:border-[#C6A15B] text-[#171513] dark:text-white py-2.5 px-3.5 outline-none font-sans">
                            @error('email') <span class="text-[#171513] dark:text-[#F8F5EF] text-[11px] mt-0.5 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs uppercase font-bold text-[#171513] dark:text-[#C6A15B] mb-1">Phone Number</label>
                            <input type="text" wire:model="phone" placeholder="03001234567" class="w-full bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/15 focus:border-[#C6A15B] text-[#171513] dark:text-white py-2.5 px-3.5 outline-none font-sans">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs uppercase font-bold text-[#171513] dark:text-[#C6A15B] mb-1">Assigned Role *</label>
                        <select wire:model="role" class="w-full bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/15 focus:border-[#C6A15B] text-[#171513] dark:text-white py-2.5 px-3.5 font-medium outline-none">
                            <option value="ROLE_FRONT_DESK">🛎️ Front Desk / Booking Receptionist</option>
                            <option value="ROLE_MANAGER">👔 General Manager (Operations)</option>
                            <option value="ROLE_ACCOUNTANT">📊 Accountant / Finance Officer</option>
                            <option value="ROLE_CHEF">👨‍🍳 Executive Chef / Bawarchi Head</option>
                            <option value="ROLE_FLOOR_STAFF">🧹 Floor Staff / Supervisor</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs uppercase font-bold text-[#171513] dark:text-[#C6A15B] mb-1">Login Password *</label>
                        <input type="password" wire:model="password" placeholder="At least 6 characters" class="w-full bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/15 focus:border-[#C6A15B] text-[#171513] dark:text-white py-2.5 px-3.5 outline-none font-sans">
                        @error('password') <span class="text-[#171513] dark:text-[#F8F5EF] text-[11px] mt-0.5 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-[#E7E5E4] dark:border-white/15">
                        <button type="button" wire:click="closeCreateModal" class="border border-[#E7E5E4] dark:border-white/20 bg-transparent text-stone-700 dark:text-stone-300 hover:text-[#171513] dark:hover:text-white px-4 py-2 font-bold uppercase tracking-wider text-xs transition">
                            Cancel
                        </button>
                        <button type="submit" class="btn-gold !h-10 !px-5 text-xs font-bold uppercase tracking-wider shadow">
                            Save &amp; Grant Access
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Edit Staff Modal -->
    @if($isEditing)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md overflow-y-auto">
            <div class="bg-white dark:bg-[#171513] border border-[#C6A15B]/60 gold-foil-border max-w-lg w-full p-6 sm:p-8 space-y-6 shadow-2xl text-stone-800 dark:text-stone-200 transition-colors">
                <div class="flex justify-between items-start border-b border-[#E7E5E4] dark:border-white/15 pb-4">
                    <div>
                        <span class="text-[10px] font-mono uppercase tracking-widest text-[#8F7239] dark:text-[#C6A15B] font-bold block">
                            Staff Modification
                        </span>
                        <h2 class="font-serif-luxury text-xl text-[#171513] dark:text-white font-bold mt-1">Edit Team Member</h2>
                    </div>
                    <button wire:click="closeEditModal" class="text-stone-400 hover:text-stone-700 dark:hover:text-white text-2xl leading-none">&times;</button>
                </div>

                <form wire:submit="updateStaffMember" class="space-y-4 text-xs font-mono">
                    <div>
                        <label class="block text-xs uppercase font-bold text-[#171513] dark:text-[#C6A15B] mb-1">Full Name *</label>
                        <input type="text" wire:model="editName" placeholder="Full name" class="w-full bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/15 focus:border-[#C6A15B] text-[#171513] dark:text-white py-2.5 px-3.5 outline-none font-sans">
                        @error('editName') <span class="text-[#171513] dark:text-[#F8F5EF] text-[11px] mt-0.5 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs uppercase font-bold text-[#171513] dark:text-[#C6A15B] mb-1">Official Email *</label>
                            <input type="email" wire:model="editEmail" placeholder="staff@banquet.com" class="w-full bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/15 focus:border-[#C6A15B] text-[#171513] dark:text-white py-2.5 px-3.5 outline-none font-sans">
                            @error('editEmail') <span class="text-[#171513] dark:text-[#F8F5EF] text-[11px] mt-0.5 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs uppercase font-bold text-[#171513] dark:text-[#C6A15B] mb-1">Phone Number</label>
                            <input type="text" wire:model="editPhone" placeholder="03001234567" class="w-full bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/15 focus:border-[#C6A15B] text-[#171513] dark:text-white py-2.5 px-3.5 outline-none font-sans">
                            @error('editPhone') <span class="text-[#171513] dark:text-[#F8F5EF] text-[11px] mt-0.5 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs uppercase font-bold text-[#171513] dark:text-[#C6A15B] mb-1">Assigned Role *</label>
                        <select wire:model="editRole" class="w-full bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/15 focus:border-[#C6A15B] text-[#171513] dark:text-white py-2.5 px-3.5 font-medium outline-none">
                            <option value="ROLE_FRONT_DESK">🛎️ Front Desk / Booking Receptionist</option>
                            <option value="ROLE_MANAGER">👔 General Manager (Operations)</option>
                            <option value="ROLE_ACCOUNTANT">📊 Accountant / Finance Officer</option>
                            <option value="ROLE_CHEF">👨‍🍳 Executive Chef / Bawarchi Head</option>
                            <option value="ROLE_FLOOR_STAFF">🧹 Floor Staff / Supervisor</option>
                        </select>
                        @error('editRole') <span class="text-[#171513] dark:text-[#F8F5EF] text-[11px] mt-0.5 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs uppercase font-bold text-[#171513] dark:text-[#C6A15B] mb-1">Reset Password (Leave blank to keep unchanged)</label>
                        <input type="password" wire:model="editPassword" placeholder="Enter new password (optional)" class="w-full bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/15 focus:border-[#C6A15B] text-[#171513] dark:text-white py-2.5 px-3.5 outline-none font-sans">
                        @error('editPassword') <span class="text-[#171513] dark:text-[#F8F5EF] text-[11px] mt-0.5 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-[#E7E5E4] dark:border-white/15">
                        <button type="button" wire:click="closeEditModal" class="border border-[#E7E5E4] dark:border-white/20 bg-transparent text-stone-700 dark:text-stone-300 hover:text-[#171513] dark:hover:text-white px-4 py-2 font-bold uppercase tracking-wider text-xs transition">
                            Cancel
                        </button>
                        <button type="submit" class="btn-gold !h-10 !px-5 text-xs font-bold uppercase tracking-wider shadow">
                            Update Member Profile
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

</div>
