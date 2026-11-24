<?php

namespace App\Models;

use App\Services\RegistroReserva;
use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

// MODELO RESERVA: REGISTRA CADA RESERVA DE UN CLIENTE SOBRE UN HORARIO
// RELACIONES: PERTENECE A UN USER (N:1) Y A UN HORARIO (N:1)
// ESTADOS DE PAGO: pendiente -> aprobado | anulada (NO-SHOW) | cancelado_por_mantenimiento
// Yape real se verifica en recepción; Efectivo se registra presencialmente.
// Tarjeta solo existe en el simulador local, identificado por pago_demo.
// GENERA TICKET CON CODIGO QR USANDO LA LIBRERIA bacon/bacon-qr-code
class Reserva extends Model
{
    use HasFactory, SoftDeletes;

    // CONSTANTE: MINUTOS ANTES DE LA HORA DE JUEGO EN QUE CADUCA UNA RESERVA EN EFECTIVO NO PAGADA
    public const MINUTOS_GRACIA = 30;

    // CONSTANTE: HORAS MINIMAS DE ANTICIPACION PARA QUE UN CLIENTE PUEDA CANCELAR.
    // Con menos de este tiempo, solo el admin puede cancelar (politica del club).
    public const HORAS_MIN_CANCELACION = 2;

    public const ESTADO_PENDIENTE = 'pendiente';

    public const ESTADO_APROBADO = 'aprobado';

    public const ESTADO_ANULADA = 'anulada';

    public const ESTADO_CANCELADO_MANT = 'cancelado_por_mantenimiento';

    protected $fillable = [
        'estado_reserva', 'clave_solicitud', 'motivo', 'contacto',
        'user_id',
        'horario_id',
        'metodo_pago',
        'numero_operacion',
        'estado_pago',
        'monto_pagado',
        'codigo_validacion',
        'expira_at',
        'pago_demo',
    ];

