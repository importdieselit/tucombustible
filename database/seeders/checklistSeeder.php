<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Checklist;

class ChecklistSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // El JSON completo y limpio de la estructura del checklist.
        $checklistJson = [
            "checklist_name" => "Checkout - IMPORDIESEL",
            "version" => "1.2",
            "sections" => [
                [
                    "section_title" => "Datos del Vehículo",
                    "items" => [
                        ["label" => "Fecha Emisión", "response_type" => "date", "value" => null],
                        ["label" => "Lugar de Inspección", "response_type" => "text", "value" => ""],
                        ["label" => "Inspección a cargo de", "response_type" => "text", "value" => ""],
                        ["label" => "Placa", "response_type" => "text", "value" => ""],
                        ["label" => "Color", "response_type" => "text", "value" => ""],
                        ["label" => "Marca", "response_type" => "text", "value" => ""],
                        ["label" => "Modelo", "response_type" => "text", "value" => ""],
                        ["label" => "Versión", "response_type" => "text", "value" => ""],
                        ["label" => "No. Motor", "response_type" => "text", "value" => ""],
                        ["label" => "No. Serial", "response_type" => "text", "value" => ""],
                        ["label" => "Tipo de Vehículo", "response_type" => "text", "value" => ""],
                        ["label" => "Km. Recorridos", "response_type" => "text", "value" => ""],
                        [
                            "label" => "Verificación Técnica",
                            "response_type" => "composite",
                            "value" => ["status" => null, "vigencia" => null],
                            "fields" => [["label" => "Estado", "type" => "boolean"], ["label" => "Vigente Hasta", "type" => "date"]]
                        ],
                        ["label" => "Documentos de propiedad", "response_type" => "boolean", "value" => true],
                        [
                            "label" => "Documentos de Seguros",
                            "response_type" => "composite",
                            "value" => ["status" => null, "vigencia" => null],
                            "fields" => [["label" => "Estado", "type" => "boolean"], ["label" => "Vigente Hasta", "type" => "date"]]
                        ]
                    ]
                ],
                [
                    "section_title" => "Datos del Conductor",
                    "items" => [
                        ["label" => "Nombre", "response_type" => "text", "value" => ""],
                        ["label" => "Empresa", "response_type" => "text", "value" => ""],
                        ["label" => "No. Licencia", "response_type" => "text", "value" => ""],
                        ["label" => "Categoría", "response_type" => "text", "value" => ""],
                        ["label" => "Vigente hasta", "response_type" => "date", "value" => null],
                        ["label" => "Permiso para conducir", "response_type" => "boolean", "value" => true],
                        [
                            "label" => "Curso de Manejo Defensivo Vigente",
                            "response_type" => "composite",
                            "value" => ["status" => null, "vigencia" => null],
                            "fields" => [["label" => "Estado", "type" => "boolean"], ["label" => "Vigente Hasta", "type" => "date"]]
                        ]
                    ]
                ],
                [
                    "section_title" => "1.- SISTEMA ELÉCTRICO",
                    "items" => [
                        ["label" => "Luces de Alta y Baja", "response_type" => "boolean", "value" => true],
                        ["label" => "Luces de Frenos", "response_type" => "boolean", "value" => true],
                        ["label" => "Luces de Cruce (Giro/guiñadores)", "response_type" => "boolean", "value" => true],
                        ["label" => "Luz de Retroceso", "response_type" => "boolean", "value" => true],
                        ["label" => "Luces Intermitentes (Balizas)", "response_type" => "boolean", "value" => true],
                        ["label" => "Luces de delimitación", "response_type" => "boolean", "value" => true],
                        ["label" => "Luz del Tablero", "response_type" => "boolean", "value" => true],
                        ["label" => "Bocina ó Corneta", "response_type" => "boolean", "value" => true],
                        ["label" => "Alarma de Retroceso", "response_type" => "boolean", "value" => true],
                        ["label" => "Alternador", "response_type" => "boolean", "value" => true],
                        ["label" => "Batería", "response_type" => "boolean", "value" => true],
                        ["label" => "Cables de Batería", "response_type" => "boolean", "value" => true],
                        ["label" => "Aire Acondicionado", "response_type" => "boolean", "value" => true],
                        ["label" => "Limpiaparabrisas", "response_type" => "boolean", "value" => true],
                        ["label" => "Lavaparabrisas", "response_type" => "boolean", "value" => true],
                        ["label" => "Calefacción", "response_type" => "boolean", "value" => true],
                        ["label" => "Seguros de Puertas", "response_type" => "boolean", "value" => true],
                        ["label" => "Vidrios Eléctricos", "response_type" => "boolean", "value" => true],
                        ["label" => "Indicador de Combustible y otros", "response_type" => "boolean", "value" => true],
                        ["label" => "Velocímetro/Odometro", "response_type" => "boolean", "value" => true],
                        ["label" => "Tacógrafo (Drive Right)", "response_type" => "boolean", "value" => true],
                        ["label" => "Vigia", "response_type" => "boolean", "value" => true]
                    ]
                ],
                [
                    "section_title" => "2.- NEUMÁTICOS",
                    "items" => [
                        ["label" => "Neumáticos Delanteros", "response_type" => "boolean", "value" => true],
                        ["label" => "Neumáticos Traseros", "response_type" => "boolean", "value" => true],
                        ["label" => "Neumático de Repuesto", "response_type" => "boolean", "value" => true],
                        ["label" => "Tuercas / Pernos", "response_type" => "boolean", "value" => true],
                        ["label" => "Presión de Aire", "response_type" => "boolean", "value" => true]
                    ]
                ],
                [
                    "section_title" => "3.- SISTEMA MECÁNICO",
                    "items" => [
                        ["label" => "Nivel de Aceite del Motor", "response_type" => "boolean", "value" => true],
                        ["label" => "Nivel de Aceite de Transmisión", "response_type" => "boolean", "value" => true],
                        ["label" => "Nivel de Agua del Radiador", "response_type" => "boolean", "value" => true],
                        ["label" => "Funcionamiento de Frenos", "response_type" => "boolean", "value" => true],
                        ["label" => "Frenos de Estacionamiento (de mano)", "response_type" => "boolean", "value" => true],
                        ["label" => "Tapa del Radiador", "response_type" => "boolean", "value" => true],
                        ["label" => "Tapa Compartimiento de Aceite", "response_type" => "boolean", "value" => true],
                        ["label" => "Tapa Depósito de Agua del Motor", "response_type" => "boolean", "value" => true],
                        ["label" => "Tapa Tanque de Gasolina/Gasoil", "response_type" => "boolean", "value" => true],
                        ["label" => "Filtro de aire", "response_type" => "boolean", "value" => true],
                        ["label" => "Amortiguadores", "response_type" => "boolean", "value" => true],
                        ["label" => "Caño de escape/Silenciador", "response_type" => "boolean", "value" => true],
                        ["label" => "Arrestachispas", "response_type" => "boolean", "value" => true],
                        ["label" => "Sistema de dirección", "response_type" => "boolean", "value" => true]
                    ]
                ],
                [
                    "section_title" => "4.- TAPICERÍA",
                    "items" => [
                        ["label" => "Asientos del Conductor", "response_type" => "boolean", "value" => true],
                        ["label" => "Asientos del Copiloto/acompañante", "response_type" => "boolean", "value" => true],
                        ["label" => "Asientos Traseros", "response_type" => "boolean", "value" => true],
                        ["label" => "Asientos de Pasajeros", "response_type" => "boolean", "value" => true]
                    ]
                ],
                [
                    "section_title" => "5.- CRISTALERIA",
                    "items" => [
                        ["label" => "Parabrisas", "response_type" => "boolean", "value" => true],
                        ["label" => "Vidrios Puertas", "response_type" => "boolean", "value" => true],
                        ["label" => "Vidrio Trasero", "response_type" => "boolean", "value" => true],
                        ["label" => "Vidrios Laterales (Autobuses)", "response_type" => "boolean", "value" => true],
                        ["label" => "Espejo Retrovisor Interno", "response_type" => "boolean", "value" => true],
                        ["label" => "Espejo Retrovisor Izquierdo", "response_type" => "boolean", "value" => true],
                        ["label" => "Espejo Retrovisor Derecho", "response_type" => "boolean", "value" => true]
                    ]
                ],
                [
                    "section_title" => "6.- LATONERÍA Y PINTURA",
                    "items" => [
                        ["label" => "Maleta (Baúl)", "response_type" => "boolean", "value" => true],
                        ["label" => "Caja Trasera (Pick Up)", "response_type" => "boolean", "value" => true],
                        ["label" => "Parachoques Delanteros", "response_type" => "boolean", "value" => true],
                        ["label" => "Parachoques Traseros", "response_type" => "boolean", "value" => true],
                        ["label" => "Careta / Parrilla Frontal", "response_type" => "boolean", "value" => true],
                        ["label" => "Capot", "response_type" => "boolean", "value" => true],
                        ["label" => "Guardafangos Delanteros", "response_type" => "boolean", "value" => true],
                        ["label" => "Guardafangos Traseros", "response_type" => "boolean", "value" => true],
                        ["label" => "Techo", "response_type" => "boolean", "value" => true]
                    ]
                ],
                [
                    "section_title" => "7.- OTROS ELEMENTOS",
                    "items" => [
                        ["label" => "Cinturón de Seguridad", "response_type" => "boolean", "value" => true],
                        ["label" => "Apoya Cabezas", "response_type" => "boolean", "value" => true],
                        ["label" => "Caja de Herramientas", "response_type" => "boolean", "value" => true],
                        ["label" => "Barra Anti-vuelco", "response_type" => "boolean", "value" => true],
                        ["label" => "Botiquín Primeros Auxilios", "response_type" => "boolean", "value" => true],
                        ["label" => "Extintores (Matafuegos)", "response_type" => "boolean", "value" => true],
                        ["label" => "Criket (Gato) y Llaves de Ruedas", "response_type" => "boolean", "value" => true],
                        ["label" => "Triángulos (Balizas) o Conos", "response_type" => "boolean", "value" => true],
                        ["label" => "Banderas de Advertencia", "response_type" => "boolean", "value" => true],
                        ["label" => "Kit de emergencias (Vans)", "response_type" => "boolean", "value" => true],
                        ["label" => "Cable o Barra de Remolque", "response_type" => "boolean", "value" => true],
                        ["label" => "Cadenas/Eslingas para cargas", "response_type" => "boolean", "value" => true],
                        ["label" => "Caja/Plataforma de carga", "response_type" => "boolean", "value" => true],
                        ["label" => "Plato de enganche", "response_type" => "boolean", "value" => true],
                        ["label" => "Protección de Cabina", "response_type" => "boolean", "value" => true],
                        ["label" => "Cisterna", "response_type" => "boolean", "value" => true]
                    ]
                ],
                [
                    "section_title" => "8.- DOCUMENTACIÓN Y EQUIPO",
                    "subsections" => [
                        [
                            "subsection_title" => "Documentación de la Unidad",
                            "items" => [
                                ["label" => "Permiso de Transporte Terrestre", "response_type" => "composite", "value" => ["status" => null, "vigencia" => null]],
                                ["label" => "Póliza de Seguro - Cobertura de Daños Ambientales", "response_type" => "composite", "value" => ["status" => null, "vigencia" => null]],
                                ["label" => "RACDA", "response_type" => "composite", "value" => ["status" => null, "vigencia" => null]],
                                ["label" => "ROTC", "response_type" => "composite", "value" => ["status" => null, "vigencia" => null]],
                                ["label" => "RCV", "response_type" => "composite", "value" => ["status" => null, "vigencia" => null]]
                            ]
                        ],
                        [
                            "subsection_title" => "Documentación del Conductor",
                            "items" => [
                                ["label" => "Cédula de Identidad", "response_type" => "composite", "value" => ["status" => null, "vigencia" => null]],
                                ["label" => "Certificado Médico", "response_type" => "composite", "value" => ["status" => null, "vigencia" => null]],
                                ["label" => "Licencia de Conducir Profesional", "response_type" => "composite", "value" => ["status" => null, "vigencia" => null]],
                                ["label" => "Certificado de Manejo de Materiales Peligrosos", "response_type" => "composite", "value" => ["status" => null, "vigencia" => null]]
                            ]
                        ],
                        [
                            "subsection_title" => "Implementos de Trabajo",
                            "items" => [
                                ["label" => "Uniforme", "response_type" => "boolean", "value" => true],
                                ["label" => "Camisa", "response_type" => "boolean", "value" => true],
                                ["label" => "Pantalón", "response_type" => "boolean", "value" => true],
                                ["label" => "Gorra", "response_type" => "boolean", "value" => true],
                                ["label" => "Botas", "response_type" => "boolean", "value" => true],
                                ["label" => "Kit de Seguridad", "response_type" => "boolean", "value" => true],
                                ["label" => "Kit Antiderrame", "response_type" => "boolean", "value" => true],
                                ["label" => "Lentes", "response_type" => "boolean", "value" => true],
                                ["label" => "Guantes", "response_type" => "boolean", "value" => true],
                                ["label" => "Poncho", "response_type" => "boolean", "value" => true],
                                ["label" => "Aserrín", "response_type" => "boolean", "value" => true]
                            ]
                        ]
                    ]
                ],
                [
                    "section_title" => "9.- KIT DE EQUIPAMIENTO",
                    "items" => [
                        ["label" => "Manguera de 1 pulgada y 1/2", "response_type" => "boolean", "value" => true],
                        ["label" => "Manguera de 1 pulgada", "response_type" => "boolean", "value" => true],
                        ["label" => "Pico para abastecer de 1 pulgada y 1/2", "response_type" => "boolean", "value" => true],
                        ["label" => "Pico para abastecer de 1 pulgada", "response_type" => "boolean", "value" => true],
                        ["label" => "Tapón para los picos de la manguera", "response_type" => "boolean", "value" => true],
                        ["label" => "Mecate", "response_type" => "boolean", "value" => true],
                        ["label" => "Gracera", "response_type" => "boolean", "value" => true],
                        ["label" => "Dispensador de aceite", "response_type" => "boolean", "value" => true],
                        ["label" => "Manguera de aire grasera", "response_type" => "boolean", "value" => true],
                        ["label" => "Manguera de aire dispensador de aceite", "response_type" => "boolean", "value" => true],
                        ["label" => "Tapa de los tanques", "response_type" => "boolean", "value" => true],
                        ["label" => "Protector del medidor de nivel de los tanques", "response_type" => "boolean", "value" => true],
                        ["label" => "Medidor", "response_type" => "boolean", "value" => true],
                        ["label" => "Bomba y medidor de 1 pulgada", "response_type" => "boolean", "value" => true],
                        ["label" => "Llaves de paso", "response_type" => "boolean", "value" => true],
                        ["label" => "Filtro Yee", "response_type" => "boolean", "value" => true],
                        ["label" => "Candados", "response_type" => "boolean", "value" => true],
                        ["label" => "Llaves de candados", "response_type" => "boolean", "value" => true],
                        ["label" => "Tapón de la salida de despacho de la motobomba", "response_type" => "boolean", "value" => true],
                        ["label" => "Gato y palanca", "response_type" => "boolean", "value" => true],
                        ["label" => "Bandejas contenedores de líquidos", "response_type" => "boolean", "value" => true],
                        ["label" => "Calcomanías", "response_type" => "boolean", "value" => true],
                        ["label" => "Kit de herramientas", "response_type" => "boolean", "value" => true],
                        ["label" => "Conos", "response_type" => "boolean", "value" => true],
                        ["label" => "Extintores", "response_type" => "boolean", "value" => true]
                    ]
                ],
                [
                    "section_title" => "Observaciones",
                    "items" => [
                        ["label" => "Observaciones Generales", "response_type" => "textarea", "value" => ""]
                    ]
                ]
            ]
        ];

        // Crear el registro del checklist
        Checklist::create([
            'id' => 1,
            'titulo' => 'Checkout',
            'activo' => true,
            // Almacenar el array de PHP, Laravel lo convertirá a JSON en la base de datos
            'checklist' => $checklistJson, 
        ]);
    
    }
}