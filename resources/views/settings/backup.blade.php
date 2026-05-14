<x-app-layout :title="'Backup'" :breadcrumbs="[['label' => 'Settings', 'href' => route('settings.profile')], ['label' => 'Backup']]">
    <div class="max-w-3xl space-y-6">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Backup</h1>
            <p class="mt-1 text-sm text-vault-text-subtle">Export an encrypted snapshot of your vault. Import to restore.</p>
        </div>

        @include('settings._nav')

        {{-- Important warning --}}
        <div class="card p-4 border-vault-warning/30 bg-vault-warning/5">
            <div class="flex items-start gap-3">
                <x-icon name="alert-triangle" class="w-5 h-5 text-vault-warning mt-0.5 shrink-0" />
                <div class="text-sm">
                    <p class="font-medium text-vault-warning">About backup encryption</p>
                    <p class="mt-1 text-vault-text-subtle">
                        Backups are encrypted with a passphrase you choose, using AES-256-GCM with PBKDF2-SHA256 (200,000 iterations).
                        <strong class="text-vault-text-light dark:text-vault-text">If you lose the passphrase, the backup is unrecoverable.</strong>
                        Backups also do NOT include 2FA secrets — you'll need to reconfigure 2FA after a restore.
                    </p>
                </div>
            </div>
        </div>

        <x-card title="Export" description="Download an encrypted .svault file containing all credentials and categories.">
            <form method="POST" action="{{ route('settings.backup.export') }}" class="space-y-4 max-w-md">
                @csrf

                <x-input
                    name="passphrase"
                    type="password"
                    label="Backup passphrase"
                    hint="At least 12 characters. Used to decrypt the file later — store it safely."
                    required
                />
                <x-input
                    name="passphrase_confirmation"
                    type="password"
                    label="Confirm passphrase"
                    required
                />
                <x-input
                    name="current_password"
                    type="password"
                    label="Your master password"
                    hint="Required to authorize the export."
                    autocomplete="current-password"
                    required
                />

                <div class="pt-2">
                    <x-button type="submit" variant="primary" icon="download">Export encrypted backup</x-button>
                </div>
            </form>
        </x-card>

        <x-card title="Import" description="Upload a previously exported .svault file. Imports add to existing data (no overwrite).">
            <form method="POST" action="{{ route('settings.backup.import') }}" enctype="multipart/form-data" class="space-y-4 max-w-md">
                @csrf

                <div>
                    <label class="block text-sm font-medium mb-1.5">Backup file</label>
                    <input
                        type="file"
                        name="backup_file"
                        accept=".svault,.txt,application/octet-stream"
                        required
                        class="block w-full text-sm
                               file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0
                               file:text-sm file:font-medium file:bg-vault-accent-soft file:text-vault-accent
                               hover:file:bg-vault-accent/15
                               text-vault-text-subtle"
                    />
                </div>

                <x-input
                    name="passphrase"
                    type="password"
                    label="Backup passphrase"
                    required
                />
                <x-input
                    name="current_password"
                    type="password"
                    label="Your master password"
                    autocomplete="current-password"
                    required
                />

                <div class="pt-2">
                    <x-button type="submit" variant="secondary" icon="upload">Import backup</x-button>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>
