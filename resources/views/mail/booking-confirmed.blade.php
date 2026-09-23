<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Falcore booking {{ $booking->booking_reference }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f1ea;font-family:Arial,Helvetica,sans-serif;color:#0b1a33;">
@php
    $itinerary = $booking->itinerary ?? [];
    $depart = $itinerary['departure_at'] ?? optional($booking->flight?->departure_at)->toIso8601String();
    $arrive = $itinerary['arrival_at'] ?? optional($booking->flight?->arrival_at)->toIso8601String();
    $extras = $itinerary['extras']['lines'] ?? [];
    $cabin = $booking->cabinLabel();
@endphp
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f4f1ea;padding:24px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:16px;overflow:hidden;">
                <tr>
                    <td style="background:#0b1a33;padding:28px 32px;">
                        <p style="margin:0;color:#d4b06a;font-size:12px;letter-spacing:2px;text-transform:uppercase;font-weight:bold;">Falcore</p>
                        <h1 style="margin:8px 0 0;color:#ffffff;font-size:26px;">Booking confirmed</h1>
                        <p style="margin:10px 0 0;color:#efe3c4;font-size:14px;">Your trip is confirmed. Keep this email for the airport.</p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:28px 32px;">
                        <p style="margin:0;font-size:12px;color:#64748b;text-transform:uppercase;letter-spacing:1px;">Booking reference</p>
                        <p style="margin:6px 0 0;font-size:28px;font-weight:bold;letter-spacing:1px;">{{ $booking->booking_reference }}</p>
                        @if($booking->duffel_booking_reference)
                            <p style="margin:8px 0 0;font-size:14px;color:#334155;">Airline PNR: <strong>{{ $booking->duffel_booking_reference }}</strong></p>
                        @endif

                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-top:24px;border-top:1px solid #e2e8f0;">
                            <tr>
                                <td style="padding-top:20px;">
                                    <p style="margin:0;font-size:13px;color:#64748b;">{{ $booking->airlineName() }} · {{ $cabin }}</p>
                                    <h2 style="margin:6px 0 0;font-size:22px;">{{ $booking->routeLabel() }}</h2>
                                </td>
                            </tr>
                        </table>

                        @foreach($booking->legs() as $leg)
                            @php
                                $legDepart = $leg['departure_at'] ?? $depart;
                                $legArrive = $leg['arrival_at'] ?? $arrive;
                            @endphp
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-top:16px;">
                                <tr>
                                    <td colspan="2" style="padding-bottom:8px;">
                                        <p style="margin:0;font-size:11px;color:#d4b06a;letter-spacing:1px;text-transform:uppercase;font-weight:bold;">{{ $leg['label'] ?? 'Flight' }} {{ $leg['flight_number'] ?? '' }}</p>
                                    </td>
                                </tr>
                                <tr>
                                    <td width="50%" valign="top" style="padding-right:12px;">
                                        <p style="margin:0;font-size:11px;color:#94a3b8;text-transform:uppercase;">Depart</p>
                                        @if($legDepart)
                                            <p style="margin:4px 0 0;font-size:20px;font-weight:bold;">{{ \Carbon\Carbon::parse($legDepart)->format('H:i') }}</p>
                                            <p style="margin:4px 0 0;font-size:13px;color:#475569;">{{ \Carbon\Carbon::parse($legDepart)->format('D, M j, Y') }}</p>
                                        @endif
                                        <p style="margin:6px 0 0;font-size:13px;">{{ $leg['origin_name'] ?? $leg['origin_city'] ?? '' }} ({{ $leg['origin_code'] ?? '' }})</p>
                                    </td>
                                    <td width="50%" valign="top" style="padding-left:12px;">
                                        <p style="margin:0;font-size:11px;color:#94a3b8;text-transform:uppercase;">Arrive</p>
                                        @if($legArrive)
                                            <p style="margin:4px 0 0;font-size:20px;font-weight:bold;">{{ \Carbon\Carbon::parse($legArrive)->format('H:i') }}</p>
                                            <p style="margin:4px 0 0;font-size:13px;color:#475569;">{{ \Carbon\Carbon::parse($legArrive)->format('D, M j, Y') }}</p>
                                        @endif
                                        <p style="margin:6px 0 0;font-size:13px;">{{ $leg['destination_name'] ?? $leg['destination_city'] ?? '' }} ({{ $leg['destination_code'] ?? '' }})</p>
                                    </td>
                                </tr>
                            </table>
                        @endforeach

                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-top:24px;border-top:1px solid #e2e8f0;">
                            <tr>
                                <td style="padding-top:20px;">
                                    <p style="margin:0;font-size:14px;font-weight:bold;">Passengers</p>
                                    @foreach($booking->passengers as $passenger)
                                        <p style="margin:10px 0 0;font-size:14px;">
                                            {{ $passenger->full_name }} · {{ $passenger->typeLabel() }}
                                            @if($passenger->seat_number)
                                                · Seat {{ $passenger->seat_number }}
                                            @endif
                                            @if($passenger->extra_bags)
                                                · Extra bag × {{ $passenger->extra_bags }}
                                            @endif
                                        </p>
                                    @endforeach
                                </td>
                            </tr>
                        </table>

                        @if($extras)
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-top:20px;border-top:1px solid #e2e8f0;">
                                <tr>
                                    <td style="padding-top:20px;">
                                        <p style="margin:0;font-size:14px;font-weight:bold;">Add-ons</p>
                                        @foreach($extras as $line)
                                            <p style="margin:8px 0 0;font-size:13px;color:#475569;">
                                                {{ $line['label'] ?? 'Add-on' }}
                                                @if(($line['amount'] ?? 0) > 0)
                                                    — {{ $booking->formatMoney((float) $line['amount'], $booking->currency) }}
                                                @endif
                                            </p>
                                        @endforeach
                                    </td>
                                </tr>
                            </table>
                        @endif

                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-top:24px;background:#f8f3e8;border-radius:12px;">
                            <tr>
                                <td style="padding:16px 20px;">
                                    <p style="margin:0;font-size:13px;color:#64748b;">Total paid</p>
                                    <p style="margin:4px 0 0;font-size:24px;font-weight:bold;color:#0b1a33;">{{ $booking->formattedTotal() }}</p>
                                    <p style="margin:8px 0 0;font-size:13px;color:#475569;">A copy of this confirmation was sent to {{ $booking->contact_email }}.</p>
                                </td>
                            </tr>
                        </table>

                        <p style="margin:24px 0 0;font-size:12px;color:#94a3b8;">Need help with this trip? Reply to this email or visit Falcore with your booking reference.</p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
