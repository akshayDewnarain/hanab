<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="referrer" content="no-referrer">
    <title>{{ config('app.name', 'Hanab') }}</title>
</head>
<body style="margin:0;background:#f4f7fb;color:#0f172a;font-family:Helvetica,Arial,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f7fb;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border:1px solid #dbe3ee;border-radius:16px;padding:32px;">
                    <tr>
                        <td>
                            <p style="margin:0 0 8px;font-size:13px;letter-spacing:.04em;text-transform:uppercase;color:#64748b;">{{ config('app.name', 'Hanab') }}</p>
                            <h1 style="margin:0 0 12px;font-size:24px;line-height:1.3;">Activeer je account</h1>
                            <p style="margin:0 0 16px;font-size:15px;line-height:1.6;">Er is een account voor je klaargezet op {{ $recipientEmail }}. Kies zelf een wachtwoord. De link is {{ $expiresInDays }} dagen geldig.</p>
                            <p style="margin:0 0 20px;">
                                <a href="{{ $acceptUrl }}" style="display:inline-block;background:#00416e;color:#ffffff;text-decoration:none;border-radius:10px;padding:12px 18px;font-weight:700;">Account activeren</a>
                            </p>
                            <p style="margin:0 0 8px;font-size:13px;line-height:1.5;color:#475569;">Werkt de knop niet, plak dan deze link in je browser:</p>
                            <p style="margin:0 0 24px;font-size:13px;line-height:1.5;word-break:break-all;"><a href="{{ $acceptUrl }}" style="color:#00416e;">{{ $acceptUrl }}</a></p>
                            <hr style="border:0;border-top:1px solid #e2e8f0;margin:0 0 24px;">
                            <h2 style="margin:0 0 12px;font-size:20px;line-height:1.3;">Activate your account</h2>
                            <p style="margin:0 0 16px;font-size:15px;line-height:1.6;">An account is waiting for {{ $recipientEmail }}. Choose your own password. This link expires in {{ $expiresInDays }} days.</p>
                            <p style="margin:0 0 20px;">
                                <a href="{{ $acceptUrl }}" style="display:inline-block;background:#00416e;color:#ffffff;text-decoration:none;border-radius:10px;padding:12px 18px;font-weight:700;">Activate account</a>
                            </p>
                            <p style="margin:0 0 8px;font-size:13px;line-height:1.5;color:#475569;">If the button does not work, paste this link into your browser:</p>
                            <p style="margin:0;font-size:13px;line-height:1.5;word-break:break-all;"><a href="{{ $acceptUrl }}" style="color:#00416e;">{{ $acceptUrl }}</a></p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
