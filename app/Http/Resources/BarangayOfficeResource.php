<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BarangayOfficeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'barangay' => $this['barangay'],
            'office_name' => $this['office_name'],
            'latitude' => (float) $this['latitude'],
            'longitude' => (float) $this['longitude'],
            'address' => $this['address'] ?? null,
            'distance_km' => isset($this['distance_km']) ? (float) $this['distance_km'] : null,
            'source' => $this['source'] ?? 'unknown',
            'validation_status' => $this['validation_status'] ?? 'unknown',
            'recommendation_status' => $this['recommendation_status'] ?? null,
            'recommendation_notice' => $this['recommendation_notice'] ?? null,
        ];
    }
}
