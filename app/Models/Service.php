<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Traits\UppercaseAttributes;
use App\Traits\HasApproval;
use App\Traits\HasMexicoTimezone;

class Service extends Model
{
    use HasFactory;
    use UppercaseAttributes;
    use HasApproval;
    use HasMexicoTimezone;

    protected $fillable = [
        'client_id',
        'folio',
        'type_operation',
        'terminal',
        'type_unit',
        'IMO',
        'dispatch_date',
        'delivery_date',
        'operator_id',
        'diesel',
        'initial_expenses_filled_by',
        'initial_diesel_filled_by',
        'unit_id',
        'state_id',
        'substate_id',
        'legacy',
        'aux_operator_id', 
        'aux_unit_id', 
        'aux2_operator_id', 
        'aux2_unit_id', 
        'order_paid',
        'commission',
        'diesel_cost_id',
        'zombie'
    ];

    protected $casts = [
        'commission' => 'decimal:2',
        'diesel_cost_id' => 'integer',
    ];

    protected $appends = [
        'formatted_dispatch_date',
        'formatted_delivery_date',
        'formatted_imo',
        'formatted_unit',
        'approvals_map',
        'is_assigned_operator',
    ];

    protected $hidden  = ['approvals'];

    public function onApproved(Approval $approval): void {
        switch ($approval->kind) {
            case "initial_expenses":
                // Crear orden de tesorería al aprobar gastos iniciales
                $expensesApproval = $this->approvalOf('initial_expenses');
                $expensesApproved = $expensesApproval && $expensesApproval->status === 'approved';

                if ($expensesApproved) {
                    TreasuryService::create([
                        'service_id' => $this->id,
                        'user_id'     => $approval->requested_by,
                        'reviewed_by' => $approval->reviewed_by,
                        'order_date'  => date('Y-m-d'),
                        'total'       => $approval->metadata['total'],
                        'type_payment'=> 1,
                        'paid'        => 0,
                    ]);

                    // Intentar transición automática a En Ruta
                    $this->checkAndTransitionToEnRuta();
                }
                break;

            case "initial_diesel_required":
                // Solo el diesel aprobado es suficiente para pasar a Programado
                $dieselApproval = $this->approvalOf('initial_diesel_required');
                $dieselApproved = $dieselApproval && $dieselApproval->status === 'approved';

                if ($dieselApproved) {
                    $this->update(['state_id' => 2]);
                    Historical::create([
                        'type'          => 'STATUS',
                        'service_id'    => $this->id,
                        'date'          => date('Y-m-d'),
                        'first_details' => 'Programado'
                    ]);
                }
                break;
            case "extra_expenses":
                    TreasuryService::create([
                        'service_id' => $this->id,
                        'user_id' => $approval->requested_by,
                        'reviewed_by' => $approval->reviewed_by,
                        'order_date' => date('Y-m-d'),
                        'total' => $approval->metadata['total'],
                        'type_payment' => 2,
                        'paid' => 0,
                    ]);
                break;
            case "extra_booth":
                    TreasuryService::create([
                        'service_id' => $this->id,
                        'user_id' => $approval->requested_by,
                        'reviewed_by' => $approval->reviewed_by,
                        'order_date' => date('Y-m-d'),
                        'total' => $approval->metadata['booth_cost'],
                        'type_payment' => 2,
                        'paid' => 0,
                    ]);
                break;
        }
    }

    public function onRejected(Approval $approval): void {
        switch ($approval->kind) {
            case "initial_expenses":
            case "initial_diesel_required":
                break;
            case "extra_expenses":
                    Expense::where('service_id', $this->id)->where('type', 'EXTRAS')->delete();
                break;
            case "extra_booth":
                    Expense::where('service_id', $this->id)
                        ->where('type', 'EXTRAS')
                        ->where('concept', 'like', 'CASETA EXTRA:%')
                        ->delete();
                break;
        }
    }

