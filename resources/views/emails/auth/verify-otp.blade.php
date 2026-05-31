@php
    $userName = $userName ?? 'there';
    $otp = $otp ?? '------';
    $expiresInMinutes = $expiresInMinutes ?? 10;
@endphp

<x-emails.layout
    title="Verify your email — tab3"
    preheader="Your tab3 verification code is {{ $otp }}. Valid for {{ $expiresInMinutes }} minutes."
>

    <h1 class="h1" style="margin:0 0 16px 0; font-size:28px; line-height:34px; font-weight:800; color:#0c0628; letter-spacing:-0.5px;">
        Verify your email
    </h1>

    <p style="margin:0 0 24px 0; font-size:16px; line-height:24px; color:#4a4663;">
        Hi {{ $userName }}, welcome to tab3! 👋
    </p>

    <p style="margin:0 0 32px 0; font-size:16px; line-height:24px; color:#4a4663;">
        Use the verification code below to confirm your email address and finish setting up your account.
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

    {{-- Expiry note --}}
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color:#f5f3ff; border-left:4px solid #c8ff00; border-radius:8px; margin:0 0 32px 0;">
        <tr>
            <td style="padding:16px 20px; font-size:14px; line-height:20px; color:#4a4663;">
                ⏱ This code expires in <strong style="color:#0c0628;">{{ $expiresInMinutes }} minutes</strong>. Don't share it with anyone — tab3 staff will never ask you for it.
            </td>
        </tr>
    </table>

    <p style="margin:0 0 8px 0; font-size:14px; line-height:22px; color:#4a4663;">
        Didn't sign up for tab3? You can safely ignore this email — no account will be created without verification.
    </p>

    <p style="margin:32px 0 0 0; font-size:15px; line-height:22px; color:#4a4663;">
        See you on the pitch,<br>
        <strong style="color:#0c0628;">The tab3 Team</strong>
    </p>

</x-emails.layout>
