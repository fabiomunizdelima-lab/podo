<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Promemoria appuntamento</title>
</head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:Segoe UI,Helvetica,Arial,sans-serif;color:#1e293b;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                       style="max-width:520px;background:#ffffff;border-radius:14px;overflow:hidden;box-shadow:0 1px 3px rgba(15,23,42,.12);">
                    <tr>
                        <td style="padding:22px 26px;background:#0f766e;color:#ffffff;">
                            <div style="font-size:18px;font-weight:600;">{{ $studio }}</div>
                            <div style="font-size:13px;opacity:.85;">Promemoria appuntamento</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:26px;">
                            <p style="margin:0 0 14px;font-size:15px;">
                                Gentile {{ $patient->first_name ?: $patient->full_name }},
                            </p>
                            <p style="margin:0 0 18px;font-size:15px;line-height:1.5;">
                                le ricordiamo il suo appuntamento presso il nostro studio.
                            </p>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                                   style="background:#f8fafc;border-radius:10px;padding:14px 16px;font-size:15px;">
                                <tr>
                                    <td style="padding:5px 0;color:#64748b;width:34%;">Data</td>
                                    <td style="padding:5px 0;font-weight:600;">
                                        {{ $appointment->starts_at->timezone(config('app.timezone'))->format('d/m/Y') }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:5px 0;color:#64748b;">Orario</td>
                                    <td style="padding:5px 0;font-weight:600;">
                                        {{ $appointment->starts_at->timezone(config('app.timezone'))->format('H:i') }}
                                    </td>
                                </tr>
                                @if ($appointment->treatment)
                                    <tr>
                                        <td style="padding:5px 0;color:#64748b;">Prestazione</td>
                                        <td style="padding:5px 0;font-weight:600;">{{ $appointment->treatment }}</td>
                                    </tr>
                                @endif
                                @if ($studioAddress)
                                    <tr>
                                        <td style="padding:5px 0;color:#64748b;">Dove</td>
                                        <td style="padding:5px 0;font-weight:600;">{{ $studioAddress }}</td>
                                    </tr>
                                @endif
                            </table>

                            <p style="margin:18px 0 0;font-size:14px;line-height:1.5;color:#475569;">
                                Se non puo presentarsi, la preghiamo di avvisarci con almeno 24 ore di anticipo.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 26px;border-top:1px solid #e2e8f0;font-size:12px;color:#94a3b8;">
                            Messaggio automatico di servizio, non rispondere a questa email.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
