<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Tablas sin dependencias - modificar primero
        DB::statement('ALTER TABLE booths MODIFY COLUMN id INT AUTO_INCREMENT');
        DB::statement('ALTER TABLE catalogs MODIFY COLUMN id INT AUTO_INCREMENT');
        DB::statement('ALTER TABLE places MODIFY COLUMN id INT AUTO_INCREMENT');
        DB::statement('ALTER TABLE operators MODIFY COLUMN id INT AUTO_INCREMENT');
        DB::statement('ALTER TABLE inventories MODIFY COLUMN id INT AUTO_INCREMENT');
        DB::statement('ALTER TABLE states MODIFY COLUMN id INT AUTO_INCREMENT');
        DB::statement('ALTER TABLE substates MODIFY COLUMN id INT AUTO_INCREMENT');
        DB::statement('ALTER TABLE diesel_cost MODIFY COLUMN id INT AUTO_INCREMENT');
        DB::statement('ALTER TABLE maintenance_status MODIFY COLUMN id INT AUTO_INCREMENT');
        DB::statement('ALTER TABLE types_maintenance MODIFY COLUMN id INT AUTO_INCREMENT');
        
        // Tablas con dependencias simples
        DB::statement('ALTER TABLE containers MODIFY COLUMN id INT AUTO_INCREMENT');
        DB::statement('ALTER TABLE destinations MODIFY COLUMN id INT AUTO_INCREMENT');
        DB::statement('ALTER TABLE costs MODIFY COLUMN id INT AUTO_INCREMENT');
        DB::statement('ALTER TABLE diesel MODIFY COLUMN id INT AUTO_INCREMENT');
        DB::statement('ALTER TABLE evidences MODIFY COLUMN id INT AUTO_INCREMENT');
        DB::statement('ALTER TABLE expenses MODIFY COLUMN id INT AUTO_INCREMENT');
        DB::statement('ALTER TABLE historicals MODIFY COLUMN id INT AUTO_INCREMENT');
        DB::statement('ALTER TABLE stocks MODIFY COLUMN id INT AUTO_INCREMENT');
        DB::statement('ALTER TABLE tires MODIFY COLUMN id INT AUTO_INCREMENT');
        DB::statement('ALTER TABLE maintenances MODIFY COLUMN id INT AUTO_INCREMENT');
        DB::statement('ALTER TABLE inventory_requests MODIFY COLUMN id INT AUTO_INCREMENT');
        DB::statement('ALTER TABLE parts_supplier_requests MODIFY COLUMN id INT AUTO_INCREMENT');
        DB::statement('ALTER TABLE treasury_maintenances MODIFY COLUMN id INT AUTO_INCREMENT');
        DB::statement('ALTER TABLE treasury_payments MODIFY COLUMN id INT AUTO_INCREMENT');
        DB::statement('ALTER TABLE treasury_services MODIFY COLUMN id INT AUTO_INCREMENT');
        DB::statement('ALTER TABLE payments MODIFY COLUMN id INT AUTO_INCREMENT');
        DB::statement('ALTER TABLE discounts MODIFY COLUMN id INT AUTO_INCREMENT');
        
        // Tabla especial con configuración compleja - modificar al final
        DB::statement('ALTER TABLE approvals MODIFY COLUMN id INT AUTO_INCREMENT');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remover AUTO_INCREMENT de todas las tablas
        DB::statement('ALTER TABLE approvals MODIFY COLUMN id INT NOT NULL');
        
        DB::statement('ALTER TABLE discounts MODIFY COLUMN id INT NOT NULL');
        DB::statement('ALTER TABLE payments MODIFY COLUMN id INT NOT NULL');
        DB::statement('ALTER TABLE treasury_services MODIFY COLUMN id INT NOT NULL');
        DB::statement('ALTER TABLE treasury_payments MODIFY COLUMN id INT NOT NULL');
        DB::statement('ALTER TABLE treasury_maintenances MODIFY COLUMN id INT NOT NULL');
        DB::statement('ALTER TABLE parts_supplier_requests MODIFY COLUMN id INT NOT NULL');
        DB::statement('ALTER TABLE inventory_requests MODIFY COLUMN id INT NOT NULL');
        DB::statement('ALTER TABLE maintenances MODIFY COLUMN id INT NOT NULL');
        DB::statement('ALTER TABLE tires MODIFY COLUMN id INT NOT NULL');
        DB::statement('ALTER TABLE stocks MODIFY COLUMN id INT NOT NULL');
        DB::statement('ALTER TABLE historicals MODIFY COLUMN id INT NOT NULL');
        DB::statement('ALTER TABLE expenses MODIFY COLUMN id INT NOT NULL');
        DB::statement('ALTER TABLE evidences MODIFY COLUMN id INT NOT NULL');
        DB::statement('ALTER TABLE diesel MODIFY COLUMN id INT NOT NULL');
        DB::statement('ALTER TABLE costs MODIFY COLUMN id INT NOT NULL');
        DB::statement('ALTER TABLE destinations MODIFY COLUMN id INT NOT NULL');
        DB::statement('ALTER TABLE containers MODIFY COLUMN id INT NOT NULL');
        
        DB::statement('ALTER TABLE types_maintenance MODIFY COLUMN id INT NOT NULL');
        DB::statement('ALTER TABLE maintenance_status MODIFY COLUMN id INT NOT NULL');
        DB::statement('ALTER TABLE diesel_cost MODIFY COLUMN id INT NOT NULL');
        DB::statement('ALTER TABLE substates MODIFY COLUMN id INT NOT NULL');
        DB::statement('ALTER TABLE states MODIFY COLUMN id INT NOT NULL');
        DB::statement('ALTER TABLE inventories MODIFY COLUMN id INT NOT NULL');
        DB::statement('ALTER TABLE operators MODIFY COLUMN id INT NOT NULL');
        DB::statement('ALTER TABLE places MODIFY COLUMN id INT NOT NULL');
        DB::statement('ALTER TABLE catalogs MODIFY COLUMN id INT NOT NULL');
        DB::statement('ALTER TABLE booths MODIFY COLUMN id INT NOT NULL');
    }
};
