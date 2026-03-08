{{-- Shared form fields for create/edit user --}}

<div>
    <label class="block text-sm font-medium text-gray-700 mb-1.5">Full Name</label>
    <input type="text" name="name" value="{{ old('name', $user?->name) }}"
           class="w-full px-3 py-2.5 border rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-transparent outline-none {{ $errors->has('name') ? 'border-red-400' : 'border-gray-200' }}"
           placeholder="John Doe" required>
    @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
</div>

<div>
    <label class="block text-sm font-medium text-gray-700 mb-1.5">Email Address</label>
    <input type="email" name="email" value="{{ old('email', $user?->email) }}"
           class="w-full px-3 py-2.5 border rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-transparent outline-none {{ $errors->has('email') ? 'border-red-400' : 'border-gray-200' }}"
           placeholder="john@example.com" required>
    @error('email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
</div>

<div>
    <label class="block text-sm font-medium text-gray-700 mb-1.5">Role</label>
    <select name="role"
            class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-transparent outline-none {{ $errors->has('role') ? 'border-red-400' : '' }}">
        @foreach(['admin' => 'Admin — Full access including user management', 'analyst' => 'Analyst — Can import reports and view all data', 'viewer' => 'Viewer — Read-only access'] as $val => $label)
        <option value="{{ $val }}" {{ old('role', $user?->role) === $val ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
    </select>
    @error('role') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
</div>

<div>
    <label class="block text-sm font-medium text-gray-700 mb-1.5">
        Password {{ $user ? '(leave blank to keep current)' : '' }}
    </label>
    <input type="password" name="password"
           class="w-full px-3 py-2.5 border rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-transparent outline-none {{ $errors->has('password') ? 'border-red-400' : 'border-gray-200' }}"
           {{ $user ? '' : 'required' }}>
    @error('password') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
</div>

<div>
    <label class="block text-sm font-medium text-gray-700 mb-1.5">Confirm Password</label>
    <input type="password" name="password_confirmation"
           class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-transparent outline-none"
           {{ $user ? '' : 'required' }}>
</div>
