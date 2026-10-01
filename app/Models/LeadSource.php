<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeadSource extends Model
{
    use BelongsToOrganization;

    protected $fillable = ['organization_id', 'name', 'slug', 'icon', 'type', 'status'];

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class, 'source_id');
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class, 'source_id');
    }

    public static function aliases(): array
    {
        return [
            'facebook' => 'meta',
        ];
    }

    public static function canonicalSlug(?string $slug): string
    {
        $slug = strtolower((string) $slug);

        return self::aliases()[$slug] ?? $slug;
    }

    public static function leadOriginSlugs(): array
    {
        return ['csv', 'manual', 'referral', 'other'];
    }

    public function isLeadOrigin(): bool
    {
        $slug = strtolower((string) $this->slug);
        $type = strtolower((string) $this->type);

        return in_array($slug, self::leadOriginSlugs(), true)
            || in_array($type, ['import', 'manual', 'other'], true);
    }

    public static function withoutDuplicateAliases($sources)
    {
        $sources = collect($sources);
        $slugs = $sources->pluck('slug')->map(fn ($slug) => strtolower((string) $slug))->all();

        return $sources->reject(function ($source) use ($slugs) {
            $slug = strtolower((string) $source->slug);
            $canonical = self::canonicalSlug($slug);

            return $slug !== $canonical && in_array($canonical, $slugs, true);
        })->values();
    }

    public static function forCampaignPicker($sources)
    {
        return self::withoutDuplicateAliases($sources)
            ->reject(fn ($source) => $source->isLeadOrigin())
            ->values();
    }

    public static function expandAliasIds(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (! $ids) {
            return $ids;
        }

        $selected = self::query()->whereIn('id', $ids)->get();
        foreach ($selected as $source) {
            $canonical = self::canonicalSlug($source->slug);
            if ($canonical !== strtolower((string) $source->slug)) {
                continue;
            }

            $aliasSlugs = array_keys(array_filter(self::aliases(), fn ($name) => $name === $canonical));
            if (! $aliasSlugs) {
                continue;
            }

            $ids = array_merge($ids, self::query()
                ->where('organization_id', $source->organization_id)
                ->whereIn('slug', $aliasSlugs)
                ->pluck('id')
                ->all());
        }

        return array_values(array_unique(array_map('intval', $ids)));
    }
}
