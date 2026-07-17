<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Limpiar datos no numéricos antes de conversión
        // Tabla costs (aunque son integers, validamos por consistencia)
        DB::statement("UPDATE costs SET booth_costs = 0 WHERE booth_costs IS NULL");
        DB::statement("UPDATE costs SET travel_cost = 0 WHERE travel_cost IS NULL");
        
        // Tabla expenses (aunque son integers, validamos por consistencia)
        DB::statement("UPDATE expenses SET cost = 0 WHERE cost IS NULL");
        
        // Tablas con strings que necesitan limpieza
        DB::statement("UPDATE diesel SET amount = '0' WHERE amount = '' OR amount IS NULL OR amount NOT REGEXP '^[0-9]+(\\.[0-9]+)?$'");
        DB::statement("UPDATE booths SET cost = '0' WHERE cost = '' OR cost IS NULL OR cost NOT REGEXP '^[0-9]+(\\.[0-9]+)?$'");
        DB::statement("UPDATE treasury_payments SET total = '0' WHERE total = '' OR total IS NULL OR total NOT REGEXP '^[0-9]+(\\.[0-9]+)?$'");
        DB::statement("UPDATE payments SET total = '0' WHERE total = '' OR total IS NULL OR total NOT REGEXP '^[0-9]+(\\.[0-9]+)?$'");
        DB::statement("UPDATE discounts SET total = '0' WHERE total = '' OR total IS NULL OR total NOT REGEXP '^[0-9]+(\\.[0-9]+)?$'");
        DB::statement("UPDATE parts_supplier_requests SET cost = '0' WHERE cost = '' OR cost IS NULL OR cost NOT REGEXP '^[0-9]+(\\.[0-9]+)?$'");
        DB::statement("UPDATE services SET commission = NULL WHERE commission = '' OR commission NOT REGEXP '^[0-9]+(\\.[0-9]+)?$'");
        
        // 1. Tabla costs
        DB::statement('ALTER TABLE costs MODIFY COLUMN booth_costs DECIMAL(10, 2) NOT NULL');
        DB::statement('ALTER TABLE costs MODIFY COLUMN travel_cost DECIMAL(10, 2) NOT NULL');
        
        // 2. Tabla expenses
        DB::statement('ALTER TABLE expenses MODIFY COLUMN cost DECIMAL(10, 2) NOT NULL');
        
        // 3. Tabla diesel (de string a decimal)
        DB::statement('ALTER TABLE diesel MODIFY COLUMN amount DECIMAL(10, 2) NOT NULL');
        
        // 4. Tabla booths (de string a decimal)
        DB::statement('ALTER TABLE booths MODIFY COLUMN cost DECIMAL(10, 2) NOT NULL DEFAULT 0');
        
        // 5. Tabla treasury_services (de decimal(10,0) a decimal(10,2))
        DB::statement('ALTER TABLE treasury_services MODIFY COLUMN total DECIMAL(10, 2) NOT NULL');
        
        // 6. Tabla treasury_maintenances (de decimal(10,0) a decimal(10,2))
        DB::statement('ALTER TABLE treasury_maintenances MODIFY COLUMN total DECIMAL(10, 2) NOT NULL');
        
        // 7. Tabla treasury_payments (de string a decimal)
        DB::statement('ALTER TABLE treasury_payments MODIFY COLUMN total DECIMAL(10, 2) NOT NULL');
        
        // 8. Tabla payments (de string a decimal)
        DB::statement('ALTER TABLE payments MODIFY COLUMN total DECIMAL(10, 2) NOT NULL');
        
        // 9. Tabla discounts (de string a decimal)
        DB::statement('ALTER TABLE discounts MODIFY COLUMN total DECIMAL(10, 2) NOT NULL');
        
        // 10. Tabla parts_supplier_requests (de string a decimal)
        DB::statement('ALTER TABLE parts_supplier_requests MODIFY COLUMN cost DECIMAL(10, 2) NOT NULL');
        
        // 11. Tabla services - commission (de string a decimal, nullable)
        DB::statement('ALTER TABLE services MODIFY COLUMN commission DECIMAL(10, 2) NULL');
    }

    public function down(): void
    {
        // Revertir cambios (no recomendado en producción)
        // 1. Tabla costs
        DB::statement('ALTER TABLE costs MODIFY COLUMN booth_costs INT NOT NULL');
        DB::statement('ALTER TABLE costs MODIFY COLUMN travel_cost INT NOT NULL');
        
        // 2. Tabla expenses
        DB::statement('ALTER TABLE expenses MODIFY COLUMN cost INT NOT NULL');
        
        // 3. Tabla diesel
        DB::statement('ALTER TABLE diesel MODIFY COLUMN amount VARCHAR(255) NOT NULL');
        
        // 4. Tabla booths
        DB::statement('ALTER TABLE booths MODIFY COLUMN cost VARCHAR(255) NOT NULL DEFAULT \'0\'');
        
        // 5. Tabla treasury_services
        DB::statement('ALTER TABLE treasury_services MODIFY COLUMN total DECIMAL(10, 0) NOT NULL');
        
        // 6. Tabla treasury_maintenances
        DB::statement('ALTER TABLE treasury_maintenances MODIFY COLUMN total DECIMAL(10, 0) NOT NULL');
        
        // 7. Tabla treasury_payments
        DB::statement('ALTER TABLE treasury_payments MODIFY COLUMN total VARCHAR(25) NOT NULL');
        
        // 8. Tabla payments
        DB::statement('ALTER TABLE payments MODIFY COLUMN total VARCHAR(25) NOT NULL');
        
        // 9. Tabla discounts
        DB::statement('ALTER TABLE discounts MODIFY COLUMN total VARCHAR(25) NOT NULL');
        
        // 10. Tabla parts_supplier_requests
        DB::statement('ALTER TABLE parts_supplier_requests MODIFY COLUMN cost VARCHAR(100) NOT NULL');
        
        // 11. Tabla services
        DB::statement('ALTER TABLE services MODIFY COLUMN commission VARCHAR(11) NULL');
    }
};
