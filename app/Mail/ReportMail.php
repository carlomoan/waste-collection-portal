<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReportMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  string  $pdfBinary  Raw PDF output
     * @param  string  $reportName  Human-readable report name
     */
    public function __construct(
        public string $pdfBinary,
        public string $reportName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Scheduled Report: {$this->reportName}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.report',
            with: ['reportName' => $this->reportName],
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->pdfBinary, $this->safeFileName().'.pdf')
                ->withMime('application/pdf'),
        ];
    }

    private function safeFileName(): string
    {
        return str_replace([' ', '/'], '_', strtolower($this->reportName)).'_'.now()->format('Ymd');
    }
}
