<?php

namespace App\Console\Commands;

use App\Models\Domain;
use App\Models\DomainRedirect;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PurgeExpiredDomainRedirects extends Command
{
    protected $signature = 'domains:purge-expired-redirects';

    protected $description = 'Clear expired previous-domain grace redirects and free hostnames back into the pool';

    public function handle(): int
    {
        $count = 0;

        $expiredColumns = Domain::query()
            ->whereNotNull('previous_domain')
            ->whereNotNull('previous_domain_expires_at')
            ->where('previous_domain_expires_at', '<=', now())
            ->get(['id', 'previous_domain', 'previous_domain_expires_at']);

        foreach ($expiredColumns as $site) {
            Log::info('Domain grace redirect freed', [
                'domain_id' => $site->id,
                'old_domain' => $site->previous_domain,
                'expired_at' => optional($site->previous_domain_expires_at)->toIso8601String(),
                'freed_at' => now()->toIso8601String(),
                'source' => 'domains.previous_domain',
            ]);
            $site->previous_domain = null;
            $site->previous_domain_expires_at = null;
            $site->save();
            $count++;
        }

        $expiredRows = DomainRedirect::query()
            ->where('expires_at', '<=', now())
            ->get();

        foreach ($expiredRows as $row) {
            Log::info('Domain grace redirect freed', [
                'domain_id' => $row->domain_id,
                'old_domain' => $row->old_domain,
                'expired_at' => optional($row->expires_at)->toIso8601String(),
                'freed_at' => now()->toIso8601String(),
                'source' => 'domain_redirects',
            ]);
            $row->delete();
            $count++;
        }

        $this->info("Freed {$count} expired domain redirect(s).");

        return self::SUCCESS;
    }
}
