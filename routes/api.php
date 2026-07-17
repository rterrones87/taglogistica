<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\TravelController;
use App\Http\Controllers\OperatorController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\UnitTypeController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\PlaceController;
use App\Http\Controllers\BoothController;
use App\Http\Controllers\CostController;
use App\Http\Controllers\ExtrasController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\TreasuryController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\TireController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\ClientPlaceController;
use App\Http\Controllers\ServiceOperatorTypeController;
use App\Http\Controllers\ServiceOperatorTypeRateController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('fcm_token/register', [AuthController::class, 'fcm_token_register']);

Route::middleware('auth:sanctum')->group(function () {
    //Route::post('logout', [AuthController::class, 'logout']);
    
    // Auth y Catálogos (sin permisos, disponibles para todos los autenticados)
    Route::put('password/{user_id}', [AuthController::class, 'password'])->middleware('permission:users.change_password');
    Route::get('roles', [AuthController::class, 'roles']);
    
    // Gestión de Permisos por Rol
    Route::get('roles/permissions', [RolePermissionController::class, 'permissions'])->middleware('permission:roles.manage_permissions');
    Route::get('roles/{role}/permissions', [RolePermissionController::class, 'show'])->middleware('permission:roles.manage_permissions');
    Route::put('roles/{role}/permissions', [RolePermissionController::class, 'update'])->middleware('permission:roles.manage_permissions');
    Route::post('roles', [RolePermissionController::class, 'store']);
    
    Route::get('catalog/units', [CatalogController::class, 'units']);
    Route::get('unit-types', [UnitTypeController::class, 'index']);
    Route::get('catalog/containers', [CatalogController::class, 'containers']);
    Route::get('catalog/terminals', [CatalogController::class, 'terminals']);
    Route::get('catalog/destines', [CatalogController::class, 'destines']);
    Route::get('catalog/container-numbers', [CatalogController::class, 'container_numbers']);
    
    // Destinos de Clientes
    Route::get('client-places', [ClientPlaceController::class, 'index'])->middleware('permission:client_places.view');
    Route::post('client-places', [ClientPlaceController::class, 'store'])->middleware('permission:client_places.create');
    Route::get('client-places/{id}', [ClientPlaceController::class, 'show'])->middleware('permission:client_places.view');
    Route::put('client-places/{id}', [ClientPlaceController::class, 'update'])->middleware('permission:client_places.edit');
    Route::delete('client-places/{id}', [ClientPlaceController::class, 'destroy'])->middleware('permission:client_places.delete');
    // Plantilla de casetas por destino de cliente
    Route::get('client-places/{id}/booths', [ClientPlaceController::class, 'listBooths'])->middleware('permission:client_places.edit');
    Route::post('client-places/{id}/booths', [ClientPlaceController::class, 'addBooth'])->middleware('permission:client_places.edit');
    Route::delete('client-places/{clientPlaceId}/booths/{boothRecordId}', [ClientPlaceController::class, 'removeBooth'])->middleware('permission:client_places.edit');

    // Clientes
    Route::get('clients', [ClientController::class, 'index'])->middleware('permission:clients.view,clients.consult');
    Route::post('clients', [ClientController::class, 'store'])->middleware('permission:clients.create');
    Route::get('clients/{client}', [ClientController::class, 'show'])->middleware('permission:clients.view');
    Route::put('clients/{client}', [ClientController::class, 'update'])->middleware('permission:clients.edit');
    Route::delete('clients/{client}', [ClientController::class, 'destroy'])->middleware('permission:clients.delete');
    
    // Proveedores
    Route::get('suppliers', [SupplierController::class, 'index'])->middleware('permission:suppliers.view');
    Route::post('suppliers', [SupplierController::class, 'store'])->middleware('permission:suppliers.create');
    Route::get('suppliers/{supplier}', [SupplierController::class, 'show'])->middleware('permission:suppliers.view');
    Route::put('suppliers/{supplier}', [SupplierController::class, 'update'])->middleware('permission:suppliers.edit');
    Route::delete('suppliers/{supplier}', [SupplierController::class, 'destroy'])->middleware('permission:suppliers.delete');
    
    // Unidades
    Route::get('units/maintenance', [UnitController::class, 'indexForMaintenance'])->middleware('permission:units.view,units.consult');
    Route::get('units', [UnitController::class, 'index'])->middleware('permission:units.view,units.consult');
    Route::post('units', [UnitController::class, 'store'])->middleware('permission:units.create');
    Route::get('units/{unit}', [UnitController::class, 'show'])->middleware('permission:units.view');
    Route::put('units/{unit}', [UnitController::class, 'update'])->middleware('permission:units.edit');
    Route::delete('units/{unit}', [UnitController::class, 'destroy'])->middleware('permission:units.delete');
    
    // Lugares
    Route::get('places', [PlaceController::class, 'index'])->middleware('permission:places.view,places.consult');
    Route::post('places', [PlaceController::class, 'store'])->middleware('permission:places.create');
    Route::get('places/{place}', [PlaceController::class, 'show'])->middleware('permission:places.view');
    Route::put('places/{place}', [PlaceController::class, 'update'])->middleware('permission:places.edit');
    Route::delete('places/{place}', [PlaceController::class, 'destroy'])->middleware('permission:places.delete');
    
    // Usuarios
    Route::get('users', [UserController::class, 'index'])->middleware('permission:users.view,users.consult');
    Route::post('users', [UserController::class, 'store'])->middleware('permission:users.create');
    Route::get('users/{user}', [UserController::class, 'show'])->middleware('permission:users.view');
    Route::put('users/{user}', [UserController::class, 'update'])->middleware('permission:users.edit');
    Route::delete('users/{user}', [UserController::class, 'destroy'])->middleware('permission:users.delete');

    // Casetas
    Route::get('booths', [BoothController::class, 'index'])->middleware('permission:booths.view,booths.consult');
    Route::post('booths', [BoothController::class, 'store'])->middleware('permission:booths.create');
    Route::get('booths/{booth}', [BoothController::class, 'show'])->middleware('permission:booths.view');
    Route::put('booths/{booth}', [BoothController::class, 'update'])->middleware('permission:booths.edit');
    Route::delete('booths/{booth}', [BoothController::class, 'destroy'])->middleware('permission:booths.delete');
    
    // Servicios
    Route::get('services', [ServiceController::class, 'index'])->middleware('permission:services.view,services.consult');
    Route::post('services', [ServiceController::class, 'store'])->middleware(['permission:services.create', 'idempotency']);
    Route::get('services/{service}', [ServiceController::class, 'show'])->middleware('permission:services.view');
    Route::put('services/{service}', [ServiceController::class, 'update'])->middleware('permission:services.edit,services.assign,services.assign_diesel');
    Route::delete('services/{service}', [ServiceController::class, 'destroy'])->middleware('permission:services.delete');
    Route::post('services/cancel/{service_id}', [ServiceController::class, 'cancel'])->middleware('permission:services.cancel');
    Route::post('services/reassign/{service_id}', [ServiceController::class, 'reassign'])->middleware('permission:services.reassign');
    Route::get('services/historical/{service_id}', [ServiceController::class, 'historical'])->middleware('permission:services.view');
    Route::get('services/{service_id}/substate-history', [ServiceController::class, 'substateHistory'])->middleware('permission:services.view');
    Route::post('services/request_diesel/{service_id}', [ServiceController::class, 'request_diesel'])->middleware('permission:services.request_diesel');
    Route::post('services/request_booth/{service_id}', [ServiceController::class, 'request_booth'])->middleware('permission:services.request_booth');
    Route::post('services/change_substate', [ServiceController::class, 'change_substate'])->middleware('permission:services.change_substate');
    Route::get('services/authorized_expenses/{service_id}', [ServiceController::class, 'authorized_expenses'])->middleware('permission:services.view');
    Route::get('services/weekly-payments/operators', [ServiceController::class, 'weekly_payments'])->middleware('permission:operator_payments.view');
    Route::get('services/weekly-payments/operator/{operator_id}', [ServiceController::class, 'weekly_operator_payments'])->middleware('permission:operator_payments.view');
    Route::put('services/weekly-payments/operator/{operator_id}', [ServiceController::class, 'save_weekly_operator_payments'])->middleware('permission:operator_payments.create,operator_payments.edit');
    
    // Travels (mantener sin cambios por ahora)
    Route::apiResource('travels', TravelController::class);
    
    // Operadores
    Route::get('operators', [OperatorController::class, 'index'])->middleware('permission:operators.view,operators.consult');
    Route::post('operators', [OperatorController::class, 'store'])->middleware('permission:operators.create');
    Route::get('operators/{operator}', [OperatorController::class, 'show'])->middleware('permission:operators.view');
    Route::put('operators/{operator}', [OperatorController::class, 'update'])->middleware('permission:operators.edit');
    Route::delete('operators/{operator}', [OperatorController::class, 'destroy'])->middleware('permission:operators.delete');
    Route::get('operator/{id}/payments', [OperatorController::class, 'payments'])->middleware('permission:operators.view_payments');
    

    // Inventarios
    Route::get('inventories', [InventoryController::class, 'index'])->middleware('permission:inventories.view');
    Route::post('inventories', [InventoryController::class, 'store'])->middleware('permission:inventories.create');
    Route::get('inventories/{inventory}', [InventoryController::class, 'show'])->middleware('permission:inventories.view');
    Route::put('inventories/{inventory}', [InventoryController::class, 'update'])->middleware('permission:inventories.edit');
    Route::delete('inventories/{inventory}', [InventoryController::class, 'destroy'])->middleware('permission:inventories.delete');
    
    // Llantas
    Route::get('tires', [TireController::class, 'index'])->middleware('permission:tires.view');
    Route::post('tires', [TireController::class, 'store'])->middleware('permission:tires.create');
    Route::get('tires/{tire}', [TireController::class, 'show'])->middleware('permission:tires.view');
    Route::put('tires/{tire}', [TireController::class, 'update'])->middleware('permission:tires.edit');
    Route::delete('tires/{tire}', [TireController::class, 'destroy'])->middleware('permission:tires.delete');
    
    // Costos
    Route::get('costs/{cost}', [CostController::class, 'show'])->middleware('permission:costs.view');
    Route::put('costs/{cost}', [CostController::class, 'update'])->middleware('permission:costs.edit');
    
    // Gastos Extras
    Route::get('extras/{extra}', [ExtrasController::class, 'show'])->middleware('permission:expenses.view');
    Route::put('extras/{extra}', [ExtrasController::class, 'update'])->middleware('permission:expenses.edit');
    
    // Mantenimientos
    Route::get('maintenances', [MaintenanceController::class, 'index'])->middleware('permission:maintenances.view');
    Route::post('maintenances', [MaintenanceController::class, 'store'])->middleware('permission:maintenances.create');
    Route::get('maintenances/{maintenance}', [MaintenanceController::class, 'show'])->middleware('permission:maintenances.view');
    Route::put('maintenances/{maintenance}', [MaintenanceController::class, 'update'])->middleware('permission:maintenances.edit');
    Route::delete('maintenances/{maintenance}', [MaintenanceController::class, 'destroy'])->middleware('permission:maintenances.delete');
    Route::get('types/maintenances', [MaintenanceController::class, 'maintenance_types'])->middleware('permission:maintenances.view');
    Route::post('maintenances/cancel/{id}', [MaintenanceController::class, 'cancel'])->middleware('permission:maintenances.cancel');
    Route::post('maintenances/change_state', [MaintenanceController::class, 'change_state'])->middleware('permission:maintenances.change_state');
    Route::post('maintenances/upload-photos/{id}', [MaintenanceController::class, 'uploadEvidence'])->middleware('permission:maintenances.upload_evidence');

    // Tesorería
    Route::get('treasury/maintenances', [TreasuryController::class, 'maintenances'])->middleware('permission:treasury.view_maintenances');
    Route::get('treasury/maintenances/details/{id}', [TreasuryController::class, 'maintenanceDetails'])->middleware('permission:treasury.view_maintenances');
    Route::get('treasury/services', [TreasuryController::class, 'services'])->middleware('permission:treasury.view_services');
    Route::get('treasury/payments', [TreasuryController::class, 'payments'])->middleware('permission:treasury.view_payments');
    Route::get('treasury/payments/details/{id}', [TreasuryController::class, 'payments_details'])->middleware('permission:treasury.view_payments');
    Route::put('treasury/apply-payment', [TreasuryController::class, 'applyPayment'])->middleware('permission:treasury.apply_payment');
    Route::get('treasury/payments/pdf/{id}', [TreasuryController::class, 'payment_pdfhtml'])->middleware('permission:treasury.view_payments');
    Route::get('treasury/init/expenses/{id}', [TreasuryController::class, 'initExpenses'])->middleware('permission:treasury.init_expenses');
    Route::get('treasury/ext/expenses/{id}', [TreasuryController::class, 'extExpenses'])->middleware('permission:treasury.ext_expenses');
    Route::post('treasury/upload-photos/{id}', [TreasuryController::class, 'upload'])->middleware('permission:treasury.upload_evidence');

    // Descargas
    Route::get('download/services', [ServiceController::class, 'download'])->middleware('permission:services.download');
    Route::get('download/maintenances', [MaintenanceController::class, 'download'])->middleware('permission:maintenances.view');
    Route::get('download/treasury/services', [TreasuryController::class, 'download'])->middleware('permission:treasury.view_services');
    Route::get('download/treasury/maintenances', [TreasuryController::class, 'downloadMaintenances'])->middleware('permission:treasury.view_maintenances');

    // Aprobaciones
    Route::post('/approvals/{approval}/approve', [ApprovalController::class, 'approve'])->middleware('permission:approvals.approve')->name('approvals.approve');
    Route::post('/approvals/{approval}/reject',  [ApprovalController::class, 'reject'])->middleware('permission:approvals.reject')->name('approvals.reject');
    Route::get('/approvals',  [ApprovalController::class, 'index'])->middleware('permission:approvals.view')->name('approvals.index');
    
    // Costos de Diesel
    Route::get('diesel_costs', [ServiceController::class, 'diesel_costs'])->middleware('permission:diesel_costs.view')->name('diesel_costs');
    Route::post('diesel_cost', [ServiceController::class, 'diesel_cost'])->middleware('permission:diesel_costs.create,diesel_costs.edit')->name('diesel_cost');
    
    // Comisiones
    Route::post('commission/service', [ServiceController::class, 'save_commission'])->middleware('permission:services.edit')->name('save_commission');
    
    // Dashboards
    Route::get('dashboard/services', [ServiceController::class, 'dashboard'])->middleware('permission:dashboard.view_services');
    Route::get('dashboard/services-details', [ServiceController::class, 'dashboard_services'])->middleware('permission:dashboard.view_services');
    Route::get('dashboard/maintenances', [MaintenanceController::class, 'dashboard'])->middleware('permission:dashboard.view_maintenances');
    Route::get('dashboard/maintenances-details', [MaintenanceController::class, 'dashboard_maintenances'])->middleware('permission:dashboard.view_maintenances');

    // Tipos de operador de servicio (refactor dinámico)
    Route::get('service-operator-types', [ServiceOperatorTypeController::class, 'index']);
    Route::get('substates/for-service/{id}', [ServiceOperatorTypeController::class, 'forService']);

    // Tarifas por tipo de operador
    Route::get('service-operator-type-rates', [ServiceOperatorTypeRateController::class, 'index']);
    Route::post('service-operator-type-rates', [ServiceOperatorTypeRateController::class, 'store']);
    Route::delete('service-operator-type-rates/{id}', [ServiceOperatorTypeRateController::class, 'destroy']);
});




