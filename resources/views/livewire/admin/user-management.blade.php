<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h2 class="text-2xl font-semibold">User Management</h2>
        <button wire:click="openCreateModal" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
            + Create User
        </button>
    </div>

    @if (session()->has('message'))
        <div class="p-4 bg-green-100 dark:bg-green-900 border border-green-400 dark:border-green-600 text-green-700 dark:text-green-200 rounded">
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="p-4 bg-red-100 dark:bg-red-900 border border-red-400 dark:border-red-600 text-red-700 dark:text-red-200 rounded">
            {{ session('error') }}
        </div>
    @endif

    <div class="mb-4">
        <input 
            type="text" 
            wire:model.live="search" 
            placeholder="Search users..." 
            class="w-full px-4 py-2 border rounded dark:bg-gray-800 dark:border-gray-700"
        />
    </div>

    <div class="bg-white dark:bg-gray-800 rounded shadow overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-100 dark:bg-gray-700">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase">Name</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase">Email</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase">Role</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase">Created</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($users as $user)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="px-4 py-3 text-sm">{{ $user->name }}</td>
                        <td class="px-4 py-3 text-sm">{{ $user->email }}</td>
                        <td class="px-4 py-3 text-sm">
                            <span class="px-2 py-1 text-xs rounded {{ $user->is_admin ? 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200' }}">
                                {{ $user->is_admin ? 'Admin' : 'User' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm">{{ $user->created_at->format('d.m.Y') }}</td>
                        <td class="px-4 py-3 text-sm">
                            <div class="flex gap-2">
                                <button 
                                    wire:click="openEditModal({{ $user->id }})" 
                                    class="px-2 py-1 text-xs bg-blue-500 text-white rounded hover:bg-blue-600"
                                >
                                    Edit
                                </button>
                                
                                @if($user->id !== auth()->id())
                                    <button 
                                        wire:click="toggleAdmin({{ $user->id }})" 
                                        class="px-2 py-1 text-xs bg-purple-500 text-white rounded hover:bg-purple-600"
                                    >
                                        {{ $user->is_admin ? 'Remove Admin' : 'Make Admin' }}
                                    </button>
                                    
                                    <button 
                                        wire:click="loginAsUser({{ $user->id }})" 
                                        class="px-2 py-1 text-xs bg-yellow-500 text-white rounded hover:bg-yellow-600"
                                        title="Remote Support"
                                    >
                                        🔧 Support
                                    </button>
                                    
                                    <button 
                                        wire:click="deleteUser({{ $user->id }})" 
                                        wire:confirm="Are you sure you want to delete this user?"
                                        class="px-2 py-1 text-xs bg-red-500 text-white rounded hover:bg-red-600"
                                    >
                                        Delete
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                            No users found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $users->links() }}
    </div>

    <!-- Create User Modal -->
    @if($showCreateModal)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 w-full max-w-md">
                <h3 class="text-lg font-semibold mb-4">Create New User</h3>
                
                <form wire:submit.prevent="createUser" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Name</label>
                        <input 
                            type="text" 
                            wire:model="name" 
                            class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:border-gray-600"
                        />
                        @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Email</label>
                        <input 
                            type="email" 
                            wire:model="email" 
                            class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:border-gray-600"
                        />
                        @error('email') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Password</label>
                        <input 
                            type="password" 
                            wire:model="password" 
                            class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:border-gray-600"
                        />
                        @error('password') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="flex items-center gap-2">
                            <input type="checkbox" wire:model="is_admin" />
                            <span class="text-sm">Admin User</span>
                        </label>
                    </div>

                    <div class="flex justify-end gap-2 mt-6">
                        <button 
                            type="button" 
                            wire:click="closeCreateModal" 
                            class="px-4 py-2 border rounded hover:bg-gray-100 dark:hover:bg-gray-700"
                        >
                            Cancel
                        </button>
                        <button 
                            type="submit" 
                            class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
                        >
                            Create User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Edit User Modal -->
    @if($showEditModal)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 w-full max-w-md">
                <h3 class="text-lg font-semibold mb-4">Edit User</h3>
                
                <form wire:submit.prevent="updateUser" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Name</label>
                        <input 
                            type="text" 
                            wire:model="name" 
                            class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:border-gray-600"
                        />
                        @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Email</label>
                        <input 
                            type="email" 
                            wire:model="email" 
                            class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:border-gray-600"
                        />
                        @error('email') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Password (leave empty to keep current)</label>
                        <input 
                            type="password" 
                            wire:model="password" 
                            class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:border-gray-600"
                        />
                        @error('password') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="flex items-center gap-2">
                            <input type="checkbox" wire:model="is_admin" />
                            <span class="text-sm">Admin User</span>
                        </label>
                    </div>

                    <div class="flex justify-end gap-2 mt-6">
                        <button 
                            type="button" 
                            wire:click="closeEditModal" 
                            class="px-4 py-2 border rounded hover:bg-gray-100 dark:hover:bg-gray-700"
                        >
                            Cancel
                        </button>
                        <button 
                            type="submit" 
                            class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
                        >
                            Update User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
