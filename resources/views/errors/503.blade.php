<!DOCTYPE html>
<html lang="sd" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>سار سنڀال - Baakh</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Noto+Naskh+Arabic:wght@400;700&display=swap');

        body {
            margin: 0;
            padding: 0;
            background: #1A212D;
            color: #A4ADBE;
            font-family: 'Noto Naskh Arabic', serif;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            text-align: center;
        }

        main {
            max-width: 600px;
            padding: 2rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 2rem;
        }

        .icon-container {
            width: 120px;
            height: 120px;
            background: rgba(164, 173, 190, 0.1);
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 1rem;
            animation: pulse 2s infinite ease-in-out;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
                opacity: 0.8;
            }

            50% {
                transform: scale(1.05);
                opacity: 1;
            }

            100% {
                transform: scale(1);
                opacity: 0.8;
            }
        }

        h1 {
            font-size: 2.5rem;
            margin: 0;
            color: #ffffff;
        }

        p {
            font-size: 1.4rem;
            line-height: 1.8;
            margin: 0;
            color: #A4ADBE;
        }

        .brand {
            font-weight: bold;
            letter-spacing: 2px;
            color: #4F46E5;
            margin-top: 2rem;
            font-size: 1.2rem;
            text-transform: uppercase;
        }

        svg {
            width: 64px;
            height: 64px;
            fill: none;
            stroke: #4F46E5;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }
    </style>
</head>

<body>
    <main>
        <div class="icon-container">
            <svg viewBox="0 0 24 24">
                <path
                    d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z">
                </path>
            </svg>
        </div>
        <h1>اسان ويبسٽائيٽ تي ڪم ڪري رھيا آھيون</h1>
        <p>ٿورو وقت انتظار ڪريو. جلد واپس اينداسين.</p>
        <div class="brand">Baakh</div>
    </main>
</body>

</html>