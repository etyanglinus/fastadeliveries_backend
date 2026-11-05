<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class BrandSiteDomainConnection
{
    public function configured(): bool
    {
        return strlen((string) config('brandsite.ingress_secret')) >= 32
            && filter_var(config('brandsite.ingress_ip'), FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)
            && $this->validHost((string) config('brandsite.ingress_host'));
    }

    public function validHost(string $host): bool
    {
        return (bool) preg_match('/^(?=.{1,233}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z][a-z0-9-]{0,61}[a-z0-9]$/D', $host)
            && ! preg_match('/\.(local|localhost|internal|test|invalid|example|onion)$/D', $host);
    }

    public function dnsReady(object $domain): bool
    {
        if (! $this->configured() || ! $this->validHost($domain->hostname)) return false;
        $records = @dns_get_record('_fasta-verification.'.$domain->hostname.'.', DNS_TXT) ?: [];
        $owned = collect($records)->contains(fn ($record) => hash_equals('fasta-verify='.$domain->verification_token, $record['txt'] ?? ''));
        if (! $owned) return false;
        $addresses = @dns_get_record($domain->hostname.'.', DNS_A | DNS_AAAA) ?: [];
        if ($this->pointsToIngress($addresses)) return true;
        if ($addresses !== []) return false;

        $aliases = @dns_get_record($domain->hostname.'.', DNS_CNAME) ?: [];
        if (! collect($aliases)->contains(fn ($record) => strtolower(rtrim($record['target'] ?? '', '.')) === config('brandsite.ingress_host'))) return false;
        $addresses = @dns_get_record(config('brandsite.ingress_host').'.', DNS_A | DNS_AAAA) ?: [];
        return $this->pointsToIngress($addresses);
    }

    private function pointsToIngress(array $addresses): bool
    {
        return $addresses !== [] && collect($addresses)->every(
            fn ($record) => ($record['ip'] ?? null) === config('brandsite.ingress_ip')
        );
    }

    public function httpsReady(string $host): bool
    {
        if (! $this->configured() || ! $this->validHost($host)) return false;
        $nonce = bin2hex(random_bytes(24));
        $expected = hash_hmac('sha256', $host.':'.$nonce, config('brandsite.ingress_secret'));
        $proof = '';
        $curl = curl_init('https://'.$host.'/?fasta_domain_check='.$nonce);
        curl_setopt_array($curl, [
            CURLOPT_RESOLVE => [$host.':443:'.config('brandsite.ingress_ip')],
            CURLOPT_PROXY => '', CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => 20, CURLOPT_NOBODY => true,
            CURLOPT_HEADERFUNCTION => function ($handle, string $line) use (&$proof) {
                if (stripos($line, 'x-fasta-domain-proof:') === 0) $proof = trim(substr($line, 21));
                return strlen($line);
            },
        ]);
        return curl_exec($curl) !== false && curl_getinfo($curl, CURLINFO_RESPONSE_CODE) === 200 && hash_equals($expected, $proof);
    }

    public function refreshActive(): void
    {
        DB::table('brandsite_domains')->whereNotNull('activated_at')->chunkById(100, function ($domains) {
            foreach ($domains as $domain) {
                DB::table('brandsite_domains')->where('id', $domain->id)->where('verification_token', $domain->verification_token)
                    ->where('activated_at', $domain->activated_at)->update($this->dnsReady($domain) && $this->httpsReady($domain->hostname)
                        ? ['routing_verified_at' => now(), 'last_connected_at' => now()]
                        : ['activated_at' => null, 'routing_verified_at' => null]);
            }
        });
    }
}
