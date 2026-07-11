<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $link->title }} &ndash; OriginPay</title>
    <link rel="shortcut icon" href="{{ asset('frontend/images/originpay/originpay-app-icon.png') }}" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg:        #0B0B0F;
            --surface:   #111218;
            --surface2:  #161820;
            --border:    rgba(255,255,255,0.07);
            --border-hl: rgba(var(--primary-rgb),0.55);
            --primary:   #7C3AED;
            --primary-rgb: 124,58,237;
            --primary-2: #9B5DE5;
            --text:      #F0F0F5;
            --muted:     #6E6E85;
            --muted-2:   #3A3A50;
            --success:   #22C55E;
            --error:     #EF4444;
            --radius:    14px;
            --radius-sm: 9px;
            --transition: 0.2s ease;
        }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px; /* reduced from 40px */
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background: radial-gradient(ellipse 70% 55% at 50% -10%, rgba(var(--primary-rgb),0.18) 0%, transparent 70%);
            pointer-events: none;
            z-index: 0;
        }

        /* ─── CARD ──────────────────────────────── */
        .checkout {
            width: 100%;
            max-width: 580px;
            position: relative;
            z-index: 1;
            animation: fadeUp 0.45s cubic-bezier(.16,1,.3,1) both;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(18px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: 0 0 0 1px rgba(var(--primary-rgb),0.08), 0 32px 80px rgba(0,0,0,0.6);
            overflow: hidden;
        }

        /* ─── HEAD ─────────────────────────────── */
        .head {
            padding: 22px 28px 18px; /* reduced */
            border-bottom: 1px solid var(--border);
            position: relative;
        }

        .head-top {
            display: flex;
            align-items: flex-start;
            gap: 14px;
        }

        .seller-avatar {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: linear-gradient(135deg, #7C3AED, #9B5DE5);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            overflow: hidden;
        }

        .seller-avatar img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        /* Fallback: OriginPay horizontal logo — wider, no purple bg */
        .seller-avatar-logo {
            height: 36px;
            display: flex;
            align-items: center;
            flex-shrink: 0;
        }

        .seller-avatar-logo img {
            height: 36px;
            width: auto;
            object-fit: contain;
        }

        .head-info {
            flex: 1;
        }

        .product-name {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--text);
            line-height: 1.3;
        }

        .seller-name {
            font-size: 0.75rem;
            color: var(--muted);
            margin-top: 3px;
        }

        .head-amount {
            text-align: right;
            flex-shrink: 0;
        }

        .amount-value {
            font-size: 1.75rem;
            font-weight: 900;
            color: var(--text);
            line-height: 1;
            letter-spacing: -0.03em;
        }

        .amount-currency {
            font-size: 0.72rem;
            color: var(--muted);
            margin-top: 4px;
            text-align: right;
        }

        .head-badges {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 14px;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .badge-pending {
            background: rgba(var(--primary-rgb),0.15);
            color: #C4B5FD;
            border: 1px solid rgba(var(--primary-rgb),0.25);
        }

        .badge-pending i { animation: pulse 2s ease-in-out infinite; }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50%       { opacity: 0.4; }
        }

        .badge-blocked {
            background: rgba(239,68,68,0.1);
            color: #FCA5A5;
            border: 1px solid rgba(239,68,68,0.22);
        }

        .badge-paid {
            background: rgba(34,197,94,0.1);
            color: #86EFAC;
            border: 1px solid rgba(34,197,94,0.2);
        }

        .countdown {
            font-size: 0.72rem;
            font-weight: 600;
            color: var(--muted);
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .countdown-timer { color: #FCD34D; font-weight: 700; font-variant-numeric: tabular-nums; }

        /* ─── SUMMARY BOX ─────────────────────────────── */
        .summary {
            padding: 0 28px 0;
            margin-top: 16px;
        }

        .detail-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 9px 0;
            border-bottom: 1px solid var(--border);
            font-size: 0.78rem;
        }

        .detail-item:last-child { border-bottom: none; }
        .detail-label { color: var(--muted); }
        .detail-value { color: var(--text); font-weight: 600; text-align: right; }

        /* ─── BLOCKED STATE ─────────────────────────────── */
        .blocked-box {
            margin: 20px 28px;
            padding: 18px;
            background: rgba(239,68,68,0.06);
            border: 1px solid rgba(239,68,68,0.18);
            border-radius: var(--radius-sm);
        }

        /* ─── PAYMENT METHOD TABS ─────────────────────────────── */
        .method-section {
            padding: 20px 28px 0;
        }

        .method-label {
            font-size: 0.7rem;
            font-weight: 700;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 10px;
        }

        .method-tabs {
            display: flex;
            gap: 8px;
        }

        .method-tab {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            padding: 12px 8px;
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            cursor: pointer;
            transition: var(--transition);
            font-size: 0.72rem;
            font-weight: 600;
            color: var(--muted);
            user-select: none;
        }

        .method-tab:hover {
            border-color: rgba(var(--primary-rgb),0.35);
            color: var(--text);
        }

        .method-tab.active {
            border-color: var(--primary);
            background: rgba(var(--primary-rgb),0.1);
            color: var(--text);
            box-shadow: 0 0 0 3px rgba(var(--primary-rgb),0.12);
        }

        .method-tab i {
            font-size: 1.1rem;
            color: inherit;
        }
        
        .method-tab-pix-icon {
            width: 22px; height: 22px;
            background-color: currentColor;
            -webkit-mask-image: url('{{ asset("frontend/images/originpay/pix-logo.svg") }}');
            -webkit-mask-size: contain;
            -webkit-mask-repeat: no-repeat;
            -webkit-mask-position: center;
            mask-image: url('{{ asset("frontend/images/originpay/pix-logo.svg") }}');
            mask-size: contain;
            mask-repeat: no-repeat;
            mask-position: center;
            opacity: 0.75;
        }
        .method-tab.active .method-tab-pix-icon { opacity: 1; }

        /* ─── FORM ──────────────────────────────── */
        .form-section {
            padding: 20px 28px 0;
        }

        .form-panel {
            display: none;
            animation: fadeIn 0.25s ease;
        }

        .form-panel.active { display: block; }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(6px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .form-grid .full { grid-column: 1 / -1; }

        .field {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .field label {
            font-size: 0.68rem;
            font-weight: 600;
            color: var(--muted);
            letter-spacing: 0.03em;
        }

        .input-wrap {
            position: relative;
        }

        .input-wrap i {
            position: absolute;
            left: 11px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 0.8rem;
            color: var(--muted-2);
            pointer-events: none;
        }

        .field input, .field select {
            width: 100%;
            height: 40px;
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            color: var(--text);
            font-family: 'Inter', sans-serif;
            font-size: 0.82rem;
            padding: 0 12px 0 34px;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
            -webkit-appearance: none;
        }

        .field input::placeholder { color: var(--muted-2); }

        .field input:focus, .field select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(var(--primary-rgb),0.15);
        }

        .field input.no-icon, .field select.no-icon {
            padding-left: 12px;
        }

        .field i {
            position: absolute;
            left: 14px;
            color: var(--muted);
            font-size: 0.9rem;
        }

        .field input.error { border-color: var(--error); }

        /* ─── CARD PREVIEW ──────────────────────────────── */
        .card-preview-wrap {
            perspective: 800px;
            margin-bottom: 14px;
        }

        .card-preview {
            width: 100%;
            height: 110px; /* reduced from 120px */
            border-radius: 12px;
            background: linear-gradient(135deg, #1a1235, #4c1d95, #6d28d9);
            border: 1px solid rgba(255,255,255,0.1);
            box-shadow: 0 8px 32px rgba(0,0,0,0.5);
            padding: 16px 18px;
            position: relative;
            transition: transform 0.6s;
            transform-style: preserve-3d;
        }

        .card-preview.flipped { transform: rotateY(180deg); }

        .card-front, .card-back {
            position: absolute;
            inset: 0;
            padding: 14px 18px;
            backface-visibility: hidden;
            border-radius: 12px;
        }

        .card-back {
            transform: rotateY(180deg);
            background: linear-gradient(135deg, #2e1065, #6d28d9);
            display: flex;
            align-items: center;
            justify-content: flex-end;
        }

        .card-chip {
            width: 30px;
            height: 22px;
            background: linear-gradient(135deg, #D4AF37, #FFF3A3, #D4AF37);
            border-radius: 4px;
            margin-bottom: 8px;
        }

        .card-number-display {
            font-size: 0.85rem;
            font-weight: 600;
            letter-spacing: 0.22em;
            color: rgba(255,255,255,0.9);
            font-variant-numeric: tabular-nums;
        }

        .card-meta {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
        }

        .card-meta-label {
            font-size: 0.55rem;
            text-transform: uppercase;
            color: rgba(255,255,255,0.45);
            letter-spacing: 0.08em;
            margin-bottom: 2px;
        }

        .card-meta-val {
            font-size: 0.75rem;
            font-weight: 600;
            color: rgba(255,255,255,0.85);
            letter-spacing: 0.05em;
        }

        .card-cvv-strip {
            background: rgba(0,0,0,0.4);
            border-radius: 4px;
            padding: 4px 12px;
            font-size: 0.8rem;
            font-weight: 700;
            letter-spacing: 0.15em;
            color: rgba(255,255,255,0.85);
            min-width: 48px;
            text-align: center;
        }

        .cvv-display {
            font-size: 0.8rem;
            font-weight: 600;
            letter-spacing: 0.1em;
            color: rgba(255,255,255,0.9);
            background: rgba(0,0,0,0.2);
            padding: 4px 10px;
            border-radius: 4px;
        }

        .helper-text {
            font-size: 0.7rem;
            color: var(--muted);
            margin-top: 4px;
            display: block;
            text-align: center;
        }

        /* ─── ADDRESS ACCORDION ──────────────────────────────── */
        .address-toggle {
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            padding: 10px 0;
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--muted);
            border-top: 1px solid var(--border);
            margin-top: 10px;
            user-select: none;
            transition: color 0.2s;
        }

        .address-toggle:hover { color: var(--text); }

        .address-toggle i { transition: transform 0.25s; }
        .address-toggle.open i { transform: rotate(180deg); }

        .address-body {
            display: none;
            padding-top: 6px;
        }

        .address-body.open { display: block; }

        /* Ã¢â€â‚¬Ã¢â€â‚¬ SUBMIT AREA Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬ */
        .submit-area {
            padding: 20px 28px 24px;
        }

        .btn-pay {
            width: 100%;
            height: 50px;
            border-radius: 12px;
            border: none;
            background: linear-gradient(160deg, #9B5DE5 0%, #7C3AED 55%, #5B21B6 100%);
            box-shadow: 0 4px 20px rgba(var(--primary-rgb),0.35);
            color: #fff;
            font-family: 'Inter', sans-serif;
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: opacity 0.2s, transform 0.15s, box-shadow 0.2s;
            position: relative;
            overflow: hidden;
        }

        .btn-pay:hover { opacity: 0.92; transform: translateY(-1px); box-shadow: 0 8px 28px rgba(var(--primary-rgb),0.45); }
        .btn-pay:active { transform: scale(0.99); }

        .btn-pay:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        /* Ã¢â€â‚¬Ã¢â€â‚¬ SECURITY SEAL Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬ */
        .seal {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            margin-top: 14px;
            font-size: 0.68rem;
            color: var(--muted);
        }

        .seal i { color: var(--primary); font-size: 0.75rem; }
        .seal a {
            color: var(--muted);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s;
        }
        .seal a:hover { color: var(--text); }

        /* Ã¢â€â‚¬Ã¢â€â‚¬ POST-SUBMIT: QR / BOLETO Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬ */
        .payment-result {
            padding: 0 28px 20px;
        }

        .qr-box {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 14px;
            padding: 20px;
            background: var(--surface2);
            border-radius: var(--radius-sm);
            border: 1px solid var(--border);
        }

        .qr-img {
            width: 180px;
            height: 180px;
            border-radius: 10px;
            background: #fff;
            padding: 8px;
            object-fit: contain;
        }

        .copy-area {
            width: 100%;
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 10px 12px;
            font-size: 0.72rem;
            color: var(--muted);
            word-break: break-all;
            line-height: 1.5;
        }

        .btn-secondary {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            height: 40px;
            padding: 0 16px;
            border-radius: var(--radius-sm);
            border: 1px solid var(--border);
            background: var(--surface2);
            color: var(--text);
            font-family: 'Inter', sans-serif;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: border-color 0.2s;
        }

        .btn-secondary:hover { border-color: rgba(var(--primary-rgb),0.4); }

        .btn-primary-alt {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            height: 40px;
            padding: 0 16px;
            border-radius: var(--radius-sm);
            border: none;
            background: var(--primary);
            color: #fff;
            font-family: 'Inter', sans-serif;
            font-size: 0.8rem;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
        }

        .actions-row { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 8px; }

        /* Ã¢â€â‚¬Ã¢â€â‚¬ ERROR BOX Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬ */
        .error-notice {
            background: rgba(239,68,68,0.07);
            border: 1px solid rgba(239,68,68,0.2);
            border-radius: var(--radius-sm);
            padding: 10px 14px;
            font-size: 0.78rem;
            color: #FCA5A5;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 12px;
        }

        /* â”€â”€ NEW STATES (PROCESSING, SUCCESS, ERROR) â”€â”€ */
        .state-container {
            padding: 40px 28px 48px;
            text-align: center;
            animation: fadeUp 0.4s ease forwards;
            display: none;
            flex-direction: column;
            align-items: center;
        }

        .state-icon {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            margin-bottom: 24px;
            position: relative;
        }

        .state-icon.processing {
            background: rgba(var(--primary-rgb),0.08);
            color: var(--primary);
        }

        .state-icon.success {
            background: rgba(34,197,94,0.1);
            color: #22C55E;
            box-shadow: 0 0 24px rgba(34,197,94,0.15);
            animation: scaleFadeIn 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
        }

        .state-icon.error {
            background: rgba(249,115,22,0.1);
            color: #F97316; /* Soft orange/red */
        }

        @keyframes scaleFadeIn {
            0% { transform: scale(0.5); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }

        .spinner {
            border: 3.5px solid rgba(var(--primary-rgb),0.15);
            border-top-color: var(--primary);
            border-radius: 50%;
            width: 34px;
            height: 34px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin { 100% { transform: rotate(360deg); } }

        .state-title {
            font-size: 1.35rem;
            font-weight: 800;
            color: var(--text);
            margin-bottom: 10px;
            letter-spacing: -0.02em;
        }

        .state-desc {
            font-size: 0.9rem;
            color: var(--muted);
            line-height: 1.5;
            margin-bottom: 28px;
            max-width: 90%;
        }

        .state-error-detail {
            font-size: 0.75rem;
            color: var(--muted-2);
            margin-bottom: 28px;
            background: rgba(255,255,255,0.03);
            padding: 6px 12px;
            border-radius: 6px;
            font-family: monospace;
        }

        .success-summary {
            width: 100%;
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 18px 20px;
            text-align: left;
            margin-bottom: 28px;
        }

        .success-summary-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid var(--border);
        }
        .success-summary-item:last-child { border-bottom: none; padding-bottom: 0; }
        .success-summary-item:first-child { padding-top: 0; }
        
        .success-summary-label { font-size: 0.78rem; color: var(--muted); font-weight: 500; }
        .success-summary-value { font-size: 0.85rem; font-weight: 700; color: var(--text); display: flex; align-items: center; gap: 8px; }

        .copy-id-btn {
            background: rgba(var(--primary-rgb),0.1);
            border: none;
            color: var(--primary);
            cursor: pointer;
            width: 26px;
            height: 26px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            transition: all 0.2s;
        }
        .copy-id-btn:hover { background: var(--primary); color: #fff; }

        .btn-secondary-link {
            background: transparent;
            border: none;
            color: var(--muted);
            font-size: 0.82rem;
            font-weight: 600;
            cursor: pointer;
            margin-top: 16px;
            text-decoration: none;
            display: inline-block;
            transition: color 0.2s;
        }
        .btn-secondary-link:hover { color: var(--text); }

        /* Ã¢â€â‚¬Ã¢â€â‚¬ RESPONSIVE Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬ */
        @media (max-width: 520px) {
            body { padding: 16px 12px 48px; }
            .head { padding: 20px 18px 16px; }
            .summary, .method-section, .form-section, .submit-area, .payment-result { padding-left: 18px; padding-right: 18px; }
            .amount-value { font-size: 1.4rem; }
            .method-tab { padding: 10px 4px; font-size: 0.65rem; }
        }
    </style>
</head>
<body>
@php
    $charge       = $link->charge;
    $subscription = $link->subscription;
    $money        = 'R$ ' . number_format((float) $link->amount, 2, ',', '.');
    $payable      = $link->isPubliclyPayable();
    $activePaymentMethodCodes = $activePaymentMethodCodes ?? [];
    $availablePaymentMethods = $availablePaymentMethods ?? collect();
    $methodPresentations = $availablePaymentMethods->keyBy('code');
    $allowedMethods = collect($link->allowed_payment_methods ?: [$link->payment_method])
        ->intersect($activePaymentMethodCodes)
        ->values();
    $customization  = $link->metadata['customization'] ?? [];
    $logoPath       = $customization['logo'] ?? null;
    $primaryColor   = $customization['primary_color'] ?? '#7C3AED';
    $bgTheme        = $customization['bg_theme'] ?? 'dark';
    $bgColor        = $customization['bg_color'] ?? null;
    $sellerName     = $link->user?->username ?: 'OriginPay';
    $sellerInitial  = strtoupper(substr($sellerName, 0, 1));
    $expiresAt      = $link->expires_at;

    $hex = ltrim($primaryColor, '#');
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    $primaryRgb = "$r,$g,$b";
@endphp

@if($bgTheme === 'light')
    <style>body{background:#F5F5FA;}:root{--bg:#F5F5FA;--surface:#FFFFFF;--surface2:#F0F0F7;--border:rgba(0,0,0,0.09);--text:#0F0F1A;--muted:#6E6E85;}</style>
@elseif($bgTheme === 'custom' && $bgColor)
    <style>body{background:{{ $bgColor }};}</style>
@endif

@if($primaryColor !== '#7C3AED')
    <style>:root{--primary:{{ $primaryColor }};--primary-rgb:{{ $primaryRgb }};}</style>
@endif

<div class="checkout">
    <div class="card">

        {{-- Ã¢â€â‚¬Ã¢â€â‚¬ HEAD Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬ --}}
        <div class="head">
            <div class="head-top">
                @if($logoPath)
                <div class="seller-avatar">
                    <img src="{{ asset('storage/' . $logoPath) }}" alt="{{ $sellerName }}">
                </div>
                @else
                <div class="seller-avatar" style="background:transparent;">
                    <img src="{{ asset('frontend/images/originpay/originpay-app-icon.svg') }}" alt="OriginPay" style="width:48px;height:48px;object-fit:contain;">
                </div>
                @endif
                <div class="head-info">
                    <div class="product-name">{{ $link->title }}</div>
                    <div class="seller-name">{{ $sellerName }}</div>
                    @if($link->description)
                        <div style="font-size:0.72rem; color:var(--muted); margin-top:5px;">{{ $link->description }}</div>
                    @endif
                </div>
                <div class="head-amount">
                    <div class="amount-value">
                        {{ $money }}
                        @if($link->type === 'subscription' && isset($link->metadata['interval']))
                            <span style="font-size: 0.9rem; font-weight: 600; color: var(--muted); display: inline-block; margin-left: 4px;">
                                / {{ $link->metadata['interval'] === 'month' ? 'mês' : ($link->metadata['interval'] === 'year' ? 'ano' : ($link->metadata['interval'] === 'week' ? 'sem' : 'dia')) }}
                            </span>
                        @endif
                    </div>
                    <div class="amount-currency">{{ $link->currency }}</div>
                </div>
            </div>

            <div class="head-badges">
                @if($payable)
                    <span class="badge badge-pending"><i class="fas fa-clock" style="font-size:0.7rem;"></i> Aguardando pagamento</span>
                @elseif($link->status === 'paid')
                    <span class="badge badge-paid"><i class="fas fa-check-circle"></i> Pago</span>
                @else
                    <span class="badge badge-blocked"><i class="fas fa-ban"></i> indispon&iacute;vel</span>
                @endif

                @if($payable && $expiresAt)
                    <span class="countdown">
                        <i class="fas fa-clock"></i>
                        Expira em <span class="countdown-timer" id="cdown" data-expires="{{ $expiresAt->timestamp }}">--:--:--</span>
                    </span>
                @endif
            </div>
        </div>

        {{-- Ã¢â€â‚¬Ã¢â€â‚¬ SUMMARY Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬ --}}
        <div class="summary">
            @if($link->type === 'subscription' && isset($link->metadata['interval']))
            <div class="detail-item">
                <span class="detail-label">Recorrência</span>
                <span class="detail-value">A cada {{ $link->metadata['interval_count'] ?? 1 }} {{ $link->metadata['interval'] === 'month' ? 'mês(es)' : ($link->metadata['interval'] === 'year' ? 'ano(s)' : ($link->metadata['interval'] === 'week' ? 'semana(s)' : 'dia(s)')) }}</span>
            </div>
            @endif
        </div>

        {{-- â”€â”€ LINK UNAVAILABLE â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ --}}
        @if(!$payable && !$charge)
            <div class="blocked-box">
                <strong>Este link n&atilde;o est&aacute; dispon&iacute;vel para pagamento.</strong>
                <div style="color:var(--muted);font-size:0.8rem;margin-top:6px;">Ele pode estar pago, expirado ou cancelado.</div>
            </div>
        @endif

        {{-- â”€â”€ PROCESSING STATE (HIDDEN BY DEFAULT) â”€â”€ --}}
        <div class="state-container" id="state-processing">
            <div class="state-icon processing">
                <div class="spinner"></div>
            </div>
            <div class="state-title">Processando seu pagamento...</div>
            <div class="state-desc">Isso pode levar alguns segundos. Por favor, n&atilde;o feche esta tela.</div>
        </div>

        {{-- â”€â”€ PAYMENT FORM â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ --}}
        @if($payable && !$charge && $allowedMethods->isNotEmpty())
        <div id="payment-flow-container">
            <form method="POST" action="{{ route('payment-links.public.submit', $link->slug) }}" id="checkout-form">
                @csrf

            {{-- Method Tabs --}}
            <div class="method-section">
                <div class="method-label">Como deseja pagar?</div>
                <div class="method-tabs" id="method-tabs">
                    @foreach($allowedMethods as $method)
                        @php
                            $presentation = $methodPresentations->get($method);
                        @endphp
                        <div class="method-tab {{ $loop->first ? 'active' : '' }}" data-method="{{ $method }}" onclick="selectMethod('{{ $method }}')">
                            <i class="{{ $presentation['icon_class'] ?? 'fas fa-wallet' }}"></i>{{ $presentation['label'] ?? str($method)->headline() }}
                        </div>
                    @endforeach
                </div>
                <input type="hidden" name="payment_method" id="payment_method" value="{{ old('payment_method', $allowedMethods->first()) }}">
            </div>

            {{-- Form Panels --}}
            <div class="form-section">

                @if($errors->any())
                    <div class="error-notice">
                        <i class="fas fa-exclamation-circle"></i>
                        Revise os dados e tente novamente.
                    </div>
                @endif

                {{-- PIX Panel --}}
                <div class="form-panel {{ (old('payment_method', $allowedMethods->first()) === 'pix') ? 'active' : '' }}" id="panel-pix">
                    <div class="form-grid">
                        <div class="field">
                            <label>Nome completo</label>
                            <div class="input-wrap">
                                <i class="fas fa-user"></i>
                                <input type="text" name="customer_name" value="{{ old('customer_name') }}" placeholder="Jo&atilde;o da Silva" required autocomplete="name">
                            </div>
                        </div>
                        <div class="field">
                            <label>E-mail</label>
                            <div class="input-wrap">
                                <i class="fas fa-envelope"></i>
                                <input type="email" name="customer_email" value="{{ old('customer_email') }}" placeholder="joao@email.com" required autocomplete="email">
                            </div>
                        </div>
                        <div class="field">
                            <label>CPF / CNPJ</label>
                            <div class="input-wrap">
                                <i class="fas fa-id-card"></i>
                                <input type="text" name="customer_document" id="doc-pix" value="{{ old('customer_document') }}" placeholder="000.000.000-00" required maxlength="18">
                            </div>
                        </div>
                        <div class="field">
                            <label>Telefone (opcional)</label>
                            <div class="input-wrap">
                                <i class="fas fa-phone"></i>
                                <input type="text" name="customer_phone" id="phone-pix" value="{{ old('customer_phone') }}" placeholder="(11) 99999-9999">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- CARD Panel --}}
                <div class="form-panel {{ (old('payment_method', $allowedMethods->first()) === 'card') ? 'active' : '' }}" id="panel-card">
                    <div class="card-preview-wrap">
                        <div class="card-preview" id="card-preview-el">
                            <div class="card-front">
                                <div class="card-chip"></div>
                                <div class="card-number-display" id="card-num-display">&bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull;</div>
                                <div class="card-meta">
                                    <div>
                                        <div class="card-meta-label">Nome impresso</div>
                                        <div class="card-meta-val" id="card-name-display">NOME NO CART&Atilde;O</div>
                                    </div>
                                    <div>
                                        <div class="card-meta-label">Validade</div>
                                        <div class="card-meta-val" id="card-exp-display">MM/AA</div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-back">
                                <div class="card-cvv-strip" id="card-cvv-display">&bull;&bull;&bull;</div>
                            </div>
                        </div>
                    </div>
                    <div class="form-grid">
                        <div class="field full">
                            <label>N&uacute;mero do Cart&atilde;o</label>
                            <div class="input-wrap">
                                <i class="far fa-credit-card"></i>
                                <input type="text" name="card_number" id="card-number-input" placeholder="0000 0000 0000 0000" maxlength="19" autocomplete="cc-number">
                            </div>
                        </div>
                        <div class="field">
                            <label>Validade</label>
                            <div class="input-wrap">
                                <i class="fas fa-calendar-alt"></i>
                                <input type="text" name="card_expiry" id="card-exp-input" placeholder="MM/AA" maxlength="5" autocomplete="cc-exp">
                            </div>
                        </div>
                        <div class="field">
                            <label>CVV</label>
                            <div class="input-wrap">
                                <i class="fas fa-lock"></i>
                                <input type="text" name="card_cvv" id="card-cvv-input" placeholder="&bull;&bull;&bull;" maxlength="4" autocomplete="cc-csc">
                            </div>
                        </div>
                        <div class="field full">
                            <label>Nome impresso no Cart&atilde;o</label>
                            <div class="input-wrap">
                                <i class="fas fa-user"></i>
                                <input type="text" name="card_holder_name" id="card-name-input" placeholder="NOME SOBRENOME" autocomplete="cc-name" style="text-transform:uppercase;">
                            </div>
                        </div>
                        <div class="field full" style="border-top:1px solid var(--border);padding-top:12px;margin-top:2px;">
                            <label>Nome completo</label>
                            <div class="input-wrap">
                                <i class="fas fa-user"></i>
                                <input type="text" name="customer_name" value="{{ old('customer_name') }}" placeholder="Jo&atilde;o da Silva" required autocomplete="name">
                            </div>
                        </div>
                        <div class="field">
                            <label>E-mail</label>
                            <div class="input-wrap">
                                <i class="fas fa-envelope"></i>
                                <input type="email" name="customer_email" value="{{ old('customer_email') }}" placeholder="joao@email.com" required autocomplete="email">
                            </div>
                        </div>
                        <div class="field">
                            <label>CPF / CNPJ</label>
                            <div class="input-wrap">
                                <i class="fas fa-id-card"></i>
                                <input type="text" name="customer_document" id="doc-card" value="{{ old('customer_document') }}" placeholder="000.000.000-00" required maxlength="18">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- BOLETO Panel --}}
                <div class="form-panel {{ (old('payment_method', $allowedMethods->first()) === 'boleto') ? 'active' : '' }}" id="panel-boleto">
                    <div class="form-grid">
                        <div class="field">
                            <label>Nome completo</label>
                            <div class="input-wrap">
                                <i class="fas fa-user"></i>
                                <input type="text" name="customer_name" value="{{ old('customer_name') }}" placeholder="Jo&atilde;o da Silva" required autocomplete="name">
                            </div>
                        </div>
                        <div class="field">
                            <label>E-mail</label>
                            <div class="input-wrap">
                                <i class="fas fa-envelope"></i>
                                <input type="email" name="customer_email" value="{{ old('customer_email') }}" placeholder="joao@email.com" required autocomplete="email">
                            </div>
                        </div>
                        <div class="field">
                            <label>CPF / CNPJ</label>
                            <div class="input-wrap">
                                <i class="fas fa-id-card"></i>
                                <input type="text" name="customer_document" id="doc-boleto" value="{{ old('customer_document') }}" placeholder="000.000.000-00" required maxlength="18">
                            </div>
                        </div>
                        <div class="field">
                            <label>Telefone (opcional)</label>
                            <div class="input-wrap">
                                <i class="fas fa-phone"></i>
                                <input type="text" name="customer_phone" id="phone-boleto" value="{{ old('customer_phone') }}" placeholder="(11) 99999-9999">
                            </div>
                        </div>
                    </div>
                    {{-- Address Accordion --}}
                    <div class="address-toggle" id="addr-toggle" onclick="toggleAddress()">
                        <span><i class="fas fa-map-marker-alt" style="margin-right:7px;color:var(--primary);"></i>Endere&ccedil;o de cobran&ccedil;a</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="address-body" id="addr-body">
                        <div class="form-grid" style="padding-top:4px;">
                            <div class="field">
                                <label>CEP</label>
                                <div class="input-wrap">
                                    <i class="fas fa-map-pin"></i>
                                    <input type="text" name="address_zip" id="cep-input" value="{{ old('address_zip') }}" placeholder="00000-000" maxlength="9">
                                </div>
                            </div>
                            <div class="field full">
                                <label>Endere&ccedil;o</label>
                                <div class="input-wrap">
                                    <i class="fas fa-road"></i>
                                    <input type="text" name="address_line" id="addr-line" value="{{ old('address_line') }}" placeholder="Rua, N&uacute;mero, complemento">
                                </div>
                            </div>
                            <div class="field">
                                <label>Cidade</label>
                                <div class="input-wrap">
                                    <i class="fas fa-city"></i>
                                    <input type="text" name="address_city" id="addr-city" value="{{ old('address_city') }}" placeholder="S&atilde;o Paulo">
                                </div>
                            </div>
                            <div class="field">
                                <label>Estado (UF)</label>
                                <div class="input-wrap">
                                    <i class="fas fa-flag"></i>
                                    <input type="text" name="address_state" id="addr-state" value="{{ old('address_state') }}" placeholder="SP" maxlength="2">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Submit --}}
            <div class="submit-area">
                <button type="submit" class="btn-pay" id="btn-pay">
                    <i class="fas fa-lock" style="font-size:0.85rem;"></i>
                    <span id="btn-pay-text">{{ $link->type === 'subscription' ? 'Assinar por' : 'Pagar' }} {{ $money }}</span>
                </button>
                <div class="seal" id="security-seal">
                    <i class="fas fa-shield-halved"></i>
                    Pagamento seguro via <a href="https://originpay.com.br" target="_blank" rel="noopener">OriginPay</a>
                </div>
            </div>
            </form>
        </div>
        @endif

        @if($payable && !$charge && $allowedMethods->isEmpty())
            <div class="blocked-box">
                <strong>Nenhum metodo de pagamento esta disponivel.</strong>
                <div style="color:var(--muted);font-size:0.8rem;margin-top:6px;">Entre em contato com o vendedor ou administrador para liberar um metodo de pagamento.</div>
            </div>
        @endif

        {{-- ---------------- POST-SUBMIT RESULT ---------------- --}}
        @if($charge)
            @php
                $status = strtolower($charge->status?->value ?? $charge->status);
                $isSuccess = in_array($status, ['paid', 'approved', 'success']);
                $isError = in_array($status, ['failed', 'error', 'refused', 'canceled']);
            @endphp

            @if($isSuccess)
                {{-- SUCCESS STATE --}}
                <div class="state-container" style="display: flex;">
                    <div class="state-icon success">
                        <i class="fas fa-check"></i>
                    </div>
                    <div class="state-title">Pagamento confirmado!</div>
                    <div class="state-desc">Sua transa&ccedil;&atilde;o foi processada com sucesso e o vendedor j&aacute; foi notificado.</div>
                    
                    <div class="success-summary">
                        <div class="success-summary-item">
                            <span class="success-summary-label">Valor pago</span>
                            <span class="success-summary-value">{{ $money }}</span>
                        </div>
                        <div class="success-summary-item">
                            <span class="success-summary-label">M&eacute;todo</span>
                            <span class="success-summary-value" style="text-transform: capitalize;">
                                @if($link->payment_method === 'pix') <i class="fab fa-pix text-muted" style="margin-right:4px;"></i> @endif
                                @if($link->payment_method === 'card') <i class="far fa-credit-card text-muted" style="margin-right:4px;"></i> @endif
                                @if($link->payment_method === 'boleto') <i class="fas fa-barcode text-muted" style="margin-right:4px;"></i> @endif
                                {{ $link->payment_method }}
                            </span>
                        </div>
                        <div class="success-summary-item">
                            <span class="success-summary-label">ID da Transa&ccedil;&atilde;o</span>
                            <span class="success-summary-value">
                                <span id="txn-id">{{ $charge->trx ?? $charge->id }}</span>
                                <button type="button" class="copy-id-btn" onclick="copyTxnId(this)" title="Copiar ID">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </span>
                        </div>
                    </div>

                    @if(!empty($link->success_url))
                        <a href="{{ $link->success_url }}" class="btn-pay" style="text-decoration:none;">Voltar para o site</a>
                    @else
                        <button type="button" class="btn-pay" onclick="window.close()" style="margin-bottom:12px;">Concluir</button>
                        <div style="font-size:0.75rem; color:var(--muted);">Voc&ecirc; j&aacute; pode fechar esta janela.</div>
                    @endif
                    
                    <button type="button" class="btn-secondary-link"><i class="far fa-envelope" style="margin-right: 4px;"></i> Enviar comprovante por e-mail</button>
                </div>
                
                <div class="seal" style="padding-bottom:20px;">
                    <i class="fas fa-shield-halved"></i>
                    Pagamento seguro via <a href="https://originpay.com.br" target="_blank" rel="noopener">OriginPay</a>
                </div>

            @elseif($isError)
                {{-- ERROR STATE --}}
                <div class="state-container" style="display: flex;">
                    <div class="state-icon error">
                        <i class="fas fa-times"></i>
                    </div>
                    <div class="state-title">N&atilde;o foi poss&iacute;vel processar</div>
                    <div class="state-desc">
                        @if(isset($charge->error_message))
                            {{ $charge->error_message }}
                        @else
                            Ocorreu um problema ao processar seu pagamento. Verifique seus dados ou tente outro m&eacute;todo.
                        @endif
                    </div>

                    <div class="state-error-detail">
                        C&oacute;digo: {{ $charge->error_code ?? 'ERR_PAYMENT_REFUSED' }}
                    </div>

                    <button type="button" class="btn-pay" onclick="history.back()">
                        <i class="fas fa-undo-alt" style="margin-right: 4px;"></i> Tentar novamente
                    </button>

                    @if(!empty($link->failure_url))
                        <a href="{{ $link->failure_url }}" class="btn-secondary-link">Voltar para o site da loja</a>
                    @endif
                </div>

                <div class="seal" style="padding-bottom:20px;">
                    <i class="fas fa-shield-halved"></i>
                    Pagamento seguro via <a href="https://originpay.com.br" target="_blank" rel="noopener">OriginPay</a>
                </div>

            @else
                {{-- PENDING STATE (PIX/BOLETO) --}}
                <div class="payment-result" style="padding-top:16px;">
                    <div class="detail-item">
                        <span class="detail-label">Status da cobran&ccedil;a</span>
                        <span class="detail-value" style="color:#FCD34D;">Aguardando pagamento</span>
                    </div>

                    @if($payable)
                        @if($link->payment_method === 'pix')
                            <div class="qr-box" style="margin-top:14px;">
                                @if($charge->qr_code)
                                    <img class="qr-img" src="{{ $charge->qr_code }}" alt="QR Code Pix">
                                @endif
                                @if($charge->pix_copy_paste)
                                    <div style="font-size:0.72rem;color:var(--muted);margin-bottom:4px;">Pix copia e cola</div>
                                    <div class="copy-area" id="copy-data">{{ $charge->pix_copy_paste }}</div>
                                    <button class="btn-secondary" type="button" onclick="copyAndFeedback(this)">
                                        <i class="fas fa-copy"></i> Copiar c&oacute;digo
                                    </button>
                                @endif
                            </div>
                        @endif

                        @if($link->payment_method === 'boleto')
                            @if($charge->digitable_line)
                                <div class="copy-area" id="copy-data" style="margin-top:14px;">{{ $charge->digitable_line }}</div>
                            @endif
                            <div class="actions-row">
                                @if($charge->boleto_url)
                                    <a class="btn-primary-alt" href="{{ $charge->boleto_url }}" target="_blank"><i class="fas fa-external-link-alt"></i> Abrir boleto</a>
                                @endif
                                @if($charge->boleto_pdf_url)
                                    <a class="btn-secondary" href="{{ $charge->boleto_pdf_url }}" target="_blank"><i class="fas fa-file-pdf"></i> Baixar PDF</a>
                                @endif
                                @if($charge->digitable_line)
                                    <button class="btn-secondary" onclick="copyAndFeedback(this)"><i class="fas fa-copy"></i> Copiar c&oacute;digo</button>
                                @endif
                            </div>
                        @endif

                        @if($link->payment_method !== 'pix' && $link->payment_method !== 'boleto')
                            @if($charge->payment_link)
                                <div style="margin-top:14px;">
                                    <a class="btn-pay" style="text-decoration:none;display:flex;" href="{{ $charge->payment_link }}" target="_blank">
                                        <i class="far fa-credit-card" style="font-size:0.85rem;"></i> Pagar com Cart&atilde;o
                                    </a>
                                </div>
                            @else
                                <div style="color:var(--muted);font-size:0.82rem;margin-top:14px;">Pagamento por Cart&atilde;o indispon&iacute;vel para este link.</div>
                            @endif
                        @endif

                    @else
                        <div style="color:var(--muted);font-size:0.82rem;margin-top:10px;">Os dados de pagamento n&atilde;o s&atilde;o exibidos para links indispon&iacute;veis.</div>
                    @endif
                </div>

                <div class="seal" style="padding-bottom:20px;">
                    <i class="fas fa-shield-halved"></i>
                    Pagamento seguro via <a href="https://originpay.com.br" target="_blank" rel="noopener">OriginPay</a>
                </div>
            @endif
        @endif

        @if(!$payable && !$charge)
        <div class="seal" style="padding:16px 28px 20px;">
            <i class="fas fa-shield-halved"></i>
            Powered by OriginPay
        </div>
        @endif

    </div>{{-- .card --}}
</div>{{-- .checkout --}}

<script>
// -------------------- Method Tab Switcher --------------------
function selectMethod(method) {
    document.getElementById('payment_method').value = method;

    document.querySelectorAll('.method-tab').forEach(t => t.classList.toggle('active', t.dataset.method === method));
    document.querySelectorAll('.form-panel').forEach(p => p.classList.remove('active'));
    const panel = document.getElementById('panel-' + method);
    if (panel) panel.classList.add('active');
}

// -------------------- Address Accordion --------------------
function toggleAddress() {
    const toggle = document.getElementById('addr-toggle');
    const body   = document.getElementById('addr-body');
    toggle.classList.toggle('open');
    body.classList.toggle('open');
}

// -------------------- Card Preview --------------------
const numInput  = document.getElementById('card-number-input');
const expInput  = document.getElementById('card-exp-input');
const cvvInput  = document.getElementById('card-cvv-input');
const nameInput = document.getElementById('card-name-input');
const preview   = document.getElementById('card-preview-el');

if(numInput) {
    numInput.addEventListener('input', function() {
        let v = this.value.replace(/\D/g, '').substring(0, 16);
        this.value = v.replace(/(.{4})/g, '$1 ').trim();
        const parts = v.padEnd(16, '\u2022').replace(/(.{4})/g, '$1 ').trim();
        document.getElementById('card-num-display').textContent = parts;
    });

    expInput.addEventListener('input', function() {
        let v = this.value.replace(/\D/g, '').substring(0, 4);
        if(v.length > 2) v = v.substring(0,2) + '/' + v.substring(2);
        this.value = v;
        document.getElementById('card-exp-display').textContent = v || 'MM/AA';
    });

    cvvInput.addEventListener('focus',  () => preview && preview.classList.add('flipped'));
    cvvInput.addEventListener('blur',   () => preview && preview.classList.remove('flipped'));
    cvvInput.addEventListener('input',  function() {
        document.getElementById('card-cvv-display').textContent = this.value || '\u2022\u2022\u2022';
    });

    nameInput.addEventListener('input', function() {
        document.getElementById('card-name-display').textContent = this.value.toUpperCase() || 'NOME NO CART\u00C3O';
    });
}

// Ã¢â€â‚¬Ã¢â€â‚¬ Document Mask Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬
function maskDoc(el) {
    if(!el) return;
    el.addEventListener('input', function() {
        let v = this.value.replace(/\D/g,'');
        if(v.length <= 11) {
            v = v.replace(/(\d{3})(\d)/, '$1.$2')
                 .replace(/(\d{3})(\d)/, '$1.$2')
                 .replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        } else {
            v = v.substring(0,14)
                 .replace(/(\d{2})(\d)/, '$1.$2')
                 .replace(/(\d{3})(\d)/, '$1.$2')
                 .replace(/(\d{3})(\d)/, '$1/$2')
                 .replace(/(\d{4})(\d{1,2})$/, '$1-$2');
        }
        this.value = v;
    });
}
maskDoc(document.getElementById('doc-pix'));
maskDoc(document.getElementById('doc-card'));
maskDoc(document.getElementById('doc-boleto'));

// Ã¢â€â‚¬Ã¢â€â‚¬ Phone Mask Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬
function maskPhone(el) {
    if(!el) return;
    el.addEventListener('input', function() {
        let v = this.value.replace(/\D/g,'').substring(0,11);
        if(v.length > 6) v = '(' + v.substring(0,2) + ') ' + v.substring(2,7) + '-' + v.substring(7);
        else if(v.length > 2) v = '(' + v.substring(0,2) + ') ' + v.substring(2);
        this.value = v;
    });
}
maskPhone(document.getElementById('phone-pix'));
maskPhone(document.getElementById('phone-boleto'));

// Ã¢â€â‚¬Ã¢â€â‚¬ CEP Autocomplete Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬
const cepInput = document.getElementById('cep-input');
if(cepInput) {
    cepInput.addEventListener('input', function() {
        let v = this.value.replace(/\D/g,'').substring(0,8);
        if(v.length > 5) v = v.substring(0,5) + '-' + v.substring(5);
        this.value = v;

        if(v.replace('-','').length === 8) {
            fetch('https://viacep.com.br/ws/' + v.replace('-','') + '/json/')
                .then(r => r.json())
                .then(d => {
                    if(!d.erro) {
                        document.getElementById('addr-line').value = d.logradouro || '';
                        document.getElementById('addr-city').value = d.localidade || '';
                        document.getElementById('addr-state').value = d.uf || '';
                        if(!document.getElementById('addr-body').classList.contains('open')) toggleAddress();
                    }
                }).catch(() => {});
        }
    });
}

// â”€â”€ Submit feedback â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
const form   = document.getElementById('checkout-form');
const btnPay = document.getElementById('btn-pay');
if(form) {
    form.addEventListener('submit', function() {
        btnPay.disabled = true;
        const flowContainer = document.getElementById('payment-flow-container');
        const processingState = document.getElementById('state-processing');
        if(flowContainer && processingState) {
            flowContainer.style.display = 'none';
            processingState.style.display = 'flex';
        }
    });
}

// â”€â”€ Copy TXN ID â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
function copyTxnId(btn) {
    const data = document.getElementById('txn-id');
    if(data) navigator.clipboard.writeText(data.innerText).then(() => {
        const orig = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i>';
        setTimeout(() => btn.innerHTML = orig, 2000);
    });
}

// Ã¢â€â‚¬Ã¢â€â‚¬ Copy feedback Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬
function copyAndFeedback(btn) {
    const data = document.getElementById('copy-data');
    if(data) navigator.clipboard.writeText(data.innerText).then(() => {
        const orig = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i> Copiado!';
        setTimeout(() => btn.innerHTML = orig, 2000);
    });
}

// Ã¢â€â‚¬Ã¢â€â‚¬ Countdown Timer Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬
const cdEl = document.getElementById('cdown');
if(cdEl) {
    const expires = parseInt(cdEl.dataset.expires, 10) * 1000;
    function updateCd() {
        const diff = expires - Date.now();
        if(diff <= 0) { cdEl.textContent = 'Expirado'; return; }
        const h = Math.floor(diff / 3600000);
        const m = Math.floor((diff % 3600000) / 60000);
        const s = Math.floor((diff % 60000) / 1000);
        cdEl.textContent = [h,m,s].map(n => String(n).padStart(2,'0')).join(':');
        setTimeout(updateCd, 1000);
    }
    updateCd();
}
</script>
</body>
</html>




