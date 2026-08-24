<x-mail::message>
# {{ $reportName }}

Your scheduled report from the **Waste Collection Portal** is attached as a PDF.

Generated: {{ now()->format('d M Y, H:i') }}

If you were not expecting this email, please contact your administrator.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
