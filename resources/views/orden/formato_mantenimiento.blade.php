<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Formato Preventivo TUCOMBUSTIBLE</title>
    <style>
        /* CONFIGURACIÓN GLOBAL DE PÁGINA Y TIPOGRAFÍA */
        @page {
            size: letter;
            margin: 1.5cm;
        }

        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1e293b;
            line-height: 1.5;
            font-size: 10pt;
            background-color: #ffffff;
            margin: 0;
            padding: 0;
        }

        /* CONTROL DE PÁGINAS PARA IMPRESIÓN */
        .page-section {
            page-break-before: always;
            break-before: page;
            padding-top: 10px;
        }

        /* La primera página no necesita salto previo */
        .page-section:first-of-type {
            page-break-before: avoid;
            break-before: avoid;
        }

        /* ENCABEZADOS CORPORATIVOS */
        .corporate-header {
            text-align: center;
            border-bottom: 3px solid #0f2d59;
            padding-bottom: 12px;
            margin-bottom: 25px;
        }

        h1 {
            color: #0f2d59;
            font-size: 20pt;
            letter-spacing: 1px;
            margin: 0 0 5px 0;
        }

        h2 {
            color: #475569;
            font-size: 11pt;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0;
            font-weight: 600;
        }

        h3 {
            color: #0f2d59;
            border-bottom: 2px solid #0f2d59;
            padding-bottom: 5px;
            font-size: 11pt;
            margin-top: 0;
            margin-bottom: 12px;
            text-transform: uppercase;
        }

        /* ESTILOS DE TABLAS CORPORATIVAS */
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9.5pt;
            margin-bottom: 15px;
        }

        th {
            background-color: #0f2d59;
            color: #ffffff;
            padding: 8px 10px;
            border: 1px solid #0f2d59;
            text-align: center;
            font-weight: 600;
        }

        td {
            padding: 7px 10px;
            border: 1px solid #cbd5e1;
            vertical-align: middle;
        }

        .bg-light {
            background-color: #f1f5f9;
            font-weight: bold;
            color: #334155;
            width: 22%;
        }

        .category-header {
            background-color: #e2e8f0 !important;
            color: #0f2d59;
            font-weight: bold;
            font-size: 9.5pt;
            text-align: left;
        }

        .text-center { text-align: center; }
        .text-left { text-align: left; }

        .notes {
            font-size: 8.5pt;
            color: #64748b;
            margin-top: -3px;
            margin-bottom: 10px;
        }

        .row-alt {
            background-color: #f8fafc;
        }

        /* SECCIÓN DE OBSERVACIONES */
        .observation-box {
            border: 1px solid #cbd5e1;
            height: 140px;
            margin-bottom: 30px;
            background-color: #f8fafc;
            border-radius: 4px;
        }

        /* TABLA DE FIRMAS PROFESIONAL */
        .signature-container {
            margin-top: 60px;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .signature-table {
            width: 100%;
            border: none;
        }

        .signature-table td {
            border: none;
            border-top: 1px solid #1e293b;
            padding-top: 12px;
            text-align: center;
            font-weight: bold;
            color: #0f2d59;
            width: 30%;
        }

        .signature-table .spacer {
            border: none;
            border-top: none;
            width: 5%;
        }
    </style>
</head>
<body>

    <!-- PÁGINA 1: ENCABEZADO Y DATOS GENERALES -->
    <div class="page-section">
        <div class="corporate-header">
            <h1>TUCOMBUSTIBLE</h1>
            <h2>Formato Corporativo de Mantenimiento Preventivo</h2>
        </div>

        <h3>1. Datos Generales de la Orden</h3>
        <table>
            <tbody>
                <tr>
                    <td class="bg-light">Orden de Trabajo Nº:</td>
                    <td style="width: 28%;"></td>
                    <td class="bg-light">Fecha:</td>
                    <td style="width: 28%;"></td>
                </tr>
                <tr>
                    <td class="bg-light">Vehículo:</td>
                    <td></td>
                    <td class="bg-light">Placa:</td>
                    <td></td>
                </tr>
                <tr>
                    <td class="bg-light">Marca / Modelo:</td>
                    <td></td>
                    <td class="bg-light">Plan de Mant.:</td>
                    <td>[ ] M1 &nbsp;&nbsp;[ ] M2 &nbsp;&nbsp;[ ] M3 &nbsp;&nbsp;[ ] M4</td>
                </tr>
                <tr>
                    <td class="bg-light">Kilometraje:</td>
                    <td></td>
                    <td class="bg-light">Horómetro:</td>
                    <td></td>
                </tr>
                <tr>
                    <td class="bg-light">Técnico Asignado:</td>
                    <td></td>
                    <td class="bg-light">Supervisor:</td>
                    <td></td>
                </tr>
                <tr>
                    <td class="bg-light">Hora Inicio:</td>
                    <td></td>
                    <td class="bg-light">Hora Fin:</td>
                    <td></td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- PÁGINA 2: CHECKLIST DE ACTIVIDADES DE EJECUCIÓN Y CONTROL -->
    <div class="page-section">
        <h3>2. Checklist de Actividades de Ejecución y Control</h3>
        <p class="notes"><strong>Simbología de Estado:</strong> <strong>[ C ]</strong> Conforme &nbsp;|&nbsp; <strong>[ N ]</strong> No Conforme &nbsp;|&nbsp; <strong>[ N/A ]</strong> No Aplica</p>
        
        <table>
            <thead>
                <tr>
                    <th style="width: 6%;">N°</th>
                    <th class="text-left" style="width: 42%;">Actividad / Punto de Control</th>
                    <th style="width: 5%;">M1</th>
                    <th style="width: 5%;">M2</th>
                    <th style="width: 5%;">M3</th>
                    <th style="width: 5%;">M4</th>
                    <th style="width: 14%;">Estado (C / N / NA)</th>
                    <th class="text-left" style="width: 18%;">Observaciones</th>
                </tr>
            </thead>
            <tbody>
                <!-- CATEGORÍA 1 -->
                <tr class="category-header">
                    <td colspan="8">1. CAMBIOS Y SUSTITUCIONES</td>
                </tr>
                <tr>
                    <td class="text-center">1.1</td>
                    <td>Cambio de Aceite de Motor y Filtro</td>
                    <td class="text-center">X</td><td class="text-center">X</td><td class="text-center">X</td><td class="text-center">X</td>
                    <td class="text-center">[  &nbsp;  ]</td>
                    <td></td>
                </tr>
                <tr class="row-alt">
                    <td class="text-center">1.2</td>
                    <td>Sustitución Filtro Combustible y Trampa de Agua</td>
                    <td class="text-center">—</td><td class="text-center">X</td><td class="text-center">X</td><td class="text-center">X</td>
                    <td class="text-center">[  &nbsp;  ]</td>
                    <td></td>
                </tr>
                <tr>
                    <td class="text-center">1.3</td>
                    <td>Sustitución Filtro de Aire (Seco y/o Húmedo)</td>
                    <td class="text-center">—</td><td class="text-center">X</td><td class="text-center">X</td><td class="text-center">X</td>
                    <td class="text-center">[  &nbsp;  ]</td>
                    <td></td>
                </tr>
                <tr class="row-alt">
                    <td class="text-center">1.4</td>
                    <td>Sustitución Líquido de Sistema de Freno</td>
                    <td class="text-center">—</td><td class="text-center">—</td><td class="text-center">X</td><td class="text-center">—</td>
                    <td class="text-center">[  &nbsp;  ]</td>
                    <td></td>
                </tr>
                <tr>
                    <td class="text-center">1.5</td>
                    <td>Sustitución Aceite de Grupos y Órganos</td>
                    <td class="text-center">—</td><td class="text-center">—</td><td class="text-center">X</td><td class="text-center">—</td>
                    <td class="text-center">[  &nbsp;  ]</td>
                    <td></td>
                </tr>

                <!-- CATEGORÍA 2 -->
                <tr class="category-header">
                    <td colspan="8">2. ADMISIÓN, MOTOR Y SISTEMAS DE FLUIDOS</td>
                </tr>
                <tr>
                    <td class="text-center">2.1</td>
                    <td>Control de apriete de mangueras y estado de manguitos de admisión</td>
                    <td class="text-center">X</td><td class="text-center">X</td><td class="text-center">X</td><td class="text-center">X</td>
                    <td class="text-center">[  &nbsp;  ]</td>
                    <td></td>
                </tr>
                <tr class="row-alt">
                    <td class="text-center">2.2</td>
                    <td>Reapretar abrazaderas de manguito en sistema de admisión</td>
                    <td class="text-center">X</td><td class="text-center">X</td><td class="text-center">X</td><td class="text-center">X</td>
                    <td class="text-center">[  &nbsp;  ]</td>
                    <td></td>
                </tr>
                <tr>
                    <td class="text-center">2.3</td>
                    <td>Control de pérdidas por grupos (fugas de aceite/refrigerante)</td>
                    <td class="text-center">X</td><td class="text-center">X</td><td class="text-center">X</td><td class="text-center">X</td>
                    <td class="text-center">[  &nbsp;  ]</td>
                    <td></td>
                </tr>
                <tr class="row-alt">
                    <td class="text-center">2.4</td>
                    <td>Control de nivel de todos los fluidos (motor, dirección, refrigerante)</td>
                    <td class="text-center">X</td><td class="text-center">X</td><td class="text-center">X</td><td class="text-center">X</td>
                    <td class="text-center">[  &nbsp;  ]</td>
                    <td></td>
                </tr>
                <tr>
                    <td class="text-center">2.5</td>
                    <td>Revisión de inyectores en banco de prueba</td>
                    <td class="text-center">—</td><td class="text-center">—</td><td class="text-center">X</td><td class="text-center">—</td>
                    <td class="text-center">[  &nbsp;  ]</td>
                    <td></td>
                </tr>

                <!-- CATEGORÍA 3 -->
                <tr class="category-header">
                    <td colspan="8">3. FRENOS, TREN DE RODAJE Y DIRECCIÓN</td>
                </tr>
                <tr>
                    <td class="text-center">3.1</td>
                    <td>Control de pérdida en tubería de frenos hidráulicos</td>
                    <td class="text-center">X</td><td class="text-center">X</td><td class="text-center">X</td><td class="text-center">X</td>
                    <td class="text-center">[  &nbsp;  ]</td>
                    <td></td>
                </tr>
                <tr class="row-alt">
                    <td class="text-center">3.2</td>
                    <td>Control de nivel de líquido de frenos hidráulicos</td>
                    <td class="text-center">X</td><td class="text-center">X</td><td class="text-center">X</td><td class="text-center">X</td>
                    <td class="text-center">[  &nbsp;  ]</td>
                    <td></td>
                </tr>
                <tr>
                    <td class="text-center">3.3</td>
                    <td>Control de desgaste de discos, pastillas, tambores y zapatas</td>
                    <td class="text-center">X</td><td class="text-center">X</td><td class="text-center">X</td><td class="text-center">X</td>
                    <td class="text-center">[  &nbsp;  ]</td>
                    <td></td>
                </tr>
                <tr class="row-alt">
                    <td class="text-center">3.4</td>
                    <td>Control de estado de correas de mandos varios</td>
                    <td class="text-center">—</td><td class="text-center">X</td><td class="text-center">X</td><td class="text-center">X</td>
                    <td class="text-center">[  &nbsp;  ]</td>
                    <td></td>
                </tr>
                <tr>
                    <td class="text-center">3.5</td>
                    <td>Sustitución de correas de distribución y alternador</td>
                    <td class="text-center">—</td><td class="text-center">—</td><td class="text-center">X</td><td class="text-center">X</td>
                    <td class="text-center">[  &nbsp;  ]</td>
                    <td></td>
                </tr>
                <tr class="row-alt">
                    <td class="text-center">3.6</td>
                    <td>Control de fijación de caja de dirección y soporte</td>
                    <td class="text-center">—</td><td class="text-center">X</td><td class="text-center">X</td><td class="text-center">X</td>
                    <td class="text-center">[  &nbsp;  ]</td>
                    <td></td>
                </tr>
                <tr>
                    <td class="text-center">3.7</td>
                    <td>Control de tirantes y rótulas de dirección</td>
                    <td class="text-center">—</td><td class="text-center">X</td><td class="text-center">X</td><td class="text-center">X</td>
                    <td class="text-center">[  &nbsp;  ]</td>
                    <td></td>
                </tr>
                <tr class="row-alt">
                    <td class="text-center">3.8</td>
                    <td>Comprobar apriete de tuercas de rueda (Torque)</td>
                    <td class="text-center">—</td><td class="text-center">X</td><td class="text-center">X</td><td class="text-center">X</td>
                    <td class="text-center">[  &nbsp;  ]</td>
                    <td></td>
                </tr>

                <!-- CATEGORÍA 4 -->
                <tr class="category-header">
                    <td colspan="8">4. LUBRICACIÓN GENERAL Y PRUEBAS</td>
                </tr>
                <tr>
                    <td class="text-center">4.1</td>
                    <td>Engrase general de puntos de chasis / articulaciones</td>
                    <td class="text-center">X</td><td class="text-center">X</td><td class="text-center">X</td><td class="text-center">X</td>
                    <td class="text-center">[  &nbsp;  ]</td>
                    <td></td>
                </tr>
                <tr class="row-alt">
                    <td class="text-center">4.2</td>
                    <td>Prueba funcional y operaciones de movimiento</td>
                    <td class="text-center">X</td><td class="text-center">X</td><td class="text-center">X</td><td class="text-center">X</td>
                    <td class="text-center">[  &nbsp;  ]</td>
                    <td></td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- PÁGINA 3: CONTROL DE REPUESTOS Y CONSUMIBLES -->
    <div class="page-section">
        <h3>3. Control de Repuestos y Consumibles</h3>
        <p class="notes">Marque los insumos aplicables al plan ejecutado e indique los detalles de consumo.</p>
        <table>
            <thead>
                <tr>
                    <th style="width: 5%;">✓</th>
                    <th style="width: 32%; text-align: left;">Descripción</th>
                    <th style="width: 10%;">Cant.</th>
                    <th style="width: 20%;">Marca</th>
                    <th style="width: 33%; text-align: left;">Observaciones</th>
                </tr>
            </thead>
            <tbody>
                <tr><td class="text-center"> </td><td>Aceite motor</td><td></td><td></td><td></td></tr>
                <tr class="row-alt"><td class="text-center"></td><td>Grasa</td><td></td><td></td><td></td></tr>
                <tr><td class="text-center"></td><td>Filtro aceite motor</td><td></td><td></td><td></td></tr>
                <tr class="row-alt"><td class="text-center"></td><td>Filtro combustible</td><td></td><td></td><td></td></tr>
                <tr><td class="text-center"></td><td>Trampa de agua</td><td></td><td></td><td></td></tr>
                <tr class="row-alt"><td class="text-center"></td><td>Filtro aire seco</td><td></td><td></td><td></td></tr>
                <tr><td class="text-center"></td><td>Filtro aire baño aceite</td><td></td><td></td><td></td></tr>
                <tr class="row-alt"><td class="text-center"></td><td>Aceite caja / Transmisión</td><td></td><td></td><td></td></tr>
                <tr><td class="text-center"></td><td>Aceite puente / Eje delantero</td><td></td><td></td><td></td></tr>
                <tr class="row-alt"><td class="text-center"></td><td>Aceite D/H / Filtro D/H</td><td></td><td></td><td></td></tr>
                <tr><td class="text-center"></td><td>Filtro secante / Transmisión auto.</td><td></td><td></td><td></td></tr>
                <tr class="row-alt"><td class="text-center"></td><td>Correas (Alt., Compresor, Dist.)</td><td></td><td></td><td></td></tr>
            </tbody>
        </table>
    </div>

    <!-- PÁGINA 4: OBSERVACIONES Y VALIDACIÓN DE FIRMAS -->
    <div class="page-section">
        <h3>4. Observaciones Generales y Diagnóstico</h3>
        <div class="observation-box"></div>

        <div class="signature-container">
            <table class="signature-table">
                <tr>
                    <td>Firma Técnico Asignado</td>
                    <td class="spacer"></td>
                    <td>Firma Supervisor a Cargo</td>
                    <td class="spacer"></td>
                    <td>Firma Gerencia / Operaciones</td>
                </tr>
            </table>
        </div>
    </div>

</body>
</html>