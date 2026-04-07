<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VenezuelaMapSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Desactivar claves foráneas temporalmente para limpieza
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('ciudades')->truncate();
        DB::table('estados')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $venezuela = [
            'Amazonas' => ['Puerto Ayacucho', 'San Fernando de Atabapo', 'Maroa', 'Puerto Páez'],
            'Anzoátegui' => ['Barcelona', 'Puerto La Cruz', 'Lechería', 'Guanta', 'Anaco', 'El Tigre', 'Cantaura', 'Puerto Píritu'],
            'Apure' => ['San Fernando de Apure', 'Guasdualito', 'Elorza', 'Bruzual'],
            'Aragua' => ['Maracay', 'Turmero', 'La Victoria', 'Cagua', 'Villa de Cura', 'El Limón', 'Palo Negro', 'San Sebastián'],
            'Barinas' => ['Barinas', 'Socopó', 'Santa Bárbara', 'Sabaneta', 'Barinitas'],
            'Bolívar' => ['Ciudad Bolívar', 'Puerto Ordaz', 'San Félix', 'Upata', 'Caicara del Orinoco', 'Tumeremo', 'Guasipati', 'Santa Elena de Uairén'],
            'Carabobo' => ['Valencia', 'Puerto Cabello', 'Guacara', 'Naguanagua', 'San Diego', 'Mariara', 'Bejuma', 'Morón'],
            'Cojedes' => ['San Carlos', 'Tinaquillo', 'El Pao'],
            'Delta Amacuro' => ['Tucupita', 'Pedernales', 'Curiapo'],
            'Distrito Capital' => ['Caracas'],
            'Falcón' => ['Coro', 'Punto Fijo', 'Chichiriviche', 'Dabajuro', 'La Vela de Coro', 'Pueblo Nuevo'],
            'Guárico' => ['San Juan de los Morros', 'Valle de la Pascua', 'Calabozo', 'Zaraza', 'Altagracia de Orituco'],
            'Lara' => ['Barquisimeto', 'Carora', 'Cabudare', 'El Tocuyo', 'Duaca', 'Quíbor'],
            'Mérida' => ['Mérida', 'El Vigía', 'Ejido', 'Tovar', 'Mucuchíes'],
            'Miranda' => ['Los Teques', 'Guarenas', 'Guatire', 'Charallave', 'Santa Teresa del Tuy', 'Ocumare del Tuy', 'Higuerote', 'Río Chico', 'San Antonio de los Altos', 'Santa Lucía del Tuy', 'Chacao', 'El Hatillo', 'Baruta', 'Sucre'],
            'Monagas' => ['Maturín', 'Punta de Mata', 'Caripe', 'Caripito', 'Temblador'],
            'Nueva Esparta' => ['La Asunción', 'Porlamar', 'Pampatar', 'Juan Griego', 'Punta de Piedras'],
            'Portuguesa' => ['Guanare', 'Acarigua', 'Araure', 'Turén', 'Boconó'],
            'Sucre' => ['Cumaná', 'Carúpano', 'Güiria', 'Cumanacoa', 'Cariaco'],
            'Táchira' => ['San Cristóbal', 'Táriba', 'Rubio', 'La Grita', 'San Antonio del Táchira', 'La Fría', 'Capacho'],
            'Trujillo' => ['Trujillo', 'Valera', 'Boconó', 'La Puerta', 'Pampán'],
            'La Guaira' => ['La Guaira', 'Catia La Mar', 'Maiquetía', 'Caraballeda', 'Naiguatá'],
            'Yaracuy' => ['San Felipe', 'Yaritagua', 'Chivacoa', 'Nirgua'],
            'Zulia' => ['Maracaibo', 'Cabimas', 'Ciudad Ojeda', 'San Francisco', 'Santa Bárbara del Zulia', 'Machiques', 'La Concepción', 'Bachaquero', 'Mene Grande'],
        ];

        foreach ($venezuela as $estado => $ciudades) {
            $estadoId = DB::table('estados')->insertGetId([
                'nombre' => $estado,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($ciudades as $ciudad) {
                DB::table('ciudades')->insert([
                    'estado_id' => $estadoId,
                    'nombre' => $ciudad,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
