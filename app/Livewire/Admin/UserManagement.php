<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use Livewire\WithPagination;

class UserManagement extends Component
{
    use WithPagination;

    public $search = '';
    public $showCreateModal = false;
    public $showEditModal = false;
    public $selectedUserId = null;

    // Form fields
    public $name = '';
    public $email = '';
    public $password = '';
    public $is_admin = false;

    protected function rules()
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'is_admin' => ['boolean'],
        ];

        if ($this->showCreateModal || $this->password) {
            $rules['password'] = ['required', 'string', 'min:8'];
        }

        if ($this->selectedUserId) {
            $rules['email'][] = 'unique:users,email,' . $this->selectedUserId;
        } else {
            $rules['email'][] = 'unique:users,email';
        }

        return $rules;
    }

    public function mount()
    {
        if (!Auth::user()->is_admin) {
            abort(403, 'Unauthorized');
        }
    }

    public function render()
    {
        $users = User::query()
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('email', 'like', '%' . $this->search . '%');
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('livewire.admin.user-management', [
            'users' => $users,
        ]);
    }

    public function openCreateModal()
    {
        $this->reset(['name', 'email', 'password', 'is_admin', 'selectedUserId']);
        $this->showCreateModal = true;
    }

    public function openEditModal($userId)
    {
        $user = User::findOrFail($userId);
        $this->selectedUserId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->is_admin = $user->is_admin;
        $this->password = '';
        $this->showEditModal = true;
    }

    public function createUser()
    {
        $this->validate();

        User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make($this->password),
            'is_admin' => $this->is_admin,
        ]);

        $this->showCreateModal = false;
        $this->reset(['name', 'email', 'password', 'is_admin']);
        session()->flash('message', 'User created successfully.');
    }

    public function updateUser()
    {
        $this->validate();

        $user = User::findOrFail($this->selectedUserId);

        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'is_admin' => $this->is_admin,
        ];

        if ($this->password) {
            $data['password'] = Hash::make($this->password);
        }

        $user->update($data);

        $this->showEditModal = false;
        $this->reset(['name', 'email', 'password', 'is_admin', 'selectedUserId']);
        session()->flash('message', 'User updated successfully.');
    }

    public function deleteUser($userId)
    {
        if ($userId === Auth::id()) {
            session()->flash('error', 'You cannot delete your own account.');
            return;
        }

        $user = User::findOrFail($userId);
        $user->delete();

        session()->flash('message', 'User deleted successfully.');
    }

    public function toggleAdmin($userId)
    {
        if ($userId === Auth::id()) {
            session()->flash('error', 'You cannot change your own admin status.');
            return;
        }

        $user = User::findOrFail($userId);
        $user->update(['is_admin' => !$user->is_admin]);

        session()->flash('message', 'Admin status updated.');
    }

    public function loginAsUser($userId)
    {
        if (!Auth::user()->is_admin) {
            abort(403);
        }

        $user = User::findOrFail($userId);

        // Store the admin's ID in session for later restoration
        session()->put('admin_user_id', Auth::id());
        session()->put('remote_support_active', true);

        Auth::login($user);

        return redirect()->route('dashboard')->with('message', 'Remote support session started.');
    }

    public function closeCreateModal()
    {
        $this->showCreateModal = false;
        $this->reset(['name', 'email', 'password', 'is_admin']);
    }

    public function closeEditModal()
    {
        $this->showEditModal = false;
        $this->reset(['name', 'email', 'password', 'is_admin', 'selectedUserId']);
    }
}