    protected $casts = [
        'contacto' => 'array',
        'expira_at' => 'datetime',
        'usado_at' => 'datetime',
        'monto_pagado' => 'decimal:2',
        'pago_demo' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function horario()
    {
        return $this->belongsTo(Horario::class)->withTrashed();
    }

    public function eventos()
    {
        return $this->hasMany(BookingEvent::class)->orderByDesc('id');
    }

    public function activa(): bool
    {
        return ! $this->trashed() && in_array($this->estado_reserva, ['pendiente', 'confirmada'], true)
            && in_array($this->estado_pago, [self::ESTADO_PENDIENTE, self::ESTADO_APROBADO], true);
    }

    protected static function booted(): void
    {
        static::creating(function (Reserva $reserva) {
            if (! $reserva->estado_reserva) {
                $reserva->estado_reserva = match ($reserva->estado_pago) {
                    self::ESTADO_APROBADO => 'confirmada',
                    self::ESTADO_ANULADA, self::ESTADO_CANCELADO_MANT => 'cancelada',
                    default => 'pendiente',
                };
            }
        });
        static::updating(function (Reserva $reserva) {
            if ($reserva->isDirty('estado_pago') && ! $reserva->isDirty('estado_reserva')) {
                $reserva->estado_reserva = match ($reserva->estado_pago) {
                    self::ESTADO_APROBADO => 'confirmada',
                    self::ESTADO_ANULADA, self::ESTADO_CANCELADO_MANT => 'cancelada',
                    default => 'pendiente',
                };
            }
        });
    }

    /**
     * Reservas en Efectivo pendientes cuyo plazo de pago ya venció
     * (faltan MINUTOS_GRACIA o menos para la hora_inicio).
     */
    public function scopeVencidas($query)
    {
        return $query->where('estado_reserva', 'pendiente')->where('estado_pago', self::ESTADO_PENDIENTE)
            ->whereNotNull('expira_at')
            ->where('expira_at', '<=', now());
    }

    /**
     * ¿Esta reserva en Efectivo ya caducó por no pagar a tiempo?
     */
    public function estaVencida(): bool
    {
        return $this->estado_pago === self::ESTADO_PENDIENTE
            && $this->expira_at !== null
            && $this->expira_at->lte(now());
    }

    /**
     * Caduca las reservas en Efectivo vencidas: las marca 'anulada' y
     * libera su horario (vuelve a 'disponible'). Devuelve cuántas liberó.
     *
     * La usan tanto el command programado (reservas:liberar-vencidas) como
     * el modo "lazy" (al listar/confirmar), para que la regla se vea siempre.
     */
    // LIBERACION AUTOMATICA DE NO-SHOWS: ANULA RESERVAS EN EFECTIVO VENCIDAS
    // USA DB::TRANSACTION CON lockForUpdate PARA EVITAR RACE CONDITIONS
    // SE LLAMA DESDE: 1) COMANDO ARTISAN (CRON) Y 2) MODO LAZY (AL CARGAR VISTAS)
    // EXPO: los no-shows: anula las reservas en efectivo que no pagaron a tiempo y libera su horario
    public static function liberarVencidas(): int
    {
        $liberadas = 0;

        // lockForUpdate evita que el job y un pago en caja toquen la misma fila a la vez
        DB::transaction(function () use (&$liberadas) {
            $vencidas = static::vencidas()->lockForUpdate()->get();

            foreach ($vencidas as $reserva) {
                $reserva->horario()->update(['estado' => 'disponible']);
                $reserva->update(['estado_pago' => self::ESTADO_ANULADA, 'estado_reserva' => 'cancelada', 'motivo' => 'Plazo de pago vencido']);
                RegistroReserva::guardar($reserva, 'vencimiento');
                $liberadas++;
            }
        });

        return $liberadas;
    }

    /**
     * Genera un código de validación único para el ticket, ej. TT-2406.
     */
    // EXPO: genera el codigo TT-XXXX del ticket y repite hasta que salga uno libre
    public static function generarCodigoValidacion(): string
    {
        do {
            $codigo = 'TT-'.strtoupper(bin2hex(random_bytes(6)));
        } while (static::withTrashed()->where('codigo_validacion', $codigo)->exists());

        return $codigo;
    }

    /**
     * Asegura que la reserva tenga un token único para su QR.
     * Se genera de forma perezosa (lazy), así también los tickets antiguos
     * sin token obtienen uno la primera vez que se muestran.
     */
    public function asegurarToken(): string
    {
        if (! $this->token_validacion) {
            do {
                $token = bin2hex(random_bytes(20)); // 40 caracteres, aleatorio y seguro
            } while (static::where('token_validacion', $token)->exists());

            $this->token_validacion = $token;
            $this->save();
        }

        return $this->token_validacion;
    }

    /**
     * URL pública de verificación del ticket (lo que se codifica en el QR).
     */
    public function urlValidacion(): string
    {
        return route('validacion.mostrar', $this->asegurarToken());
    }

    /**
     * Contenido del QR: ahora es la URL de verificación en tiempo real
     * (antes era solo texto). Al escanear, se abre la página de validación.
     */
    // EXPO: el QR ya no es texto suelto: lleva un enlace único que abre la pagina de validacion
    public function contenidoQr(): string
    {
        return $this->urlValidacion();
    }

    /**
     * ¿La reserva ya fue usada para ingresar?
     */
    public function estaUsada(): bool
    {
        return $this->usado_at !== null;
    }

    /**
     * Evalúa el estado de ingreso para la página de validación del QR.
     * Devuelve: ['ok' => bool, 'codigo' => string, 'titulo' => string, 'mensaje' => string]
     */
    public function estadoIngreso(): array
    {
        if ($this->pago_demo) {
            return ['ok' => false, 'codigo' => 'demostracion', 'titulo' => 'Ticket de demostración',
                'mensaje' => 'Este pago es simulado. El ticket no acredita un cobro real ni autoriza ingreso al club.'];
        }
        if ($this->trashed() || in_array($this->estado_reserva, ['cancelada', 'rechazada', 'completada'], true)) {
            return ['ok' => false, 'codigo' => $this->estado_reserva, 'titulo' => 'Reserva '.$this->estado_reserva,
                'mensaje' => 'Esta reserva ya no permite registrar un ingreso.'];
        }
        if ($this->estado_pago === self::ESTADO_CANCELADO_MANT) {
            return ['ok' => false, 'codigo' => 'mantenimiento', 'titulo' => 'Reserva cancelada por mantenimiento',
                'mensaje' => 'Esta reserva fue cancelada porque la cancha no está disponible. Comunícate con el club.'];
        }

        if ($this->estado_pago === self::ESTADO_ANULADA) {
            return ['ok' => false, 'codigo' => 'anulada', 'titulo' => 'Reserva anulada',
                'mensaje' => 'Esta reserva fue anulada (no-show o cancelada). No permite el ingreso.'];
        }

        if ($this->estaUsada()) {
            return ['ok' => false, 'codigo' => 'usada', 'titulo' => 'Ticket ya utilizado',
                'mensaje' => 'Este ticket ya fue validado el '.$this->usado_at->format('d/m/Y H:i').'.'];
        }

        $this->loadMissing('horario');
        if (! $this->horario) {
            return ['ok' => false, 'codigo' => 'no_disponible', 'titulo' => 'Horario no disponible',
                'mensaje' => 'El horario de esta reserva fue eliminado. Comunícate con el club.'];
        }

        if ($this->estaVencida()) {
            return ['ok' => false, 'codigo' => 'expirada', 'titulo' => 'Plazo de pago vencido',
                'mensaje' => 'Esta reserva venció porque el pago no se confirmó a tiempo.'];
        }

        $fin = optional($this->horario)->hora_fin;
        if ($fin && $fin->isPast()) {
            return ['ok' => false, 'codigo' => 'expirada', 'titulo' => 'Reserva expirada',
                'mensaje' => 'El horario de esta reserva ya finalizó.'];
        }

        if ($this->estado_pago === self::ESTADO_PENDIENTE) {
            return ['ok' => false, 'codigo' => 'pendiente', 'titulo' => 'Pendiente de verificar pago',
                'mensaje' => 'El personal del club debe verificar el pago antes de autorizar el ingreso.'];
        }

        return ['ok' => true, 'codigo' => 'valida', 'titulo' => 'Reserva válida',
            'mensaje' => 'Ingreso autorizado.'];
    }

    /**
     * QR de la reserva como SVG (sin GD) con el logo (pelota) en el centro, B/N.
     * Nivel de corrección H (~30%) para que el logo no rompa el escaneo.
     */
    // GENERACION DE QR: USA LA LIBRERIA bacon/bacon-qr-code (COMPOSER)
    // RENDERIZA EN SVG (SIN NECESITAR LA EXTENSION GD DE PHP)
    // NIVEL DE CORRECCION H (~30%) PARA QUE EL LOGO CENTRAL NO ROMPA EL ESCANEO
    // EXPO: el QR se arma aqui: texto -> matriz QR -> SVG (vectores, sin GD).
    // el nivel H de correccion es lo que deja poner el logo al centro sin romper el escaneo
    public function qrSvg(int $tamano = 220): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle($tamano),
            new SvgImageBackEnd
        );

