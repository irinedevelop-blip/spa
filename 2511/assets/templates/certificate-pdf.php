<?php
/**
 * MEDI SIAM SPA — PDF-сертификат.
 * Размер: 210 × 99 мм.
 */

if (!defined('ABSPATH')) {
    exit;
}

/* =========================================================
   ДАННЫЕ
========================================================= */

$design = isset($data['design'])
    ? sanitize_key($data['design'])
    : 'gold';

if (!in_array($design, ['gold', 'dark', 'classic'], true)) {
    $design = 'gold';
}

$first_name = isset($data['first_name'])
    ? sanitize_text_field($data['first_name'])
    : '';

$last_name = isset($data['last_name'])
    ? sanitize_text_field($data['last_name'])
    : '';

$recipient = trim($first_name . ' ' . $last_name);

if ($recipient === '') {
    $recipient = 'Имя Получателя';
}

$amount_raw = isset($data['amount'])
    ? (float) $data['amount']
    : 0;

$amount_number = number_format(
    $amount_raw,
    0,
    '.',
    ' '
);

$number = isset($data['number'])
    ? sanitize_text_field($data['number'])
    : '';

if ($number === '') {
    $number = 'MS-0000-000000';
}

/* =========================================================
   BASE64
========================================================= */

$mscert_data_uri = static function ($relative_path, $mime) {
    $path = MSCERT_PATH . ltrim(
        (string) $relative_path,
        '/'
    );

    if (!is_file($path) || !is_readable($path)) {
        return '';
    }

    $contents = file_get_contents($path);

    if ($contents === false || $contents === '') {
        return '';
    }

    return 'data:'
        . $mime
        . ';base64,'
        . base64_encode($contents);
};

/* =========================================================
   ИЗОБРАЖЕНИЯ
========================================================= */

$logo_relative = 'assets/images/logo.webp';
$emblem_relative = 'assets/images/emblem.svg';

$logo_path = MSCERT_PATH . $logo_relative;

$logo = $mscert_data_uri(
    $logo_relative,
    'image/webp'
);

$emblem = $mscert_data_uri(
    $emblem_relative,
    'image/svg+xml'
);

/* =========================================================
   ПРОПОРЦИИ ЛОГОТИПА
========================================================= */

$logo_width = 67;
$logo_height = 47;

if (is_file($logo_path)) {
    $logo_size = @getimagesize($logo_path);

    if (
        is_array($logo_size) &&
        !empty($logo_size[0]) &&
        !empty($logo_size[1])
    ) {
        $original_width = (float) $logo_size[0];
        $original_height = (float) $logo_size[1];

        $logo_height = 47;

        $logo_width = $logo_height
            * ($original_width / $original_height);

        if ($logo_width > 82) {
            $logo_width = 82;

            $logo_height = $logo_width
                * ($original_height / $original_width);
        }
    }
}

/* =========================================================
   ШРИФТЫ
========================================================= */

$cormorant_regular = $mscert_data_uri(
    'assets/fonts/CormorantGaramond-Regular.ttf',
    'font/truetype'
);

$cormorant_medium = $mscert_data_uri(
    'assets/fonts/CormorantGaramond-Medium.ttf',
    'font/truetype'
);

$cormorant_italic = $mscert_data_uri(
    'assets/fonts/CormorantGaramond-Italic.ttf',
    'font/truetype'
);

$manrope_regular = $mscert_data_uri(
    'assets/fonts/Manrope-Regular.ttf',
    'font/truetype'
);

$manrope_medium = $mscert_data_uri(
    'assets/fonts/Manrope-Medium.ttf',
    'font/truetype'
);

