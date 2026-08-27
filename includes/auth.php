<?php
/** ระบบผู้ใช้งานและสิทธิ์ */

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function user_role(): string
{
    return current_user()['role'] ?? 'guest';
}

/**
 * ลำดับสิทธิ์: viewer(1) < staff(2) < admin(3)
 * can('staff') = ต้องเป็น staff ขึ้นไป
 */
function can(string $minRole): bool
{
    $rank = ['guest' => 0, 'viewer' => 1, 'staff' => 2, 'admin' => 3];
    return ($rank[user_role()] ?? 0) >= ($rank[$minRole] ?? 99);
}

function require_login(): void
{
    if (!is_logged_in()) {
        redirect(BASE_URL . '/login.php');
    }
}

function require_role(string $minRole): void
{
    require_login();
    if (!can($minRole)) {
        http_response_code(403);
        include __DIR__ . '/../pages/_denied.php';
        exit;
    }
}

function attempt_login(string $username, string $password): bool
{
    $u = q1('SELECT * FROM users WHERE username = ? LIMIT 1', [$username]);
    if (!$u || !$u['is_active'] || !password_verify($password, $u['password_hash'])) {
        return false;
    }
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id'       => (int)$u['id'],
        'username' => $u['username'],
        'fullname' => $u['fullname'],
        'role'     => $u['role'],
    ];
    q('UPDATE users SET last_login_at = NOW() WHERE id = ?', [$u['id']]);
    return true;
}

function logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}
