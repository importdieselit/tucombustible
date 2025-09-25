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
            "checklist_name" => "checkout- IMPORDIESEL",
            "version" => "1.3", // Versión actualizada para incluir col_width
            "sections" => [
                [
                    "section_title" => "Datos del Vehículo",
                    "items" => [
                        // Primera Fila (3 columnas)
                        ["label" => "Fecha Emisión", "response_type" => "date", "value" => null, "col_width" => 4],
                        ["label" => "Placa", "response_type" => "text", "value" => "", "col_width" => 4],
                        ["label" => "Color", "response_type" => "text", "value" => "", "col_width" => 4],
                        
                        // Segunda Fila (3 columnas)
                        ["label" => "Marca", "response_type" => "text", "value" => "", "col_width" => 4],
                        ["label" => "Modelo", "response_type" => "text", "value" => "", "col_width" => 4],
                        ["label" => "Versión", "response_type" => "text", "value" => "", "col_width" => 4],
                        
                        // Tercera Fila (2 columnas)
                        ["label" => "No. Motor", "response_type" => "text", "value" => "", "col_width" => 6],
                        ["label" => "No. Serial", "response_type" => "text", "value" => "", "col_width" => 6],
                        
                        // Cuarta Fila (2 columnas)
                        ["label" => "Tipo de Vehículo", "response_type" => "text", "value" => "", "col_width" => 6],
                        ["label" => "Km. Recorridos", "response_type" => "text", "value" => "", "col_width" => 6],
                        
                        // Quinta Fila (2 columnas - Campos compuestos)
                        [
                            "label" => "Verificación Técnica",
                            "response_type" => "composite",
                            "value" => ["status" => null, "vigencia" => null],
                            "fields" => [["label" => "Estado", "type" => "boolean"], ["label" => "Vigente Hasta", "type" => "date"]],
                            "col_width" => 6
                        ],
                        [
                            "label" => "Documentos de Seguros",
                            "response_type" => "composite",
                            "value" => ["status" => null, "vigencia" => null],
                            "fields" => [["label" => "Estado", "type" => "boolean"], ["label" => "Vigente Hasta", "type" => "date"]],
                            "col_width" => 6
                        ],
                        
                        // Sexta Fila (2 columnas)
                        ["label" => "Documentos de propiedad", "response_type" => "boolean", "value" => true, "col_width" => 6],
                        ["label" => "Lugar de Inspección", "response_type" => "text", "value" => "", "col_width" => 6],
                        
                        // Séptima Fila (1 columna)
                        ["label" => "Inspección a cargo de", "response_type" => "text", "value" => "", "col_width" => 12],
                    ]
                ],
                [
                    "section_title" => "Datos del Conductor",
                    "items" => [
                        // Primera Fila (2 columnas)
                        ["label" => "Nombre", "response_type" => "text", "value" => "", "col_width" => 6],
                        ["label" => "Empresa", "response_type" => "text", "value" => "", "col_width" => 6],
                        
                        // Segunda Fila (3 columnas)
                        ["label" => "No. Licencia", "response_type" => "text", "value" => "", "col_width" => 4],
                        ["label" => "Categoría", "response_type" => "text", "value" => "", "col_width" => 4],
                        ["label" => "Vigente hasta", "response_type" => "date", "value" => null, "col_width" => 4],
                        
                        // Tercera Fila (2 columnas)
                        ["label" => "Permiso para conducir", "response_type" => "boolean", "value" => true, "col_width" => 6],
                        [
                            "label" => "Curso de Manejo Defensivo Vigente",
                            "response_type" => "composite",
                            "value" => ["status" => null, "vigencia" => null],
                            "fields" => [["label" => "Estado", "type" => "boolean"], ["label" => "Vigente Hasta", "type" => "date"]],
                            "col_width" => 6
                        ]
                    ]
                ],
                // El resto de las secciones usa col_width: 4 (por defecto o explícito) para que cada ítem ocupe su propia fila.
                [
                    "section_title" => "1.- SISTEMA ELÉCTRICO",
                    "items" => [
                        ["label" => "Luces de Alta y Baja", "response_type" => "boolean", "value" => true, "col_width" => 4],
                        ["label" => "Luces de Frenos", "response_type" => "boolean", "value" => true, "col_width" => 4],
                        ["label" => "Luces de Cruce (Giro/guiñadores)", "response_type" => "boolean", "value" => true, "col_width" => 4],
                        ["label" => "Luz de Retroceso", "response_type" => "boolean", "value" => true, "col_width" => 4],
                        ["label" => "Luces Intermitentes (Balizas)", "response_type" => "boolean", "value" => true, "col_width" => 4],
                        ["label" => "Luces de delimitación", "response_type" => "boolean", "value" => true, "col_width" => 4],
                        ["label" => "Luz del Tablero", "response_type" => "boolean", "value" => true, "col_width" => 4],
                        ["label" => "Bocina ó Corneta", "response_type" => "boolean", "value" => true, "col_width" => 4],
                        ["label" => "Alarma de Retroceso", "response_type" => "boolean", "value" => true, "col_width" => 4],
                        ["label" => "Alternador", "response_type" => "boolean", "value" => true, "col_width" => 4],
                        ["label" => "Batería", "response_type" => "boolean", "value" => true, "col_width" => 4],
                        ["label" => "Cables de Batería", "response_type" => "boolean", "value" => true, "col_width" => 4],
                        ["label" => "Aire Acondicionado", "response_type" => "boolean", "value" => true, "col_width" => 4],
                        ["label" => "Limpiaparabrisas", "response_type" => "boolean", "value" => true, "col_width" => 4],
                        ["label" => "Lavaparabrisas", "response_type" => "boolean", "value" => true, "col_width" => 4],
                        ["label" => "Calefacción", "response_type" => "boolean", "value" => true, "col_width" => 4],
                        ["label" => "Seguros de Puertas", "response_type" => "boolean", "value" => true, "col_width" => 4],
                        ["label" => "Vidrios Eléctricos", "response_type" => "boolean", "value" => true, "col_width" => 4],
                        ["label" => "Indicador de Combustible y otros", "response_type" => "boolean", "value" => true, "col_width" => 4],
                        ["label" => "Velocímetro/Odometro", "response_type" => "boolean", "value" => true, "col_width" => 4],
                        ["label" => "Tacógrafo (Drive Right)", "response_type" => "boolean", "value" => true, "col_width" => 4],
                        ["label" => "Vigia", "response_type" => "boolean", "value" => true, "col_width" => 4]
                    ]
                ],
                [
                    "section_title" => "2.- NEUMÁTICOS",
                    "items" => [
                        ["label" => "Neumáticos Delanteros", "response_type" => "boolean", "value" => true, "col_width" => 4],
                        ["label" => "Neumáticos Traseros", "response_type" => "boolean", "value" => true, "col_width" => 4],
                        ["label" => "Neumático de Repuesto", "response_type" => "boolean", "value" => true, "col_width" => 4],
                        ["label" => "Tuercas / Pernos", "response_type" => "boolean", "value" => true, "col_width" => 4],
                        ["label" => "Presión de Aire", "response_type" => "boolean", "value" => true, "col_width" => 4]
                    ]
                ],
                [
                    "section_title" => "3.- SISTEMA MECÁNICO",
                    "items" => [
                        ["label" => "Nivel de Aceite del Motor", "response_type" => "boolean", "value" => true, "col_width" => 4],
                        ["label" => "Nivel de Aceite de Transmisión", "response_type" => "boolean", "value" => true, "col_width" => 4],
                        ["label" => "Nivel de Agua del Radiador", "response_type" => "boolean", "value" => true, "col_width" => 4],
                        ["label" => "Funcionamiento de Frenos", "response_type" => "boolean", "value" => true, "col_width" => 4],
                        ["label" => "Frenos de Estacionamiento (de mano)", "response_type" => "boolean", "value" => true, "col_width" => 4],
                        ["label" => "Tapa del Radiador", "response_type" => "boolean", "value" => true, "col_width" => 4],
                        ["label" => "Tapa Compartimiento de Aceite", "response_type" => "boolean", "value" => true, "col_width" => 4],
                        ["label" => "Tapa Depósito de Agua del Motor", "response_type" => "boolean", "value" => true, "col_width" => 4],
                        ["label" => "Tapa Tanque de Gasolina/Gasoil", "response_type" => "boolean", "value" => true, "col_width" => 4],
                        ["label" => "Filtro de aire", "response_type" => "boolean", "value" => true, "col_width" => 4],
                        ["label" => "Amortiguadores", "response_type" => "boolean", "value" => true, "col_width" => 4],
                        ["label" => "Caño de escape/Silenciador", "response_type" => "boolean", "value" => true, "col_width" => 4],
                        ["label" => "Arrestachispas", "response_type" => "boolean", "value" => true, "col_width" => 4],
                        ["label" => "Sistema de dirección", "response_type" => "boolean", "value" => true, "col_width" => 4]
                    ]
                ],
                [
                    "section_title" => "4.- TAPICERÍA",
                    "items" => [
                        ["label" => "Asientos del Conductor", "response_type" => "boolean", "value" => true, "col_width" => 4],
                        ["label" => "Asientos del Copiloto/acompañante", "response_type" => "boolean", "value" => true, "col_width" => 4],
                        ["label" => "Asientos Traseros", "response_type" => "boolean", "value" => true, "col_width" => 4],
                        ["label" => "Asientos de Pasajeros", "response_type" => "boolean", "value" => true, "col_width" => 4]
                    ]
                ],
                [
                    "section_title" => "5.- CRISTALERIA",
                    "items" => [
                        ["label" => "Parabrisas", "response_type" => "boolean", "value" => true, "col_width" => 4],
                        ["label" => "Vidrios Puertas", "response_type" => "boolean", "value" => true, "col_width" => 4],
                        ["label" => "Vidrio Trasero", "response_type" => "boolean", "value" => true, "col_width" => 4],
                        ["label" => "Vidrios Laterales (Autobuses)", "response_type" => "boolean", "value" => true, "col_width" => 4],
                        ["label" => "Espejo Retrovisor Interno", "response_type" => "boolean", "value" => true, "col_width" => 4],
                        ["label" => "Espejo Retrovisor Izquierdo", "response_type" => "boolean", "value" => true, "col_width" => 4],
                        ["label" => "Espejo Retrovisor Derecho", "response_type" => "boolean", "value" => true, "col_width" => 4]
                    ]
                ],
                [
                    "section_title" => "6.- LATONERÍA Y PINTURA",
                    "items" => [
                        ["label" => "Maleta (Baúl)", "response_type" => "boolean", "value" => true, "col_width" => 4],
                        ["label" => "Caja Trasera (Pick Up)", "response_type" => "boolean", "value" => true, "col_width" => 4],
                        ["label" => "Parachoques Delanteros", "response_type" => "boolean", "value" => true, "col_width" => 4],
                        ["label" => "Parachoques Traseros", "response_type" => "boolean", "value" => true, "col_width" => 4],
                        ["label" => "Careta / Parrilla Frontal", "response_type" => "boolean", "value" => true, "col_width" => 4],
                        ["label" => "Capot", "response_type" => "boolean", "value" => true, "col_width" => 4],
                        ["label" => "Guardafangos Delanteros", "response_type" => "boolean", "value" => true, "col_width" => 4],
                        ["label" => "Guardafangos Traseros", "response_type" => "boolean", "value" => true, "col_width" => 4],
                        ["label" => "Techo", "response_type" => "boolean", "value" => true, "col_width" => 4]
                    ]
                ],
                [
                    "section_title" => "7.- OTROS ELEMENTOS",
                    "items" => [
                        ["label" => "Cinturón de Seguridad", "response_type" => "boolean", "value" => true, "col_width" => 4],
                        ["label" => "Apoya Cabezas", "response_type" => "boolean", "value" => true, "col_width" => 4],
                        ["label" => "Caja de Herramientas", "response_type" => "boolean", "value" => true, "col_width" => 4],
                        ["label" => "Barra Anti-vuelco", "response_type" => "boolean", "value" => true, "col_width" => 4],
                        ["label" => "Botiquín Primeros Auxilios", "response_type" => "boolean", "value" => true, "col_width" => 4],
                        ["label" => "Extintores (Matafuegos)", "response_type" => "boolean", "value" => true, "col_width" => 4],
                        ["label" => "Criket (Gato) y Llaves de Ruedas", "response_type" => "boolean", "value" => true, "col_width" => 4],
                        ["label" => "Triángulos (Balizas) o Conos", "response_type" => "boolean", "value" => true, "col_width" => 4],
                        ["label" => "Banderas de Advertencia", "response_type" => "boolean", "value" => true, "col_width" => 4],
                        ["label" => "Kit de emergencias (Vans)", "response_type" => "boolean", "value" => true, "col_width" => 4],
                        ["label" => "Cable o Barra de Remolque", "response_type" => "boolean", "value" => true, "col_width" => 4],
                        ["label" => "Cadenas/Eslingas para cargas", "response_type" => "boolean", "value" => true, "col_width" => 4],
                        ["label" => "Caja/Plataforma de carga", "response_type" => "boolean", "value" => true, "col_width" => 4],
                        ["label" => "Plato de enganche", "response_type" => "boolean", "value" => true, "col_width" => 4],
                        ["label" => "Protección de Cabina", "response_type" => "boolean", "value" => true, "col_width" => 4],
                        ["label" => "Cisterna", "response_type" => "boolean", "value" => true, "col_width" => 4]
                    ]
                ],
                [
                    "section_title" => "8.- DOCUMENTACIÓN Y EQUIPO",
                    "subsections" => [
                        [
                            "subsection_title" => "Documentación de la Unidad",
                            // Estos ítems deben ocupar el ancho completo para sus campos compuestos
                            "items" => [
                                ["label" => "Permiso de Transporte Terrestre", "response_type" => "composite", "value" => ["status" => null, "vigencia" => null], "col_width" => 6],
                                ["label" => "Póliza de Seguro - Cobertura de Daños Ambientales", "response_type" => "composite", "value" => ["status" => null, "vigencia" => null], "col_width" => 6],
                                ["label" => "RACDA", "response_type" => "composite", "value" => ["status" => null, "vigencia" => null], "col_width" => 6],
                                ["label" => "ROTC", "response_type" => "composite", "value" => ["status" => null, "vigencia" => null], "col_width" => 6],
                                ["label" => "RCV", "response_type" => "composite", "value" => ["status" => null, "vigencia" => null], "col_width" => 6]
                            ]
                        ],
                        [
                            "subsection_title" => "Documentación del Conductor",
                            // Estos ítems deben ocupar el ancho completo para sus campos compuestos
                            "items" => [
                                ["label" => "Cédula de Identidad", "response_type" => "composite", "value" => ["status" => null, "vigencia" => null], "col_width" => 6],
                                ["label" => "Certificado Médico", "response_type" => "composite", "value" => ["status" => null, "vigencia" => null], "col_width" => 6],
                                ["label" => "Licencia de Conducir Profesional", "response_type" => "composite", "value" => ["status" => null, "vigencia" => null], "col_width" => 6],
                                ["label" => "Certificado de Manejo de Materiales Peligrosos", "response_type" => "composite", "value" => ["status" => null, "vigencia" => null], "col_width" => 6]
                            ]
                        ],
                        [
                            "subsection_title" => "Implementos de Trabajo",
                            "items" => [
                                // Estos ítems son binarios, se agrupan en 3 columnas
                                ["label" => "Uniforme", "response_type" => "boolean", "value" => true, "col_width" => 4],
                                ["label" => "Camisa", "response_type" => "boolean", "value" => true, "col_width" => 4],
                                ["label" => "Pantalón", "response_type" => "boolean", "value" => true, "col_width" => 4],
                                ["label" => "Gorra", "response_type" => "boolean", "value" => true, "col_width" => 4],
                                ["label" => "Botas", "response_type" => "boolean", "value" => true, "col_width" => 4],
                                ["label" => "Kit de Seguridad", "response_type" => "boolean", "value" => true, "col_width" => 4],
                                ["label" => "Kit Antiderrame", "response_type" => "boolean", "value" => true, "col_width" => 4],
                                ["label" => "Lentes", "response_type" => "boolean", "value" => true, "col_width" => 4],
                                ["label" => "Guantes", "response_type" => "boolean", "value" => true, "col_width" => 4],
                                ["label" => "Poncho", "response_type" => "boolean", "value" => true, "col_width" => 4],
                                ["label" => "Aserrín", "response_type" => "boolean", "value" => true, "col_width" => 4]
                            ]
                        ]
                    ]
                ],
                [
                    "section_title" => "9.- KIT DE EQUIPAMIENTO",
                    // Ítems binarios, se agrupan en 2 columnas para optimizar el espacio
                    "items" => [
                        ["label" => "Manguera de 1 pulgada y 1/2", "response_type" => "boolean", "value" => true, "col_width" => 6],
                        ["label" => "Manguera de 1 pulgada", "response_type" => "boolean", "value" => true, "col_width" => 6],
                        ["label" => "Pico para abastecer de 1 pulgada y 1/2", "response_type" => "boolean", "value" => true, "col_width" => 6],
                        ["label" => "Pico para abastecer de 1 pulgada", "response_type" => "boolean", "value" => true, "col_width" => 6],
                        ["label" => "Tapón para los picos de la manguera", "response_type" => "boolean", "value" => true, "col_width" => 6],
                        ["label" => "Mecate", "response_type" => "boolean", "value" => true, "col_width" => 6],
                        ["label" => "Gracera", "response_type" => "boolean", "value" => true, "col_width" => 6],
                        ["label" => "Dispensador de aceite", "response_type" => "boolean", "value" => true, "col_width" => 6],
                        ["label" => "Manguera de aire grasera", "response_type" => "boolean", "value" => true, "col_width" => 6],
                        ["label" => "Manguera de aire dispensador de aceite", "response_type" => "boolean", "value" => true, "col_width" => 6],
                        ["label" => "Tapa de los tanques", "response_type" => "boolean", "value" => true, "col_width" => 6],
                        ["label" => "Protector del medidor de nivel de los tanques", "response_type" => "boolean", "value" => true, "col_width" => 6],
                        ["label" => "Medidor", "response_type" => "boolean", "value" => true, "col_width" => 6],
                        ["label" => "Bomba y medidor de 1 pulgada", "response_type" => "boolean", "value" => true, "col_width" => 6],
                        ["label" => "Llaves de paso", "response_type" => "boolean", "value" => true, "col_width" => 6],
                        ["label" => "Filtro Yee", "response_type" => "boolean", "value" => true, "col_width" => 6],
                        ["label" => "Candados", "response_type" => "boolean", "value" => true, "col_width" => 6],
                        ["label" => "Llaves de candados", "response_type" => "boolean", "value" => true, "col_width" => 6],
                        ["label" => "Tapón de la salida de despacho de la motobomba", "response_type" => "boolean", "value" => true, "col_width" => 6],
                        ["label" => "Gato y palanca", "response_type" => "boolean", "value" => true, "col_width" => 6],
                        ["label" => "Bandejas contenedores de líquidos", "response_type" => "boolean", "value" => true, "col_width" => 6],
                        ["label" => "Calcomanías", "response_type" => "boolean", "value" => true, "col_width" => 6],
                        ["label" => "Kit de herramientas", "response_type" => "boolean", "value" => true, "col_width" => 6],
                        ["label" => "Conos", "response_type" => "boolean", "value" => true, "col_width" => 6],
                        ["label" => "Extintores", "response_type" => "boolean", "value" => true, "col_width" => 6]
                    ]
                ],
                [
                    "section_title" => "Observaciones",
                    // Textarea debe ocupar el ancho completo para comentarios
                    "items" => [
                        ["label" => "Observaciones Generales", "response_type" => "textarea", "value" => "", "col_width" => 12]
                    ]
                ]
            ]
        ];

        // Crear el registro del checklist
        Checklist::create([
            'id' => 1,
            'titulo' => 'Checkout - IMPORDIESEL',
            //'version' => '1.3',
            'activo' => true,
            // Almacenar el array de PHP, Laravel lo convertirá a JSON en la base de datos
            'checklist' => $checklistJson, 
        ]);
    }
}