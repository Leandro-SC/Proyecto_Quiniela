(function () {
    'use strict';

    function log(message) {
        console.log('[Public Modern]', message);
    }

    function hasGSAP() {
        return typeof window.gsap !== 'undefined';
    }

    function prefersReducedMotion() {
        return window.matchMedia &&
            window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    }

    function initHeroAnimations() {
        if (!document.body.classList.contains('qv-public-body')) {
            return;
        }

        if (!hasGSAP()) {
            log('GSAP no cargó.');
            return;
        }

        if (prefersReducedMotion()) {
            log('Animaciones desactivadas por reduced motion.');
            return;
        }

        var gsap = window.gsap;

        var ballPath = document.querySelector('.hero-scene__ball-path');
        var ball = document.querySelector('.hero-scene__ball');
        var goalWrap = document.querySelector('.hero-scene__goal-wrap');
        var netWave = document.querySelector('.hero-scene__net-wave');
        var goalFlash = document.querySelector('.hero-scene__goal-flash');
        var goalText = document.querySelector('.hero-scene__goal-text');
        var mascot = document.querySelector('.hero-scene__mascot');
        var field = document.querySelector('.hero-scene__field');
        var particles = document.querySelectorAll('.hero-scene__particles span');
        var lights = document.querySelectorAll('.hero-scene__stadium-light');

        if (!ballPath || !ball || !goalWrap) {
            log('Faltan elementos del hero. Revisa home/index.php.');
            return;
        }

        log('Animaciones inicializadas correctamente.');

        document.documentElement.classList.add('qv-public-js-ready');

        gsap.set(ballPath, {
            x: -170,
            y: 70,
            scale: 0.8,
            opacity: 0,
            force3D: true
        });

        gsap.set(ball, {
            rotation: 0,
            force3D: true
        });

        gsap.set(goalWrap, {
            scaleX: 1,
            scaleY: 1,
            transformOrigin: '50% 100%',
            force3D: true
        });

        gsap.set([netWave, goalFlash, goalText], {
            opacity: 0
        });

        gsap.set(particles, {
            opacity: 0,
            scale: 0.3,
            x: 0,
            y: 0
        });

        if (mascot) {
            gsap.set(mascot, {
                transformOrigin: '50% 100%',
                force3D: true
            });
        }

        var tl = gsap.timeline({
            repeat: -1,
            repeatDelay: 1.1,
            defaults: {
                ease: 'power2.out'
            }
        });

        tl.to(ballPath, {
            duration: 0.18,
            opacity: 1
        }, 0);

        tl.to(ballPath, {
            duration: 0.48,
            x: -60,
            y: 20,
            scale: 0.98,
            ease: 'power2.out'
        }, 0.05);

        tl.to(ballPath, {
            duration: 0.58,
            x: 88,
            y: -34,
            scale: 0.74,
            ease: 'power3.inOut'
        }, 0.48);

        tl.to(ballPath, {
            duration: 0.28,
            x: 148,
            y: -28,
            scale: 0.48,
            opacity: 0.96,
            ease: 'power2.in'
        }, 1.02);

        tl.to(ball, {
            duration: 1.35,
            rotation: 780,
            ease: 'none'
        }, 0.05);

        if (mascot) {
            tl.to(mascot, {
                duration: 0.2,
                y: -8,
                rotation: -5,
                scale: 1.04,
                ease: 'power2.out'
            }, 0.32);

            tl.to(mascot, {
                duration: 0.28,
                y: 0,
                rotation: 6,
                scale: 1.02,
                ease: 'back.out(2.2)'
            }, 0.54);
        }

        tl.to(goalWrap, {
            duration: 0.12,
            scaleX: 1.08,
            scaleY: 0.94,
            ease: 'power2.out'
        }, 1.18);

        tl.to(goalWrap, {
            duration: 0.42,
            scaleX: 1,
            scaleY: 1,
            ease: 'elastic.out(1, 0.45)'
        }, 1.32);

        if (netWave) {
            tl.to(netWave, {
                duration: 0.12,
                opacity: 1,
                scale: 1.1,
                x: 8,
                ease: 'power2.out'
            }, 1.18);

            tl.to(netWave, {
                duration: 0.38,
                opacity: 0,
                scale: 1,
                x: 0,
                ease: 'power2.out'
            }, 1.34);
        }

        if (goalFlash) {
            tl.to(goalFlash, {
                duration: 0.12,
                opacity: 1,
                scale: 1.15,
                ease: 'power2.out'
            }, 1.16);

            tl.to(goalFlash, {
                duration: 0.42,
                opacity: 0,
                scale: 1,
                ease: 'power2.out'
            }, 1.32);
        }

        if (goalText) {
            tl.to(goalText, {
                duration: 0.16,
                opacity: 1,
                y: -8,
                scale: 1.18,
                rotation: 0,
                ease: 'back.out(2)'
            }, 1.2);

            tl.to(goalText, {
                duration: 0.35,
                opacity: 0,
                y: -26,
                scale: 0.92,
                ease: 'power2.in'
            }, 1.68);
        }

        particles.forEach(function (particle, index) {
            var spread = [-42, -18, 12, 32, 52][index] || 20;
            var lift = [-38, -54, -45, -60, -36][index] || -40;

            tl.to(particle, {
                duration: 0.14,
                opacity: 1,
                scale: 1,
                x: spread * 0.3,
                y: lift * 0.3,
                ease: 'power2.out'
            }, 1.22 + index * 0.025);

            tl.to(particle, {
                duration: 0.44,
                opacity: 0,
                scale: 0.2,
                x: spread,
                y: lift,
                ease: 'power2.out'
            }, 1.38 + index * 0.025);
        });

        tl.to(ballPath, {
            duration: 0.28,
            opacity: 0,
            scale: 0.38,
            ease: 'power2.in'
        }, 1.36);

        if (field) {
            gsap.to(field, {
                y: -5,
                duration: 4,
                repeat: -1,
                yoyo: true,
                ease: 'sine.inOut'
            });
        }

        if (lights.length) {
            gsap.to(lights, {
                opacity: 0.72,
                scale: 1.08,
                duration: 3.4,
                repeat: -1,
                yoyo: true,
                stagger: 0.8,
                ease: 'sine.inOut'
            });
        }

        gsap.to('.prize-card', {
            y: -5,
            duration: 2.2,
            repeat: -1,
            yoyo: true,
            stagger: 0.25,
            ease: 'sine.inOut'
        });

        gsap.to('.countdown', {
            y: -4,
            duration: 2.4,
            repeat: -1,
            yoyo: true,
            ease: 'sine.inOut'
        });
    }

    function initHeroEntrance() {
        if (!hasGSAP() || prefersReducedMotion()) {
            return;
        }

        var gsap = window.gsap;

        gsap.from('.hero-central-logo', {
            opacity: 0,
            y: 18,
            scale: 0.92,
            duration: 0.7,
            ease: 'power3.out'
        });

        gsap.from('.btn-league-custom, .btn-league', {
            opacity: 0,
            y: 12,
            duration: 0.55,
            stagger: 0.06,
            delay: 0.12,
            ease: 'power3.out'
        });

        gsap.from('.ch-hero h1, .ch-hero h2, .ch-round-form, .ch-date-wrap, .ch-prizes-wrap, .ch-countdown-wrapper', {
            opacity: 0,
            y: 18,
            duration: 0.72,
            stagger: 0.08,
            delay: 0.2,
            ease: 'power3.out'
        });
    }

    function initPickButtons() {
        if (!hasGSAP() || prefersReducedMotion()) {
            return;
        }

        var gsap = window.gsap;
        var buttons = document.querySelectorAll('.btn-choice, .btn-ch-pick');

        if (!buttons.length) {
            return;
        }

        buttons.forEach(function (button, index) {
            gsap.to(button, {
                scale: 1.035,
                duration: 0.72,
                repeat: -1,
                yoyo: true,
                delay: (index % 3) * 0.12,
                ease: 'sine.inOut'
            });

            button.addEventListener('click', function () {
                gsap.fromTo(button, {
                    scale: 0.86
                }, {
                    scale: 1.12,
                    duration: 0.14,
                    ease: 'power2.out',
                    onComplete: function () {
                        gsap.to(button, {
                            scale: 1,
                            duration: 0.24,
                            ease: 'elastic.out(1, 0.45)'
                        });
                    }
                });
            });
        });
    }

    function boot() {
        if (!document.body.classList.contains('qv-public-body')) {
            return;
        }

        log('Archivo public-modern.js cargado.');

        initHeroEntrance();
        initHeroAnimations();
        initPickButtons();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();