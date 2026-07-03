<?php

namespace Modules\Finance\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Finance\Enums\JenisPengeluaranTetap;

class FixedExpenseEntryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $jenis = $this->jenis instanceof JenisPengeluaranTetap
            ? $this->jenis
            : JenisPengeluaranTetap::from($this->jenis);

        return [
            'id' => $this->id,
            'jenis' => $jenis->value,
            'jenis_label' => $jenis->label(),
            'bulan' => $this->bulan,
            'tahun' => $this->tahun,
            'amount' => (float) $this->amount,
            'is_filled' => (bool) $this->is_filled,
            'notes' => $this->notes,
            'recorded_by' => $this->recorded_by,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
