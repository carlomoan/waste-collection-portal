<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

// php artisan wcp:generate-invoices --month=5 --year=2026
class GenerateMonthlyInvoices extends Command
{
    protected $signature = 'wcp:generate-invoices
                            {--month= : Month number (default: current)}
                            {--year= : Year (default: current)}';

    public function handle(InvoiceService $service): int
    {
        $month = $this->option('month') ?? now()->month;
        $year  = $this->option('year')  ?? now()->year;

        $this->info("Generating invoices for {$month}/{$year}...");
        $count = $service->generateMonthlyInvoices((int)$month, (int)$year);
        $this->info("Generated {$count} invoices.");

        return Command::SUCCESS;
    }
}
