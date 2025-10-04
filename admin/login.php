<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Shades of Beauty Admin Login</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700;800&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
            padding: 1rem;
        }

        /* Subtle background pattern */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 30%, rgba(102, 126, 234, 0.03) 0%, transparent 50%),
                radial-gradient(circle at 80% 70%, rgba(249, 168, 212, 0.03) 0%, transparent 50%),
                radial-gradient(circle at 50% 50%, rgba(118, 75, 162, 0.02) 0%, transparent 60%);
            z-index: 0;
        }

        /* Floating minimal particles */
        .particles {
            position: absolute;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 1;
        }

        .particle {
            position: absolute;
            background: rgba(102, 126, 234, 0.05);
            border-radius: 50%;
            animation: float 25s infinite;
        }

        .particle:nth-child(1) { width: 60px; height: 60px; left: 10%; animation-delay: 0s; }
        .particle:nth-child(2) { width: 40px; height: 40px; left: 30%; animation-delay: 3s; }
        .particle:nth-child(3) { width: 80px; height: 80px; left: 70%; animation-delay: 6s; }
        .particle:nth-child(4) { width: 50px; height: 50px; left: 90%; animation-delay: 2s; }

        @keyframes float {
            0% {
                transform: translateY(100vh) scale(0);
                opacity: 0;
            }
            50% {
                opacity: 0.6;
            }
            100% {
                transform: translateY(-100vh) scale(1);
                opacity: 0;
            }
        }

        .login-container {
            position: relative;
            z-index: 10;
            animation: fadeInScale 0.8s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        @keyframes fadeInScale {
            from {
                opacity: 0;
                transform: scale(0.95) translateY(20px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        .login-card {
            background: #ffffff;
            border-radius: 24px;
            padding: 3rem 2.5rem;
            box-shadow: 
                0 20px 60px rgba(0, 0, 0, 0.08),
                0 0 0 1px rgba(0, 0, 0, 0.05);
            width: 100%;
            max-width: 440px;
            position: relative;
            overflow: hidden;
        }

        /* Elegant top border */
        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2, #f093fb);
            background-size: 200% 100%;
            animation: borderGlow 3s ease infinite;
        }

        @keyframes borderGlow {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }

        /* Subtle corner decorations */
        .corner-deco {
            position: absolute;
            width: 100px;
            height: 100px;
            opacity: 0.03;
        }

        .corner-deco.top-left {
            top: 0;
            left: 0;
            background: radial-gradient(circle at top left, #667eea, transparent);
        }

        .corner-deco.bottom-right {
            bottom: 0;
            right: 0;
            background: radial-gradient(circle at bottom right, #f093fb, transparent);
        }

        .login-header {
            text-align: center;
            margin-bottom: 2.5rem;
            position: relative;
        }

        .logo-wrapper {
            margin-bottom: 1.5rem;
            position: relative;
            display: inline-block;
        }

        .logo-circle {
            width: 90px;
            height: 90px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.2);
            animation: logoFloat 3s ease-in-out infinite;
            position: relative;
        }

        @keyframes logoFloat {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }

        .logo-circle::before {
            content: '';
            position: absolute;
            inset: -2px;
            background: linear-gradient(45deg, #667eea, #764ba2, #f093fb);
            border-radius: 50%;
            z-index: -1;
            animation: logoRotate 4s linear infinite;
            opacity: 0.3;
        }

        @keyframes logoRotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .logo-icon {
            font-size: 2.8rem;
            color: #ffffff;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
        }

        .login-title {
            font-family: 'Playfair Display', serif;
            font-size: 2.2rem;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
            letter-spacing: 0.5px;
            line-height: 1.2;
        }

        .login-subtitle {
            color: #666;
            font-size: 0.85rem;
            font-weight: 400;
            letter-spacing: 3px;
            text-transform: uppercase;
            opacity: 0.7;
        }

        .form-group {
            margin-bottom: 1.2rem;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 600;
            font-size: 0.85rem;
            letter-spacing: 0.3px;
        }

        .input-wrapper {
            position: relative;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 0.75rem 0.75rem 2.8rem;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 0.875rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: #fafafa;
            font-family: 'Poppins', sans-serif;
            color: #1f2937;
        }

        .form-control::placeholder {
            color: #9ca3af;
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
            background: #ffffff;
            box-shadow: 
                0 0 0 4px rgba(102, 126, 234, 0.08),
                0 4px 12px rgba(102, 126, 234, 0.1);
            transform: translateY(-1px);
        }

        .input-icon {
            position: absolute;
            left: 0.9rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            transition: all 0.3s ease;
            z-index: 5;
            font-size: 0.9rem;
        }

        .form-control:focus + .input-icon {
            color: #667eea;
            transform: translateY(-50%) scale(1.1);
        }

        .password-toggle {
            position: absolute;
            right: 0.9rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 5;
            font-size: 0.9rem;
        }

        .password-toggle:hover {
            color: #667eea;
        }

        .login-btn {
            width: 100%;
            padding: 0.85rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff;
            border: none;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-top: 0.5rem;
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.25);
        }

        .login-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s ease;
        }

        .login-btn:hover::before {
            left: 100%;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 28px rgba(102, 126, 234, 0.35);
        }

        .login-btn:active {
            transform: translateY(0);
        }

        .login-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        .btn-content {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .alert {
            padding: 1rem 1.2rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            animation: slideIn 0.3s ease-out;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            font-size: 0.9rem;
        }

        .alert-danger {
            background: #fef2f2;
            border: 2px solid #fca5a5;
            color: #dc2626;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .forgot-password {
            text-align: center;
            margin-top: 1.5rem;
        }

        .forgot-password a {
            color: #667eea;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            letter-spacing: 0.3px;
            font-weight: 500;
        }

        .forgot-password a:hover {
            color: #764ba2;
            text-decoration: underline;
        }

        .spinner {
            display: inline-block;
            width: 18px;
            height: 18px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: #ffffff;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .hidden {
            display: none;
        }

        /* Remember me checkbox */
        .remember-me {
            display: flex;
            align-items: center;
            margin-bottom: 0.8rem;
        }

        .remember-me input[type="checkbox"] {
            width: 16px;
            height: 16px;
            margin-right: 0.5rem;
            cursor: pointer;
            accent-color: #667eea;
        }

        .remember-me label {
            font-size: 0.85rem;
            color: #666;
            cursor: pointer;
            user-select: none;
        }

        /* Mobile responsive */
        @media (max-width: 768px) {
            .login-card {
                padding: 2.5rem 2rem;
                border-radius: 20px;
            }

            .login-title {
                font-size: 1.8rem;
            }

            .logo-circle {
                width: 75px;
                height: 75px;
            }

            .logo-icon {
                font-size: 2.3rem;
            }

            .particle {
                display: none;
            }
        }

        /* Focus animation */
        .input-wrapper.focused .form-control {
            animation: focusPulse 0.6s ease;
        }

        @keyframes focusPulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.01); }
            100% { transform: scale(1); }
        }
    </style>
</head>

<body>
    <div class="particles">
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
    </div>

    <main id="main">
        <div class="login-container">
            <div class="login-card">
                <div class="corner-deco top-left"></div>
                <div class="corner-deco bottom-right"></div>
                
                <div class="login-header">
                    <!-- <div class="logo-wrapper">
                        <div class="logo-circle">
                            <i class="fas fa-spa logo-icon"></i>
                        </div>
                    </div> -->
                    <h1 class="login-title">Shades of Beauty</h1>
                    <p class="login-subtitle">Elegance • Luxury</p>
                </div>

                <form id="login-form">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <div class="input-wrapper">
                            <input type="text" id="username" name="username" class="form-control" placeholder="Enter your username" required autocomplete="username">
                            <i class="fas fa-user input-icon"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-wrapper">
                            <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password" required autocomplete="current-password">
                            <i class="fas fa-lock input-icon"></i>
                            <i class="fas fa-eye password-toggle" id="togglePassword"></i>
                        </div>
                    </div>

                    <div class="remember-me">
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember">Remember me</label>
                    </div>

                    <button type="submit" class="login-btn">
                        <div class="btn-content">
                            <span class="btn-text">Sign In</span>
                            <span class="spinner hidden"></span>
                        </div>
                    </button>

                    <div class="forgot-password">
                        <a href="#" onclick="showForgotPassword(); return false;">Forgot Password?</a>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script>
        // Password toggle functionality
        $('#togglePassword').click(function() {
            const passwordField = $('#password');
            const type = passwordField.attr('type') === 'password' ? 'text' : 'password';
            passwordField.attr('type', type);
            $(this).toggleClass('fa-eye fa-eye-slash');
        });

        // Form submission handler
        $('#login-form').submit(function(e) {
            e.preventDefault();
            
            const $btn = $(this).find('button[type="submit"]');
            const $btnText = $btn.find('.btn-text');
            const $spinner = $btn.find('.spinner');
            
            // Show loading state
            $btn.attr('disabled', true);
            $btnText.text('Signing in...');
            $spinner.removeClass('hidden');
            
            // Remove existing alerts
            $(this).find('.alert-danger').remove();

            // Actual AJAX implementation
            $.ajax({
                url: 'ajax.php?action=login',
                method: 'POST',
                data: $(this).serialize(),
                error: err => {
                    console.log(err);
                    $btn.removeAttr('disabled');
                    $btnText.text('Sign In');
                    $spinner.addClass('hidden');
                    
                    $('#login-form').prepend(`
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i>
                            <span>Connection error. Please try again.</span>
                        </div>
                    `);
                },
                success: function(resp) {
                    if(resp == 1) {
                        // Success animation
                        $btnText.html('<i class="fas fa-check"></i> Success!');
                        setTimeout(() => {
                            location.href = 'index.php';
                        }, 500);
                    } else if(resp == 2) {
                        location.href = 'inquiries.php';
                    } else {
                        $('#login-form').prepend(`
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle"></i>
                                <span>Username or password is incorrect.</span>
                            </div>
                        `);
                        $btn.removeAttr('disabled');
                        $btnText.text('Sign In');
                        $spinner.addClass('hidden');
                    }
                }
            });
        });

        // Input focus animations
        $('.form-control').on('focus', function() {
            $(this).parent().addClass('focused');
        }).on('blur', function() {
            $(this).parent().removeClass('focused');
        });

        // Show forgot password
        function showForgotPassword() {
            const alertHtml = `
                <div class="alert" style="background: #eff6ff; border: 2px solid #93c5fd; color: #1e40af; margin-top: 1rem;">
                    <i class="fas fa-info-circle"></i>
                    <span>Please contact your system administrator to reset your password.</span>
                </div>
            `;
            
            if($('#login-form .alert').length === 0) {
                $('#login-form').append(alertHtml);
                setTimeout(() => {
                    $('#login-form .alert').fadeOut(400, function() {
                        $(this).remove();
                    });
                }, 5000);
            }
        }

        // Auto-remove alerts after 6 seconds
        $(document).on('DOMNodeInserted', '.alert-danger', function() {
            setTimeout(() => {
                $(this).fadeOut(400, function() {
                    $(this).remove();
                });
            }, 6000);
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && (e.target.id === 'username' || e.target.id === 'password')) {
                e.preventDefault();
                $('#login-form').submit();
            }
        });

        // Subtle parallax effect
        document.addEventListener('mousemove', (e) => {
            const x = (e.clientX / window.innerWidth - 0.5) * 10;
            const y = (e.clientY / window.innerHeight - 0.5) * 10;
            
            document.querySelector('.login-card').style.transform = `translate(${x}px, ${y}px)`;
        });

        // Input auto-fill detection
        $('.form-control').on('input', function() {
            if($(this).val()) {
                $(this).parent().addClass('has-value');
            } else {
                $(this).parent().removeClass('has-value');
            }
        });
    </script>
</body>
</html>