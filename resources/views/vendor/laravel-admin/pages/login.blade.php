<style>
    /* 基础样式重置 */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    /* 背景样式 */
    .login-page {
        min-height: 100vh;
        background: linear-gradient(135deg, #143268 0%, #2c4a7a 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        position: relative;
        overflow: hidden;
    }

    /* 背景装饰 */
    .login-page::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(97, 193, 189, 0.1) 0%, transparent 70%);
        animation: float 30s ease-in-out infinite;
    }

    @keyframes float {
        0%, 100% { transform: translate(0, 0) rotate(0deg); }
        25% { transform: translate(20px, 20px) rotate(5deg); }
        50% { transform: translate(0, 40px) rotate(0deg); }
        75% { transform: translate(-20px, 20px) rotate(-5deg); }
    }

    /* 登录框容器 */
    .login-container {
        width: 100%;
        max-width: 1100px;
        display: flex;
        flex-wrap: wrap;
        gap: 30px;
        perspective: 1000px;
        position: relative;
        z-index: 1;
    }

    /* 登录卡片 */
    .login-card {
        background: linear-gradient(145deg, #ffffff 0%, #f0f7fa 100%);
        border-radius: 16px;
        box-shadow: 0 15px 35px rgba(0,0,0,0.15);
        overflow: hidden;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        flex: 1;
        min-width: 380px;
        max-width: 420px;
        border: 1px solid rgba(152, 201, 217, 0.3);
    }

    .login-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 25px 50px rgba(0,0,0,0.2);
        border-color: rgba(97, 193, 189, 0.4);
    }

    /* 介绍区域 */
    .introduction-section {
        flex: 1;
        min-width: 380px;
        max-width: 550px;
        color: white;
        display: flex;
        flex-direction: column;
        justify-content: center;
        animation: fadeIn 1s ease-in-out;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .system-name-large {
        font-size: 2.8rem;
        font-weight: 700;
        margin-bottom: 15px;
        letter-spacing: -1px;
        color: white;
        text-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    .system-slogan {
        font-size: 1.1rem;
        line-height: 1.6;
        margin-bottom: 30px;
        color: rgba(255, 255, 255, 0.95);
        font-weight: 300;
    }

    /* 区块介绍 */
    .features-container {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .feature-block {
        background: rgba(97, 193, 189, 0.1);
        backdrop-filter: blur(10px);
        padding: 20px;
        border-radius: 12px;
        transition: transform 0.3s ease, box-shadow 0.3s ease, background-color 0.3s ease;
        border: 1px solid rgba(152, 201, 217, 0.3);
    }

    .feature-block:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        background: rgba(97, 193, 189, 0.2);
    }

    .feature-title {
        font-size: 1.2rem;
        font-weight: 600;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 10px;
        color: white;
    }

    .feature-title::before {
        content: '✦';
        font-size: 1.2rem;
        color: #61c1bd;
    }

    .feature-description {
        font-size: 0.95rem;
        line-height: 1.5;
        color: rgba(255, 255, 255, 0.9);
    }

    /* 品牌区域 */
    .login-header {
        background: linear-gradient(45deg, #143268, #2c4a7a);
        color: white;
        padding: 30px;
        text-align: center;
        border-bottom: 3px solid #61c1bd;
    }

    .login-logo {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 5px;
        letter-spacing: -0.5px;
    }

    .login-subtitle {
        font-size: 0.95rem;
        opacity: 0.9;
        font-weight: 400;
    }

    /* 表单区域 */
    .login-body {
        padding: 30px;
    }

    .form-group {
        margin-bottom: 20px;
        position: relative;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #143268;
        font-size: 0.9rem;
    }

    /* 输入框样式 */
    .form-control {
        width: 100%;
        padding: 12px 40px 12px 15px;
        border: 2px solid #98c9d9;
        border-radius: 8px;
        font-size: 1rem;
        transition: all 0.3s ease;
        background-color: #ffffff;
        color: #143268;
    }

    .form-control:focus {
        outline: none;
        border-color: #61c1bd;
        background-color: white;
        box-shadow: 0 0 0 3px rgba(97, 193, 189, 0.2);
        transform: translateY(-1px);
    }

    .form-control.is-invalid {
        border-color: #e53e3e;
    }

    /* 图标样式 */
    .input-icon {
        position: absolute;
        right: 15px;
        top: 40px;
        color: #98c9d9;
        transition: color 0.3s ease;
    }

    .form-control:focus + .input-icon {
        color: #61c1bd;
    }

    /* 记住我选项 */
    .remember-section {
        display: flex;
        align-items: center;
        margin-bottom: 25px;
    }

    .remember-checkbox {
        display: flex;
        align-items: center;
        cursor: pointer;
        user-select: none;
    }

    .remember-checkbox input {
        position: absolute;
        opacity: 0;
    }

    .checkbox-custom {
        display: inline-block;
        width: 20px;
        height: 20px;
        border: 2px solid #e1e5e9;
        border-radius: 4px;
        margin-right: 10px;
        position: relative;
        transition: all 0.3s ease;
    }

    .remember-checkbox input:checked + .checkbox-custom {
        background-color: #61c1bd;
        border-color: #61c1bd;
    }

    .remember-checkbox input:checked + .checkbox-custom:after {
        content: '✓';
        position: absolute;
        color: white;
        font-size: 12px;
        font-weight: bold;
        left: 50%;
        top: 50%;
        transform: translate(-50%, -50%);
    }

    .remember-text {
        font-size: 0.9rem;
        color: #2c4a7a;
    }

    /* 提交按钮 */
    .login-button {
        width: 100%;
        padding: 12px 20px;
        background: linear-gradient(45deg, #61c1bd, #50a8a4);
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        position: relative;
        overflow: hidden;
    }

    .login-button:hover {
        background: linear-gradient(45deg, #50a8a4, #40908d);
        transform: translateY(-1px);
        box-shadow: 0 10px 20px rgba(97, 193, 189, 0.3);
    }

    .login-button:active {
        transform: translateY(0);
    }

    .login-button::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 5px;
        height: 5px;
        background: rgba(255, 255, 255, 0.5);
        opacity: 0;
        border-radius: 100%;
        transform: scale(1, 1) translate(-50%, -50%);
        transform-origin: 50% 50%;
    }

    .login-button:focus:not(:active)::after {
        animation: ripple 0.6s ease-out;
    }

    @keyframes ripple {
        0% {
            transform: scale(0, 0);
            opacity: 0.5;
        }
        20% {
            transform: scale(25, 25);
            opacity: 0.3;
        }
        100% {
            opacity: 0;
            transform: scale(40, 40);
        }
    }

    /* 错误提示 */
    .error-message {
        margin-top: 5px;
        color: #e53e3e;
        font-size: 0.85rem;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    /* 响应式设计 */
    @media (max-width: 992px) {
        .login-container {
            flex-direction: column;
            max-width: 420px;
        }

        .introduction-section {
            order: 1;
        }

        .login-card {
            order: 2;
        }

        .system-name-large {
            font-size: 2.2rem;
        }
    }

    @media (max-width: 480px) {
        .login-card,
        .introduction-section {
            min-width: 100%;
        }

        .login-card {
            border-radius: 12px;
        }

        .login-header,
        .login-body {
            padding: 25px 20px;
        }

        .login-logo,
        .system-name-large {
            font-size: 1.8rem;
        }

        .feature-block {
            padding: 15px;
        }

        .system-slogan {
            font-size: 1rem;
        }
    }

    /* 加载动画 */
    .loading-spinner {
        width: 16px;
        height: 16px;
        border: 2px solid rgba(255, 255, 255, 0.3);
        border-radius: 50%;
        border-top-color: white;
        animation: spin 1s ease-in-out infinite;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }
</style>

<div class="login-page">
    <div class="login-container">
        <!-- 左侧介绍区域 -->
        <div class="introduction-section">
            <h1 class="system-name-large">时尚星约 ERP</h1>
            <p class="system-slogan">核心价值，为企业经营赋能，实现降本增效，帮助企业从销售、库存、采购再到资金收付全链路管理</p>

            <div class="features-container">
                <div class="feature-block">
                    <h3 class="feature-title">精细化商品管理</h3>
                    <p class="feature-description">支持多种商品特性管理，灵活适应各大主流行业的需求，实现差异化的品类管理与分析，使管理更加专注高效</p>
                </div>

                <div class="feature-block">
                    <h3 class="feature-title">高效的订单管理</h3>
                    <p class="feature-description">实现订单全流程数字化跟踪，有效降低库存风险与成本，提升团队协作效率</p>
                </div>

                <div class="feature-block">
                    <h3 class="feature-title">智能库存档案与分析</h3>
                    <p class="feature-description">为您企业库存结存数据，预警库存数据，及时发现企业经营风险</p>
                </div>
            </div>
        </div>

        <!-- 右侧登录卡片 -->
        <div class="login-card">
            <!-- 品牌头部 -->
            <div class="login-header">
                <div class="login-logo">时尚星约 ERP</div>
                <div class="login-subtitle">企业数据管理解决方案</div>
            </div>

            <!-- 表单区域 -->
            <div class="login-body">
                <form id="login-form" method="POST" action="{{ admin_url('auth/login') }}">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}"/>

                    <!-- 用户名输入 -->
                    <div class="form-group">
                        <label for="username">{{ trans('admin.username') }}</label>
                        <input type="text" id="username" name="username" class="form-control" placeholder="请输入用户名" autofocus>
                        <span class="input-icon">👤</span>
                    </div>

                    <!-- 密码输入 -->
                    <div class="form-group">
                        <label for="password">{{ trans('admin.password') }}</label>
                        <input type="password" id="password" name="password" class="form-control" placeholder="请输入密码">
                        <span class="input-icon">🔒</span>
                    </div>

                    <!-- 记住我选项 -->
                    <div class="remember-section">
                        <label class="remember-checkbox">
                            <input id="remember" name="remember" value="1" type="checkbox" {{ old('remember') ? 'checked' : '' }}>
                            <span class="checkbox-custom"></span>
                            <span class="remember-text">{{ trans('admin.remember_me') }}</span>
                        </label>
                    </div>

                    <!-- 登录按钮 -->
                    @php
                        $loginError = $errors->first('username') ?: $errors->first('password') ?: $errors->first();
                    @endphp
                    <div class="error-message" id="error-message" style="{{ $loginError ? 'display: flex;' : 'display: none;' }}">
                        <span>⚠️</span>
                        <span id="error-text">{{ $loginError }}</span>
                    </div>
                    <button type="submit" class="login-button" id="login-button">
                        <span id="button-text">登录</span>
                        <span class="loading-spinner" style="display: none;"></span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // 输入框交互效果
    document.querySelectorAll('.form-control').forEach(input => {
        input.addEventListener('focus', () => {
            input.parentElement.classList.add('input-focus');
        });

        input.addEventListener('blur', () => {
            input.parentElement.classList.remove('input-focus');
        });
    });

    let bypassAjaxSubmit = false;

    // 表单提交处理
    document.getElementById('login-form').addEventListener('submit', function(e) {
        if (bypassAjaxSubmit) {
            return;
        }

        e.preventDefault();

        const username = document.getElementById('username').value.trim();
        const password = document.getElementById('password').value.trim();
        const remember = document.querySelector('input[name="remember"]').checked ? 1 : 0;
        const errorMessage = document.getElementById('error-message');
        const errorText = document.getElementById('error-text');
        const loginButton = document.getElementById('login-button');
        const buttonText = document.getElementById('button-text');
        const loadingSpinner = document.querySelector('.loading-spinner');

        // 重置错误信息
        errorMessage.style.display = 'none';

        // 基本验证
        if (!username || !password) {
            errorText.textContent = '请输入用户名和密码';
            errorMessage.style.display = 'flex';
            return;
        }

        // 显示加载状态
        buttonText.textContent = '登录中';
        loadingSpinner.style.display = 'inline-block';
        loginButton.disabled = true;

        // 创建表单数据（包含隐藏的 _token）
        const formData = new FormData(this);
        formData.set('username', username);
        formData.set('password', password);
        formData.set('remember', remember);

        const formAction = this.getAttribute('action') || window.location.href;

        // 提交表单（按 Dcat Ajax 规范，失败返回 422 JSON）
        fetch(formAction, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
            .then(async response => {
                const contentType = response.headers.get('content-type') || '';

                if (contentType.includes('application/json')) {
                    const data = await response.json();
                    return { response, data };
                }

                // 如果服务端没有返回 JSON，回退为普通提交
                bypassAjaxSubmit = true;
                this.submit();
                return null;
            })
            .then(result => {
                if (!result) return;

                const { response, data } = result;
                // 恢复按钮状态
                buttonText.textContent = '登录';
                loadingSpinner.style.display = 'none';
                loginButton.disabled = false;

                if (response.status === 422 && data && data.errors) {
                    const pickFirstError = (errors) => {
                        if (!errors) return null;
                        if (typeof errors === 'string') return errors;
                        if (Array.isArray(errors)) return errors[0];
                        if (typeof errors === 'object') {
                            for (const key of Object.keys(errors)) {
                                const v = errors[key];
                                const msg = pickFirstError(v);
                                if (msg) return msg;
                            }
                        }
                        return null;
                    };

                    const firstError =
                        pickFirstError(data.errors.username) ||
                        pickFirstError(data.errors.password) ||
                        pickFirstError(data.errors) ||
                        '登录失败，请检查用户名和密码';

                    errorText.textContent = firstError;
                    errorMessage.style.display = 'flex';

                    const loginCard = document.querySelector('.login-card');
                    loginCard.classList.add('login-error');
                    setTimeout(() => {
                        loginCard.classList.remove('login-error');
                    }, 500);

                    return;
                }

                if (data && data.status) {
                    // 登录成功，添加成功动画
                    const loginCard = document.querySelector('.login-card');
                    loginCard.classList.add('login-success');

                    // 延迟后跳转到首页
                    setTimeout(() => {
                        window.location.href = data.redirect || '/admin';
                    }, 500);
                } else {
                    // 登录失败，显示错误信息并添加抖动动画
                    errorText.textContent = (data && data.message) || '登录失败，请检查用户名和密码';
                    errorMessage.style.display = 'flex';

                    const loginCard = document.querySelector('.login-card');
                    loginCard.classList.add('login-error');
                    setTimeout(() => {
                        loginCard.classList.remove('login-error');
                    }, 500);
                }
            })
            .catch(error => {
                // 恢复按钮状态
                buttonText.textContent = '登录';
                loadingSpinner.style.display = 'none';
                loginButton.disabled = false;

                // 显示错误信息
                errorText.textContent = '网络错误，请稍后再试';
                errorMessage.style.display = 'flex';

                console.error('Login error:', error);
            });
    });

    // 增加输入框回车键支持
    document.getElementById('password').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            document.getElementById('login-button').click();
        }
    });

    // 添加额外的CSS动画样式
    const style = document.createElement('style');
    style.textContent = `
            /* 输入框聚焦效果 */
            .input-focus .form-control {
                border-color: #61c1bd;
                box-shadow: 0 0 0 3px rgba(97, 193, 189, 0.2);
            }

            /* 登录错误时的抖动动画 */
            @keyframes shake {
                0%, 100% { transform: translateX(0); }
                10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
                20%, 40%, 60%, 80% { transform: translateX(5px); }
            }

            .login-error {
                animation: shake 0.5s ease-in-out;
                box-shadow: 0 0 0 2px rgba(229, 62, 62, 0.3);
            }

            /* 登录成功时的缩放动画 */
            @keyframes successScale {
                0% { transform: scale(1); }
                50% { transform: scale(1.02); }
                100% { transform: scale(1); }
            }

            .login-success {
                animation: successScale 0.3s ease-in-out;
            }

            /* 区块介绍的hover效果增强 */
            .feature-block:hover .feature-title {
                transform: translateX(5px);
                transition: transform 0.3s ease;
                color: #61c1bd;
            }

            /* 平滑过渡动画 */
            .feature-title, .feature-description {
                transition: color 0.3s ease;
            }

            /* 背景装饰增强 */
            @keyframes float {
                0%, 100% { transform: translate(0, 0) rotate(0deg); }
                25% { transform: translate(20px, 20px) rotate(3deg); }
                50% { transform: translate(0, 40px) rotate(0deg); }
                75% { transform: translate(-20px, 20px) rotate(-3deg); }
            }

            /* 按钮波纹效果颜色调整 */
            .login-button::after {
                content: '';
                position: absolute;
                top: 50%;
                left: 50%;
                width: 5px;
                height: 5px;
                background: rgba(255, 255, 255, 0.7);
                opacity: 0;
                border-radius: 100%;
                transform: scale(1, 1) translate(-50%, -50%);
                transform-origin: 50% 50%;
            }

            /* 平滑滚动效果 */
            html {
                scroll-behavior: smooth;
            }

            /* 输入框placeholder样式 */
            ::placeholder {
                color: #98c9d9;
                opacity: 1;
            }

            :-ms-input-placeholder {
                color: #98c9d9;
            }

            ::-ms-input-placeholder {
                color: #98c9d9;
            }
        `;
    document.head.appendChild(style);
</script>
