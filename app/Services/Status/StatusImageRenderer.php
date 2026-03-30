<?php

namespace App\Services\Status;

use App\Enums\ServiceStatus;
use Illuminate\Support\Str;

class StatusImageRenderer
{
    public function renderServiceCard(array $snapshot): string
    {
        $service = $snapshot['service'];
        $status = $service['status'];
        $palette = $this->palette($status);
        $history = collect($service['history'])->take(-30)->values();
        $messageLines = $this->wrapText(
            $service['last_check_message'] ?: $status->description(),
            54,
            2,
        );

        $titleLines = $this->wrapText($service['name'], 24, 2);
        $target = mb_strimwidth((string) $service['target'], 0, 44, '…');
        $checkType = $service['check_type']?->label() ?? 'Dienst';
        $lastChecked = $service['last_checked_at']?->diffForHumans() ?? 'Noch nicht geprüft';
        $responseTime = $service['last_response_time_ms'] !== null ? $service['last_response_time_ms'].' ms' : '—';
        $badgeText = $status->label();
        $statusDescription = $status->description();
        $initial = Str::upper(Str::substr($service['name'], 0, 1));

        $bars = $history->map(function (array $day, int $index): string {
            $status = $day['status'];
            $color = match ($status) {
                ServiceStatus::Operational => '#34d399',
                ServiceStatus::Degraded => '#fbbf24',
                ServiceStatus::Down => '#fb7185',
                default => '#cbd5e1',
            };

            $x = 120 + ($index * 30);

            return '<rect x="'.$x.'" y="510" width="22" height="44" rx="8" fill="'.$color.'" fill-opacity="0.92" />';
        })->implode('');

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="630" viewBox="0 0 1200 630" fill="none">
  <defs>
    <linearGradient id="bg" x1="1200" y1="0" x2="0" y2="630" gradientUnits="userSpaceOnUse">
      <stop stop-color="#F8FBFF"/>
      <stop offset="1" stop-color="#EEF2FF"/>
    </linearGradient>
    <linearGradient id="card" x1="240" y1="72" x2="1100" y2="560" gradientUnits="userSpaceOnUse">
      <stop stop-color="#FFFFFF"/>
      <stop offset="1" stop-color="#F8FAFC"/>
    </linearGradient>
  </defs>
  <rect width="1200" height="630" fill="url(#bg)"/>
  <circle cx="1010" cy="112" r="220" fill="#C7D2FE" fill-opacity="0.32"/>
  <circle cx="148" cy="548" r="180" fill="#BFDBFE" fill-opacity="0.28"/>
  <rect x="52" y="52" width="1096" height="526" rx="36" fill="url(#card)" stroke="#E2E8F0"/>
  <rect x="88" y="88" width="96" height="96" rx="28" fill="#FFFFFF" stroke="#E2E8F0"/>
  <circle cx="136" cy="136" r="30" fill="{$palette['soft']}" />
  <text x="136" y="148" text-anchor="middle" fill="{$palette['hex']}" font-family="Manrope, Arial, sans-serif" font-size="34" font-weight="800">{$this->escape($initial)}</text>
  <text x="212" y="117" fill="#6366F1" font-family="Manrope, Arial, sans-serif" font-size="20" font-weight="700">Nebuliton Status</text>
  <text x="212" y="146" fill="#64748B" font-family="Manrope, Arial, sans-serif" font-size="16">Teilbarer Live-Status für einzelne Dienste</text>
  <rect x="924" y="92" width="184" height="46" rx="23" fill="{$palette['soft']}" />
  <text x="1016" y="121" text-anchor="middle" fill="{$palette['hex']}" font-family="Manrope, Arial, sans-serif" font-size="18" font-weight="800">{$this->escape($badgeText)}</text>
  <text x="92" y="238" fill="#0F172A" font-family="Manrope, Arial, sans-serif" font-size="46" font-weight="800">{$this->escape($titleLines[0] ?? '')}</text>
  <text x="92" y="288" fill="#0F172A" font-family="Manrope, Arial, sans-serif" font-size="46" font-weight="800">{$this->escape($titleLines[1] ?? '')}</text>
  <text x="92" y="332" fill="#475569" font-family="Manrope, Arial, sans-serif" font-size="24">{$this->escape($statusDescription)}</text>
  <text x="92" y="392" fill="#94A3B8" font-family="Manrope, Arial, sans-serif" font-size="16" font-weight="700" letter-spacing="2.2">DIENSTDETAILS</text>
  <text x="92" y="426" fill="#0F172A" font-family="Manrope, Arial, sans-serif" font-size="22" font-weight="700">{$this->escape($checkType)} · {$this->escape($target)}</text>
  <text x="92" y="462" fill="#475569" font-family="Manrope, Arial, sans-serif" font-size="20">Letzter Check {$this->escape($lastChecked)} · Antwortzeit {$this->escape($responseTime)}</text>
  <text x="92" y="594" fill="#94A3B8" font-family="Manrope, Arial, sans-serif" font-size="16">Letzte 30 Tage</text>
  {$bars}
  <rect x="864" y="192" width="244" height="132" rx="28" fill="#FFFFFF" stroke="#E2E8F0"/>
  <text x="892" y="228" fill="#94A3B8" font-family="Manrope, Arial, sans-serif" font-size="16" font-weight="700" letter-spacing="1.8">VERFÜGBARKEIT</text>
  <text x="892" y="286" fill="#0F172A" font-family="Manrope, Arial, sans-serif" font-size="46" font-weight="800">{$this->escape(number_format((float) $service['uptime_percentage'], 2, ',', '.'))}%</text>
  <text x="892" y="314" fill="#64748B" font-family="Manrope, Arial, sans-serif" font-size="18">90 Tage Durchschnitt</text>
  <rect x="864" y="346" width="244" height="156" rx="28" fill="#FFFFFF" stroke="#E2E8F0"/>
  <text x="892" y="382" fill="#94A3B8" font-family="Manrope, Arial, sans-serif" font-size="16" font-weight="700" letter-spacing="1.8">AKTUELLE MELDUNG</text>
  <text x="892" y="426" fill="#0F172A" font-family="Manrope, Arial, sans-serif" font-size="24" font-weight="700">{$this->escape($messageLines[0] ?? '')}</text>
  <text x="892" y="460" fill="#475569" font-family="Manrope, Arial, sans-serif" font-size="22">{$this->escape($messageLines[1] ?? '')}</text>
  <text x="92" y="84" fill="#94A3B8" font-family="Manrope, Arial, sans-serif" font-size="14">{$this->escape(config('app.url'))}</text>
  <text x="1092" y="594" text-anchor="end" fill="#94A3B8" font-family="Manrope, Arial, sans-serif" font-size="16">{$this->escape($snapshot['lastUpdatedLabel'])}</text>
</svg>
SVG;
    }

