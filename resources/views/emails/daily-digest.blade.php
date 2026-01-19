<x-mail::message>
{{-- Custom Styles for this email --}}
<div style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;">

<div style="margin-bottom: 25px; text-align: center;">
    <h1 style="margin: 0; font-size: 24px; color: #111827;">Daily Digest</h1>
    <p style="margin: 5px 0 0; color: #6B7280; font-size: 14px;">{{ now()->format('l, F j, Y') }}</p>
</div>

@php
    $hasAlerts = !empty($data['memory_alerts']) || !empty($data['storage_alerts']);
    $alertCount = count($data['memory_alerts'] ?? []) + count($data['storage_alerts'] ?? []);
@endphp

{{-- Hero Status --}}
@if($hasAlerts)
<div style="background-color: #FEF2F2; border: 1px solid #FECACA; border-radius: 8px; padding: 20px; text-align: center; margin-bottom: 30px;">
<h2 style="margin: 0; color: #991B1B; font-size: 18px; font-weight: 600;">Attention Required</h2>
<p style="margin: 5px 0 0; color: #B91C1C;">Found {{ $alertCount }} potential resource issues.</p>
</div>
@else
<div style="background-color: #F0FDF4; border: 1px solid #DCFCE7; border-radius: 8px; padding: 20px; text-align: center; margin-bottom: 30px;">
<h2 style="margin: 0; color: #166534; font-size: 18px; font-weight: 600;">All Systems Normal</h2>
<p style="margin: 5px 0 0; color: #15803D;">No critical resource alerts were triggered today.</p>
</div>
@endif

{{-- Alerts Section --}}
@if(!empty($data['memory_alerts']))
<div style="margin-bottom: 30px;">
<h3 style="color: #4B5563; font-size: 14px; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 700; border-bottom: 2px solid #E5E7EB; padding-bottom: 10px; margin-bottom: 15px;">
High Memory Usage
</h3>
@foreach($data['memory_alerts'] as $alert)
<div style="background-color: #fff; border: 1px solid #E5E7EB; border-left: 4px solid #EF4444; border-radius: 4px; padding: 15px; margin-bottom: 10px;">
<table width="100%" cellpadding="0" cellspacing="0">
<tr>
<td>
    <strong>
        <a href="{{ route('projects.dashboard', $alert['project_id']) }}" style="color: #111827; text-decoration: none; border-bottom: 1px dotted #9CA3AF;">{{ $alert['project'] }}</a>
    </strong>
</td>
<td align="right" style="color: #EF4444; font-weight: bold;">{{ $alert['usage'] }}%</td>
</tr>
</table>
</div>
@endforeach
</div>
@endif

@if(!empty($data['storage_alerts']))
<div style="margin-bottom: 30px;">
<h3 style="color: #4B5563; font-size: 14px; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 700; border-bottom: 2px solid #E5E7EB; padding-bottom: 10px; margin-bottom: 15px;">
Storage Warnings
</h3>
@foreach($data['storage_alerts'] as $alert)
<div style="background-color: #fff; border: 1px solid #E5E7EB; border-left: 4px solid #F59E0B; border-radius: 4px; padding: 15px; margin-bottom: 10px;">
<table width="100%" cellpadding="0" cellspacing="0">
<tr>
<td>
    <strong>
        <a href="{{ route('projects.dashboard', $alert['project_id']) }}" style="color: #111827; text-decoration: none; border-bottom: 1px dotted #9CA3AF;">{{ $alert['project'] }}</a>
    </strong>
</td>
<td align="right" style="color: #D97706; font-weight: bold;">{{ $alert['usage'] }}%</td>
</tr>
</table>
</div>
@endforeach
</div>
@endif

{{-- Logs Section --}}
@if(!empty($data['logs_summary']))
<div style="margin-bottom: 30px;">
<h3 style="color: #4B5563; font-size: 14px; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 700; border-bottom: 2px solid #E5E7EB; padding-bottom: 10px; margin-bottom: 15px;">
Log Activity (24h)
</h3>

@foreach($data['logs_summary'] as $summary)
<div style="background-color: #F9FAFB; border-radius: 8px; padding: 15px; margin-bottom: 15px;">
<h4 style="margin: 0 0 10px 0; color: #111827; font-size: 16px;">
    <a href="{{ route('projects.logs.index', $summary['id']) }}" style="color: #111827; text-decoration: none;">{{ $summary['name'] }}</a>
</h4>
<table width="100%" cellpadding="0" cellspacing="0" style="font-size: 14px;">
@foreach($summary['counts'] as $level => $count)
<tr>
<td style="padding: 5px 0; color: #4B5563;">{{ ucfirst($level) }}</td>
<td align="right" style="padding: 5px 0; font-weight: 600; color: #111827;">{{ $count }}</td>
</tr>
@endforeach
</table>
</div>
@endforeach
</div>
@endif

<div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #E5E7EB; text-align: center; font-size: 12px; color: #9CA3AF;">
    <p style="margin: 0;">Logger is proudly presented by <a href="https://admin-intelligence.de" style="color: #6B7280; text-decoration: underline;">ADMIN INTELLIGENCE GmbH</a></p>
</div>

</div>
</x-mail::message>
