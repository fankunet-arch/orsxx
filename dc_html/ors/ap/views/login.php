<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ORS 控制室 - 登录</title>
    <link rel="stylesheet" href="/ors/ap/css/admin.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-header">
            <h1>ORS</h1>
            <p>开业路线图系统</p>
            <span class="badge badge-primary">控制室</span>
        </div>

        <form id="loginForm" class="login-form">
            <?php echo ors_csrf_field(); ?>
            <div class="form-group">
                <label for="username">用户名</label>
                <input type="text" id="username" name="username" required autocomplete="username" autofocus>
            </div>

            <div class="form-group">
                <label for="password">密码</label>
                <input type="password" id="password" name="password" required autocomplete="current-password">
            </div>

            <div class="form-group checkbox-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="remember" id="remember" checked>
                    <span>记住我</span>
                </label>
            </div>

            <button type="submit" class="btn btn-primary btn-block">登录</button>
        </form>

        <div id="toast" class="toast"></div>
    </div>

    <script src="/ors/ap/js/admin.js"></script>
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
                    if (result.data.user.role !== 'admin') {
                        showToast('访问被拒绝，仅管理员可登录', 'error');
                        return;
                    }
                    showToast('登录成功！', 'success');
                    setTimeout(() => {
                        window.location.href = '/ors/ap/';
                    }, 500);
                } else {
                    showToast(result.message || '登录失败', 'error');
                }
            } catch (error) {
                showToast('网络错误，请重试', 'error');
            }
        });
    </script>
</body>
</html>