    public function renderOverviewCard(array $snapshot): string
    {
        $palette = $this->palette($snapshot['globalStatus']);
        $services = collect($snapshot['services'])->take(7)->values();

        $serviceRows = $services->map(function (array $service, int $index): string {
            $status = $service['status'];
            $color = match ($status) {
                ServiceStatus::Operational => '#10b981',
                ServiceStatus::Degraded => '#f59e0b',
                ServiceStatus::Down => '#f43f5e',
            };

            $y = 228 + ($index * 46);
            $textY = $y + 6;
            $name = $this->escape(mb_strimwidth($service['name'], 0, 28, '…'));
            $state = $this->escape($status->label());
            $uptime = $this->escape(number_format((float) $service['uptime_percentage'], 2, ',', '.')).'%';

            return <<<ROW
  <circle cx="742" cy="{$y}" r="8" fill="{$color}" />
  <text x="764" y="{$textY}" fill="#0F172A" font-family="Manrope, Arial, sans-serif" font-size="20" font-weight="700">{$name}</text>
  <text x="1088" y="{$textY}" text-anchor="end" fill="#475569" font-family="Manrope, Arial, sans-serif" font-size="18">{$state} · {$uptime}</text>
ROW;
        })->implode("\n");

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="630" viewBox="0 0 1200 630" fill="none">
  <defs>
    <linearGradient id="bg" x1="1200" y1="0" x2="0" y2="630" gradientUnits="userSpaceOnUse">
      <stop stop-color="#F8FBFF"/>
      <stop offset="1" stop-color="#EEF2FF"/>
    </linearGradient>
    <linearGradient id="left" x1="84" y1="96" x2="560" y2="548" gradientUnits="userSpaceOnUse">
      <stop stop-color="#FFFFFF"/>
      <stop offset="1" stop-color="#F8FAFC"/>
    </linearGradient>
    <linearGradient id="right" x1="646" y1="96" x2="1116" y2="548" gradientUnits="userSpaceOnUse">
      <stop stop-color="#FFFFFF"/>
      <stop offset="1" stop-color="#F8FAFC"/>
    </linearGradient>
  </defs>
  <rect width="1200" height="630" fill="url(#bg)"/>
  <circle cx="1048" cy="120" r="210" fill="#BFDBFE" fill-opacity="0.28"/>
  <circle cx="172" cy="526" r="180" fill="#C4B5FD" fill-opacity="0.20"/>
  <rect x="54" y="54" width="1092" height="522" rx="36" fill="#FFFFFF" fill-opacity="0.42" stroke="#E2E8F0"/>
  <rect x="84" y="96" width="478" height="438" rx="34" fill="url(#left)" stroke="#E2E8F0"/>
  <rect x="638" y="96" width="478" height="438" rx="34" fill="url(#right)" stroke="#E2E8F0"/>
  <text x="108" y="142" fill="#6366F1" font-family="Manrope, Arial, sans-serif" font-size="20" font-weight="700">Nebuliton Status</text>
  <text x="108" y="200" fill="#0F172A" font-family="Manrope, Arial, sans-serif" font-size="52" font-weight="800">{$this->escape($snapshot['globalMessage'])}</text>
  <text x="108" y="238" fill="#475569" font-family="Manrope, Arial, sans-serif" font-size="24">Live-Übersicht für Statusseiten, Embeds und Discord</text>
  <rect x="108" y="278" width="198" height="46" rx="23" fill="{$palette['soft']}" />
  <text x="207" y="307" text-anchor="middle" fill="{$palette['hex']}" font-family="Manrope, Arial, sans-serif" font-size="18" font-weight="800">{$this->escape($snapshot['globalStatus']->label())}</text>
  <text x="108" y="378" fill="#94A3B8" font-family="Manrope, Arial, sans-serif" font-size="16" font-weight="700" letter-spacing="2.2">STATUSVERTEILUNG</text>
  <text x="108" y="424" fill="#0F172A" font-family="Manrope, Arial, sans-serif" font-size="24" font-weight="700">{$snapshot['statusBreakdown']['operational']} betriebsbereit</text>
  <text x="108" y="462" fill="#0F172A" font-family="Manrope, Arial, sans-serif" font-size="24" font-weight="700">{$snapshot['statusBreakdown']['degraded']} beeinträchtigt</text>
  <text x="108" y="500" fill="#0F172A" font-family="Manrope, Arial, sans-serif" font-size="24" font-weight="700">{$snapshot['statusBreakdown']['down']} Ausfall</text>
  <text x="108" y="554" fill="#64748B" font-family="Manrope, Arial, sans-serif" font-size="20">Ø Verfügbarkeit {$this->escape(number_format((float) $snapshot['averageUptime'], 2, ',', '.'))}% · {$snapshot['serviceCount']} Dienste</text>
  <text x="662" y="142" fill="#94A3B8" font-family="Manrope, Arial, sans-serif" font-size="16" font-weight="700" letter-spacing="2.2">AKTUELLE DIENSTE</text>
  {$serviceRows}
  <text x="1112" y="564" text-anchor="end" fill="#94A3B8" font-family="Manrope, Arial, sans-serif" font-size="16">{$this->escape($snapshot['lastUpdatedLabel'])}</text>
</svg>
SVG;
    }