        $svg = (new Writer($renderer))->writeString(
            $this->contenidoQr(),
            'utf-8',
            ErrorCorrectionLevel::H()
        );

        return str_replace('</svg>', $this->logoCentralSvg($tamano).'</svg>', $svg);
    }

    private function logoCentralSvg(int $tamano): string
    {
        $centro = $tamano / 2;
        $caja = $tamano * 0.30;
        $cajaX = $centro - $caja / 2;
        $logo = $tamano * 0.255;
        $escala = $logo / 56;
        $logoX = $centro - $logo / 2;
        $radio = $tamano * 0.05;

        return sprintf(
            '<rect x="%1$.2F" y="%2$.2F" width="%3$.2F" height="%3$.2F" rx="%4$.2F" fill="#ffffff"/>'
            .'<g transform="translate(%5$.2F,%5$.2F) scale(%6$.4F)">'
            .'<circle cx="28" cy="28" r="26" fill="#ffffff" stroke="#000000" stroke-width="3.5"/>'
            .'<path d="M6 22 Q28 13 50 22" stroke="#000000" stroke-width="3" fill="none" stroke-linecap="round"/>'
            .'<path d="M6 34 Q28 43 50 34" stroke="#000000" stroke-width="3" fill="none" stroke-linecap="round"/>'
            .'</g>',
            $cajaX, $cajaX, $caja, $radio, $logoX, $escala
        );
    }
}