    public function diesel_cost()
    {
        return $this->belongsTo(DieselCost::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    public function extra_diesel()
    {
        return $this->hasMany(Diesel::class);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function substate()
    {
        return $this->belongsTo(Substate::class);
    }
    
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function serviceOperators()
    {
        return $this->hasMany(ServiceOperator::class)->where('zombie', 0)->with(['operator', 'unit', 'type']);
    }

    /**
     * Para legacy=1 verifica columnas antiguas; para legacy=0 verifica service_operators.
     */
    public function getIsAssignedOperatorAttribute()
    {
        $userId = auth()->id();
        if (!$userId) return false;

        if ($this->legacy) {
            return in_array($userId, array_filter([
                $this->operator_id,
                $this->aux_operator_id,
                $this->aux2_operator_id,
            ]));
        }

        return $this->serviceOperators->contains('operator_id', $userId);
    }

    public function operator()
    {
        return $this->belongsTo(Operator::class);
    }

    public function auxOperator()
    {
        return $this->belongsTo(Operator::class, 'aux_operator_id');
    }

    public function auxUnit()
    {
        return $this->belongsTo(Unit::class, 'aux_unit_id');
    }

    public function aux2Operator()
    {
        return $this->belongsTo(Operator::class, 'aux2_operator_id');
    }

    public function aux2Unit()
    {
        return $this->belongsTo(Unit::class, 'aux2_unit_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function containers()
    {
        return $this->hasMany(Container::class);
    }

    public function cost()
    {
        return $this->hasOne(Cost::class);
    }

    public function evidences()
    {
        return $this->hasMany(Evidence::class)->where('zombie', 0);
    }

    public function substateHistory()
    {
        return $this->hasMany(SubstateHistory::class);
    }

    public function getFormattedDispatchDateAttribute()
    {
        return $this->formatDateLocalized($this->dispatch_date);
    }

    public function getFormattedDeliveryDateAttribute()
    {
        return $this->formatDateLocalized($this->delivery_date);
    }

    public function getFormattedImoAttribute()
    {
        return $this->IMO?"IMO":"No IMO";
    }

    private function formatDateLocalized($date)
    {
        if (!$date) return null;

        return Carbon::parse($date)->translatedFormat('l d \d\e F Y'); 
    }

    /**
     * Retorna el substate mínimo que debe haberse alcanzado para
     * que el servicio pueda pasar automáticamente a En Ruta.
     * Importación (1) y Carga Suelta (3): substate 1 (Cargar en Puerto)
     * Exportación (2): substate 10 (Vacío Cargado)
     */
    public function enRutaThresholdSubstate(): int
    {
        return $this->type_operation === 2 ? 10 : 1;
    }

    /**
     * Evalúa si se cumplen las dos condiciones para pasar a En Ruta:
     * 1. El servicio está en Programado (state_id = 2)
     * 2. initial_expenses está aprobado
     * 3. substate_id >= umbral del tipo de operación
     * Si se cumplen, actualiza state_id = 3 y registra el historial.
     */
    public function checkAndTransitionToEnRuta(): void
    {
        if ($this->state_id !== 2) {
            return;
        }

        $expensesApproval = $this->approvalOf('initial_expenses');
        if (!$expensesApproval || $expensesApproval->status !== 'approved') {
            return;
        }

        if ($this->substate_id < $this->enRutaThresholdSubstate()) {
            return;
        }

        $this->update(['state_id' => 3]);

        Historical::create([
            'type'          => 'STATUS',
            'service_id'    => $this->id,
            'date'          => date('Y-m-d'),
            'first_details' => 'En Ruta',
        ]);
    }

    public function hasApprovalPendingOrApproved(string $kind): bool
    {
        $approval = $this->approvalOf($kind);
        return $approval && in_array($approval->status, ['pending', 'approved']);
    }

    public function snapshotForInitialDieselRequired() {
        return [
            'DIESEL REQUERIDO' => $this->diesel . " LITROS"
        ];
    }

    public function snapshotForInitialExpenses($total_booths)
    {
        if (! $this->cost) {
            return [];
        }

        $total = collect($this->cost->formatted_initial_costs)
            ->mapWithKeys(function ($item) {
                return [
                    $item['concept'] => '$' . number_format($item['cost'], 2, '.', ',')
                ];
            })
            ->toArray();

        return $total + ["TOTAL CASETAS" => '$' . number_format($total_booths, 2, '.', ',')];
    }

    public function snapshotForExtraExpenses()
    {
        if (! $this->cost) {
            return [];
        }

        return collect($this->cost->formatted_extras_costs)
            ->mapWithKeys(function ($item) {
                return [
                    $item['concept'] => '$' . number_format($item['cost'], 2, '.', ',')
                ];
            })
            ->toArray();
    }

    public function snapshotForExtraDiesel($amount, $description = null) {
        $snapshot = [
            'DIESEL INICIAL' => $this->diesel . ' LITROS',
            'DIESEL EXTRA' => $amount . ' LITROS'
        ];
        
        if ($description) {
            $snapshot['DESCRIPCIÓN'] = $description;
        }
        
        return $snapshot;
    }

    public function snapshotForExtraBooth($booth) {
        return [
            'COSTO CASETA' => '$' . number_format($booth->cost, 2, '.', ',') . ' - ' . $booth->name
        ];
    }

    public function getFormattedUnitAttribute()
    {
        static $types = null;

        if ($types === null) {
            $types = \App\Models\UnitType::query()
                ->where('zombie', 0)
                ->pluck('name', 'id');
        }

        return $types[$this->type_unit] ?? '';
    }

    public static function generarFolio()
    {
        $prefijo = 'TAG';
        $fecha = now()->format('ymd'); // AAMMDD

        // Buscar el último folio generado hoy
        $ultimoFolio = self::where('folio', 'like', "{$prefijo}{$fecha}%")
            ->orderByDesc('folio')
            ->value('folio');

        // Extraer el consecutivo numérico (últimos 3 dígitos)
        $consecutivo = $ultimoFolio
            ? (int)substr($ultimoFolio, -3) + 1
            : 1;

        // Asegurar formato de 3 dígitos (001, 002, ..., 999)
        $consecutivo = str_pad($consecutivo, 3, '0', STR_PAD_LEFT);

        return "{$prefijo}{$fecha}{$consecutivo}";
    }

    // Evento para asignar el folio automáticamente al crear
    protected static function booted()
    {
        static::creating(function ($servicio) {
            if (empty($servicio->folio)) {
                $servicio->folio = self::generarFolio();
            }
        });
    }
}
