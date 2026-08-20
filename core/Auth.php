<?php
declare(strict_types=1);

namespace Core;

final class Auth
{
    private const MAX_ATTEMPTS   = 5;
    private const WINDOW_SECONDS = 900; // 15 minutes
    private const REMEMBER_DAYS  = 30;

    private static ?array $user = null;

    public static function user(): ?array
    {
        if (self::$user !== null) {
            return self::$user;
        }
        $id = (int)Session::get('user_id', 0);
        if ($id === 0) {
            $id = self::userIdFromRememberCookie();
        }
        if ($id === 0) {
            return null;
        }
        $row = DB::first('SELECT * FROM users WHERE id = ? AND is_active = 1', [$id]);
        return self::$user = $row;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function id(): int
    {
        return (int)(self::user()['id'] ?? 0);
    }

    public static function role(): string
    {
        return (string)(self::user()['role'] ?? 'guest');
    }

    /** Role capability matrix. */
    public static function can(string $ability): bool
    {
        $role = self::role();
        if ($role === 'admin') {
            return true;
        }
        if ($role === 'editor') {
            return !in_array($ability, ['manage_users', 'manage_tools', 'view_activity'], true);
        }
        if ($role === 'viewer') {
            return in_array($ability, ['view_dashboard', 'view_enquiries'], true);
        }
        return false;
    }

    public static function requireLogin(): void
    {
        if (self::check()) {
            return;
        }
        $path = Router::currentPath();
        Session::set('intended', $path);
        redirect('/login');
    }

    public static function requireAbility(string $ability): void
    {
        self::requireLogin();
        if (!self::can($ability)) {
            http_response_code(403);
            echo View::render('admin/403');
            exit;
        }
    }

    public static function throttled(string $email): bool
    {
        $since = gmdate('Y-m-d H:i:s', time() - self::WINDOW_SECONDS);
        $count = (int)DB::value(
            'SELECT COUNT(*) FROM login_attempts
             WHERE succeeded = 0 AND created_at > ? AND identifier IN (?, ?)',
            [$since, strtolower($email), ip_hash()]
        );
        return $count >= self::MAX_ATTEMPTS;
    }

    public static function recordAttempt(string $email, bool $ok): void
    {
        foreach ([strtolower($email), ip_hash()] as $identifier) {
            DB::insert('login_attempts', [
                'identifier' => $identifier,
                'succeeded'  => $ok ? 1 : 0,
                'created_at' => now(),
            ]);
        }
        if ($ok) {
            DB::delete('login_attempts', 'identifier IN (?, ?) AND succeeded = 0', [strtolower($email), ip_hash()]);
        }
        // Keep the table small on shared hosting.
        DB::delete('login_attempts', "created_at < ?", [gmdate('Y-m-d H:i:s', time() - 86400)]);
    }

    public static function attempt(string $email, string $password, bool $remember = false): ?array
    {
        $email = strtolower(trim($email));
        if (self::throttled($email)) {
            Activity::log(null, 'auth.throttled', 'user', null, ['email' => $email]);
            return null;
        }
        $user = DB::first('SELECT * FROM users WHERE lower(email) = ? AND is_active = 1', [$email]);
        if (!$user || !password_verify($password, (string)$user['password_hash'])) {
            self::recordAttempt($email, false);
            return null;
        }
        if (password_needs_rehash((string)$user['password_hash'], PASSWORD_DEFAULT)) {
            DB::update('users', ['password_hash' => password_hash($password, PASSWORD_DEFAULT)], 'id = :id', ['id' => $user['id']]);
        }
        self::recordAttempt($email, true);
        self::login($user, $remember);
        return $user;
    }

    public static function login(array $user, bool $remember = false): void
    {
        Session::regenerate();
        Csrf::rotate();
        Session::set('user_id', (int)$user['id']);
        self::$user = $user;
        DB::update('users', ['last_login_at' => now()], 'id = :id', ['id' => $user['id']]);
        if ($remember) {
            self::issueRememberToken((int)$user['id']);
        }
        Activity::log((int)$user['id'], 'auth.login', 'user', (int)$user['id']);
    }

    public static function logout(): void
    {
        $id = self::id();
        self::clearRememberCookie();
        if ($id) {
            Activity::log($id, 'auth.logout', 'user', $id);
        }
        Session::destroy();
        self::$user = null;
    }

    // ---------- remember me: rotating selector / verifier ----------

    private static function issueRememberToken(int $userId): void
    {
        $selector = bin2hex(random_bytes(9));
        $verifier = bin2hex(random_bytes(32));
        $expires  = gmdate('Y-m-d H:i:s', time() + self::REMEMBER_DAYS * 86400);
        DB::insert('auth_tokens', [
            'id'            => null,
            'user_id'       => $userId,
            'selector'      => $selector,
            'verifier_hash' => hash('sha256', $verifier),
            'purpose'       => 'remember',
            'expires_at'    => $expires,
            'created_at'    => now(),
        ]);
        setcookie('eden_remember', $selector . ':' . $verifier, [
            'expires'  => time() + self::REMEMBER_DAYS * 86400,
            'path'     => '/',
            'secure'   => Session::isHttps(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    private static function userIdFromRememberCookie(): int
    {
        $raw = (string)($_COOKIE['eden_remember'] ?? '');
        if (!str_contains($raw, ':')) {
            return 0;
        }
        [$selector, $verifier] = explode(':', $raw, 2);
        $token = DB::first(
            "SELECT * FROM auth_tokens WHERE selector = ? AND purpose = 'remember' AND expires_at > ?",
            [$selector, now()]
        );
        if (!$token || !hash_equals((string)$token['verifier_hash'], hash('sha256', $verifier))) {
            self::clearRememberCookie();
            return 0;
        }
        // Rotate on use.
        DB::delete('auth_tokens', 'id = ?', [$token['id']]);
        $userId = (int)$token['user_id'];
        Session::set('user_id', $userId);
        self::issueRememberToken($userId);
        return $userId;
    }

    public static function clearRememberCookie(): void
    {
        $raw = (string)($_COOKIE['eden_remember'] ?? '');
        if (str_contains($raw, ':')) {
            DB::delete('auth_tokens', 'selector = ?', [explode(':', $raw, 2)[0]]);
        }
        setcookie('eden_remember', '', ['expires' => time() - 3600, 'path' => '/']);
    }

    // ---------- password reset ----------

    public static function createResetToken(int $userId): string
    {
        DB::delete('auth_tokens', "user_id = ? AND purpose = 'reset'", [$userId]);
        $selector = bin2hex(random_bytes(9));
        $verifier = bin2hex(random_bytes(32));
        DB::insert('auth_tokens', [
            'id'            => null,
            'user_id'       => $userId,
            'selector'      => $selector,
            'verifier_hash' => hash('sha256', $verifier),
            'purpose'       => 'reset',
            'expires_at'    => gmdate('Y-m-d H:i:s', time() + 3600),
            'created_at'    => now(),
        ]);
        return $selector . ':' . $verifier;
    }

    public static function consumeResetToken(string $raw): ?array
    {
        if (!str_contains($raw, ':')) {
            return null;
        }
        [$selector, $verifier] = explode(':', $raw, 2);
        $token = DB::first(
            "SELECT * FROM auth_tokens WHERE selector = ? AND purpose = 'reset' AND expires_at > ?",
            [$selector, now()]
        );
        if (!$token || !hash_equals((string)$token['verifier_hash'], hash('sha256', $verifier))) {
            return null;
        }
        return DB::first('SELECT * FROM users WHERE id = ?', [$token['user_id']]);
    }

    public static function clearResetTokens(int $userId): void
    {
        DB::delete('auth_tokens', "user_id = ? AND purpose = 'reset'", [$userId]);
    }

    public static function setPassword(int $userId, string $password): void
    {
        DB::update('users', [
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'must_reset'    => 0,
        ], 'id = :id', ['id' => $userId]);
        self::$user = null;
    }

    /** Minimum policy: 10 chars. Returns an error string or null. */
    public static function passwordProblem(string $password, string $confirm): ?string
    {
        if (strlen($password) < 10) {
            return 'Password must be at least 10 characters.';
        }
        if ($password !== $confirm) {
            return 'The two passwords do not match.';
        }
        return null;
    }
}
