<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Fashion Admin Login</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            height: 100vh;
            background: linear-gradient(135deg, #1a1a1a 0%, #2d1b2e 25%, #1a1a1a 50%, #2e2420 75%, #1a1a1a 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        /* Animated fashion elements */
        .fashion-bg {
            position: absolute;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 1;
        }

        .fashion-element {
            position: absolute;
            opacity: 0.04;
            animation: fashionFloat 25s ease-in-out infinite;
            color: #d4af37;
        }

        .fashion-element:nth-child(1) { 
            font-size: 12rem; 
            left: -10%; 
            top: 10%; 
            animation-delay: 0s; 
        }
        .fashion-element:nth-child(2) { 
            font-size: 8rem; 
            right: -5%; 
            top: 60%; 
            animation-delay: 8s; 
        }
        .fashion-element:nth-child(3) { 
            font-size: 10rem; 
            left: 5%; 
            bottom: -10%; 
            animation-delay: 16s; 
        }

        @keyframes fashionFloat {
            0%, 100% { transform: translateY(0) rotate(0deg) scale(1); opacity: 0.04; }
            25% { transform: translateY(-50px) rotate(10deg) scale(1.1); opacity: 0.06; }
            50% { transform: translateY(0) rotate(-5deg) scale(0.9); opacity: 0.05; }
            75% { transform: translateY(30px) rotate(8deg) scale(1.05); opacity: 0.07; }
        }

        /* Geometric shapes for fashion aesthetic */
        .geometric-shape {
            position: absolute;
            border: 1px solid rgba(212, 175, 55, 0.1);
            z-index: 1;
        }

        .shape-1 {
            width: 200px;
            height: 200px;
            top: 15%;
            left: 10%;
            transform: rotate(45deg);
            animation: rotateShape 30s linear infinite;
        }

        .shape-2 {
            width: 150px;
            height: 150px;
            bottom: 20%;
            right: 15%;
            border-radius: 50%;
            animation: pulseShape 20s ease-in-out infinite;
        }

        @keyframes rotateShape {
            0% { transform: rotate(45deg); }
            100% { transform: rotate(405deg); }
        }

        @keyframes pulseShape {
            0%, 100% { transform: scale(1); opacity: 0.1; }
            50% { transform: scale(1.2); opacity: 0.15; }
        }

        .login-container {
            position: relative;
            z-index: 10;
            animation: fadeInUp 1.2s ease-out;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(30px);
            border-radius: 32px;
            padding: 4rem;
            box-shadow: 
                0 50px 100px rgba(0, 0, 0, 0.25),
                0 0 0 1px rgba(212, 175, 55, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.9);
            width: 100%;
            max-width: 480px;
            position: relative;
            overflow: hidden;
        }

        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, #d4af37, #f4e4bc, #d4af37);
            background-size: 200% 100%;
            animation: shimmer 4s ease-in-out infinite;
        }

        @keyframes shimmer {
            0%, 100% { background-position: -100% 0; }
            50% { background-position: 100% 0; }
        }

        .login-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .brand-icon {
            font-size: 4rem;
            color: #d4af37;
            margin-bottom: 1.5rem;
            animation: iconPulse 3s ease-in-out infinite;
        }

        @keyframes iconPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .login-title {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 0.5rem;
            letter-spacing: 1px;
        }

        .login-subtitle {
            color: #666;
            font-size: 1rem;
            font-weight: 300;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .form-group {
            margin-bottom: 2rem;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.8rem;
            color: #333;
            font-weight: 500;
            font-size: 0.95rem;
            letter-spacing: 0.5px;
        }

        .input-wrapper {
            position: relative;
        }

        .form-control {
            width: 100%;
            padding: 1.2rem 1.2rem 1.2rem 3.5rem;
            border: 2px solid #e8e8e8;
            border-radius: 16px;
            font-size: 1rem;
            transition: all 0.4s ease;
            background: rgba(248, 248, 248, 0.8);
            backdrop-filter: blur(10px);
            font-family: 'Inter', sans-serif;
        }

        .form-control:focus {
            outline: none;
            border-color: #d4af37;
            box-shadow: 0 0 0 4px rgba(212, 175, 55, 0.1);
            background: rgba(255, 255, 255, 1);
            transform: translateY(-2px);
        }

        .input-icon {
            position: absolute;
            left: 1.2rem;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            transition: all 0.3s ease;
            z-index: 5;
        }

        .form-control:focus + .input-icon {
            color: #d4af37;
            transform: translateY(-50%) scale(1.1);
        }

        .login-btn {
            width: 100%;
            padding: 1.3rem;
            background: linear-gradient(135deg, #d4af37 0%, #f4e4bc 50%, #d4af37 100%);
            color: #1a1a1a;
            border: none;
            border-radius: 16px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-top: 1rem;
        }

        .login-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            transition: left 0.6s ease;
        }

        .login-btn:hover::before {
            left: 100%;
        }

        .login-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(212, 175, 55, 0.4);
        }

        .login-btn:active {
            transform: translateY(-1px);
        }

        .login-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        .alert {
            padding: 1.2rem;
            border-radius: 16px;
            margin-bottom: 1.5rem;
            animation: slideDown 0.4s ease-out;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .alert-danger {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            border: 2px solid #f87171;
            color: #dc2626;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(40px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-15px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .forgot-password {
            text-align: center;
            margin-top: 2rem;
        }

        .forgot-password a {
            color: #d4af37;
            text-decoration: none;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            letter-spacing: 0.5px;
        }

        .forgot-password a:hover {
            color: #b8941f;
            text-decoration: underline;
        }

        /* Loading spinner */
        .spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(26, 26, 26, 0.3);
            border-radius: 50%;
            border-top-color: #1a1a1a;
            animation: spin 1s ease-in-out infinite;
            margin-right: 0.5rem;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .hidden {
            display: none;
        }

        /* Fashion quote */
        .fashion-quote {
            position: absolute;
            bottom: 2rem;
            left: 50%;
            transform: translateX(-50%);
            color: rgba(212, 175, 55, 0.6);
            font-style: italic;
            font-size: 0.9rem;
            text-align: center;
            letter-spacing: 1px;
        }

        /* Mobile responsive */
        @media (max-width: 768px) {
            .login-card {
                margin: 1rem;
                padding: 2.5rem;
                border-radius: 24px;
            }

            .login-title {
                font-size: 2rem;
            }

            .brand-icon {
                font-size: 3rem;
            }

            .fashion-element {
                font-size: 4rem !important;
            }
        }

        /* Luxury details */
        .luxury-border {
            position: absolute;
            top: 1rem;
            left: 1rem;
            right: 1rem;
            bottom: 1rem;
            border: 1px solid rgba(212, 175, 55, 0.2);
            border-radius: 24px;
            pointer-events: none;
        }

        /* Input focus glow effect */
        .form-control:focus {
            box-shadow: 
                0 0 0 4px rgba(212, 175, 55, 0.1),
                0 8px 25px rgba(212, 175, 55, 0.15);
        }
    </style>
</head>

<body>
    <div class="fashion-bg">
        <div class="fashion-element"><i class="fas fa-gem"></i></div>
        <div class="fashion-element"><i class="fas fa-crown"></i></div>
        <div class="fashion-element"><i class="fas fa-star"></i></div>
        
        <div class="geometric-shape shape-1"></div>
        <div class="geometric-shape shape-2"></div>
    </div>

    <main id="main">
        <div class="login-container">
            <div class="login-card">
                <div class="luxury-border"></div>
                
                <div class="login-header">
                    <div class="brand-icon">
                        <i class="fas fa-tshirt"></i>
                    </div>
                    <h1 class="login-title">Fashion Admin</h1>
                    <p class="login-subtitle">Style • Elegance • Luxury</p>
                </div>

                <form id="login-form">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <div class="input-wrapper">
                            <input type="text" id="username" name="username" class="form-control" placeholder="Enter your username" required>
                            <i class="fas fa-user input-icon"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-wrapper">
                            <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password" required>
                            <i class="fas fa-lock input-icon"></i>
                        </div>
                    </div>

                    <button type="submit" class="login-btn">
                        <span class="btn-text">Sign In</span>
                        <span class="spinner hidden"></span>
                    </button>

                    <div class="forgot-password">
                        <a href="#" onclick="showForgotPassword()">Forgot Password?</a>
                    </div>
                </form>

                <div class="fashion-quote">
                    "Fashion is art and you are the canvas"
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script>
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
            if($(this).find('.alert-danger').length > 0) {
                $(this).find('.alert-danger').remove();
            }

            // Demo - replace with your actual AJAX call
            setTimeout(() => {
                $btn.removeAttr('disabled');
                $btnText.text('Sign In');
                $spinner.addClass('hidden');
                
                // For demo - show error (replace with actual logic)
                $('#login-form').prepend(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span>Invalid credentials. Please try again.</span>
                    </div>
                `);
            }, 2000);

            
            // Uncomment for actual implementation:
            
            $.ajax({
                url: 'ajax.php?action=login',
                method: 'POST',
                data: $(this).serialize(),
                error: err => {
                    console.log(err);
                    $btn.removeAttr('disabled');
                    $btnText.text('Sign In');
                    $spinner.addClass('hidden');
                },
                success: function(resp) {
                    if(resp == 1) {
                        location.href = 'index.php?page=home';
                    } else if(resp == 2) {
                        location.href = 'voting.php';
                    } else {
                        $('#login-form').prepend(`
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle"></i>
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
            if (!$(this).val()) {
                $(this).parent().removeClass('focused');
            }
        });

        // Show forgot password
        function showForgotPassword() {
            alert('Please contact your system administrator to reset your password.');
        }

        // Auto-remove alerts after 6 seconds
        $(document).on('DOMNodeInserted', '.alert', function() {
            setTimeout(() => {
                $(this).fadeOut(400, function() {
                    $(this).remove();
                });
            }, 6000);
        });

        // Add subtle mouse movement effect
        document.addEventListener('mousemove', (e) => {
            const x = (e.clientX / window.innerWidth - 0.5) * 20;
            const y = (e.clientY / window.innerHeight - 0.5) * 20;
            
            document.querySelector('.login-card').style.transform = `translate(${x}px, ${y}px)`;
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && (e.target.id === 'username' || e.target.id === 'password')) {
                e.preventDefault();
                $('#login-form').submit();
            }
        });
    </script>
</body>
</html>