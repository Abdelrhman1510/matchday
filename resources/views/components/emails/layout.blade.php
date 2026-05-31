@props([
    'title' => 'tab3',
    'preheader' => '',
])
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="color-scheme" content="light dark">
    <meta name="supported-color-schemes" content="light dark">
    <title>{{ $title }}</title>

    <style>
        /* Reset */
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; outline: none; text-decoration: none; }
        body { margin: 0 !important; padding: 0 !important; width: 100% !important; }

        /* Mobile */
        @media screen and (max-width: 600px) {
            .container { width: 100% !important; max-width: 100% !important; }
            .px-mobile { padding-left: 24px !important; padding-right: 24px !important; }
            .py-mobile { padding-top: 32px !important; padding-bottom: 32px !important; }
            .otp-box { font-size: 32px !important; letter-spacing: 8px !important; padding: 20px 16px !important; }
            .h1 { font-size: 24px !important; line-height: 30px !important; }
        }

        /* Dark mode inbox tweaks (Apple Mail, iOS) */
        @media (prefers-color-scheme: dark) {
            .bg-card { background-color: #ffffff !important; }
            .text-dark { color: #0c0628 !important; }
            .text-muted { color: #4a4663 !important; }
        }
    </style>
</head>
<body style="margin:0; padding:0; background-color:#0c0628; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; -webkit-font-smoothing: antialiased;">

    {{-- Hidden preheader text (preview snippet in inbox list) --}}
    @if($preheader)
    <div style="display:none; font-size:1px; color:#0c0628; line-height:1px; max-height:0; max-width:0; opacity:0; overflow:hidden;">
        {{ $preheader }}
    </div>
    @endif

    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color:#0c0628;">
        <tr>
            <td align="center" style="padding:32px 16px;">

                {{-- Outer container --}}
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="600" class="container" style="max-width:600px; width:100%;">

                    {{-- Header / Logo --}}
                    <tr>
                        <td align="center" style="padding:0 0 24px 0;">
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="vertical-align:middle;">
                                        <img src="{{ asset('images/tab3_icon.png') }}" alt="tab3" width="48" height="48" style="display:block; width:48px; height:48px; border-radius:10px; border:0; outline:none; text-decoration:none;">
                                    </td>
                                    <td style="padding-left:14px; font-family:'Helvetica Neue', Helvetica, Arial, sans-serif; font-size:26px; font-weight:800; color:#ffffff; letter-spacing:-0.5px; vertical-align:middle;">
                                        tab3
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Card --}}
                    <tr>
                        <td class="bg-card" style="background-color:#ffffff; border-radius:16px; padding:48px 48px; box-shadow: 0 8px 32px rgba(0,0,0,0.2);">
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" class="px-mobile py-mobile">
                                <tr>
                                    <td style="font-family:'Helvetica Neue', Helvetica, Arial, sans-serif; color:#0c0628;">
                                        {{ $slot }}
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td align="center" style="padding:32px 24px 0 24px; font-family:'Helvetica Neue', Helvetica, Arial, sans-serif;">
                            <p style="margin:0 0 8px 0; font-size:13px; color:#8b87a3; line-height:20px;">
                                You are receiving this email because an account was created or a security action was requested on tab3.
                            </p>
                            <p style="margin:0 0 16px 0; font-size:13px; color:#8b87a3; line-height:20px;">
                                If you didn't expect this email, you can safely ignore it.
                            </p>
                            <p style="margin:0; font-size:12px; color:#5a5670;">
                                © {{ date('Y') }} tab3. All rights reserved.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
