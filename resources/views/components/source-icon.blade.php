@php
    $slug = strtolower($slug ?? ($source->slug ?? $source->name ?? 'other'));
    $map = [
        'facebook' => ['bi-facebook', '#1877F2'],
        'meta' => ['bi-facebook', '#1877F2'],
        'instagram' => ['bi-instagram', '#E1306C'],
        'tiktok' => ['bi-tiktok', '#111111'],
        'website' => ['bi-globe', '#2563eb'],
        'web-form' => ['bi-ui-checks', '#0ea5e9'],
        'whatsapp' => ['bi-whatsapp', '#22c55e'],
        'google' => ['bi-google', '#ea4335'],
        'csv' => ['bi-filetype-csv', '#64748b'],
        'manual' => ['bi-person', '#8b5cf6'],
        'referral' => ['bi-people', '#f59e0b'],
        'linkedin' => ['bi-linkedin', '#0a66c2'],
        'email' => ['bi-envelope', '#8b5cf6'],
        'webhook' => ['bi-link-45deg', '#0f172a'],
        'api' => ['bi-link-45deg', '#0f172a'],
        'bayut' => ['bi-house', '#16a34a'],
        'dubizzle' => ['bi-bag', '#f97316'],
    ];
    $hit = $map[$slug] ?? null;
    if (!$hit) {
        foreach ($map as $key => $val) {
            if (str_contains($slug, $key)) { $hit = $val; break; }
        }
    }
    $hit = $hit ?: ['bi-circle-fill', '#2563eb'];
@endphp
<span class="src-ico" style="background:{{ $hit[1] }}"><i class="bi {{ $hit[0] }}"></i></span>
