<?php

require_once __DIR__ . '/includes/bootstrap.php';

try {
    db()->query('SELECT 1 FROM users LIMIT 1');
} catch (PDOException $e) {
    redirect(BASE_URL . '/install.php');
}

if (is_logged_in()) {
    redirect(url('dashboard'));
}

$error = '';

if (is_post()) {

    csrf_check();

    $user = (string) inp('username');
    $pass = (string) ($_POST['password'] ?? '');

    if ($user === '' || $pass === '') {

        $error = 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน';

    } elseif (attempt_login($user, $pass)) {

        $me = current_user();
        flash('success',
            'ยินดีต้อนรับ <b>' . h($me['fullname']) . '</b> · สิทธิ์' . h(role_label($me['role'])),
            'เข้าสู่ระบบสำเร็จ');
        redirect(url('dashboard'));

    } else {

        $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง หรือบัญชีถูกปิดใช้งาน';

        // หน่วงเวลาเล็กน้อย ป้องกันการสุ่มรหัสผ่าน
        usleep(400000);
    }
}

?>
<!doctype html>
<html lang="th">

<head>

    <meta charset="utf-8">

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>เข้าสู่ระบบ · <?= h(APP_NAME) ?></title>

    <!-- ========================================= -->
    <!-- Favicon : Icon บน Tab Browser -->
    <!-- ========================================= -->

    <link
        rel="icon"
        type="image/png"
        href="<?= h(BASE_URL) ?>/assets/favicon.png"
    >

    <!-- สำหรับ iPhone / iPad -->
    <link
        rel="apple-touch-icon"
        href="<?= h(BASE_URL) ?>/assets/favicon.png"
    >

    <!-- ========================================= -->
    <!-- Google Fonts -->
    <!-- ========================================= -->

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&family=Cormorant+Garamond:wght@500;600;700&display=swap"
    >

    <!-- ========================================= -->
    <!-- Main CSS -->
    <!-- ========================================= -->

    <link
        rel="stylesheet"
        href="<?= h(BASE_URL) ?>/assets/style.css?v=<?= h(APP_VERSION) ?>"
    >

    <!-- ========================================= -->
    <!-- Main JS : ใช้กล่องแจ้งเตือนร่วมกับหน้าอื่น -->
    <!-- ========================================= -->

    <script src="<?= h(BASE_URL) ?>/assets/app.js?v=<?= h(APP_VERSION) ?>"></script>

</head>


<body class="loginpage">


<div class="loginbox">


    <!-- ========================================= -->
    <!-- Login Header -->
    <!-- ========================================= -->

    <div class="loginhead">

        <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="1.6"
            stroke-linecap="round"
            stroke-linejoin="round"
        >

            <path d="m3 8 9-5 9 5v8l-9 5-9-5z"/>

            <path d="m3 8 9 5 9-5"/>

            <path d="M12 13v8"/>

        </svg>


        <h1>
            <?= h(APP_NAME) ?>
        </h1>


        <p>
            เข้าสู่ระบบเพื่อจัดการสต๊อกและคลังสินค้า
        </p>

    </div>


    <!-- ========================================= -->
    <!-- Error Message -->
    <!-- ========================================= -->

    <?php if ($error): ?>

        <div
            class="flashseed"
            data-type="danger"
            data-title="เข้าสู่ระบบไม่สำเร็จ"
        ><?= h($error) ?></div>

        <noscript>
            <div class="alert danger"><?= h($error) ?></div>
        </noscript>

    <?php endif; ?>


    <!-- ========================================= -->
    <!-- Login Form -->
    <!-- ========================================= -->

    <form
        method="post"
        autocomplete="off"
    >

        <?= csrf_field() ?>


        <label>

            ชื่อผู้ใช้

            <input
                type="text"
                name="username"
                value="<?= h(inp('username')) ?>"
                required
                autofocus
            >

        </label>


        <label>

            รหัสผ่าน

            <input
                type="password"
                name="password"
                required
            >

        </label>


        <button
            class="btn primary block"
            type="submit"
        >

            เข้าสู่ระบบ

        </button>


    </form>


    <!-- ========================================= -->
    <!-- Install Link -->
    <!-- ========================================= -->

    <p class="hint">

        ยังไม่ได้ติดตั้ง?

        <a href="<?= h(BASE_URL) ?>/install.php">
            เริ่มติดตั้งระบบ
        </a>

    </p>


</div>


</body>

</html>