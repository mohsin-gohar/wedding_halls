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

    <div class="grid grid-cols-1 md:grid-cols-4 gap-3 bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 p-4">
        <div class="md:col-span-2">
            <label for="staff-search" class="block text-[10px] uppercase font-mono text-stone-500 mb-1">Find a staff profile</label>
            <input id="staff-search" type="search" wire:model.live.debounce.300ms="search" placeholder="Name, email or phone" class="w-full bg-[#171513] border border-white/15 px-3 py-2 text-sm text-white">
        </div>
        <div>
            <label for="staff-role-filter" class="block text-[10px] uppercase font-mono text-stone-500 mb-1">Role</label>
            <select id="staff-role-filter" wire:model.live="roleFilter" class="w-full bg-[#171513] border border-white/15 px-3 py-2 text-sm text-white">
                <option value="ALL">All roles</option>
                @foreach(['ROLE_TENANT_OWNER', 'ROLE_MANAGER', 'ROLE_ACCOUNTANT', 'ROLE_FRONT_DESK', 'ROLE_CHEF', 'ROLE_FLOOR_STAFF'] as $staffRole)
                    <option value="{{ $staffRole }}">{{ str_replace('ROLE_', '', $staffRole) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="staff-status-filter" class="block text-[10px] uppercase font-mono text-stone-500 mb-1">Account state</label>
            <select id="staff-status-filter" wire:model.live="statusFilter" class="w-full bg-[#171513] border border-white/15 px-3 py-2 text-sm text-white">
                <option value="ACTIVE">Active accounts</option>
                <option value="SUSPENDED">Suspended accounts</option>
                <option value="ALL">All current accounts</option>
            </select>
        </div>
        <label class="md:col-span-4 inline-flex items-center gap-2 text-xs text-stone-300">
            <input type="checkbox" wire:model.live="showFormerStaff" class="accent-[#C6A15B]">
            Show former staff profiles (archived)
        </label>
    </div>

    <!-- Team Members Table -->
    <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10 shadow-sm dark:shadow-xl overflow-x-auto overflow-hidden transition-colors duration-200">
        <div class="px-6 py-4 border-b border-[#E7E5E4] dark:border-white/10 bg-[#F8F5EF] dark:bg-[#171513] flex justify-between items-center">
            <h3 class="font-serif-luxury text-sm font-bold uppercase tracking-wider text-[#171513] dark:text-white">
                Authorized Team Members ({{ $currentStaffCount }})
            </h3>
            <span class="text-xs text-stone-500 dark:text-stone-400 font-mono">
                Tenant ID: {{ substr(auth()->user()->tenant_id, 0, 8) }}...
            </span>
        </div>

        <div class="table-wrapper-enterprise" tabindex="0" role="region" aria-label="Staff Management Directory"><table class="table-enterprise">
                <thead>
                    <tr class="border-b border-[#E7E5E4] dark:border-white/10 bg-[#F8F5EF] dark:bg-[#171513] text-[#78716C] dark:text-[#C6A15B] uppercase font-mono tracking-wider text-[10px]">
                        <th class="py-3 px-6 font-bold">Staff Member</th>
                        <th class="py-3 px-6 font-bold">Email / Phone</th>
                        <th class="py-3 px-6 font-bold">System Role</th>
                        <th class="py-3 px-6 font-bold">Base Salary</th>
                        <th class="py-3 px-6 font-bold">Account</th>
                        <th class="py-3 px-6 font-bold">Access Permissions</th>
                        <th class="py-3 px-6 font-bold text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E7E5E4] dark:divide-white/10 text-stone-800 dark:text-stone-200 font-medium">
                    @foreach($staffMembers as $member)
                        <tr class="hover:bg-[#F8F5EF] dark:hover:bg-[#171513]/70 transition">
                            <td class="py-4 px-6">
                                <button type="button" wire:click="openProfileModal({{ $member->id }})" class="font-bold text-[#171513] dark:text-white text-sm hover:text-[#C6A15B] transition text-left flex items-center gap-1.5 group cursor-pointer">
                                    <span>{{ $member->name }}</span>
                                    <span class="text-[10px] text-[#C6A15B] font-mono opacity-0 group-hover:opacity-100 transition">👤 View</span>
                                </button>
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
                            <td class="py-4 px-6 font-mono text-xs whitespace-nowrap">
                                {{ $member->base_salary !== null ? 'PKR ' . number_format((float) $member->base_salary) : 'Role default' }}
                            </td>
                            <td class="py-4 px-6">
                                @if($member->trashed())
                                    <span class="px-2 py-1 text-[10px] uppercase font-mono bg-stone-500/15 text-stone-400 border border-stone-500/30">Former</span>
                                @elseif($member->status === 'SUSPENDED')
                                    <span class="px-2 py-1 text-[10px] uppercase font-mono bg-red-500/10 text-red-400 border border-red-500/30">Suspended</span>
                                @else
                                    <span class="px-2 py-1 text-[10px] uppercase font-mono bg-emerald-500/10 text-emerald-400 border border-emerald-500/30">Active</span>
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
                            <td class="py-4 px-6 text-right whitespace-nowrap">
                                <div class="inline-flex items-center justify-end gap-1.5 font-sans">
                                    <button type="button" wire:click="openProfileModal({{ $member->id }})" class="tbtn tbtn-gold" title="View Staff Profile">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                        <span>Profile</span>
                                    </button>
                                    @if(!$member->trashed())
                                        <button type="button" wire:click="openEditModal({{ $member->id }})" class="tbtn tbtn-outline" title="Edit Staff Details">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            <span>Edit</span>
                                        </button>
                                        @if($member->id !== auth()->id() && !$member->isTenantOwner() && (auth()->user()->isTenantOwner() || !$member->isManager()))
                                            <button type="button" wire:click="removeStaffMember({{ $member->id }})" 
                                                    wire:confirm="Archive this staff account? Access will be revoked while history is retained."
                                                    class="tbtn tbtn-amber" title="Archive Staff Account">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                                                <span>Archive</span>
                                            </button>
                                            @if(auth()->user()->isTenantOwner())
                                                <button type="button" wire:click="purgeStaffMember({{ $member->id }})"
                                                        wire:confirm="Permanently purge {{ $member->name }} from the database? This deletes this user to save server space and cannot be undone."
                                                        class="tbtn tbtn-red" title="Permanently Delete">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                    <span>Purge</span>
                                                </button>
                                            @endif
                                        @else
                                            <span class="text-stone-400 dark:text-stone-500 font-mono text-[10px] uppercase px-2 py-1 rounded bg-stone-100 dark:bg-white/5 border border-stone-200 dark:border-white/10">Protected</span>
                                        @endif
                                    @else
                                        @if(auth()->user()->isTenantOwner())
                                            <button type="button" wire:click="restoreStaffMember({{ $member->id }})" wire:confirm="Restore {{ $member->name }}'s access to this venue?" class="tbtn tbtn-green">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                                <span>Restore</span>
                                            </button>
                                            <button type="button" wire:click="purgeStaffMember({{ $member->id }})" wire:confirm="Permanently purge {{ $member->name }} from the database completely?" class="tbtn tbtn-red">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                <span>Purge</span>
                                            </button>
                                        @else
                                            <span class="text-stone-400 text-[10px] uppercase font-mono px-2 py-1 rounded bg-stone-100 dark:bg-white/5 border border-stone-200 dark:border-white/10">Archived</span>
                                        @endif
                                    @endif
                                </div>
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
                        <label class="block text-xs uppercase font-bold text-[#171513] dark:text-[#C6A15B] mb-1">Monthly Basic Salary (PKR)</label>
                        <input type="number" min="0" step="100" wire:model="baseSalary" placeholder="Leave blank to use role default" class="w-full bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/15 focus:border-[#C6A15B] text-[#171513] dark:text-white py-2.5 px-3.5 outline-none font-sans">
                        @error('baseSalary') <span class="text-[#171513] dark:text-[#F8F5EF] text-[11px] mt-0.5 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs uppercase font-bold text-[#171513] dark:text-[#C6A15B] mb-1">Assigned Role *</label>
                        <select wire:model="role" class="w-full bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/15 focus:border-[#C6A15B] text-[#171513] dark:text-white py-2.5 px-3.5 font-medium outline-none">@foreach($allowedRoles as $staffRole)
                                <option value="{{ $staffRole }}">{{ str_replace('ROLE_', '', $staffRole) }}</option>
                            @endforeach</select>
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
                        <label class="block text-xs uppercase font-bold text-[#171513] dark:text-[#C6A15B] mb-1">Monthly Basic Salary (PKR)</label>
                        <input type="number" min="0" step="100" wire:model="editBaseSalary" placeholder="Leave blank to use role default" class="w-full bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/15 focus:border-[#C6A15B] text-[#171513] dark:text-white py-2.5 px-3.5 outline-none font-sans">
                        @error('editBaseSalary') <span class="text-[#171513] dark:text-[#F8F5EF] text-[11px] mt-0.5 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs uppercase font-bold text-[#171513] dark:text-[#C6A15B] mb-1">Assigned Role *</label>
                        <select wire:model="editRole" @disabled($editRole === 'ROLE_TENANT_OWNER') class="w-full bg-[#F8F5EF] dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/15 focus:border-[#C6A15B] text-[#171513] dark:text-white py-2.5 px-3.5 font-medium outline-none"><option value="ROLE_TENANT_OWNER">Venue Owner (protected)</option>
                            @foreach($allowedRoles as $staffRole)
                                <option value="{{ $staffRole }}">{{ str_replace('ROLE_', '', $staffRole) }}</option>
                            @endforeach</select>
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

    <!-- Staff Profile & Executive Management Modal -->
    @if($showProfileModal && $profileStats)
        @php
            $profileUser = $profileStats['staff'];
        @endphp
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md overflow-y-auto">
            <div class="bg-white dark:bg-[#171513] border-2 border-[#C6A15B] gold-foil-border max-w-2xl w-full p-6 sm:p-8 space-y-6 shadow-2xl text-stone-800 dark:text-stone-200 transition-colors">
                
                <!-- Modal Header -->
                <div class="flex justify-between items-start border-b border-[#E7E5E4] dark:border-white/15 pb-4">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 border border-[#C6A15B] bg-[#171513] text-[#C6A15B] flex items-center justify-center font-cinzel text-2xl font-bold shadow-md">
                            {{ substr($profileUser->name, 0, 1) }}
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h2 class="font-serif-luxury text-xl sm:text-2xl text-[#171513] dark:text-white font-bold">
                                    {{ $profileUser->name }}
                                </h2>
                                @if($profileUser->trashed())
                                    <span class="px-2 py-0.5 text-[9px] uppercase font-mono bg-stone-500/20 text-stone-400 border border-stone-500/40 font-bold">Archived</span>
                                @elseif($profileUser->status === 'SUSPENDED')
                                    <span class="px-2 py-0.5 text-[9px] uppercase font-mono bg-red-500/15 text-red-500 border border-red-500/40 font-bold">Suspended</span>
                                @else
                                    <span class="px-2 py-0.5 text-[9px] uppercase font-mono bg-emerald-500/15 text-emerald-500 border border-emerald-500/40 font-bold">Active</span>
                                @endif
                            </div>
                            <span class="text-xs font-mono text-[#8F7239] dark:text-[#C6A15B] font-semibold block mt-0.5">
                                {{ str_replace('ROLE_', '', $profileUser->role) }}
                            </span>
                        </div>
                    </div>
                    <button wire:click="closeProfileModal" class="text-stone-400 hover:text-stone-700 dark:hover:text-white text-2xl leading-none cursor-pointer">&times;</button>
                </div>

                <!-- Info Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs font-mono">
                    <div class="p-3 bg-[#F8F5EF] dark:bg-white/5 border border-[#E7E5E4] dark:border-white/10 space-y-1">
                        <span class="text-stone-500 dark:text-stone-400 text-[10px] uppercase block">Official Email</span>
                        <div class="font-bold text-[#171513] dark:text-white select-all">{{ $profileUser->email }}</div>
                    </div>
                    <div class="p-3 bg-[#F8F5EF] dark:bg-white/5 border border-[#E7E5E4] dark:border-white/10 space-y-1">
                        <span class="text-stone-500 dark:text-stone-400 text-[10px] uppercase block">Phone / Mobile</span>
                        <div class="font-bold text-[#171513] dark:text-white">{{ $profileUser->phone ?? 'Not provided' }}</div>
                    </div>
                    <div class="p-3 bg-[#F8F5EF] dark:bg-white/5 border border-[#E7E5E4] dark:border-white/10 space-y-1">
                        <span class="text-stone-500 dark:text-stone-400 text-[10px] uppercase block">Base Monthly Salary</span>
                        <div class="font-bold text-[#8F7239] dark:text-[#C6A15B] font-cinzel text-sm">
                            {{ $profileUser->base_salary !== null ? 'PKR ' . number_format((float) $profileUser->base_salary) : 'Role Default Assigned' }}
                        </div>
                    </div>
                    <div class="p-3 bg-[#F8F5EF] dark:bg-white/5 border border-[#E7E5E4] dark:border-white/10 space-y-1">
                        <span class="text-stone-500 dark:text-stone-400 text-[10px] uppercase block">Account Onboarded</span>
                        <div class="font-bold text-[#171513] dark:text-white">{{ $profileUser->created_at?->format('d M Y') ?? '—' }}</div>
                    </div>
                </div>

                <!-- Workforce Snapshot -->
                <div class="border border-[#C6A15B]/30 bg-[#C6A15B]/5 p-4 space-y-2">
                    <span class="text-[10px] uppercase tracking-wider font-mono font-bold text-[#8F7239] dark:text-[#C6A15B] block">
                        Workforce Snapshot (Current Month)
                    </span>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-center font-mono">
                        <div class="p-2 bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10">
                            <span class="text-[9px] uppercase text-stone-500 block">Days Present</span>
                            <span class="text-sm font-bold text-emerald-600 dark:text-emerald-400">{{ $profileStats['attendances_present'] }}</span>
                        </div>
                        <div class="p-2 bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10">
                            <span class="text-[9px] uppercase text-stone-500 block">OT Hours</span>
                            <span class="text-sm font-bold text-[#8F7239] dark:text-[#C6A15B]">{{ number_format($profileStats['overtime_total'], 1) }}h</span>
                        </div>
                        <div class="p-2 bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10">
                            <span class="text-[9px] uppercase text-stone-500 block">Latest Payroll</span>
                            <span class="text-xs font-bold text-[#171513] dark:text-white">{{ $profileStats['latest_payroll_month'] ?? 'None' }}</span>
                        </div>
                        <div class="p-2 bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-white/10">
                            <span class="text-[9px] uppercase text-stone-500 block">Payroll Status</span>
                            <span class="text-xs font-bold {{ ($profileStats['latest_payroll_status'] ?? '') === 'PAID' ? 'text-emerald-500' : 'text-amber-500' }}">
                                {{ $profileStats['latest_payroll_status'] ?? 'Not compiled' }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- 1-Click Operational Action Bar -->
                <div class="border-t border-[#E7E5E4] dark:border-white/15 pt-4 space-y-3">
                    <span class="text-[10px] uppercase font-mono tracking-wider text-stone-500 dark:text-stone-400 block font-bold">
                        1-Click Operational Controls
                    </span>
                    <div class="flex flex-wrap items-center gap-2.5">
                        <a href="{{ route('staff.attendance', ['staff_id' => $profileUser->id]) }}" 
                           class="btn-outline-gold !h-9 !px-3 text-xs font-mono font-bold uppercase inline-flex items-center gap-1.5 shadow-sm">
                            <span>📅 View Attendance</span>
                        </a>

                        <a href="{{ route('staff.payroll', ['staff_id' => $profileUser->id]) }}" 
                           class="btn-gold !h-9 !px-3 text-xs font-mono font-bold uppercase inline-flex items-center gap-1.5 shadow-sm">
                            <span>💰 View Salary Slips</span>
                        </a>

                                                @if(!$profileUser->trashed())
                            <button type="button" wire:click="openEditModal({{ $profileUser->id }})" 
                                    class="tbtn tbtn-outline" title="Edit Profile Details">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                <span>Edit Profile</span>
                            </button>

                            @if($profileUser->id !== auth()->id() && !$profileUser->isTenantOwner())
                                <button type="button" wire:click="toggleStaffStatus({{ $profileUser->id }})" 
                                        class="tbtn tbtn-dark" title="Toggle Status">
                                    <span>{{ $profileUser->status === 'SUSPENDED' ? '🔓 Activate Account' : '🔒 Suspend Account' }}</span>
                                </button>
                                
                                <button type="button" wire:click="removeStaffMember({{ $profileUser->id }})" 
                                        wire:confirm="Archive this staff account? Access will be revoked while salary history is retained."
                                        class="tbtn tbtn-amber" title="Archive Account">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                                    <span>Archive</span>
                                </button>

                                @if(auth()->user()->isTenantOwner())
                                    <button type="button" wire:click="purgeStaffMember({{ $profileUser->id }})" 
                                            wire:confirm="PERMANENTLY PURGE {{ $profileUser->name }} from the server? All records for this user will be deleted to free up server database storage. This action cannot be undone."
                                            class="tbtn tbtn-red" title="Purge Record">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        <span>Purge From Server</span>
                                    </button>
                                @endif
                            @endif
                        
                        @else
                            @if(auth()->user()->isTenantOwner())
                                <button type="button" wire:click="restoreStaffMember({{ $profileUser->id }})" 
                                        class="border border-emerald-500/40 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 px-3 py-2 text-xs font-mono font-bold uppercase hover:bg-emerald-500/20 transition cursor-pointer">
                                    ♻️ Restore Access
                                </button>
                                <button type="button" wire:click="purgeStaffMember({{ $profileUser->id }})" 
                                        wire:confirm="Permanently purge {{ $profileUser->name }} from the server database completely?"
                                        class="border border-red-500/40 bg-red-500/10 text-red-600 dark:text-red-400 px-3 py-2 text-xs font-mono font-bold uppercase hover:bg-red-500/20 transition cursor-pointer">
                                    🗑️ Purge From Server
                                </button>
                            @endif
                        @endif
                    </div>
                </div>

            </div>
        </div>
    @endif

</div>
