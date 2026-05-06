<?php

namespace App\Exports;

use App\Enums\OperationStatusEnum;
use App\Enums\PrescriptionTypologyEnum;
use App\Enums\QuoteStatusEnum;
use App\Models\Operation;
use App\Models\Quote;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OperationsExport implements FromQuery, WithHeadings, WithMapping, WithTitle, ShouldAutoSize, WithChunkReading, WithStyles
{
    /**
     * @param Builder $baseQuery The Eloquent query for Operation, already filtered.
     * @param bool $includeCategoryColumn If true, appends the "Categoria" column (Bozza/Attiva/Archiviata).
     */
    public function __construct(
        private readonly Builder $baseQuery,
        private readonly bool $includeCategoryColumn = false,
    ) {
    }

    public function query()
    {
        return $this->baseQuery
            ->with([
                'building:id,name,agent_id',
                'building.agent:id,name,surname',
                'latestPrescription:id,operation_id,user_id,typology,ref,send_at,expire_at',
                'latestPrescription.user:id,name,surname',
                'selectedSupplier:id,name',
            ])
            ->select([
                'operations.id',
                'operations.building_id',
                'operations.typology',
                'operations.status',
                'operations.batch_number',
                'operations.canceled_at',
                'operations.archived_at',
                'operations.created_at',
            ])
            ->addSelect([
                'latest_quote_status' => Quote::query()
                    ->select('status')
                    ->whereColumn('quotes.operation_id', 'operations.id')
                    ->latest('created_at')
                    ->latest('id')
                    ->limit(1),
            ]);
    }

    public function headings(): array
    {
        $base = [
            'ID',
            'Codice di lotto',
            'Riferimento',
            'Tipologia',
            'Struttura',
            'Richiedente',
            'Stato',
            'Stato preventivo',
            'Fornitore selezionato',
            'Agente',
            'Data creazione',
            'Data invio prescrizione',
            'Data scadenza prescrizione',
            'Data assegnazione fornitore',
        ];

        if ($this->includeCategoryColumn) {
            $base[] = 'Categoria';
        }

        return $base;
    }

    public function map($operation): array
    {
        /** @var Operation $operation */
        $prescription = $operation->latestPrescription;
        $building = $operation->building;
        $supplier = $operation->selectedSupplier->first() ?? null;

        $typologyLabel = '';
        if ($prescription?->typology) {
            $typologyLabel = PrescriptionTypologyEnum::tryFrom((string) $prescription->typology)?->label() ?? (string) $prescription->typology;
        }

        $statusLabel = OperationStatusEnum::tryFrom((string) $operation->status)?->label() ?? (string) $operation->status;

        $latestQuoteStatus = $operation->getAttribute('latest_quote_status');
        $quoteStatusLabel = '';
        if ($latestQuoteStatus !== null && $latestQuoteStatus !== '') {
            $quoteStatusLabel = QuoteStatusEnum::tryFrom((string) $latestQuoteStatus)?->label() ?? (string) $latestQuoteStatus;
        }

        $requesterName = '';
        if ($prescription?->user) {
            $requesterName = trim(($prescription->user->name ?? '') . ' ' . ($prescription->user->surname ?? ''));
        }

        $agentName = '';
        if ($building?->agent) {
            $agentName = trim(($building->agent->name ?? '') . ' ' . ($building->agent->surname ?? ''));
        }

        $supplierName = $supplier?->name ?? '';
        $supplierSelectedAt = '';
        if ($supplier && isset($supplier->pivot)) {
            $selectedAt = $supplier->pivot->selected_at ?? null;
            if ($selectedAt) {
                $supplierSelectedAt = optional($selectedAt instanceof \DateTimeInterface
                    ? $selectedAt
                    : \Illuminate\Support\Carbon::parse($selectedAt))->format('d/m/Y') ?? '';
            }
        }

        $row = [
            (string) $operation->id,
            (string) ($operation->batch_number ?? ''),
            (string) ($prescription?->ref ?? ''),
            $typologyLabel,
            (string) ($building?->name ?? ''),
            $requesterName,
            $statusLabel,
            $quoteStatusLabel,
            $supplierName,
            $agentName,
            $operation->created_at?->format('d/m/Y H:i') ?? '',
            $prescription?->send_at?->format('d/m/Y') ?? '',
            $prescription?->expire_at?->format('d/m/Y') ?? '',
            $supplierSelectedAt,
        ];

        if ($this->includeCategoryColumn) {
            $row[] = $this->resolveCategoryLabel($operation);
        }

        return $row;
    }

    public function title(): string
    {
        return 'Lavorazioni';
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    private function resolveCategoryLabel(Operation $operation): string
    {
        if ($operation->archived_at !== null) {
            return 'Archiviata';
        }
        if ($operation->status === OperationStatusEnum::DRAFT->value) {
            return 'Bozza';
        }
        return 'Attiva';
    }
}
