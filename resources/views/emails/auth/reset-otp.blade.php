@php
    $userName = $userName ?? 'there';
    $otp = $otp ?? '------';
    $expiresInMinutes = $expiresInMinutes ?? 10;
@endphp

<x-emails.layout
    title="Reset your password — tab3"
    preheader="Your tab3 password reset code is {{ $otp }}. Valid for {{ $expiresInMinutes }} minutes."
>

    <h1 class="h1" style="margin:0 0 16px 0; font-size:28px; line-height:34px; font-weight:800; color:#0c0628; letter-spacing:-0.5px;">
        Reset your password
    </h1>

    <p style="margin:0 0 24px 0; font-size:16px; line-height:24px; color:#4a4663;">
        Hi {{ $userName }},
    </p>

    <p style="margin:0 0 32px 0; font-size:16px; line-height:24px; color:#4a4663;">
        We received a request to reset the password for your tab3 account. Use the code below in the app to set a new password.
    </p>

    {{-- OTP box --}}
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
        <tr>
            <td align="center" style="padding:0 0 32px 0;">
                <div class="otp-box" style="display:inline-block; background-color:#c8ff00; color:#0c0628; font-family:'Courier New', Courier, monospace; font-size:40px; font-weight:800; letter-spacing:12px; padding:24px 32px; border-radius:12px; min-width:280px; text-align:center;">
                    {{ $otp }}
                </div>
            </td>
        </tr>
    </table>

    {{-- Expiry warning --}}
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color:#fff5f5; border-left:4px solid #ff4757; border-radius:8px; margin:0 0 32px 0;">
        <tr>
            <td style="padding:16px 20px; font-size:14px; line-height:20px; color:#4a4663;">
                ⏱ This code expires in <strong style="color:#0c0628;">{{ $expiresInMinutes }} minutes</strong>. For your security, all active sessions will be signed out once your password is reset.
            </td>
        </tr>
    </table>

    {{-- Security note --}}
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color:#f5f3ff; border-radius:8px; margin:0 0 32px 0;">
        <tr>
            <td style="padding:16px 20px; font-size:14px; line-height:20px; color:#4a4663;">
                🔒 <strong style="color:#0c0628;">Didn't request this?</strong> Someone may have entered your email by mistake. You can safely ignore this message — your password won't change.
            </td>
        </tr>
    </table>

    <p style="margin:32px 0 0 0; font-size:15px; line-height:22px; color:#4a4663;">
        Stay safe,<br>
        <strong style="color:#0c0628;">The tab3 Security Team</strong>
    </p>

</x-emails.layout>
