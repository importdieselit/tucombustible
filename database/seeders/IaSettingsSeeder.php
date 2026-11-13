<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\IaSetting;

class IaSettingsSeeder extends Seeder
{
    public function run(): void
    {
        IaSetting::truncate();

        $settings = [
            [
                'key' => 'agent_name',
                'value' => 'C.O.R.E.',
                'description' => 'Nombre del asistente virtual'
            ],
            [
                'key' => 'model_name',
                'value' => 'openai/gpt-oss-20b',
                'description' => 'Modelo de lenguaje a utilizar (ej. gpt-4, openai/gpt-oss-20b)'
            ],
            [
                'key' => 'temperature',
                'value' => '0.2',
                'description' => 'Nivel de creatividad (0.0 muy estricto, 1.0 muy creativo)'
            ],
            [
                'key' => 'system_prompt',
                'value' => "Eres C.O.R.E., el Asistente Corporativo de Alta Gerencia de Impordiesel. Tu interlocutor es el Director de la empresa. Tu función no es solo leer datos, sino ANALIZARLOS y dar CONCLUSIONES GERENCIALES. 
        
                            REGLAS DE ORO:
                            1. Tu respuesta será dictada por un sintetizador de voz. NUNCA uses tablas (|), asteriscos (*), viñetas o formatos Markdown. Habla en prosa natural.
                            2. Extrae la conclusión ejecutiva de los datos.
                            3. Tienes memoria. Si el Director hace una pregunta de seguimiento, continúa el hilo.
                            TONO: Ejecutivo, seguro, rápido. Como un Jefe de Operaciones brillante.",
                'description' => 'Instrucciones base del comportamiento (Cerebro de la IA)'
            ],
            [
                'key' => 'api_base_url',
                'value' => 'https://api.groq.com/openai/v1/chat/completions',
                'description' => 'URL del proveedor de IA (Cambiar si se migra a Ollama local u OpenAI)'
            ]
        ];

        foreach ($settings as $setting) {
            IaSetting::create($setting);
        }
    }
}