$marcellus_regular = $mscert_data_uri(
    'assets/fonts/Marcellus-Regular.ttf',
    'font/truetype'
);
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">

    <meta
        http-equiv="Content-Type"
        content="text/html; charset=UTF-8"
    >

    <title>
        Подарочный сертификат <?php echo esc_html($number); ?>
    </title>

    <style>
        <?php if ($cormorant_regular): ?>
        @font-face {
            font-family: "Medi Cormorant";
            src: url("<?php echo $cormorant_regular; ?>")
                format("truetype");
            font-weight: 400;
            font-style: normal;
        }
        <?php endif; ?>

        <?php if ($cormorant_medium): ?>
        @font-face {
            font-family: "Medi Cormorant";
            src: url("<?php echo $cormorant_medium; ?>")
                format("truetype");
            font-weight: 500;
            font-style: normal;
        }
        <?php endif; ?>

        <?php if ($cormorant_italic): ?>
        @font-face {
            font-family: "Medi Cormorant";
            src: url("<?php echo $cormorant_italic; ?>")
                format("truetype");
            font-weight: 400;
            font-style: italic;
        }
        <?php endif; ?>

        <?php if ($manrope_regular): ?>
        @font-face {
            font-family: "Medi Manrope";
            src: url("<?php echo $manrope_regular; ?>")
                format("truetype");
            font-weight: 400;
            font-style: normal;
        }
        <?php endif; ?>

        <?php if ($manrope_medium): ?>
        @font-face {
            font-family: "Medi Manrope";
            src: url("<?php echo $manrope_medium; ?>")
                format("truetype");
            font-weight: 500;
            font-style: normal;
        }
        <?php endif; ?>

        <?php if ($marcellus_regular): ?>
        @font-face {
            font-family: "Medi Marcellus";
            src: url("<?php echo $marcellus_regular; ?>")
                format("truetype");
            font-weight: 400;
            font-style: normal;
        }
        <?php endif; ?>

        @page {
            size: 595.28pt 280.63pt;
            margin: 0;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            width: 595.28pt;
            height: 280.63pt;
            margin: 0;
            padding: 0;
            overflow: hidden;
        }

        body {
            font-family:
                "Medi Manrope",
                "DejaVu Sans",
                Arial,
                sans-serif;
        }

        .certificate {
            position: relative;
            width: 595.28pt;
            height: 280.63pt;
            overflow: hidden;
            color: #5d4935;
            background: #f2e8d9;
        }

        .certificate--gold {
            color: #5d4935;
            background: #f2e8d9;
        }

        .certificate--dark {
            color: #f1e8da;
            background: #1b130d;
        }

        .certificate--classic {
            color: #322a23;
            background: #f3efe8;
        }

        /* СВЕЧЕНИЕ */

        .light {
            position: absolute;
            z-index: 0;
            left: -70pt;
            top: -125pt;
            width: 470pt;
            height: 470pt;
            border-radius: 235pt;
            background: #fffaf2;
            opacity: .31;
        }

        .certificate--dark .light {
            background: #d8b879;
            opacity: .04;
        }

        .certificate--classic .light {
            display: none;
        }

        /* ЭМБЛЕМА */

        .emblem-window {
            position: absolute;
            z-index: 1;
            right: 0;
            top: 0;
            width: 205pt;
            height: 280.63pt;
            overflow: hidden;
        }

        .emblem {
            position: absolute;
            right: -28pt;
            top: -16pt;
            width: 230pt;
            height: 315pt;
            margin: 0;
            padding: 0;
            opacity: .97;
        }

        .certificate--dark .emblem {
            opacity: .92;
        }

        .certificate--classic .emblem {
            right: -12pt;
            opacity: .14;
        }

        /*
         * Закрывает прямую у левой границы SVG.
         */

        .emblem-edge-mask {
            position: absolute;
            z-index: 2;
            right: 198pt;
            top: 0;
            width: 7pt;
            height: 280.63pt;
            background: #f2e8d9;
        }

        .certificate--dark .emblem-edge-mask {
            background: #1b130d;
        }

        .certificate--classic .emblem-edge-mask {
            background: #f3efe8;
        }

        .emblem-fallback {
            position: absolute;
            z-index: 1;
            right: -18pt;
            top: 4pt;
            width: 220pt;
            color: #b58b4c;
            font-family:
                "Medi Cormorant",
                "DejaVu Serif",
                Georgia,
                serif;
            font-size: 170pt;
            line-height: 1;
            text-align: center;
            opacity: .16;
        }

        .certificate--dark .emblem-fallback {
            color: #d9bd84;
        }

        /* ЗАГОЛОВОК */

        .title {
            position: absolute;
            z-index: 10;
            left: 47pt;
            top: 29pt;
            width: 330pt;
            margin: 0;
            padding: 0;
            color: #8f6c3c;
            font-family:
                "Medi Cormorant",
                "DejaVu Serif",
                Georgia,
                serif;
            font-size: 27pt;
            font-weight: 500;
            line-height: 1;
            white-space: nowrap;
        }

        .certificate--dark .title {
            color: #d9bd84;
        }

        .certificate--classic .title {
            color: #211914;
        }

        /* НОМИНАЛ */

        .nominal-label {
            position: absolute;
            z-index: 10;
            left: 47pt;
            top: 72pt;
            margin: 0;
            padding: 0;
            color: #9a8268;
            font-family:
                "Medi Manrope",
                "DejaVu Sans",
                Arial,
                sans-serif;
            font-size: 5.5pt;
            font-weight: 500;
            line-height: 1;
            text-transform: uppercase;
        }

        .certificate--dark .nominal-label {
            color: #ad9a80;
        }

        .certificate--classic .nominal-label {
            color: #8e8275;
        }

        .amount {
            position: absolute;
            z-index: 10;
            left: 46pt;
            top: 82pt;
            width: 330pt;
            margin: 0;
            padding: 0;
            color: #8f6c3c;
            font-family:
                "Medi Marcellus",
                "Medi Cormorant",
                "DejaVu Serif",
                Georgia,
                serif;
            font-size: 39pt;
            font-weight: 400;
            line-height: 1;
            white-space: nowrap;
        }

        .certificate--dark .amount {
            color: #d9bd84;
        }

        .certificate--classic .amount {
            color: #211914;
        }

        .currency {
            font-family:
                "DejaVu Sans",
                Arial,
                sans-serif;
            font-size: 30pt;
            font-weight: 400;
            vertical-align: 3pt;
        }

        /* ПОЛУЧАТЕЛЬ */

        .recipient-row {
            position: absolute;
            z-index: 15;
            left: 47pt;
            top: 148pt;
            width: 270pt;
            height: 25pt;
            margin: 0;
            padding: 0;
            white-space: nowrap;
        }

        .recipient-prefix {
            display: inline;
            margin: 0 8pt 0 0;
            padding: 0;
            color: #9a8268;
            font-family:
                "Medi Manrope",
                "DejaVu Sans",
                Arial,
                sans-serif;
            font-size: 6pt;
            font-weight: 400;
            line-height: 1;
            text-transform: lowercase;
        }

        .recipient-name {
            display: inline;
            margin: 0;
            padding: 0;
            color: #735a40;
            font-family:
                "Medi Cormorant",
                "DejaVu Serif",
                Georgia,
                serif;
            font-size: 15pt;
            font-weight: 400;
            font-style: italic;
            line-height: 1;
        }

        .certificate--dark .recipient-prefix {
            color: #a9977f;
        }

        .certificate--dark .recipient-name {
            color: #f1e8da;
        }

        .certificate--classic .recipient-prefix {
            color: #8e8275;
        }

        .certificate--classic .recipient-name {
            color: #322a23;
        }

        .recipient-line {
            position: absolute;
            z-index: 14;
            left: 47pt;
            top: 169pt;
            width: 188pt;
            height: .6pt;
            background: #bda98e;
            opacity: .55;
        }

        .certificate--dark .recipient-line {
            background: #d9bd84;
            opacity: .35;
        }

        .certificate--classic .recipient-line {
            background: #8e8275;
            opacity: .42;
        }

        /* ЛОГОТИП */

        .logo {
            position: absolute;
            z-index: 20;
            left: 47pt;
            bottom: 24pt;
            display: block;
            margin: 0;
            padding: 0;
        }

        .logo-fallback {
            position: absolute;
            z-index: 20;
            left: 47pt;
            bottom: 25pt;
            width: 74pt;
            color: #9b763f;
            font-family:
                "Medi Cormorant",
                "DejaVu Serif",
                Georgia,
                serif;
            font-size: 13pt;
            font-weight: 500;
            line-height: .88;
            text-align: center;
        }

        .logo-fallback span {
            display: block;
            margin-top: 3pt;
            font-family:
                "Medi Manrope",
                "DejaVu Sans",
                Arial,
                sans-serif;
            font-size: 5pt;
            line-height: 1;
            text-transform: uppercase;
        }

        .certificate--dark .logo-fallback {
            color: #d9bd84;
        }

        .certificate--classic .logo-fallback {
            color: #322a23;
        }

        /* НИЖНИЙ ТЕКСТ */

        .footer-copy {
            position: absolute;
            z-index: 20;
            left: 115pt;
            bottom: 25pt;
            width: 130pt;
            margin: 0;
            padding: 0;
            color: #6e655c;
            font-family:
                "Medi Manrope",
                "DejaVu Sans",
                Arial,
                sans-serif;
            font-size: 6.5pt;
            font-weight: 400;
            line-height: 1.45;
        }

        .certificate--dark .footer-copy {
            color: #b5a58d;
        }

        .certificate--classic .footer-copy {
            color: #776e65;
        }

        .site {
            color: #8f6c3c;
        }

        .certificate--dark .site {
            color: #d9bd84;
        }

        .certificate--classic .site {
            color: #322a23;
        }

        /* НОМЕР */

        .number {
            position: absolute;
            z-index: 20;
            left: 300pt;
            bottom: 24pt;
            margin: 0;
            padding: 0;
            color: #9a8268;
            font-family:
                "Medi Manrope",
                "DejaVu Sans",
                Arial,
                sans-serif;
            font-size: 6.5pt;
            font-weight: 400;
            line-height: 1;
            white-space: nowrap;
        }

        .certificate--dark .number {
            color: #ad9a80;
        }

        .certificate--classic .number {
            color: #776e65;
        }
    </style>
