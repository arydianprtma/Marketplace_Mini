<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="404 - Halaman tidak ditemukan">
    <meta name="theme-color" content="#556270">
    <title>404 - Halaman Tidak Ditemukan</title>
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(45deg, #ff6b6b, #556270);
            --background-gradient: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            --text-primary: #2d3436;
            --text-secondary: #636e72;
        }

        body {
            margin: 0;
            font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
            overflow: hidden;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        #particles-js {
            position: fixed;
            width: 100%;
            height: 100%;
            background: var(--background-gradient);
            z-index: 1;
        }

        .content-wrapper {
            position: relative;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2;
            padding: 1rem;
        }

        .error-container {
            text-align: center;
            padding: 2.5rem;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 24px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.12);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            max-width: 650px;
            width: 90%;
            margin: 20px;
            transition: transform 0.3s ease;
        }

        .error-number {
            font-size: clamp(80px, 15vw, 120px);
            font-weight: 800;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0;
            line-height: 1.2;
            animation: bounce 2s infinite;
        }

        .error-text {
            font-size: clamp(20px, 4vw, 24px);
            color: var(--text-primary);
            margin: 20px 0;
            font-weight: 600;
        }

        .error-description {
            color: var(--text-secondary);
            font-size: clamp(16px, 3vw, 18px);
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .home-button {
            background: var(--primary-gradient);
            border: none;
            padding: 14px 35px;
            border-radius: 50px;
            color: white;
            font-size: 18px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-block;
            position: relative;
            overflow: hidden;
        }

        .home-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(255, 107, 107, 0.3);
            color: white;
        }

        .home-button:active {
            transform: translateY(-1px);
        }

        .home-button::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.2);
            transform: scaleX(0);
            transform-origin: right;
            transition: transform 0.3s ease;
        }

        .home-button:hover::after {
            transform: scaleX(1);
            transform-origin: left;
        }

        .icon-404 {
            font-size: clamp(30px, 6vw, 40px);
            margin-bottom: 20px;
            animation: float 3s ease-in-out infinite;
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0);
            }
            40% {
                transform: translateY(-20px);
            }
            60% {
                transform: translateY(-10px);
            }
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-10px);
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .error-number,
            .icon-404 {
                animation: none;
            }
        }

        @media (max-width: 480px) {
            .error-container {
                padding: 2rem;
            }
        }

        .visually-hidden {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }
    </style>
</head>
<body>
    <div id="particles-js" role="presentation" aria-hidden="true"></div>
    <main class="content-wrapper">
        <div class="error-container">
            <span class="icon-404" role="img" aria-label="Emoji bingung">🤔</span>
            <h1 class="error-number">404</h1>
            <h2 class="error-text">Ups! Halaman Tidak Ditemukan</h2>
            <p class="error-description">
                Maaf, sepertinya halaman yang Anda cari telah dipindahkan atau tidak ada.
                Mari kembali ke halaman beranda dan mulai dari awal.
            </p>
            <a href="/" class="home-button" role="button">
                Kembali ke Beranda
                <span class="visually-hidden">(kembali ke halaman utama)</span>
            </a>
        </div>
    </main>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/particles.js/2.0.0/particles.min.js" defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof particlesJS !== 'undefined') {
                initParticles();
            } else {
                console.warn('Particles.js not loaded');
            }
        });

        function initParticles() {
            particlesJS('particles-js', {
                "particles": {
                    "number": {
                        "value": 60,
                        "density": {
                            "enable": true,
                            "value_area": 800
                        }
                    },
                    "color": {
                        "value": "#556270"
                    },
                    "shape": {
                        "type": "circle"
                    },
                    "opacity": {
                        "value": 0.5,
                        "random": true,
                        "anim": {
                            "enable": true,
                            "speed": 1,
                            "opacity_min": 0.1,
                            "sync": false
                        }
                    },
                    "size": {
                        "value": 3,
                        "random": true,
                        "anim": {
                            "enable": true,
                            "speed": 2,
                            "size_min": 0.1,
                            "sync": false
                        }
                    },
                    "line_linked": {
                        "enable": true,
                        "distance": 150,
                        "color": "#556270",
                        "opacity": 0.4,
                        "width": 1
                    },
                    "move": {
                        "enable": true,
                        "speed": 4,
                        "direction": "none",
                        "random": true,
                        "straight": false,
                        "out_mode": "out",
                        "bounce": false,
                        "attract": {
                            "enable": true,
                            "rotateX": 600,
                            "rotateY": 1200
                        }
                    }
                },
                "interactivity": {
                    "detect_on": "canvas",
                    "events": {
                        "onhover": {
                            "enable": true,
                            "mode": "bubble"
                        },
                        "onclick": {
                            "enable": true,
                            "mode": "push"
                        },
                        "resize": true
                    },
                    "modes": {
                        "bubble": {
                            "distance": 150,
                            "size": 6,
                            "duration": 2,
                            "opacity": 0.8,
                            "speed": 3
                        },
                        "push": {
                            "particles_nb": 4
                        }
                    }
                },
                "retina_detect": true
            });
        }
    </script>
</body>
</html>