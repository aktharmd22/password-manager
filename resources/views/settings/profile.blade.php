<x-app-layout :title="'Profile'" :breadcrumbs="[['label' => 'Settings', 'href' => route('settings.profile')], ['label' => 'Profile']]">
    <div class="max-w-3xl space-y-6">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Settings</h1>
            <p class="mt-1 text-sm text-vault-text-subtle">Manage your account, security, and preferences.</p>
        </div>

        @include('settings._nav')

        <x-card title="Profile" description="Your name and email.">
            <form method="POST" action="{{ route('settings.profile.update') }}" class="space-y-4 max-w-md">
                @csrf
                @method('PATCH')

                <x-input name="name" label="Name" :value="$user->name" required />
                <x-input name="email" type="email" label="Email" :value="$user->email" required />

                <div class="pt-2">
                    <x-button type="submit" variant="primary" icon="check">Save</x-button>
                </div>
            </form>
        </x-card>

        <x-card title="Session" description="Where you're signed in from.">
            <dl class="text-sm space-y-2">
                <div class="flex justify-between gap-2">
                    <dt class="text-vault-text-subtle">Last login</dt>
                    <dd>{{ optional($user->last_login_at)->format('M j, Y · g:i A') ?? '—' }}</dd>
                </div>
                <div class="flex justify-between gap-2">
                    <dt class="text-vault-text-subtle">Last login IP</dt>
                    <dd class="font-mono">{{ $user->last_login_ip ?? '—' }}</dd>
                </div>
                <div class="flex justify-between gap-2">
                    <dt class="text-vault-text-subtle">Idle timeout</dt>
                    <dd>{{ config('vault.idle_timeout_seconds') / 60 }} minutes</dd>
                </div>
                <div class="flex justify-between gap-2">
                    <dt class="text-vault-text-subtle">Session lifetime</dt>
                    <dd>{{ config('session.lifetime') }} minutes</dd>
                </div>
            </dl>
        </x-card>
    </div>
</x-app-layout>
