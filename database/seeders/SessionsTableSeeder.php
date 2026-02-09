<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class SessionsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('sessions')->delete();
        
        \DB::table('sessions')->insert(array (
            0 => 
            array (
                'id' => '6xoYg6aGSEiQ7nESMd8dw5r2yPIGqxaINVFEbXeg',
                'ip_address' => '127.0.0.1',
                'last_activity' => 1754065545,
                'payload' => 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiNDBaUko1a0g4UE5HQzRndGlxVGJkbnRGV3lCYldJRjd1MGZRRUlkSyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0',
                'user_id' => NULL,
            ),
            1 => 
            array (
                'id' => 'eNlKeQpDrgqeoHRuuT4QjICOJB7HIprrot5FmzSY',
                'ip_address' => '127.0.0.1',
                'last_activity' => 1754050416,
                'payload' => 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiMGcxQ3Eyd2RiSk1EcVF1RmVHMTdtOGNqaWZhdUJsREZZVGVYQjBVViI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MTk6Imh0dHA6Ly9vcml2YXMubG9jYWwiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0',
                'user_id' => NULL,
            ),
            2 => 
            array (
                'id' => 'PqqIsnRxFkq4hto4wfKHM9gsZsjcYJg5XlKzHiXw',
                'ip_address' => '127.0.0.1',
                'last_activity' => 1754056011,
                'payload' => 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiOHh2UWZrRkxDaWM0VHAzdU95M1pyMGpuM2ZjVEprRXhyelFpMTVMdiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjM6Imh0dHA6Ly9hcGlzLmNvbWJ1c3RpYmxlIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0',
                'user_id' => NULL,
            ),
            3 => 
            array (
                'id' => 'WCa42fWWiopGKQBtEzir6DxVO9bLW9QOVtyB5dbV',
                'ip_address' => '127.0.0.1',
                'last_activity' => 1745652358,
                'payload' => 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiSlkzd0czRExja3ZRZU13VVFzZWh4MUJZdmlrYTd4Y3MxZ3BseHBOUSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzQ6Imh0dHA6Ly9vcml2YXMubG9jYWwvbWFwYS1vcGVyYXRpdm8iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToxO30=',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 OPR/117.0.0.0',
                'user_id' => 1,
            ),
            4 => 
            array (
                'id' => 'ZAXvbpC7umgjEXeIRshGlWnzt1duXILL04g7fv9E',
                'ip_address' => '127.0.0.1',
                'last_activity' => 1754063520,
                'payload' => 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiN3dEMERVcmZzSlVVanlIWHJtVWtlcHNFNDZsMHdXSm5kNVA3aVBIZiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjM6Imh0dHA6Ly9hcGlzLmNvbWJ1c3RpYmxlIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0',
                'user_id' => NULL,
            ),
        ));
        
        
    }
}