</head>

<body>
<div
    class="certificate certificate--<?php echo esc_attr($design); ?>"
>
    <div class="light"></div>

    <div class="emblem-window">
        <?php if ($emblem): ?>
            <img
                class="emblem"
                src="<?php echo esc_attr($emblem); ?>"
                alt=""
            >
        <?php else: ?>
            <div class="emblem-fallback">✦</div>
        <?php endif; ?>

        <div class="emblem-edge-mask"></div>
    </div>

    <div class="title">
        Подарочный сертификат
    </div>

    <div class="nominal-label">
        Номинал
    </div>

    <div class="amount">
        <?php echo esc_html($amount_number); ?>
        <span class="currency">₸</span>
    </div>

    <div class="recipient-row">
        <span class="recipient-prefix">для</span>

        <span class="recipient-name">
            <?php echo esc_html($recipient); ?>
        </span>
    </div>

    <div class="recipient-line"></div>

    <?php if ($logo): ?>
        <img
            class="logo"
            src="<?php echo esc_attr($logo); ?>"
            width="<?php echo esc_attr(round($logo_width, 2)); ?>"
            height="<?php echo esc_attr(round($logo_height, 2)); ?>"
            style="<?php
                echo esc_attr(
                    'width:'
                    . round($logo_width, 2)
                    . 'pt;'
                    . 'height:'
                    . round($logo_height, 2)
                    . 'pt;'
                );
            ?>"
            alt="Medi Siam SPA"
        >
    <?php else: ?>
        <div class="logo-fallback">
            Medi Siam
            <span>SPA</span>
        </div>
    <?php endif; ?>

    <div class="footer-copy">
        Время для себя —<br>
        в премиальном формате<br>
        <span class="site">spa.yourweb.kz</span>
    </div>

    <div class="number">
        № <?php echo esc_html($number); ?>
    </div>
</div>
</body>
</html>
