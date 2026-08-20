<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;   // <-- IMPORTANTE: Soluciona el error
use Illuminate\Support\Str;          // <-- Requerido para Str::random()
use Illuminate\Support\Carbon;       // <-- Requerido para Carbon::parse()
class CargaCreditosHistoricosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // IDs por defecto requeridos por tu BD para las llaves foráneas
                $idLocalPredeterminado = 3;
                $idUserPredeterminado  = 1;
                $idCajaPredeterminada  = 1;
                // Si no existe la caja predeterminada, la creamos respetando la estructura de tu tabla `cajas`
                    if (!DB::table('cajas')->where('id', $idCajaPredeterminada)->exists()) {
                        DB::table('cajas')->insert([
                            'id'                  => $idCajaPredeterminada,
                            'id_user'             => $idUserPredeterminado,
                            'id_local'            => $idLocalPredeterminado,
                            'monto_apertura_usd'  => 0.00,
                            'monto_apertura_bs'   => 0.00,
                            'fecha_apertura'      => '2026-08-01 08:00:00', // Fecha previa a la migración
                            'estado'              => 'abierta',
                            'created_at'          => now(),
                            'updated_at'          => now(),
                        ]);
                    }
                // DATA HISTÓRICA A MIGRAR
                $creditosViejos = [
                    //MARIN LECHERO
                    [
                        'cliente' => [
                            'nombre' => 'MARIN LECHERO',
                            'identificacion' => 'V-12345678',
                            'telefono' => '0414-1234567',
                            'direccion' => 'Centro',
                            'limite_credito' => 500.00
                        ],
                        'venta' => [
                            [
                                'created_at' => '2026-08-04 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 425.18],
                                    ['id_insumo' => 128, 'cantidad' => 1, 'precio_unitario' => 27.50],
                                ]
                            ],
                            [
                                'created_at' => '2026-08-05 15:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 1.00],
                                ]
                            ],
                            [
                                'created_at' => '2026-08-06 15:00:00',
                                'productos' => [
                                    ['id_insumo' => 139, 'cantidad' => 1, 'precio_unitario' => 6.00],
                                ]
                            ],
                            [
                                'created_at' => '2026-08-07 15:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 3.00],
                                    ['id_insumo' => 128, 'cantidad' => 1, 'precio_unitario' => 11.00],
                                ]
                            ],
                            [
                                'created_at' => '2026-08-08 15:00:00',
                                'productos' => [
                                    ['id_insumo' => 128, 'cantidad' => 1, 'precio_unitario' => 11.00],
                                    ['id_insumo' => 450, 'cantidad' => 1, 'precio_unitario' => 4.00],
                                ]
                            ],
                            [
                                'created_at' => '2026-08-09 15:00:00',
                                'productos' => [
                                    ['id_insumo' => 128, 'cantidad' => 1, 'precio_unitario' => 6.00],
                                ]
                            ],
                            [
                                'created_at' => '2026-08-10 15:00:00',
                                'productos' => [
                                    ['id_insumo' => 478, 'cantidad' => 1, 'precio_unitario' => 10.00],
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 36.50],
                                    ['id_insumo' => 2, 'cantidad' => 1, 'precio_unitario' => 7.80],
                                    ['id_insumo' => 3, 'cantidad' => 1, 'precio_unitario' => 4.60],
                                ]
                            ],
                        ],
                        'credito' => [
                            'fecha_vencimiento' => '2026-12-15',
                        ],
                        'abonos' => [
                            [
                                'created_at' => '2026-08-07 10:15:00',
                                'monto_usd' => 82.30,
                                'pago_usd_efectivo' => 82.30,
                                'pago_bs_efectivo' => 0.00,
                                'pago_punto_bs' => 0.00,
                                'pago_pagomovil_bs' => 0.00,
                                'detalles' => 'Abono inicial en físico'
                            ],
                        ]
                    ],
                    //JOSE EDUARDO
                    [
                        'cliente' => [
                            'nombre' => 'JOSE EDUARDO',
                            'identificacion' => 'V-10000000',
                            'telefono' => '0414-1234567',
                            'direccion' => 'Centro',
                            'limite_credito' => 500.00
                        ],
                        'venta' => [
                            'created_at' => '2026-05-15 10:00:00',
                            'productos' => [
                                ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 217.20],
                            ]
                        ],
                        'credito' => [
                            'fecha_vencimiento' => '2026-12-15',
                        ],
                        'abonos' => [
                            [
                                'created_at' => '2026-08-06 10:15:00',
                                'monto_usd' => 90.00,
                                'pago_usd_efectivo' => 90.00,
                                'pago_bs_efectivo' => 0.00,
                                'pago_punto_bs' => 0.00,
                                'pago_pagomovil_bs' => 0.00,
                                'detalles' => 'Abono inicial en físico'
                            ],
                        ]
                    ],
                    //MANGO
                    [
                        'cliente' => [
                            'nombre' => 'MANGO',
                            'identificacion' => 'V-10000001',
                            'telefono' => '0414-1234567',
                            'direccion' => 'Centro',
                            'limite_credito' => 500.00
                        ],
                        'venta' => [
                            'created_at' => '2026-05-15 10:00:00',
                            'productos' => [
                                ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 10.00],
                            ]
                        ],
                        'credito' => [
                            'fecha_vencimiento' => '2026-12-15',
                        ],
                        'abonos' => [
                            [
                                'created_at' => '2026-08-06 10:15:00',
                                'monto_usd' => 10.00,
                                'pago_usd_efectivo' => 10.00,
                                'pago_bs_efectivo' => 0.00,
                                'pago_punto_bs' => 0.00,
                                'pago_pagomovil_bs' => 0.00,
                                'detalles' => 'Abono inicial en físico'
                            ],
                        ]
                    ],
                    //GUSTAVO ROJAS
                    [
                        'cliente' => [
                            'nombre' => 'GUSTAVO ROJAS',
                            'identificacion' => 'V-10000002',
                            'telefono' => '0414-1234567',
                            'direccion' => 'Centro',
                            'limite_credito' => 500.00
                        ],
                        'venta' => [
                            'created_at' => '2026-05-15 10:00:00',
                            'productos' => [
                                ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 10.00],
                            ]
                        ],
                        'credito' => [
                            'fecha_vencimiento' => '2026-12-15',
                        ],
                        'abonos' => [
                            [
                                'created_at' => '2026-08-06 10:15:00',
                                'monto_usd' => 10.00,
                                'pago_usd_efectivo' => 10.00,
                                'pago_bs_efectivo' => 0.00,
                                'pago_punto_bs' => 0.00,
                                'pago_pagomovil_bs' => 0.00,
                                'detalles' => 'Abono inicial en físico'
                            ],
                        ]
                    ],
                    //ANTONIO CUANES
                    [
                        'cliente' => [
                            'nombre' => 'ANTONIO CUANES',
                            'identificacion' => 'V-10000003',
                            'telefono' => '0414-1234567',
                            'direccion' => 'Centro',
                            'limite_credito' => 500.00
                        ],
                        'venta' => [
                            [
                                'created_at' => '2026-07-18 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 80.00],
                                ]
                            ],
                            [
                                'created_at' => '2026-07-19 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 31.40],
                                ]
                            ],
                            [
                                'created_at' => '2026-07-23 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 41, 'cantidad' => 1, 'precio_unitario' => 6.00],
                                ]
                            ],
                            [
                                'created_at' => '2026-07-28 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 25.20],
                                ]
                            ],
                        ],
                        'credito' => [
                            'fecha_vencimiento' => '2026-12-15',
                        ],
                    ],
                    //DARWIN DIAZ
                    [
                        'cliente' => [
                            'nombre' => 'DARWIN DIAZ',
                            'identificacion' => 'V-10000004',
                            'telefono' => '0414-1234567',
                            'direccion' => 'Centro',
                            'limite_credito' => 500.00
                        ],
                        'venta' => [
                            [
                                'created_at' => '2026-07-17 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 50.60],
                                    ['id_insumo' => 162, 'cantidad' => 1, 'precio_unitario' => 4.20],
                                ]
                            ],
                            [
                                'created_at' => '2026-07-25 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 6.00],
                                ]
                            ],
                            [
                                'created_at' => '2026-07-26 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 126, 'cantidad' => 1, 'precio_unitario' => 8.00],
                                    ['id_insumo' => 41, 'cantidad' => 1, 'precio_unitario' => 3.60],
                                ]
                            ],
                            [
                                'created_at' => '2026-07-28 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 2.40],
                                ]
                            ],
                            [
                                'created_at' => '2026-07-29 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 12.00],
                                ]
                            ],
                            [
                                'created_at' => '2026-07-30 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 64, 'cantidad' => 1, 'precio_unitario' => 4.80],
                                ]
                            ],
                            [
                                'created_at' => '2026-08-07 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 3.60],
                                    ['id_insumo' => 19, 'cantidad' => 1, 'precio_unitario' => 3.60],
                                    ['id_insumo' => 761, 'cantidad' => 1, 'precio_unitario' => 1.10],
                                ]
                            ],
                        ],
                        'credito' => [
                            'fecha_vencimiento' => '2026-12-15',
                        ],
                        'abonos' => [
                            [
                                'created_at' => '2026-07-22 10:15:00',
                                'monto_usd' => 12.00,
                                'pago_usd_efectivo' => 12.00,
                                'pago_bs_efectivo' => 0.00,
                                'pago_punto_bs' => 0.00,
                                'pago_pagomovil_bs' => 0.00,
                                'detalles' => 'Abono inicial en físico'
                            ],
                            [
                                'created_at' => '2026-07-29 10:15:00',
                                'monto_usd' => 16.00,
                                'pago_usd_efectivo' => 16.00,
                                'pago_bs_efectivo' => 0.00,
                                'pago_punto_bs' => 0.00,
                                'pago_pagomovil_bs' => 0.00,
                                'detalles' => 'Abono inicial en físico'
                            ],
                            [
                                'created_at' => '2026-07-30 10:15:00',
                                'monto_usd' => 14.00,
                                'pago_usd_efectivo' => 14.00,
                                'pago_bs_efectivo' => 0.00,
                                'pago_punto_bs' => 0.00,
                                'pago_pagomovil_bs' => 0.00,
                                'detalles' => 'Abono inicial en físico'
                            ],
                        ]
                    ],
                    //FRANKLIN RUIZ
                    [
                        'cliente' => [
                            'nombre' => 'FRANKLIN RUIZ',
                            'identificacion' => 'V-10000005',
                            'telefono' => '0414-1234567',
                            'direccion' => 'Centro',
                            'limite_credito' => 500.00
                        ],
                        'venta' => [
                            [
                                'created_at' => '2026-02-06 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 60.70],
                                    ['id_insumo' => 2, 'cantidad' => 1, 'precio_unitario' => 77.00],
                                    ['id_insumo' => 126, 'cantidad' => 1, 'precio_unitario' => 9.00],
                                ]
                            ],
                            [
                                'created_at' => '2026-07-25 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 18.20],
                                    ['id_insumo' => 2, 'cantidad' => 1, 'precio_unitario' => 13.00],
                                ]
                            ],
                            [
                                'created_at' => '2026-07-28 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 4.50],
                                    ['id_insumo' => 126, 'cantidad' => 1, 'precio_unitario' => 6.00],
                                    ['id_insumo' => 2, 'cantidad' => 1, 'precio_unitario' => 1.20],
                                ]
                            ],
                            [
                                'created_at' => '2026-07-28 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 2.40],
                                ]
                            ],
                            [
                                'created_at' => '2026-08-03 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 32.40],
                                    ['id_insumo' => 2, 'cantidad' => 1, 'precio_unitario' => 11.40],
                                    ['id_insumo' => 3, 'cantidad' => 1, 'precio_unitario' => 27.40],
                                ]
                            ],
                            [
                                'created_at' => '2026-08-04 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 112.00],
                                ]
                            ],
                        ],
                        'credito' => [
                            'fecha_vencimiento' => '2026-12-15',
                        ],
                        'abonos' => [
                            [
                                'created_at' => '2026-07-10 10:15:00',
                                'monto_usd' => 150.00,
                                'pago_usd_efectivo' => 150.00,
                                'pago_bs_efectivo' => 0.00,
                                'pago_punto_bs' => 0.00,
                                'pago_pagomovil_bs' => 0.00,
                                'detalles' => 'Abono inicial en físico'
                            ],
                        ]
                    ],
                    //RAFA LECHERO
                    [
                        'cliente' => [
                            'nombre' => 'RAFA LECHERO',
                            'identificacion' => 'V-10000006',
                            'telefono' => '0414-1234567',
                            'direccion' => 'Centro',
                            'limite_credito' => 500.00
                        ],
                        'venta' => [
                            [
                                'created_at' => '2026-02-12 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 46.00],
                                ]
                            ],
                            [
                                'created_at' => '2026-02-13 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 4.20],
                                    ['id_insumo' => 2, 'cantidad' => 1, 'precio_unitario' => 9.00],
                                ]
                            ],
                            [
                                'created_at' => '2026-02-24 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 3.40],
                                ]
                            ],
                            [
                                'created_at' => '2026-03-15 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 6.00],
                                    ['id_insumo' => 2, 'cantidad' => 1, 'precio_unitario' => 15.00],
                                ]
                            ],
                            [
                                'created_at' => '2026-04-16 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 3.00],
                                ]
                            ],
                        ],
                        'credito' => [
                            'fecha_vencimiento' => '2026-12-15',
                        ],
                    ],
                    //SARRMERA
                    [
                        'cliente' => [
                            'nombre' => 'SARRAMERA',
                            'identificacion' => 'V-10000007',
                            'telefono' => '0414-1234567',
                            'direccion' => 'Centro',
                            'limite_credito' => 500.00
                        ],
                        'venta' => [
                            [
                                'created_at' => '2026-07-26 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 55.00],
                                ]
                            ],
                        ],
                        'credito' => [
                            'fecha_vencimiento' => '2026-12-15',
                        ],
                    ],
                    //RAMON LECHERO
                    [
                        'cliente' => [
                            'nombre' => 'RAMON LECHERO',
                            'identificacion' => 'V-10000008',
                            'telefono' => '0414-1234567',
                            'direccion' => 'Centro',
                            'limite_credito' => 500.00
                        ],
                        'venta' => [
                            [
                                'created_at' => '2026-07-31 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 6.00],
                                    ['id_insumo' => 2, 'cantidad' => 1, 'precio_unitario' => 8.00],
                                ]
                            ],
                            [
                                'created_at' => '2026-08-06 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 52.00],
                                    ['id_insumo' => 2, 'cantidad' => 1, 'precio_unitario' => 4.50],
                                ]
                            ],
                            [
                                'created_at' => '2026-08-08 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 25.00],
                                ]
                            ],
                            [
                                'created_at' => '2026-08-10 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 6.00],
                                    ['id_insumo' => 2, 'cantidad' => 1, 'precio_unitario' => 8.00],
                                ]
                            ],
                        ],
                        'credito' => [
                            'fecha_vencimiento' => '2026-12-15',
                        ],
                        'abonos' => [
                            [
                                'created_at' => '2026-08-08 10:15:00',
                                'monto_usd' => 25.00,
                                'pago_usd_efectivo' => 25.00,
                                'pago_bs_efectivo' => 0.00,
                                'pago_punto_bs' => 0.00,
                                'pago_pagomovil_bs' => 0.00,
                                'detalles' => 'Abono inicial en físico'
                            ],
                        ]
                    ],
                    //MIKE MECANICO
                    [
                        'cliente' => [
                            'nombre' => 'MIKE MECANICO',
                            'identificacion' => 'V-10000009',
                            'telefono' => '0414-1234567',
                            'direccion' => 'Centro',
                            'limite_credito' => 500.00
                        ],
                        'venta' => [
                            [
                                'created_at' => '2026-02-22 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 3.00],
                                    ['id_insumo' => 2, 'cantidad' => 1, 'precio_unitario' => 2.80],
                                ]
                            ],
                            [
                                'created_at' => '2026-04-26 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 5.00],
                                ]
                            ],
                            [
                                'created_at' => '2026-05-03 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 5.00],
                                ]
                            ],
                            [
                                'created_at' => '2026-07-04 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 4.00],
                                ]
                            ],
                        ],
                        'credito' => [
                            'fecha_vencimiento' => '2026-12-15',
                        ],
                        'abonos' => [
                            [
                                'created_at' => '2026-07-11 10:15:00',
                                'monto_usd' => 10.60,
                                'pago_usd_efectivo' => 10.60,
                                'pago_bs_efectivo' => 0.00,
                                'pago_punto_bs' => 0.00,
                                'pago_pagomovil_bs' => 0.00,
                                'detalles' => 'Abono inicial en físico'
                            ],
                            [
                                'created_at' => '2026-07-17 10:15:00',
                                'monto_usd' => 6.00,
                                'pago_usd_efectivo' => 6.00,
                                'pago_bs_efectivo' => 0.00,
                                'pago_punto_bs' => 0.00,
                                'pago_pagomovil_bs' => 0.00,
                                'detalles' => 'Abono inicial en físico'
                            ],
                        ]
                    ],
                    //JONIER
                    [
                        'cliente' => [
                            'nombre' => 'JONIER',
                            'identificacion' => 'V-10000010',
                            'telefono' => '0414-1234567',
                            'direccion' => 'Centro',
                            'limite_credito' => 500.00
                        ],
                        'venta' => [
                            [
                                'created_at' => '2026-08-03 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 6.00],
                                    ['id_insumo' => 2, 'cantidad' => 1, 'precio_unitario' => 6.00],
                                ]
                            ],
                            [
                                'created_at' => '2026-08-05 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 26.00],
                                    ['id_insumo' => 2, 'cantidad' => 1, 'precio_unitario' => 24.00],
                                    ['id_insumo' => 3, 'cantidad' => 1, 'precio_unitario' => 8.00],
                                    ['id_insumo' => 4, 'cantidad' => 1, 'precio_unitario' => 0.40],
                                ]
                            ],
                        ],
                        'credito' => [
                            'fecha_vencimiento' => '2026-12-15',
                        ],
                        'abonos' => [
                            [
                                'created_at' => '2026-08-01 10:15:00',
                                'monto_usd' => 20.00,
                                'pago_usd_efectivo' => 20.00,
                                'pago_bs_efectivo' => 0.00,
                                'pago_punto_bs' => 0.00,
                                'pago_pagomovil_bs' => 0.00,
                                'detalles' => 'Abono inicial en físico'
                            ],
                        ]
                    ],
                    //PEDRO SR BOMBA
                    [
                        'cliente' => [
                            'nombre' => 'PEDRO SR BOMBA',
                            'identificacion' => 'V-10000011',
                            'telefono' => '0414-1234567',
                            'direccion' => 'Centro',
                            'limite_credito' => 500.00
                        ],
                        'venta' => [
                            [
                                'created_at' => '2026-07-25 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 49.50],
                                ]
                            ],
                            [
                                'created_at' => '2026-08-07 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 65.20],
                                ]
                            ],
                        ],
                        'credito' => [
                            'fecha_vencimiento' => '2026-12-15',
                        ]
                    ],
                    //GOLLO
                    [
                        'cliente' => [
                            'nombre' => 'GOLLO',
                            'identificacion' => 'V-10000012',
                            'telefono' => '0414-1234567',
                            'direccion' => 'Centro',
                            'limite_credito' => 500.00
                        ],
                        'venta' => [
                            [
                                'created_at' => '2026-07-29 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 4.20],
                                    ['id_insumo' => 2, 'cantidad' => 1, 'precio_unitario' => 6.00],
                                    ['id_insumo' => 3, 'cantidad' => 1, 'precio_unitario' => 2.40],
                                ]
                            ],
                            [
                                'created_at' => '2026-07-30 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 25.00],
                                    ['id_insumo' => 2, 'cantidad' => 1, 'precio_unitario' => 20.00],
                                    ['id_insumo' => 3, 'cantidad' => 1, 'precio_unitario' => 2.40],
                                ]
                            ],
                            [
                                'created_at' => '2026-08-02 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 6.00],
                                ]
                            ],
                        ],
                        'credito' => [
                            'fecha_vencimiento' => '2026-12-15',
                        ],
                        'abonos' => [
                            [
                                'created_at' => '2026-07-19 10:15:00',
                                'monto_usd' => 20.00,
                                'pago_usd_efectivo' => 20.00,
                                'pago_bs_efectivo' => 0.00,
                                'pago_punto_bs' => 0.00,
                                'pago_pagomovil_bs' => 0.00,
                                'detalles' => 'Abono inicial en físico'
                            ],
                        ]
                    ],
                    //BIGOTE
                    [
                        'cliente' => [
                            'nombre' => 'BIGOTE',
                            'identificacion' => 'V-10000013',
                            'telefono' => '0414-1234567',
                            'direccion' => 'Centro',
                            'limite_credito' => 500.00
                        ],
                        'venta' => [
                            [
                                'created_at' => '2026-08-10 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 84.80],
                                    ['id_insumo' => 2, 'cantidad' => 1, 'precio_unitario' => 32.00],
                                ]
                            ],
                        ],
                        'credito' => [
                            'fecha_vencimiento' => '2026-12-15',
                        ]
                    ],
                    //VIDAL CANACHE
                    [
                        'cliente' => [
                            'nombre' => 'VIDAL CANACHE',
                            'identificacion' => 'V-10000014',
                            'telefono' => '0414-1234567',
                            'direccion' => 'Centro',
                            'limite_credito' => 500.00
                        ],
                        'venta' => [
                            [
                                'created_at' => '2026-06-25 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 40.00],
                                ]
                            ],
                            [
                                'created_at' => '2026-06-07 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 55.00],
                                ]
                            ]
                        ],
                        'credito' => [
                            'fecha_vencimiento' => '2026-12-15',
                        ],
                        'abonos' => [
                            [
                                'created_at' => '2026-06-25 10:15:00',
                                'monto_usd' => 20.00,
                                'pago_usd_efectivo' => 20.00,
                                'pago_bs_efectivo' => 0.00,
                                'pago_punto_bs' => 0.00,
                                'pago_pagomovil_bs' => 0.00,
                                'detalles' => 'Abono inicial en físico'
                            ],
                        ]
                    ],
                    //JULIO CHARACO
                    [
                        'cliente' => [
                            'nombre' => 'JULIO CHARACO',
                            'identificacion' => 'V-17082188',
                            'telefono' => '0424-3492059',
                            'direccion' => 'ASERRADERO',
                            'limite_credito' => 500.00
                        ],
                        'venta' => [
                            [
                                'created_at' => '2025-07-19 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 59.00],
                                ]
                            ],
                            [
                                'created_at' => '2025-12-30 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 4.00],
                                    ['id_insumo' => 2, 'cantidad' => 1, 'precio_unitario' => 2.40],
                                ]
                            ],
                            [
                                'created_at' => '2026-05-31 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 5.40],
                                ]
                            ],
                            [
                                'created_at' => '2026-07-02 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 6.00],
                                ]
                            ],
                            [
                                'created_at' => '2026-07-03 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 18.00],
                                    ['id_insumo' => 2, 'cantidad' => 1, 'precio_unitario' => 5.10],
                                ]
                            ],
                            [
                                'created_at' => '2026-07-15 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 14.30],
                                    ['id_insumo' => 2, 'cantidad' => 1, 'precio_unitario' => 7.00],
                                ]
                            ],
                        ],
                        'credito' => [
                            'fecha_vencimiento' => '2026-12-15',
                        ],
                        'abonos' => [
                            [
                                'created_at' => '2025-09-10 10:15:00',
                                'monto_usd' => 30.91,
                                'pago_usd_efectivo' => 30.91,
                                'pago_bs_efectivo' => 0.00,
                                'pago_punto_bs' => 0.00,
                                'pago_pagomovil_bs' => 0.00,
                                'detalles' => 'Abono inicial en físico'
                            ],
                        ]
                    ],
                    //ZOTO GUARDIA
                    [
                        'cliente' => [
                            'nombre' => 'ZOTO GUARDIA',
                            'identificacion' => 'V-10000015',
                            'telefono' => '0414-1234567',
                            'direccion' => 'Centro',
                            'limite_credito' => 500.00
                        ],
                        'venta' => [
                            [
                                'created_at' => '2026-06-07 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 18.20],
                                ]
                            ],
                            [
                                'created_at' => '2026-06-20 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 7.80],
                                ]
                            ],
                            [
                                'created_at' => '2026-06-22 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 11.80],
                                ]
                            ],
                            [
                                'created_at' => '2026-06-25 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 38.00],
                                ]
                            ],
                            [
                                'created_at' => '2026-07-07 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 30.70],
                                    ['id_insumo' => 2, 'cantidad' => 1, 'precio_unitario' => 2.40],
                                ]
                            ],
                            [
                                'created_at' => '2026-07-11 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 3.60],
                                ]
                            ],
                            [
                                'created_at' => '2026-07-19 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 8.00],
                                ]
                            ],
                            [
                                'created_at' => '2026-07-20 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 22.20],
                                    ['id_insumo' => 2, 'cantidad' => 1, 'precio_unitario' => 7.50],
                                ]
                            ],
                            [
                                'created_at' => '2026-07-23 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 111.80],
                                    ['id_insumo' => 2, 'cantidad' => 1, 'precio_unitario' => 7.50],
                                ]
                            ],
                        ],
                        'credito' => [
                            'fecha_vencimiento' => '2026-12-15',
                        ]
                    ],
                    //LUIS ENRIQUE
                    [
                        'cliente' => [
                            'nombre' => 'LUIS ENRIQUE',
                            'identificacion' => 'V-10000016',
                            'telefono' => '0414-1234567',
                            'direccion' => 'Centro',
                            'limite_credito' => 500.00
                        ],
                        'venta' => [
                            [
                                'created_at' => '2026-04-22 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 23.50],
                                    ['id_insumo' => 2, 'cantidad' => 1, 'precio_unitario' => 2.40],
                                ]
                            ],
                            [
                                'created_at' => '2026-04-26 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 40.00],
                                ]
                            ],
                            [
                                'created_at' => '2026-05-01 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 12.20],
                                ]
                            ],
                            [
                                'created_at' => '2026-05-18 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 23.00],
                                    ['id_insumo' => 2, 'cantidad' => 1, 'precio_unitario' => 6.00],
                                ]
                            ],
                            [
                                'created_at' => '2026-05-19 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 5.10],
                                ]
                            ],
                            [
                                'created_at' => '2026-05-20 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 3.60],
                                ]
                            ],
                            [
                                'created_at' => '2026-05-27 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 2.00],
                                ]
                            ],
                            [
                                'created_at' => '2026-06-09 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 10.00],
                                    ['id_insumo' => 2, 'cantidad' => 1, 'precio_unitario' => 6.00],
                                ]
                            ],
                            [
                                'created_at' => '2026-06-10 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 6.00],
                                ]
                            ],
                            [
                                'created_at' => '2026-06-17 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 2.40],
                                ]
                            ],
                            [
                                'created_at' => '2026-06-20 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 35.00],
                                ]
                            ],
                            [
                                'created_at' => '2026-06-26 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 25.00],
                                    ['id_insumo' => 2, 'cantidad' => 1, 'precio_unitario' => 16.00],
                                    ['id_insumo' => 3, 'cantidad' => 1, 'precio_unitario' => 9.00],
                                    ['id_insumo' => 4, 'cantidad' => 1, 'precio_unitario' => 7.00],
                                    ['id_insumo' => 5, 'cantidad' => 1, 'precio_unitario' => 3.60],
                                    ['id_insumo' => 6, 'cantidad' => 1, 'precio_unitario' => 3.00],
                                    ['id_insumo' => 7, 'cantidad' => 1, 'precio_unitario' => 3.60],
                                    ['id_insumo' => 8, 'cantidad' => 1, 'precio_unitario' => 2.40],

                                ]
                            ],
                            [
                                'created_at' => '2026-06-30 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 23.20],
                                ]
                            ],
                            [
                                'created_at' => '2026-07-01 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 6.00],
                                    ['id_insumo' => 2, 'cantidad' => 1, 'precio_unitario' => 33.00],
                                    ['id_insumo' => 3, 'cantidad' => 1, 'precio_unitario' => 15.00],
                                ]
                            ],
                            [
                                'created_at' => '2026-07-03 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 30.00],
                                ]
                            ],
                            [
                                'created_at' => '2026-07-05 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 6.00],
                                ]
                            ],
                            [
                                'created_at' => '2026-07-16 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 33.00],
                                ]
                            ],
                            [
                                'created_at' => '2026-07-20 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 76.00],
                                    ['id_insumo' => 2, 'cantidad' => 1, 'precio_unitario' => 2.40],
                                ]
                            ],
                            [
                                'created_at' => '2026-07-29 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 52.00],
                                ]
                            ],
                            [
                                'created_at' => '2026-07-31 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 3.60],
                                    ['id_insumo' => 2, 'cantidad' => 1, 'precio_unitario' => 2.40],
                                ]
                            ],
                            [
                                'created_at' => '2026-08-04 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 4.80],
                                ]
                            ],
                        ],
                        'credito' => [
                            'fecha_vencimiento' => '2026-12-15',
                        ],
                        'abonos' => [
                            [
                                'created_at' => '2025-07-20 10:15:00',
                                'monto_usd' => 200.00,
                                'pago_usd_efectivo' => 200.00,
                                'pago_bs_efectivo' => 0.00,
                                'pago_punto_bs' => 0.00,
                                'pago_pagomovil_bs' => 0.00,
                                'detalles' => 'Abono inicial en físico'
                            ],
                        ]
                    ],
                    //URIANTO
                    [
                        'cliente' => [
                            'nombre' => 'URIANTO',
                            'identificacion' => 'V-10000017',
                            'telefono' => '0414-1234567',
                            'direccion' => 'Centro',
                            'limite_credito' => 500.00
                        ],
                        'venta' => [
                            [
                                'created_at' => '2026-07-06 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 10.00],
                                ]
                            ],
                            [
                                'created_at' => '2026-07-23 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 78.90],
                                ]
                            ],
                        ],
                        'credito' => [
                            'fecha_vencimiento' => '2026-12-15',
                        ],
                    ],
                    //BEILA
                    [
                        'cliente' => [
                            'nombre' => 'BEILA JIMENEZ',
                            'identificacion' => 'V-10000018',
                            'telefono' => '0414-1234567',
                            'direccion' => 'Centro',
                            'limite_credito' => 500.00
                        ],
                        'venta' => [
                            [
                                'created_at' => '2026-07-06 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 52.80],
                                ]
                            ]
                        ],
                        'credito' => [
                            'fecha_vencimiento' => '2026-12-15',
                        ],
                    ],
                    //JUNSE BANDRES
                    [
                        'cliente' => [
                            'nombre' => 'JUNSE BANDRES',
                            'identificacion' => 'V-10000019',
                            'telefono' => '0414-1234567',
                            'direccion' => 'Centro',
                            'limite_credito' => 500.00
                        ],
                        'venta' => [
                            [
                                'created_at' => '2026-08-10 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 13.50],
                                    ['id_insumo' => 2, 'cantidad' => 1, 'precio_unitario' => 11.50],
                                ]
                            ]
                        ],
                        'credito' => [
                            'fecha_vencimiento' => '2026-12-15',
                        ],
                    ],
                    //LOS BABOS
                    [
                        'cliente' => [
                            'nombre' => 'LOS BABOS',
                            'identificacion' => 'V-10000020',
                            'telefono' => '0414-1234567',
                            'direccion' => 'Centro',
                            'limite_credito' => 500.00
                        ],
                        'venta' => [
                            [
                                'created_at' => '2026-07-09 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 2.40],
                                ]
                            ],
                            [
                                'created_at' => '2026-07-11 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 23.80],
                                ]
                            ],
                            [
                                'created_at' => '2026-07-13 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 10.00],
                                ]
                            ],
                        ],
                        'credito' => [
                            'fecha_vencimiento' => '2026-12-15',
                        ],
                    ],
                    //WUILY
                    [
                        'cliente' => [
                            'nombre' => 'WUILY',
                            'identificacion' => 'V-10000021',
                            'telefono' => '0414-1234567',
                            'direccion' => 'Centro',
                            'limite_credito' => 500.00
                        ],
                        'venta' => [
                            [
                                'created_at' => '2025-10-25 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 30.00],
                                ]
                            ],
                            [
                                'created_at' => '2025-11-05 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 16.20],
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 6.00],
                                ]
                            ],
                            [
                                'created_at' => '2026-11-23 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 4.20],
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 6.00],
                                ]
                            ],
                        ],
                        'credito' => [
                            'fecha_vencimiento' => '2026-12-15',
                        ],
                        'abonos' => [
                            [
                                'created_at' => '2026-10-25 10:15:00',
                                'monto_usd' => 5.00,
                                'pago_usd_efectivo' => 5.00,
                                'pago_bs_efectivo' => 0.00,
                                'pago_punto_bs' => 0.00,
                                'pago_pagomovil_bs' => 0.00,
                                'detalles' => 'Abono inicial en físico'
                            ],
                            [
                                'created_at' => '2026-11-05 10:15:00',
                                'monto_usd' => 10.00,
                                'pago_usd_efectivo' => 10.00,
                                'pago_bs_efectivo' => 0.00,
                                'pago_punto_bs' => 0.00,
                                'pago_pagomovil_bs' => 0.00,
                                'detalles' => 'Abono inicial en físico'
                            ],
                            [
                                'created_at' => '2026-11-23 10:15:00',
                                'monto_usd' => 4.00,
                                'pago_usd_efectivo' => 4.00,
                                'pago_bs_efectivo' => 0.00,
                                'pago_punto_bs' => 0.00,
                                'pago_pagomovil_bs' => 0.00,
                                'detalles' => 'Abono inicial en físico'
                            ],
                        ]
                    ],
                    //DANIER
                    [
                        'cliente' => [
                            'nombre' => 'DANIER',
                            'identificacion' => 'V-10000022',
                            'telefono' => '0414-1234567',
                            'direccion' => 'Centro',
                            'limite_credito' => 500.00
                        ],
                        'venta' => [
                            [
                                'created_at' => '2026-07-04 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 27.00],
                                    ['id_insumo' => 2, 'cantidad' => 1, 'precio_unitario' => 72.00],
                                    ['id_insumo' => 3, 'cantidad' => 1, 'precio_unitario' => 7.00],
                                ]
                            ],
                            [
                                'created_at' => '2026-07-10 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 10.00],
                                ]
                            ],
                        ],
                        'credito' => [
                            'fecha_vencimiento' => '2026-12-15',
                        ],
                        'abonos' => [
                            [
                                'created_at' => '2026-07-04 10:15:00',
                                'monto_usd' => 24.44,
                                'pago_usd_efectivo' => 24.44,
                                'pago_bs_efectivo' => 0.00,
                                'pago_punto_bs' => 0.00,
                                'pago_pagomovil_bs' => 0.00,
                                'detalles' => 'Abono inicial en físico'
                            ],
                            [
                                'created_at' => '2026-07-10 10:15:00',
                                'monto_usd' => 13.80,
                                'pago_usd_efectivo' => 13.80,
                                'pago_bs_efectivo' => 0.00,
                                'pago_punto_bs' => 0.00,
                                'pago_pagomovil_bs' => 0.00,
                                'detalles' => 'Abono inicial en físico'
                            ],
                            [
                                'created_at' => '2026-07-19 10:15:00',
                                'monto_usd' => 50.00,
                                'pago_usd_efectivo' => 50.00,
                                'pago_bs_efectivo' => 0.00,
                                'pago_punto_bs' => 0.00,
                                'pago_pagomovil_bs' => 0.00,
                                'detalles' => 'Abono inicial en físico'
                            ],
                            [
                                'created_at' => '2026-08-09 10:15:00',
                                'monto_usd' => 20.00,
                                'pago_usd_efectivo' => 20.00,
                                'pago_bs_efectivo' => 0.00,
                                'pago_punto_bs' => 0.00,
                                'pago_pagomovil_bs' => 0.00,
                                'detalles' => 'Abono inicial en físico'
                            ],
                        ]
                    ],
                    //JORGE PALACIOS
                    [
                        'cliente' => [
                            'nombre' => 'JORGE PALACIOS',
                            'identificacion' => 'V-10000023',
                            'telefono' => '0414-1234567',
                            'direccion' => 'Centro',
                            'limite_credito' => 500.00
                        ],
                        'venta' => [
                            [
                                'created_at' => '2026-07-21 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 397.10],
                                ]
                            ],
                            [
                                'created_at' => '2026-07-26 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 90.00],
                                    ['id_insumo' => 2, 'cantidad' => 1, 'precio_unitario' => 4.50],
                                ]
                            ],
                            [
                                'created_at' => '2026-07-29 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 2.40],
                                ]
                            ],
                        ],
                        'credito' => [
                            'fecha_vencimiento' => '2026-12-15',
                        ],
                    ],
                    //ANGEL HIJO DE CHICO
                    [
                        'cliente' => [
                            'nombre' => 'ANGEL HIJO DE CHICO',
                            'identificacion' => 'V-10000024',
                            'telefono' => '0414-1234567',
                            'direccion' => 'Centro',
                            'limite_credito' => 500.00
                        ],
                        'venta' => [
                            [
                                'created_at' => '2026-07-28 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 25.70],
                                    ['id_insumo' => 2, 'cantidad' => 1, 'precio_unitario' => 24.00],
                                ]
                            ],
                            [
                                'created_at' => '2026-08-09 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 90.00],
                                    ['id_insumo' => 2, 'cantidad' => 1, 'precio_unitario' => 9.60],
                                ]
                            ],
                        ],
                        'credito' => [
                            'fecha_vencimiento' => '2026-12-15',
                        ],
                    ],
                    //HENRY BABOS
                    [
                        'cliente' => [
                            'nombre' => 'ANGEL HIJO DE CHICO',
                            'identificacion' => 'V-10000024',
                            'telefono' => '0414-1234567',
                            'direccion' => 'Centro',
                            'limite_credito' => 500.00
                        ],
                        'venta' => [
                            [
                                'created_at' => '2026-05-25 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 5.60],
                                    ['id_insumo' => 2, 'cantidad' => 1, 'precio_unitario' => 6.00],
                                ]
                            ],
                        ],
                        'credito' => [
                            'fecha_vencimiento' => '2026-12-15',
                        ],
                    ],
                    //SERGIO PEREZ
                    [
                        'cliente' => [
                            'nombre' => 'SERGIO PEREZ',
                            'identificacion' => 'V-10000025',
                            'telefono' => '0414-1234567',
                            'direccion' => 'Centro',
                            'limite_credito' => 500.00
                        ],
                        'venta' => [
                            [
                                'created_at' => '2026-05-25 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 90.00],
                                ]
                            ],
                        ],
                        'credito' => [
                            'fecha_vencimiento' => '2026-12-15',
                        ],
                    ],
                    
                    //HECTOR NOGUERA
                    [
                        'cliente' => [
                            'nombre' => 'HECTOR NOGUERA',
                            'identificacion' => 'V-10000027',
                            'telefono' => '0414-1234567',
                            'direccion' => 'Centro',
                            'limite_credito' => 500.00
                        ],
                        'venta' => [
                            [
                                'created_at' => '2026-07-04 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 37.40],
                                ]
                            ],
                            [
                                'created_at' => '2026-07-05 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 13.00],
                                ]
                            ],
                            [
                                'created_at' => '2026-07-08 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 11.00],
                                ]
                            ],
                            [
                                'created_at' => '2026-07-18 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 65.00],
                                    ['id_insumo' => 2, 'cantidad' => 1, 'precio_unitario' => 18.00],

                                ]
                            ],
                            [
                                'created_at' => '2026-07-19 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 11.00],
                                ]
                            ],
                            [
                                'created_at' => '2026-08-06 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 6.00],
                                ]
                            ],
                        ],
                        'credito' => [
                            'fecha_vencimiento' => '2026-12-15',
                        ],
                        'abonos' => [
                            [
                                'created_at' => '2026-07-18 10:15:00',
                                'monto_usd' => 40.00,
                                'pago_usd_efectivo' => 40.00,
                                'pago_bs_efectivo' => 0.00,
                                'pago_punto_bs' => 0.00,
                                'pago_pagomovil_bs' => 0.00,
                                'detalles' => 'Abono inicial en físico'
                            ],
                            [
                                'created_at' => '2026-07-25 10:15:00',
                                'monto_usd' => 37.00,
                                'pago_usd_efectivo' => 37.00,
                                'pago_bs_efectivo' => 0.00,
                                'pago_punto_bs' => 0.00,
                                'pago_pagomovil_bs' => 0.00,
                                'detalles' => 'Abono inicial en físico'
                            ],
                        ]
                    ],
                    //OSWALDO
                    [
                        'cliente' => [
                            'nombre' => 'OSWALDO',
                            'identificacion' => 'V-10000028',
                            'telefono' => '0414-1234567',
                            'direccion' => 'Centro',
                            'limite_credito' => 500.00
                        ],
                        'venta' => [
                            [
                                'created_at' => '2026-06-26 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 10.00],
                                    ['id_insumo' => 2, 'cantidad' => 1, 'precio_unitario' => 4.00],
                                ]
                            ],
                            [
                                'created_at' => '2026-07-24 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 3.60],
                                ]
                            ],
                        ],
                        'credito' => [
                            'fecha_vencimiento' => '2026-12-15',
                        ],
                    ],
                    //GUORNER RON
                    [
                        'cliente' => [
                            'nombre' => 'GUORNER RON',
                            'identificacion' => 'V-10000029',
                            'telefono' => '0414-1234567',
                            'direccion' => 'Centro',
                            'limite_credito' => 500.00
                        ],
                        'venta' => [
                            [
                                'created_at' => '2026-06-14 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 50.00],
                                ]
                            ],
                            [
                                'created_at' => '2026-06-18 10:00:00',
                                'productos' => [
                                    ['id_insumo' => 1, 'cantidad' => 1, 'precio_unitario' => 9.00],
                                ]
                            ],
                        ],
                        'credito' => [
                            'fecha_vencimiento' => '2026-12-15',
                        ],
                    ],

                ];

                foreach ($creditosViejos as $item) {
                    DB::transaction(function () use ($item, $idLocalPredeterminado, $idUserPredeterminado, $idCajaPredeterminada) {
                        
                        // Normalizamos las ventas para tratar siempre $ventasArray como una lista de ventas
                        $ventasArray = isset($item['venta'][0]) ? $item['venta'] : [$item['venta']];

                        // Tomamos la fecha de la primera venta para usarla en el registro del cliente
                        $fechaPrimerVenta = Carbon::parse($ventasArray[0]['created_at']);

                        // 1. CLIENTES (Tabla: clientes)
                        DB::table('clientes')->updateOrInsert(
                            ['identificacion' => $item['cliente']['identificacion']],
                            [
                                'nombre' => $item['cliente']['nombre'],
                                'telefono' => $item['cliente']['telefono'],
                                'direccion' => $item['cliente']['direccion'] ?? null,
                                'limite_credito' => 500.00,
                                'id_local' => $idLocalPredeterminado,
                                'activo' => 'activo',
                                'created_at' => $fechaPrimerVenta,
                                'updated_at' => $fechaPrimerVenta,
                            ]
                        );

                        $idCliente = DB::table('clientes')
                            ->where('identificacion', $item['cliente']['identificacion'])
                            ->value('id');

                        // Recorremos CADA UNA de las ventas asociadas al cliente
                        foreach ($ventasArray as $index => $ventaData) {
                            $fechaVentaEspecifica = Carbon::parse($ventaData['created_at']);

                            // Cálculo del total de la venta actual
                            $totalVentaUsd = collect($ventaData['productos'])->sum(function ($p) {
                                return $p['cantidad'] * $p['precio_unitario'];
                            });

                            // Asignamos los abonos únicamente a la primera venta procesada
                            //$totalAbonadoUsd = ($index === 0) ? collect($item['abonos'])->sum('monto_usd') : 0;
                            // Si no existe 'abonos' en $item, usará un array vacío [] automáticamente
                            $totalAbonadoUsd = ($index === 0) ? collect($item['abonos'] ?? [])->sum('monto_usd') : 0;
                            $saldoPendienteUsd = $totalVentaUsd - $totalAbonadoUsd;

                            // 2. VENTAS (Tabla: ventas)
                            $idVenta = DB::table('ventas')->insertGetId([
                                'codigo_factura' => 'MIG-' . strtoupper(Str::random(6)),
                                'id_cliente' => $idCliente,
                                'id_user' => $idUserPredeterminado,
                                'id_local' => $idLocalPredeterminado,
                                'id_caja' => $idCajaPredeterminada,
                                'pago_usd_efectivo' => 0.00,
                                'pago_bs_efectivo' => 0.00,
                                'monto_credito_usd' => $totalVentaUsd,
                                'total_usd' => $totalVentaUsd,
                                'estado' => 'completada',
                                'created_at' => $fechaVentaEspecifica,
                                'updated_at' => $fechaVentaEspecifica,
                            ]);

                            // 3. DETALLE DE VENTAS (Tabla: detalle_ventas)
                            foreach ($ventaData['productos'] as $prod) {
                                $subtotal = $prod['cantidad'] * $prod['precio_unitario'];
                                DB::table('detalle_ventas')->insert([
                                    'id_venta' => $idVenta,
                                    'id_insumo' => $prod['id_insumo'],
                                    'cantidad' => $prod['cantidad'],
                                    'precio_unitario' => $prod['precio_unitario'],
                                    'subtotal' => $subtotal,
                                    'created_at' => $fechaVentaEspecifica,
                                    'updated_at' => $fechaVentaEspecifica,
                                ]);
                            }

                            // 4. CRÉDITOS (Tabla: creditos)
                            $estadoCredito = $saldoPendienteUsd <= 0 ? 'pagado' : 'pendiente';
                            
                            $idCredito = DB::table('creditos')->insertGetId([
                                'id_venta' => $idVenta,
                                'id_cliente' => $idCliente,
                                'monto_inicial' => $totalVentaUsd,
                                'saldo_pendiente' => $saldoPendienteUsd,
                                'saldo_a_favor' => 0.00,
                                'fecha_vencimiento' => $item['credito']['fecha_vencimiento'],
                                'estado' => $estadoCredito,
                                'created_at' => $fechaVentaEspecifica,
                                'updated_at' => $fechaVentaEspecifica,
                            ]);

                            // 5. ABONOS DE CRÉDITO
                            $abonosHist = $item['abonos'] ?? [];

                            if ($index === 0 && !empty($abonosHist)) {
                                foreach ($abonosHist as $abono) {
                                    $fechaAbono = Carbon::parse($abono['created_at']);

                                    DB::table('abonos_creditos')->insert([
                                        'id_credito'        => $idCredito,
                                        'id_user'           => $idUserPredeterminado,
                                        'id_caja'           => $idCajaPredeterminada,
                                        'monto_pagado_usd'  => $abono['monto_usd'],
                                        'pago_usd_efectivo' => $abono['pago_usd_efectivo'] ?? 0.00,
                                        'pago_bs_efectivo'  => $abono['pago_bs_efectivo'] ?? 0.00,
                                        'pago_punto_bs'     => $abono['pago_punto_bs'] ?? 0.00,
                                        'pago_pagomovil_bs' => $abono['pago_pagomovil_bs'] ?? 0.00,
                                        'detalles'          => $abono['detalles'] ?? 'Abono registrado',
                                        'estado'            => 'Realizado',
                                        'created_at'        => $fechaAbono,
                                        'updated_at'        => $fechaAbono,
                                    ]);
                                }
                            }
                        }
                    });
                }
    }
}