    protected function palette(ServiceStatus $status): array
    {
        return match ($status) {
            ServiceStatus::Operational => ['hex' => '#059669', 'soft' => '#D1FAE5'],
            ServiceStatus::Degraded => ['hex' => '#D97706', 'soft' => '#FEF3C7'],
            ServiceStatus::Down => ['hex' => '#E11D48', 'soft' => '#FFE4E6'],
        };
    }

    /**
     * @return array<int, string>
     */
    protected function wrapText(string $text, int $lineLength, int $maxLines): array
    {
        $words = preg_split('/\s+/', trim($text)) ?: [];
        $lines = [];
        $current = '';

        foreach ($words as $word) {
            $candidate = trim($current === '' ? $word : $current.' '.$word);

            if (mb_strlen($candidate) > $lineLength && $current !== '') {
                $lines[] = $current;
                $current = $word;

                if (count($lines) === $maxLines - 1) {
                    break;
                }

                continue;
            }

            $current = $candidate;
        }

        if ($current !== '' && count($lines) < $maxLines) {
            $lines[] = $current;
        }

        if (count($lines) === $maxLines && count($words) > 0) {
            $lines[$maxLines - 1] = mb_strimwidth($lines[$maxLines - 1], 0, $lineLength, '…');
        }

        while (count($lines) < $maxLines) {
            $lines[] = '';
        }

        return $lines;
    }

    protected function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
