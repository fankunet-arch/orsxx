<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>ORS Capture - Login</title>
    <link rel="stylesheet" href="/ors/css/mobile.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-header">
            <h1>ORS</h1>
            <p>Opening Roadmap System</p>
            <span class="badge">Capture</span>
        </div>

        <form id="loginForm" class="login-form">
            <?php echo ors_csrf_field(); ?>
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autocomplete="username" autofocus>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required autocomplete="current-password">
            </div>

            <div class="form-group checkbox-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="remember" id="remember" checked>
                    <span>Remember me</span>
                </label>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Login</button>
        </form>

        <div id="toast" class="toast"></div>
    </div>

    <script src="/ors/js/mobile.js"></script>
    <script>
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const data = {
                username: formData.get('username'),
                password: formData.get('password'),
                remember: formData.get('remember') === 'on'
            };

            try {
                const response = await fetch('/ors/api/auth.php?action=login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.success) {
                    showToast('Login successful!', 'success');
                    setTimeout(() => {
                        window.location.href = '/ors/';
                    }, 500);
                } else {
                    showToast(result.message || 'Login failed', 'error');
                }
            } catch (error) {
                showToast('Network error, please try again', 'error');
            }
        });
    </script>
</body>
</html>
