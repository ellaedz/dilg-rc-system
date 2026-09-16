<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#082b66">
    <meta name="description" content="CIVICLEAR is the road-clearing reporting, GIS routing, verification, and response-monitoring platform of Santa Cruz, Laguna.">
    <title>CIVICLEAR | Santa Cruz Road Clearing</title>

    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            --welcome-navy: #061b45;
            --welcome-deep: #0b2c78;
            --welcome-blue: #174ea6;
            --welcome-sky: #168fd2;
            --welcome-cyan: #26b7df;
            --welcome-green: #16a36a;
            --welcome-ink: #10203a;
            --welcome-muted: #5d6f89;
            --welcome-line: #dce8f6;
            --welcome-canvas: #f4f8fd;
        }

        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body { margin: 0; color: var(--welcome-ink); background: var(--welcome-canvas); font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; line-height: 1.6; }
        body.menu-open { overflow: hidden; }
        a { color: inherit; text-decoration: none; }
        img { display: block; max-width: 100%; }
        .welcome-shell { overflow: hidden; }
        .welcome-container { width: min(1180px, calc(100% - 40px)); margin-inline: auto; }

        .welcome-skip { position: fixed; z-index: 200; top: 12px; left: 12px; padding: 10px 14px; color: white; background: var(--welcome-navy); border-radius: 10px; transform: translateY(-160%); }
        .welcome-skip:focus { transform: translateY(0); }

        .welcome-header { position: relative; z-index: 100; border-bottom: 1px solid #e5edf5; background: rgba(255, 255, 255, 0.96); backdrop-filter: blur(12px); }
        .welcome-nav { min-height: 88px; display: flex; align-items: center; justify-content: space-between; gap: 32px; }
        .welcome-brand { display: inline-flex; align-items: center; gap: 12px; color: var(--welcome-deep); }
        .welcome-brand img { width: 50px; height: 50px; padding: 3px; object-fit: contain; background: white; border: 1px solid #dfe9f4; border-radius: 13px; }
        .welcome-brand strong { display: block; font-size: 1.04rem; letter-spacing: 0.025em; }
        .welcome-brand span { display: block; color: #667b94; font-size: 0.72rem; }
        .welcome-links { display: flex; align-items: center; gap: 12px; }
        .welcome-links > a:not(.welcome-login) { position: relative; padding: 10px 8px; color: #18334f; font-size: 0.88rem; font-weight: 600; opacity: 0.78; }
        .welcome-links > a:not(.welcome-login)::after { content: ""; position: absolute; right: 11px; bottom: 5px; left: 11px; height: 2px; background: #70d8f3; transform: scaleX(0); transform-origin: left; transition: transform 180ms ease; }
        .welcome-links > a:not(.welcome-login):hover, .welcome-links > a:not(.welcome-login):focus-visible { color: var(--welcome-deep); opacity: 1; }
        .welcome-links > a:not(.welcome-login):hover::after, .welcome-links > a:not(.welcome-login):focus-visible::after { transform: scaleX(1); }

        .welcome-button { display: inline-flex; align-items: center; justify-content: center; gap: 9px; min-height: 46px; padding: 0 19px; border: 1px solid transparent; border-radius: 12px; font-size: 0.9rem; font-weight: 800; cursor: pointer; }
        .welcome-button svg { width: 17px; height: 17px; flex: none; fill: none; stroke: currentColor; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }
        .welcome-login { position: relative; padding: 10px 8px; color: #18334f; font-size: 0.88rem; font-weight: 600; opacity: 0.78; }
        .welcome-login::after { content: ""; position: absolute; right: 11px; bottom: 5px; left: 11px; height: 2px; background: #70d8f3; transform: scaleX(0); transform-origin: left; transition: transform 180ms ease; }
        .welcome-login:hover, .welcome-login:focus-visible { color: var(--welcome-deep); opacity: 1; }
        .welcome-login:hover::after, .welcome-login:focus-visible::after { transform: scaleX(1); }
        .welcome-menu { display: none; width: 44px; height: 44px; color: var(--welcome-deep); border: 1px solid #d6e2ef; border-radius: 8px; background: white; }

        .welcome-hero { position: relative; color: var(--welcome-ink); background: white; }
        .welcome-hero-grid { width: min(1320px, calc(100% - 40px)); min-height: 650px; display: grid; grid-template-columns: minmax(0, 1.08fr) minmax(390px, 0.92fr); align-items: stretch; gap: 0; padding-block: 28px 70px; }
        .welcome-hero-copy { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: clamp(54px, 7vw, 92px); text-align: center; background: linear-gradient(145deg, #dceefa, #cde4f4); }
        .welcome-eyebrow { display: inline-flex; align-items: center; gap: 10px; margin-bottom: 24px; color: var(--welcome-blue); font-size: 0.72rem; font-weight: 800; letter-spacing: 0.14em; text-transform: uppercase; }
        .welcome-eyebrow::before, .welcome-eyebrow::after { content: ""; width: 30px; height: 1px; background: currentColor; opacity: 0.8; }
        .welcome-hero h1 { max-width: 760px; margin: 0; color: #092e58; font-family: "Iowan Old Style", "Palatino Linotype", Palatino, Georgia, serif; font-size: clamp(3.15rem, 5.5vw, 5.35rem); font-weight: 600; line-height: 0.98; letter-spacing: -0.045em; text-wrap: balance; }
        .welcome-hero h1 span { display: block; color: #155f91; font-style: normal; font-weight: 500; }
        .welcome-hero-copy > p { max-width: 620px; margin: 28px 0 0; color: #3e5b73; font-size: clamp(1rem, 1.4vw, 1.14rem); }
        .welcome-actions { display: flex; flex-wrap: wrap; justify-content: center; gap: 12px; margin-top: 34px; }
        .welcome-button--ghost { color: var(--welcome-deep); border-color: rgba(11, 44, 120, 0.3); border-radius: 6px; background: rgba(255, 255, 255, 0.54); backdrop-filter: blur(8px); }
        .welcome-button--ghost:hover { border-color: var(--welcome-blue); background: white; transform: translateY(-1px); }
        .welcome-facts { display: flex; flex-wrap: wrap; gap: 26px; margin: 42px 0 0; padding: 0; list-style: none; }
        .welcome-facts { justify-content: center; margin-top: 38px; }
        .welcome-facts li { display: flex; align-items: center; gap: 10px; color: #31536d; font-size: 0.8rem; font-weight: 650; }
        .welcome-facts i { color: var(--welcome-blue); }

        .welcome-hero-visual { position: relative; isolation: isolate; overflow: hidden; min-height: 650px; display: flex; align-items: center; padding: 32px; background: #071d48; }
        .welcome-hero-visual::before { content: ""; position: absolute; z-index: -2; inset: -6%; background: linear-gradient(rgba(5, 30, 73, 0.2), rgba(5, 30, 73, 0.54)), var(--hero-map) center / cover no-repeat; animation: welcome-map-drift 18s ease-in-out infinite alternate; will-change: transform; }
        .welcome-hero-visual::after { content: ""; position: absolute; z-index: -1; inset: -15% -55%; pointer-events: none; background: linear-gradient(108deg, transparent 38%, rgba(112, 216, 243, 0.14) 49%, transparent 60%); transform: translateX(-42%); animation: welcome-map-sweep 10s ease-in-out infinite; }
        .welcome-flow { position: relative; z-index: 1; width: 100%; padding: 24px; color: white; border: 1px solid rgba(255, 255, 255, 0.24); border-radius: 8px; background: rgba(6, 31, 75, 0.9); box-shadow: 0 20px 52px rgba(1, 12, 38, 0.28); backdrop-filter: blur(12px); }
        .welcome-flow::before { display: none; }
        .welcome-flow-title { position: relative; display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 18px; }
        .welcome-flow-title strong { font-size: 1rem; }
        .welcome-live { display: inline-flex; align-items: center; gap: 7px; color: #c8f3ff; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em; }
        .welcome-live::before { content: ""; width: 8px; height: 8px; border-radius: 50%; background: #3ee3a1; box-shadow: 0 0 0 5px rgba(62, 227, 161, 0.12); }
        .welcome-flow-list { position: relative; display: grid; gap: 10px; }
        .welcome-flow-item { display: grid; grid-template-columns: 42px 1fr auto; align-items: center; gap: 13px; min-height: 66px; padding: 10px 12px; border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 5px; background: rgba(9, 41, 88, 0.66); }
        .welcome-flow-item > .welcome-flow-icon { width: 40px; height: 40px; display: grid; place-items: center; margin: 0; color: #91e5f8; border: 1px solid rgba(145, 229, 248, 0.22); border-radius: 5px; background: rgba(42, 178, 224, 0.1); }
        .welcome-flow-icon svg { width: 21px; height: 21px; fill: none; stroke: currentColor; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }
        .welcome-flow-item strong { display: block; font-size: 0.9rem; }
        .welcome-flow-item span { display: block; margin-top: 1px; color: #aac3df; font-size: 0.74rem; }
        .welcome-flow-number { color: rgba(255, 255, 255, 0.35); font-size: 0.7rem; font-weight: 800; }

        @keyframes welcome-map-drift {
            from { transform: scale(1.04) translate3d(-1.5%, -1%, 0); }
            to { transform: scale(1.11) translate3d(1.5%, 1%, 0); }
        }

        @keyframes welcome-map-sweep {
            0%, 18% { transform: translateX(-42%); opacity: 0; }
            38% { opacity: 0.75; }
            68%, 100% { transform: translateX(42%); opacity: 0; }
        }

        .welcome-section { padding-block: 96px; }
        .welcome-section--white { background: white; }
        .welcome-section--blue { color: white; background: linear-gradient(145deg, #081f4e 0%, #0d3985 62%, #0d609b 100%); }
        .welcome-section-heading { max-width: 720px; margin-bottom: 46px; }
        .welcome-kicker { margin: 0 0 10px; color: var(--welcome-blue); font-size: 0.74rem; font-weight: 850; letter-spacing: 0.14em; text-transform: uppercase; }
        .welcome-section--blue .welcome-kicker { color: #77d9f2; }
        .welcome-section-heading h2 { margin: 0; font-family: "Iowan Old Style", "Palatino Linotype", Palatino, Georgia, serif; font-size: clamp(2.2rem, 4vw, 3.55rem); font-weight: 600; line-height: 1.04; letter-spacing: -0.038em; }
        .welcome-section-heading p { margin: 18px 0 0; color: var(--welcome-muted); font-size: 1.02rem; }
        .welcome-section--blue .welcome-section-heading p { color: #c2d8ee; }

        .welcome-purpose-grid { display: grid; grid-template-columns: 0.9fr 1.1fr; gap: 58px; align-items: start; }
        .welcome-purpose-panel { position: sticky; top: 24px; padding: 36px; border-radius: 7px; color: white; background: linear-gradient(145deg, #123b91, #0a75b5); box-shadow: 0 20px 48px rgba(18, 59, 145, 0.14); }
        .welcome-purpose-mark { width: 54px; height: 54px; display: grid; place-items: center; margin-bottom: 22px; border-radius: 16px; background: rgba(255, 255, 255, 0.14); font-size: 1.25rem; }
        .welcome-purpose-panel h3 { margin: 0; font-size: 1.65rem; line-height: 1.18; }
        .welcome-purpose-panel p { margin: 16px 0 0; color: #d6e9fa; }
        .welcome-metric-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-top: 28px; }
        .welcome-metric { padding: 14px 10px; text-align: center; border: 1px solid rgba(255, 255, 255, 0.16); border-radius: 14px; background: rgba(255, 255, 255, 0.08); }
        .welcome-metric strong { display: block; font-size: 1.35rem; }
        .welcome-metric span { display: block; color: #bcd6ef; font-size: 0.66rem; text-transform: uppercase; letter-spacing: 0.06em; }
        .welcome-capabilities { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 18px; }
        .welcome-capability { min-height: 210px; padding: 28px; border: 1px solid var(--welcome-line); border-radius: 7px; background: #fbfdff; }
        .welcome-capability-icon { width: 46px; height: 46px; display: grid; place-items: center; color: var(--welcome-blue); border-radius: 14px; background: #eaf3ff; }
        .welcome-capability h3 { margin: 20px 0 8px; font-size: 1.05rem; }
        .welcome-capability p { margin: 0; color: var(--welcome-muted); font-size: 0.88rem; }

        .welcome-mandate-layout { display: grid; grid-template-columns: minmax(0, 1.08fr) minmax(320px, 0.72fr); gap: 24px; align-items: start; }
        .welcome-memo { padding: 38px; border: 1px solid #dbe7f4; border-top: 3px solid var(--welcome-blue); border-radius: 7px; background: white; box-shadow: none; }
        .welcome-memo-badge { display: inline-flex; align-items: center; gap: 8px; padding: 0; color: #0f5d9e; background: transparent; font-size: 0.7rem; font-weight: 850; letter-spacing: 0.08em; text-transform: uppercase; }
        .welcome-memo h3 { max-width: 610px; margin: 22px 0 12px; font-size: clamp(1.55rem, 2.5vw, 2.05rem); line-height: 1.15; letter-spacing: -0.03em; text-wrap: balance; }
        .welcome-memo > p { margin: 0; color: var(--welcome-muted); }
        .welcome-policy-list { display: grid; gap: 12px; margin: 28px 0 0; padding: 0; list-style: none; }
        .welcome-policy-list li { display: grid; grid-template-columns: 28px 1fr; gap: 10px; align-items: start; color: #314661; font-size: 0.9rem; }
        .welcome-policy-list i { margin-top: 3px; color: var(--welcome-green); }
        .welcome-source-link { display: inline-flex; align-items: center; gap: 8px; margin-top: 28px; color: var(--welcome-blue); font-size: 0.84rem; font-weight: 800; }
        .welcome-source-link:hover { text-decoration: underline; }
        .welcome-supporting { overflow: hidden; border: 1px solid var(--welcome-line); border-top: 3px solid #79bfe7; border-radius: 7px; background: white; }
        .welcome-support-card { padding: 27px 26px; border: 0; border-bottom: 1px solid var(--welcome-line); background: transparent; }
        .welcome-support-card span { color: var(--welcome-sky); font-size: 0.7rem; font-weight: 850; letter-spacing: 0.08em; text-transform: uppercase; }
        .welcome-support-card h3 { margin: 8px 0 9px; font-size: 1rem; }
        .welcome-support-card p { margin: 0; color: var(--welcome-muted); font-size: 0.85rem; }
        .welcome-class-grid { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 14px; }
        .welcome-class-card { position: relative; overflow: hidden; min-height: 184px; padding: 22px 20px; border: 1px solid #e1eaf4; border-radius: 7px; background: white; box-shadow: none; transition: transform 220ms ease, border-color 220ms ease, box-shadow 220ms ease; }
        .welcome-class-card::before { content: ""; position: absolute; top: 0; right: 0; left: 0; height: 2px; background: linear-gradient(90deg, var(--welcome-blue), var(--welcome-cyan)); transform: scaleX(0); transform-origin: left; transition: transform 260ms ease; }
        .welcome-class-card:hover { transform: translateY(-5px); border-color: #bad3ee; box-shadow: 0 14px 30px rgba(24, 78, 143, 0.09); }
        .welcome-class-card:hover::before { transform: scaleX(1); }
        .welcome-class-icon { width: 42px; height: 42px; display: grid; place-items: center; color: #1767b5; border: 1px solid #cfe5f7; border-radius: 10px; background: #eef8ff; transition: color 220ms ease, background 220ms ease, transform 220ms ease; }
        .welcome-class-card:hover .welcome-class-icon { color: white; background: var(--welcome-blue); transform: translateY(-2px); }
        .welcome-class-card h3 { margin: 18px 0 8px; font-size: 0.94rem; line-height: 1.25; }
        .welcome-class-card p { margin: 0; color: var(--welcome-muted); font-size: 0.78rem; line-height: 1.5; }
        .welcome-class-grid--animated .welcome-class-card { opacity: 0; }
        .welcome-class-grid--animated.is-visible .welcome-class-card { animation: welcome-class-reveal 520ms ease both; }
        .welcome-class-grid--animated.is-visible .welcome-class-card:nth-child(2) { animation-delay: 80ms; }
        .welcome-class-grid--animated.is-visible .welcome-class-card:nth-child(3) { animation-delay: 160ms; }
        .welcome-class-grid--animated.is-visible .welcome-class-card:nth-child(4) { animation-delay: 240ms; }
        .welcome-class-grid--animated.is-visible .welcome-class-card:nth-child(5) { animation-delay: 320ms; }
        @keyframes welcome-class-reveal {
            from { opacity: 0; filter: blur(2px); }
            to { opacity: 1; filter: blur(0); }
        }
        .welcome-class-note { margin: 22px 0 0; color: var(--welcome-muted); font-size: 0.8rem; }

        .welcome-mobile-layout { display: grid; grid-template-columns: 0.92fr 1.08fr; align-items: center; gap: 72px; }
        .welcome-phone-wrap { position: relative; min-height: 560px; display: grid; place-items: center; }
        .welcome-phone-glow { position: absolute; width: 440px; height: 440px; border-radius: 50%; background: radial-gradient(circle, rgba(40, 190, 225, 0.27), transparent 66%); }
        .welcome-phone { position: relative; width: min(310px, 86vw); padding: 10px; border: 1px solid rgba(255, 255, 255, 0.28); border-radius: 38px; background: #07162e; box-shadow: 0 32px 70px rgba(0, 7, 24, 0.52); transform: rotate(-3deg); }
        .welcome-phone-screen { overflow: hidden; min-height: 520px; border-radius: 30px; color: var(--welcome-ink); background: #f7fbff; }
        .welcome-phone-top { display: flex; align-items: center; justify-content: space-between; padding: 18px 18px 14px; }
        .welcome-phone-brand { display: flex; align-items: center; gap: 9px; color: var(--welcome-deep); font-size: 0.75rem; font-weight: 850; }
        .welcome-phone-brand img { width: 34px; height: 34px; padding: 2px; border-radius: 10px; background: white; }
        .welcome-phone-map { position: relative; height: 205px; background: linear-gradient(rgba(14, 69, 143, 0.52), rgba(14, 69, 143, 0.52)), var(--hero-map) center / cover; }
        .welcome-phone-pin { position: absolute; top: 48%; left: 56%; width: 44px; height: 44px; display: grid; place-items: center; color: white; border: 4px solid white; border-radius: 50% 50% 50% 0; background: var(--welcome-green); box-shadow: 0 9px 20px rgba(2, 27, 61, 0.28); transform: rotate(-45deg); }
        .welcome-phone-pin i { transform: rotate(45deg); }
        .welcome-phone-body { padding: 20px; }
        .welcome-phone-body small { color: var(--welcome-blue); font-weight: 850; text-transform: uppercase; letter-spacing: 0.08em; }
        .welcome-phone-body h3 { margin: 5px 0 15px; font-size: 1.28rem; }
        .welcome-phone-row { display: flex; align-items: center; gap: 11px; padding: 11px; border: 1px solid #dce9f7; border-radius: 13px; background: white; }
        .welcome-phone-row + .welcome-phone-row { margin-top: 9px; }
        .welcome-phone-row i { width: 32px; height: 32px; display: grid; place-items: center; color: var(--welcome-blue); border-radius: 10px; background: #eaf3ff; }
        .welcome-phone-row strong { display: block; font-size: 0.76rem; }
        .welcome-phone-row span { display: block; color: var(--welcome-muted); font-size: 0.65rem; }
        .welcome-phone-action { width: 100%; margin-top: 14px; padding: 12px; color: white; border: 0; border-radius: 12px; background: linear-gradient(135deg, #174ea6, #168fd2); font-weight: 800; }
        .welcome-mobile-copy h2 { margin: 0; font-size: clamp(2.15rem, 4vw, 3.5rem); line-height: 1.06; letter-spacing: -0.045em; }
        .welcome-mobile-copy > p { margin: 20px 0 0; color: #c2d8ee; }
        .welcome-mobile-steps { display: grid; gap: 15px; margin-top: 34px; }
        .welcome-mobile-step { display: grid; grid-template-columns: 48px 1fr; gap: 15px; align-items: start; }
        .welcome-mobile-step > span { width: 48px; height: 48px; display: grid; place-items: center; color: #95e3f6; border: 1px solid rgba(149, 227, 246, 0.28); border-radius: 14px; background: rgba(255, 255, 255, 0.07); font-weight: 850; }
        .welcome-mobile-step h3 { margin: 2px 0 3px; font-size: 0.96rem; }
        .welcome-mobile-step p { margin: 0; color: #adc6df; font-size: 0.82rem; }
        .welcome-privacy { display: flex; align-items: flex-start; gap: 11px; margin-top: 28px; padding: 16px 18px; color: #c9dff1; border: 1px solid rgba(255, 255, 255, 0.14); border-radius: 15px; background: rgba(255, 255, 255, 0.06); font-size: 0.78rem; }
        .welcome-privacy i { margin-top: 3px; color: #69e2b2; }

        .welcome-footer { padding-block: 36px; color: #9db4ce; background: #06162f; }
        .welcome-footer-row { display: flex; align-items: center; justify-content: space-between; gap: 28px; }
        .welcome-footer-brand { display: flex; align-items: center; gap: 11px; }
        .welcome-footer-brand img { width: 42px; height: 42px; padding: 2px; border-radius: 12px; background: white; }
        .welcome-footer-brand strong { display: block; color: white; font-size: 0.86rem; }
        .welcome-footer-brand span { display: block; font-size: 0.7rem; }
        .welcome-footer p { margin: 0; font-size: 0.72rem; text-align: right; }

        @media (max-width: 1050px) {
            .welcome-links > a:not(.welcome-login) { display: none; }
            .welcome-hero-grid { grid-template-columns: 1.04fr 0.96fr; gap: 0; }
            .welcome-purpose-grid { grid-template-columns: 1fr; }
            .welcome-purpose-panel { position: relative; top: 0; }
            .welcome-class-grid { grid-template-columns: repeat(3, 1fr); }
        }

        @media (max-width: 820px) {
            .welcome-container { width: min(100% - 30px, 1180px); }
            .welcome-nav { min-height: 74px; }
            .welcome-brand img { width: 46px; height: 46px; }
            .welcome-links { display: none; position: fixed; inset: 74px 15px auto; padding: 15px; border: 1px solid #dce7f2; border-radius: 8px; background: rgba(255, 255, 255, 0.98); box-shadow: 0 24px 50px rgba(12, 43, 81, 0.18); }
            .welcome-links.is-open { display: grid; }
            .welcome-links > a:not(.welcome-login) { display: block; }
            .welcome-menu { display: grid; place-items: center; }
            .welcome-hero-grid { grid-template-columns: 1fr; padding-block: 20px 56px; }
            .welcome-hero-copy { padding: 68px 38px; }
            .welcome-hero-visual { min-height: 560px; }
            .welcome-flow { max-width: none; }
            .welcome-purpose-grid, .welcome-mandate-layout, .welcome-mobile-layout { grid-template-columns: 1fr; }
            .welcome-mobile-copy { order: -1; }
            .welcome-phone-wrap { min-height: 530px; }
        }

        @media (max-width: 620px) {
            .welcome-section { padding-block: 72px; }
            .welcome-eyebrow { font-size: 0.64rem; letter-spacing: 0.1em; }
            .welcome-eyebrow::before, .welcome-eyebrow::after { width: 18px; }
            .welcome-hero h1 { font-size: clamp(2.7rem, 15vw, 4.2rem); }
            .welcome-hero-copy { padding: 52px 22px; }
            .welcome-hero-visual { min-height: 480px; padding: 16px; }
            .welcome-actions { display: grid; }
            .welcome-button { width: 100%; }
            .welcome-facts { display: grid; gap: 12px; }
            .welcome-flow { padding: 20px; }
            .welcome-capabilities, .welcome-class-grid { grid-template-columns: 1fr; }
            .welcome-capability, .welcome-class-card { min-height: auto; }
            .welcome-metric-row { grid-template-columns: 1fr; }
            .welcome-mandate-layout { gap: 18px; }
            .welcome-memo { padding: 26px; }
            .welcome-footer-row { align-items: flex-start; flex-direction: column; }
            .welcome-footer p { text-align: left; }
        }

        @media (prefers-reduced-motion: reduce) {
            html { scroll-behavior: auto; }
            *, *::before, *::after { transition-duration: 0.01ms !important; }
            .welcome-hero-visual::before, .welcome-hero-visual::after { animation: none; transform: none; }
            .welcome-class-grid--animated .welcome-class-card { opacity: 1; animation: none; }
        }
    </style>
</head>
<body>
    <a class="welcome-skip" href="#main-content">Skip to main content</a>

    <div class="welcome-shell">
        <header class="welcome-header">
            <nav class="welcome-nav welcome-container" aria-label="Primary navigation">
                <a class="welcome-brand" href="#top" aria-label="CIVICLEAR home">
                    <img src="{{ asset('images/civiclear-logo.svg') }}" alt="">
                    <span><strong>CIVICLEAR</strong><span>Santa Cruz, Laguna</span></span>
                </a>

                <button class="welcome-menu" type="button" aria-expanded="false" aria-controls="welcome-links" aria-label="Open navigation">
                    <i class="fas fa-bars" aria-hidden="true"></i>
                </button>

                <div class="welcome-links" id="welcome-links">
                    <a href="#about">About</a>
                    <a href="#mandate">DILG mandate</a>
                    <a href="#coverage">Report coverage</a>
                    <a href="#mobile-app">Mobile app</a>
                    <a class="welcome-login" href="{{ route('login') }}">Admin / Staff Login</a>
                </div>
            </nav>
        </header>

        <main id="main-content">
            <section class="welcome-hero" id="top">
                <div class="welcome-container welcome-hero-grid">
                    <div class="welcome-hero-copy">
                        <div class="welcome-eyebrow">Official municipal monitoring platform</div>
                        <h1>Clear roads. <span>Connected communities.</span></h1>
                        <p>CIVICLEAR connects citizen reports, GPS-based barangay routing, AI-assisted review, staff verification, and response tracking for road-clearing operations across Santa Cruz, Laguna.</p>
                        <div class="welcome-actions">
                            <a class="welcome-button welcome-button--ghost" href="#mobile-app">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="7" y="2.5" width="10" height="19" rx="2"/><path d="M10.5 18.2h3"/></svg>
                                Explore the Mobile App
                            </a>
                        </div>
                        <ul class="welcome-facts" aria-label="System facts">
                            <li><i class="fas fa-location-dot" aria-hidden="true"></i> 26 barangays</li>
                            <li><i class="fas fa-route" aria-hidden="true"></i> GIS-assisted routing</li>
                            <li><i class="fas fa-user-check" aria-hidden="true"></i> Staff-verified decisions</li>
                        </ul>
                    </div>

                    <aside class="welcome-hero-visual" style="--hero-map: url('{{ asset('images/welcome-gps-map-hq.png') }}');" aria-label="CIVICLEAR operational flow">
                        <div class="welcome-flow">
                            <div class="welcome-flow-title"><strong>From report to response</strong><span class="welcome-live">Connected workflow</span></div>
                            <div class="welcome-flow-list">
                                <div class="welcome-flow-item"><span class="welcome-flow-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 8.5h3l1.4-2h7.2l1.4 2h3v9.5H4Z"/><circle cx="12" cy="13" r="3.2"/></svg></span><span><strong>Citizen report</strong><span>Photo, description, and GPS evidence</span></span><span class="welcome-flow-number">01</span></div>
                                <div class="welcome-flow-item"><span class="welcome-flow-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 18.5c2.5-4 4.7-5.8 7-5.5 2.1.3 3.4-1 4.8-3.8"/><path d="M17 6.5c0 2.8-3 5.6-3 5.6s-3-2.8-3-5.6a3 3 0 1 1 6 0Z"/><circle cx="14" cy="6.5" r=".7"/></svg></span><span><strong>Barangay routing</strong><span>Boundary-aware municipal assignment</span></span><span class="welcome-flow-number">02</span></div>
                                <div class="welcome-flow-item"><span class="welcome-flow-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 19 9.7-9.7M13.4 5.6l5 5M16.5 3v2.2M20.6 7.1h-2.2M8 5l.7 1.7L10.5 7l-1.8.7L8 9.5l-.7-1.8L5.5 7l1.8-.3L8 5Z"/></svg></span><span><strong>Assisted review</strong><span>AI suggestion with staff confirmation</span></span><span class="welcome-flow-number">03</span></div>
                                <div class="welcome-flow-item"><span class="welcome-flow-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="8.5"/><path d="m8.2 12.2 2.4 2.4 5.3-5.4"/></svg></span><span><strong>Tracked action</strong><span>Verification, response, and resolution timeline</span></span><span class="welcome-flow-number">04</span></div>
                            </div>
                        </div>
                    </aside>
                </div>
            </section>

            <section class="welcome-section welcome-section--white" id="about">
                <div class="welcome-container">
                    <div class="welcome-section-heading">
                        <p class="welcome-kicker">One coordinated workspace</p>
                        <h2>A clearer view of every road-clearing report.</h2>
                        <p>CIVICLEAR keeps reporting, geographic assignment, official review, operational action, and municipal monitoring connected without replacing the judgment of authorized personnel.</p>
                    </div>

                    <div class="welcome-purpose-grid">
                        <div class="welcome-purpose-panel">
                            <div class="welcome-purpose-mark"><i class="fas fa-building-shield" aria-hidden="true"></i></div>
                            <h3>Built for Santa Cruz road-clearing operations</h3>
                            <p>DILG administrators monitor municipality-wide activity while each barangay workspace remains limited to its assigned jurisdiction.</p>
                            <div class="welcome-metric-row">
                                <div class="welcome-metric"><strong>26</strong><span>Barangays</span></div>
                                <div class="welcome-metric"><strong>1</strong><span>Municipal view</span></div>
                                <div class="welcome-metric"><strong>GPS</strong><span>Report evidence</span></div>
                            </div>
                        </div>

                        <div class="welcome-capabilities">
                            <article class="welcome-capability"><div class="welcome-capability-icon"><i class="fas fa-mobile-screen" aria-hidden="true"></i></div><h3>Structured citizen reporting</h3><p>The mobile app collects the incident description, photograph, selected barangay, and GPS location needed for review.</p></article>
                            <article class="welcome-capability"><div class="welcome-capability-icon"><i class="fas fa-map" aria-hidden="true"></i></div><h3>Municipal GIS context</h3><p>Validated barangay polygons and hall locations help staff understand where reports occurred and which office should respond.</p></article>
                            <article class="welcome-capability"><div class="welcome-capability-icon"><i class="fas fa-wand-magic-sparkles" aria-hidden="true"></i></div><h3>AI-assisted classification</h3><p>Image and text analysis provide a suggestion for staff review. AI never makes the official barangay decision.</p></article>
                            <article class="welcome-capability"><div class="welcome-capability-icon"><i class="fas fa-chart-line" aria-hidden="true"></i></div><h3>Accountable follow-through</h3><p>Status timelines, analytics, GIS markers, and exports support consistent monitoring from submission through resolution.</p></article>
                        </div>
                    </div>
                </div>
            </section>

            <section class="welcome-section" id="mandate">
                <div class="welcome-container">
                    <div class="welcome-section-heading">
                        <p class="welcome-kicker">Policy alignment</p>
                        <h2>Supporting Barangay Road Clearing Operations.</h2>
                        <p>The platform is designed around the public-road accessibility and monitoring objectives described in current DILG Barangay Road Clearing Operations guidance.</p>
                    </div>

                    <div class="welcome-mandate-layout">
                        <article class="welcome-memo">
                            <span class="welcome-memo-badge"><i class="fas fa-file-shield" aria-hidden="true"></i> Primary reference</span>
                            <h3>DILG Memorandum Circular No. 2024-053</h3>
                            <p><strong>Nationwide Implementation of the Barangay Road Clearing Operations, Assessment, Validation, and Recognition under the Bagong Pilipinas Program.</strong></p>
                            <ul class="welcome-policy-list">
                                <li><i class="fas fa-check-circle" aria-hidden="true"></i><span>Directs continuing Barangay Road Clearing Operations and the monitoring of public-road conditions and obstructions.</span></li>
                                <li><i class="fas fa-check-circle" aria-hidden="true"></i><span>Provides an assessment and validation framework involving barangays, city or municipal governments, DILG, and partner agencies.</span></li>
                                <li><i class="fas fa-check-circle" aria-hidden="true"></i><span>Covers barangay roads, streets, alleys, and other roads officially placed under barangay responsibility.</span></li>
                            </ul>
                            <a class="welcome-source-link" href="https://sites.google.com/dilg.gov.ph/2025-reports/downloadable-forms-and-issuances/road-clearing" target="_blank" rel="noopener noreferrer">View the official DILG road-clearing resources <i class="fas fa-arrow-up-right-from-square" aria-hidden="true"></i></a>
                        </article>

                        <div class="welcome-supporting">
                            <article class="welcome-support-card"><span>Supporting issuance</span><h3>DILG MC No. 2022-085</h3><p>Road-clearing implementation guidance covering the existing road right-of-way, including the travel way, shoulders, and sidewalks.</p></article>
                            <article class="welcome-support-card"><span>Supporting issuance</span><h3>DILG MC No. 2020-027</h3><p>Continued implementation of the directive to clear public roads of illegal or unauthorized obstructions.</p></article>
                        </div>
                    </div>
                </div>
            </section>

            <section class="welcome-section welcome-section--white" id="coverage">
                <div class="welcome-container">
                    <div class="welcome-section-heading">
                        <p class="welcome-kicker">Current AI-assisted coverage</p>
                        <h2>Five report classes focused on road accessibility.</h2>
                        <p>These CIVICLEAR analysis classes reflect commonly documented road obstructions. Barangay staff inspect the evidence and select the official classification.</p>
                    </div>

                    <div class="welcome-class-grid">
                        <article class="welcome-class-card"><span class="welcome-class-icon"><i class="fas fa-car-side" aria-hidden="true"></i></span><h3>Illegal Parking</h3><p>Vehicles positioned in ways that may obstruct public passage or road use.</p></article>
                        <article class="welcome-class-card"><span class="welcome-class-icon"><i class="fas fa-road-barrier" aria-hidden="true"></i></span><h3>Road Obstruction</h3><p>Objects or conditions affecting the travel way, shoulder, alley, or street.</p></article>
                        <article class="welcome-class-card"><span class="welcome-class-icon"><i class="fas fa-person-walking" aria-hidden="true"></i></span><h3>Sidewalk Obstruction</h3><p>Obstructions that may prevent safe and accessible pedestrian movement.</p></article>
                        <article class="welcome-class-card"><span class="welcome-class-icon"><i class="fas fa-person-digging" aria-hidden="true"></i></span><h3>Construction Materials</h3><p>Materials stored within the road right-of-way that may restrict passage.</p></article>
                        <article class="welcome-class-card"><span class="welcome-class-icon"><i class="fas fa-trash-can" aria-hidden="true"></i></span><h3>Waste / Garbage</h3><p>Garbage, debris, or discarded material obstructing roads or pedestrian areas.</p></article>
                    </div>
                    <p class="welcome-class-note"><i class="fas fa-circle-info" aria-hidden="true"></i> An AI result is a decision-support suggestion only. Authorized staff determine whether a submitted incident is a valid road-clearing violation.</p>
                </div>
            </section>

            <section class="welcome-section welcome-section--blue" id="mobile-app" style="--hero-map: url('{{ asset('images/welcome-gps-map-hq.png') }}');">
                <div class="welcome-container welcome-mobile-layout">
                    <div class="welcome-phone-wrap" aria-hidden="true">
                        <div class="welcome-phone-glow"></div>
                        <div class="welcome-phone">
                            <div class="welcome-phone-screen">
                                <div class="welcome-phone-top"><span class="welcome-phone-brand"><img src="{{ asset('images/civiclear-logo.svg') }}" alt=""> CIVICLEAR</span><i class="fas fa-bars"></i></div>
                                <div class="welcome-phone-map"><span class="welcome-phone-pin"><i class="fas fa-location-dot"></i></span></div>
                                <div class="welcome-phone-body">
                                    <small>New road report</small>
                                    <h3>Share what you see</h3>
                                    <div class="welcome-phone-row"><i class="fas fa-camera"></i><span><strong>Photo evidence</strong><span>Capture the road condition</span></span></div>
                                    <div class="welcome-phone-row"><i class="fas fa-location-crosshairs"></i><span><strong>GPS location</strong><span>Confirm the incident position</span></span></div>
                                    <button class="welcome-phone-action" type="button">Review report</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="welcome-mobile-copy">
                        <p class="welcome-kicker">Citizen Mobile App</p>
                        <h2>Report road concerns from where they happen.</h2>
                        <p>The CIVICLEAR Android app gives residents a focused reporting path while keeping municipal review and operational records inside the authorized staff portal.</p>
                        <div class="welcome-mobile-steps">
                            <div class="welcome-mobile-step"><span>01</span><div><h3>Document the incident</h3><p>Add a clear photo and short factual description of the road condition.</p></div></div>
                            <div class="welcome-mobile-step"><span>02</span><div><h3>Confirm location and barangay</h3><p>GPS evidence and the selected barangay help route the report to the correct workspace.</p></div></div>
                            <div class="welcome-mobile-step"><span>03</span><div><h3>Submit and retain the tracking reference</h3><p>The generated reference lets the reporting workflow preserve continuity without publishing personal information.</p></div></div>
                            <div class="welcome-mobile-step"><span>04</span><div><h3>Wait for official review</h3><p>Barangay personnel assess the evidence, confirm the classification, and record follow-up action.</p></div></div>
                        </div>
                        <div class="welcome-privacy"><i class="fas fa-user-shield" aria-hidden="true"></i><span>CIVICLEAR is designed for anonymous citizen reporting. Do not place passwords, private account information, or unnecessary personal details in a report.</span></div>
                    </div>
                </div>
            </section>

        </main>

        <footer class="welcome-footer">
            <div class="welcome-container welcome-footer-row">
                <div class="welcome-footer-brand"><img src="{{ asset('images/civiclear-logo.svg') }}" alt=""><span><strong>CIVICLEAR</strong><span>Santa Cruz road-clearing portal</span></span></div>
                <p>&copy; {{ now()->year }} Municipality of Santa Cruz, Laguna<br>Official municipal monitoring system</p>
            </div>
        </footer>
    </div>

    <script>
        const menuButton = document.querySelector('.welcome-menu');
        const menuLinks = document.getElementById('welcome-links');
        const classGrid = document.querySelector('.welcome-class-grid');

        if (classGrid && 'IntersectionObserver' in window && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            classGrid.classList.add('welcome-class-grid--animated');

            const classGridObserver = new IntersectionObserver((entries, observer) => {
                if (!entries[0]?.isIntersecting) return;

                classGrid.classList.add('is-visible');
                observer.unobserve(classGrid);
            }, { threshold: 0.2 });

            classGridObserver.observe(classGrid);
        }

        menuButton?.addEventListener('click', () => {
            const isOpen = menuButton.getAttribute('aria-expanded') === 'true';
            menuButton.setAttribute('aria-expanded', String(!isOpen));
            menuButton.setAttribute('aria-label', isOpen ? 'Open navigation' : 'Close navigation');
            menuLinks?.classList.toggle('is-open', !isOpen);
            document.body.classList.toggle('menu-open', !isOpen);
        });

        menuLinks?.querySelectorAll('a').forEach((link) => {
            link.addEventListener('click', () => {
                menuButton?.setAttribute('aria-expanded', 'false');
                menuButton?.setAttribute('aria-label', 'Open navigation');
                menuLinks.classList.remove('is-open');
                document.body.classList.remove('menu-open');
            });
        });
    </script>
</body>
</html>
