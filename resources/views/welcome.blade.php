<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>BEM TEL-U - Sistem Manajemen Organisasi</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #000;
            color: #fff;
            overflow: hidden;
            height: 100vh;
            position: relative;
        }

        /* Gradient Background */
        .gradient-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #000 0%, #1a0a0a 25%, #000 50%, #1a0a0a 75%, #000 100%);
            background-size: 400% 400%;
            animation: gradientMove 15s ease infinite;
        }

        @keyframes gradientMove {
            0% {
                background-position: 0% 50%;
            }

            50% {
                background-position: 100% 50%;
            }

            100% {
                background-position: 0% 50%;
            }
        }

        /* Matrix/Network Grid Effect */
        .grid-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image:
                linear-gradient(rgba(183, 28, 28, 0.1) 1px, transparent 1px),
                linear-gradient(90deg, rgba(183, 28, 28, 0.1) 1px, transparent 1px);
            background-size: 50px 50px;
            animation: gridMove 20s linear infinite;
            opacity: 0.3;
        }

        @keyframes gridMove {
            0% {
                transform: translate(0, 0);
            }

            100% {
                transform: translate(50px, 50px);
            }
        }

        /* Animated Network Lines */
        .network-lines {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
        }

        .network-line {
            position: absolute;
            width: 2px;
            background: linear-gradient(to bottom, transparent, rgba(183, 28, 28, 0.3), transparent);
            animation: lineMove 10s linear infinite;
        }

        @keyframes lineMove {
            0% {
                transform: translateY(-100%);
                opacity: 0;
            }

            50% {
                opacity: 1;
            }

            100% {
                transform: translateY(100vh);
                opacity: 0;
            }
        }

        /* Floating Particles */
        .particles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
        }

        .particle {
            position: absolute;
            width: 2px;
            height: 2px;
            background: rgba(183, 28, 28, 0.6);
            border-radius: 50%;
            animation: particleFloat 15s infinite linear;
        }

        @keyframes particleFloat {
            0% {
                transform: translateY(100vh) translateX(0);
                opacity: 0;
            }

            10% {
                opacity: 1;
            }

            90% {
                opacity: 1;
            }

            100% {
                transform: translateY(-100vh) translateX(100px);
                opacity: 0;
            }
        }

        /* Main Content - Floating Text */
        .main-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            z-index: 10;
            animation: floatText 3s ease-in-out infinite;
        }

        @keyframes floatText {

            0%,
            100% {
                transform: translate(-50%, -50%) translateY(0);
            }

            50% {
                transform: translate(-50%, -50%) translateY(-20px);
            }
        }

        .main-title {
            font-size: clamp(3rem, 8vw, 8rem);
            font-weight: 800;
            background: linear-gradient(135deg, #fff 0%, rgba(183, 28, 28, 0.8) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1rem;
            letter-spacing: -0.02em;
            text-shadow: 0 0 40px rgba(183, 28, 28, 0.3);
            animation: glow 3s ease-in-out infinite alternate;
        }

        @keyframes glow {
            from {
                filter: drop-shadow(0 0 20px rgba(183, 28, 28, 0.3));
            }

            to {
                filter: drop-shadow(0 0 40px rgba(183, 28, 28, 0.6));
            }
        }

        .subtitle {
            font-size: clamp(1rem, 2vw, 1.5rem);
            color: rgba(255, 255, 255, 0.6);
            font-weight: 300;
            letter-spacing: 0.1em;
        }

        /* Login Button */
        .login-btn {
            position: absolute;
            top: 2rem;
            right: 2rem;
            z-index: 20;
            padding: 0.75rem 2rem;
            background: linear-gradient(135deg, #B71C1C 0%, #D32F2F 100%);
            color: white;
            border: none;
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(183, 28, 28, 0.4);
            text-decoration: none;
            display: inline-block;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(183, 28, 28, 0.6);
            background: linear-gradient(135deg, #D32F2F 0%, #B71C1C 100%);
        }

        /* Logo */
        .logo {
            position: absolute;
            top: 2rem;
            left: 2rem;
            z-index: 20;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .logo img {
            height: 3rem;
            width: auto;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .main-title {
                font-size: 3rem;
            }

            .subtitle {
                font-size: 1rem;
            }

            .login-btn {
                padding: 0.5rem 1.5rem;
                font-size: 0.9rem;
            }

            .logo {
                top: 1rem;
                left: 1rem;
            }

            .logo img {
                height: 2rem;
            }
        }
    </style>
</head>

<body>
    <!-- Gradient Background -->
    <div class="gradient-bg"></div>

    <!-- Grid Overlay -->
    <div class="grid-overlay"></div>

    <!-- Network Lines -->
    <div class="network-lines" id="networkLines"></div>

    <!-- Particles -->
    <div class="particles" id="particles"></div>

    <!-- Logo -->
    <div class="logo">
        <img src="{{ asset('images/logo.png') }}" alt="BEM TEL-U">
    </div>

    <!-- Login Button -->
    @auth
        <a href="{{ url('/admin') }}" class="login-btn">Dashboard</a>
    @else
        <a href="{{ url('/admin/login') }}" class="login-btn">Masuk</a>
    @endauth

    <!-- Main Content - Floating Text -->
    <div class="main-content">
        <h1 class="main-title">BEM TEL-U</h1>
        <p class="subtitle">BADAN EKSEKUTIF MAHASISWA</p>
        <p class="subtitle" style="margin-top: 0.5rem; font-size: clamp(0.75rem, 1.5vw, 1rem);">Telkom University</p>
    </div>

    <script>
        // Create Network Lines
        function createNetworkLines() {
            const container = document.getElementById('networkLines');
            const numLines = 15;

            for (let i = 0; i < numLines; i++) {
                const line = document.createElement('div');
                line.className = 'network-line';
                line.style.left = Math.random() * 100 + '%';
                line.style.height = Math.random() * 200 + 100 + 'px';
                line.style.animationDelay = Math.random() * 10 + 's';
                line.style.animationDuration = (Math.random() * 5 + 8) + 's';
                container.appendChild(line);
            }
        }

        // Create Particles
        function createParticles() {
            const container = document.getElementById('particles');
            const numParticles = 50;

            for (let i = 0; i < numParticles; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.animationDelay = Math.random() * 15 + 's';
                particle.style.animationDuration = (Math.random() * 10 + 10) + 's';
                container.appendChild(particle);
            }
        }

        // Initialize on load
        window.addEventListener('DOMContentLoaded', () => {
            createNetworkLines();
            createParticles();
        });
    </script>
</body>

</html>