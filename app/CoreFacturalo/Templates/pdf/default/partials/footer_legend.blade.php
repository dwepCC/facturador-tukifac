@php
    $path_style = app_path('CoreFacturalo'.DIRECTORY_SEPARATOR.'Templates'.DIRECTORY_SEPARATOR.'pdf'.DIRECTORY_SEPARATOR.'style.css');
@endphp
<head>
    <link href="{{ $path_style }}" rel="stylesheet" />
</head>
<body>
<table class="full-width">
    <tr>
        <td class="text-center font-bold">Bienes transferidos y/o servicios prestados en la Amazonía para ser consumidos en la misma</td>
    </tr>
    <br>
</table>
<table class="full-width">
    <tr>
        <td class="text-center">
            <table cellpadding="0" cellspacing="0" style="border: 0;">
                <tr>
                    <td style="border: 0; padding: 0; vertical-align: middle; white-space: nowrap;">
                        <span style="font-size: 12px; font-weight: bold;">Elaborado por tukifac.pe</span>
                    </td>
                    <td style="border: 0; padding: 0; padding-left: 5px; vertical-align: middle;">
                        <a href="https://tukifac.pe" target="_blank">
                            <img src="/storage/logo.png" width="70" height="10" />
                        </a>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>