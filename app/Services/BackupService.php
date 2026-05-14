<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Credential;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Backup export/import.
 *
 * The export is a JSON document containing **plaintext credential data**
 * (the whole point of an off-vault backup is portability), so the entire
 * blob is then encrypted with a user-supplied passphrase using OpenSSL
 * AES-256-GCM. Without the passphrase the backup is useless.
 *
 * Format: base64( IV[12] || TAG[16] || ciphertext )
 * KDF:    PBKDF2-SHA256, 200_000 iters, random salt[16] stored alongside.
 */
class BackupService
{
    private const KDF_ITERS = 200000;
    private const SALT_LEN = 16;
    private const IV_LEN = 12;

    public function __construct(private readonly EncryptionService $encryption)
    {
    }

    /**
     * Build the plaintext backup payload from the DB.
     *
     * @return array{version: string, generated_at: string, categories: array, credentials: array}
     */
    public function buildPayload(): array
    {
        $categories = Category::orderBy('sort_order')->get()->map(fn (Category $c) => [
            'name' => $c->name,
            'slug' => $c->slug,
            'icon' => $c->icon,
            'color' => $c->color,
            'description' => $c->description,
            'sort_order' => $c->sort_order,
        ])->all();

        $credentials = Credential::with('category')->get()->map(function (Credential $c) {
            return [
                'category' => $c->category?->slug,
                'title' => $c->title,
                'username' => $c->username,
                'email' => $c->email,
                'password' => $this->encryption->decryptOrNull($c->password_encrypted),
                'url' => $c->url,
                'notes' => $this->encryption->decryptOrNull($c->notes_encrypted),
                'custom_fields' => $this->encryption->decryptJson($c->custom_fields_encrypted),
                'is_favorite' => $c->is_favorite,
                'tags' => $c->tags,
                'created_at' => $c->created_at?->toIso8601String(),
                'updated_at' => $c->updated_at?->toIso8601String(),
            ];
        })->all();

        return [
            'version' => '1.0',
            'generated_at' => now()->toIso8601String(),
            'categories' => $categories,
            'credentials' => $credentials,
        ];
    }

    /**
     * Encrypt a payload with the user's passphrase. Returns a single base64 string.
     */
    public function encryptBackup(array $payload, string $passphrase): string
    {
        if (strlen($passphrase) < 12) {
            throw new RuntimeException('Backup passphrase must be at least 12 characters.');
        }

        $plaintext = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        $salt = random_bytes(self::SALT_LEN);
        $iv = random_bytes(self::IV_LEN);
        $key = hash_pbkdf2('sha256', $passphrase, $salt, self::KDF_ITERS, 32, true);

        $tag = '';
        $ciphertext = openssl_encrypt(
            $plaintext,
            'aes-256-gcm',
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
        );

        if ($ciphertext === false) {
            throw new RuntimeException('Backup encryption failed.');
        }

        // Wrap header (salt + iv + tag + ciphertext) so a single file is portable.
        return base64_encode(json_encode([
            'v' => 1,
            'kdf' => 'pbkdf2-sha256',
            'iters' => self::KDF_ITERS,
            'salt' => base64_encode($salt),
            'iv' => base64_encode($iv),
            'tag' => base64_encode($tag),
            'data' => base64_encode($ciphertext),
        ]));
    }

    /**
     * Decrypt a backup blob with the supplied passphrase. Throws RuntimeException
     * on tampered ciphertext or wrong passphrase.
     */
    public function decryptBackup(string $blob, string $passphrase): array
    {
        $decoded = json_decode(base64_decode($blob, true), true);
        if (! is_array($decoded) || ($decoded['v'] ?? null) !== 1) {
            throw new RuntimeException('Backup file format not recognized.');
        }

        $salt = base64_decode($decoded['salt'], true);
        $iv = base64_decode($decoded['iv'], true);
        $tag = base64_decode($decoded['tag'], true);
        $ciphertext = base64_decode($decoded['data'], true);

        $key = hash_pbkdf2('sha256', $passphrase, $salt, (int) $decoded['iters'], 32, true);

        $plaintext = openssl_decrypt(
            $ciphertext,
            'aes-256-gcm',
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
        );

        if ($plaintext === false) {
            throw new RuntimeException('Wrong passphrase or backup is corrupted.');
        }

        return json_decode($plaintext, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * Import a decrypted payload into the database. Uses a transaction so partial
     * imports don't leave the vault in a half-populated state.
     *
     * @return array{categories_imported: int, credentials_imported: int}
     */
    public function importPayload(array $payload): array
    {
        if (! isset($payload['credentials']) || ! is_array($payload['credentials'])) {
            throw new RuntimeException('Backup payload is missing credentials.');
        }

        return DB::transaction(function () use ($payload) {
            $catsImported = 0;
            foreach (($payload['categories'] ?? []) as $cat) {
                Category::firstOrCreate(
                    ['slug' => $cat['slug'] ?? Str::slug($cat['name'])],
                    [
                        'name' => $cat['name'],
                        'icon' => $cat['icon'] ?? 'folder',
                        'color' => $cat['color'] ?? '#6366F1',
                        'description' => $cat['description'] ?? null,
                        'sort_order' => $cat['sort_order'] ?? 0,
                    ],
                );
                $catsImported++;
            }

            $credsImported = 0;
            foreach ($payload['credentials'] as $cred) {
                $category = Category::where('slug', $cred['category'] ?? null)->first()
                    ?? Category::orderBy('id')->first();

                if (! $category) {
                    continue; // No categories at all — skip rather than fail.
                }

                Credential::create([
                    'category_id' => $category->id,
                    'title' => $cred['title'],
                    'username' => $cred['username'] ?? null,
                    'email' => $cred['email'] ?? null,
                    'password_encrypted' => $this->encryption->encrypt($cred['password'] ?? ''),
                    'url' => $cred['url'] ?? null,
                    'notes_encrypted' => $this->encryption->encrypt($cred['notes'] ?? null),
                    'custom_fields_encrypted' => $this->encryption->encryptJson($cred['custom_fields'] ?? null),
                    'is_favorite' => (bool) ($cred['is_favorite'] ?? false),
                    'tags' => $cred['tags'] ?? null,
                    'password_changed_at' => now(),
                ]);
                $credsImported++;
            }

            return [
                'categories_imported' => $catsImported,
                'credentials_imported' => $credsImported,
            ];
        });
    }
}
