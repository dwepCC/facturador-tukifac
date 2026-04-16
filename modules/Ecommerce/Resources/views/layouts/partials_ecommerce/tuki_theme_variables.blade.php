@php
    $tukiEcommerceConfig = \App\Models\Tenant\ConfigurationEcommerce::first();
    $tukiRaw = $tukiEcommerceConfig && $tukiEcommerceConfig->color_ecommerce
        ? trim($tukiEcommerceConfig->color_ecommerce)
        : '#0d6efd';
    $tukiHex = strtoupper(ltrim($tukiRaw, '#'));
    if (strlen($tukiHex) === 3) {
        $tukiHex = $tukiHex[0].$tukiHex[0].$tukiHex[1].$tukiHex[1].$tukiHex[2].$tukiHex[2];
    }
    if (! preg_match('/^[0-9A-F]{6}$/', $tukiHex)) {
        $tukiHex = '0D6EFD';
    }
    $tukiR = hexdec(substr($tukiHex, 0, 2));
    $tukiG = hexdec(substr($tukiHex, 2, 2));
    $tukiB = hexdec(substr($tukiHex, 4, 2));
    $tukiLuminance = (0.299 * $tukiR + 0.587 * $tukiG + 0.114 * $tukiB) / 255;
    $tukiContrast = $tukiLuminance > 0.65 ? '#212529' : '#ffffff';

    $tukiRn = $tukiR / 255;
    $tukiGn = $tukiG / 255;
    $tukiBn = $tukiB / 255;
    $tukiMax = max($tukiRn, $tukiGn, $tukiBn);
    $tukiMin = min($tukiRn, $tukiGn, $tukiBn);
    $tukiDelta = $tukiMax - $tukiMin;
    $tukiLight = ($tukiMax + $tukiMin) / 2;
    if ($tukiDelta < 0.00001) {
        $tukiHue = 0;
        $tukiSat = 0;
    } else {
        $tukiSat = $tukiLight > 0.5 ? $tukiDelta / (2 - $tukiMax - $tukiMin) : $tukiDelta / ($tukiMax + $tukiMin);
        switch ($tukiMax) {
            case $tukiRn:
                $tukiHue = fmod((($tukiGn - $tukiBn) / $tukiDelta), 6);
                break;
            case $tukiGn:
                $tukiHue = (($tukiBn - $tukiRn) / $tukiDelta) + 2;
                break;
            default:
                $tukiHue = (($tukiRn - $tukiGn) / $tukiDelta) + 4;
                break;
        }
        $tukiHue *= 60;
        if ($tukiHue < 0) {
            $tukiHue += 360;
        }
    }
    $tukiPrimaryH = (int) round($tukiHue);
    $tukiPrimaryS = (int) round($tukiSat * 100);
    $tukiPrimaryL = (int) round($tukiLight * 100);
    $tukiOnPrimaryBorder = $tukiContrast === '#ffffff' ? 'rgba(255,255,255,0.22)' : 'rgba(15, 23, 42, 0.14)';
    $tukiOnPrimaryHoverSurface = $tukiContrast === '#ffffff' ? 'rgba(255,255,255,0.12)' : 'rgba(15, 23, 42, 0.08)';
    $tukiOnPrimaryMutedText = $tukiContrast === '#ffffff' ? 'rgba(255,255,255,0.88)' : 'rgba(33, 37, 41, 0.82)';
@endphp
<style id="tuki-ecommerce-theme-variables">
    :root {
        --tuki-color-primary: #{{ $tukiHex }};
        --tuki-color-primary-contrast: {{ $tukiContrast }};
        --primary-h: {{ $tukiPrimaryH }};
        --primary-s: {{ $tukiPrimaryS }}%;
        --primary-l: {{ $tukiPrimaryL }}%;
        --tuki-on-primary-border: {{ $tukiOnPrimaryBorder }};
        --tuki-on-primary-hover-surface: {{ $tukiOnPrimaryHoverSurface }};
        --tuki-on-primary-muted: {{ $tukiOnPrimaryMutedText }};
        --title-color: #001524;
        --subtitle-color: #60769a;
        --price-color: #001524;
    }
</style>
