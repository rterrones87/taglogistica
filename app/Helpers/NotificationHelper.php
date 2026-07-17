<?php

namespace App\Helpers;

use App\Services\FcmService;
use App\Models\User;
use Exception;

class NotificationHelper
{
    /**
     * Envía notificación push a todos los usuarios Administrador y Dirección
     * 
     * Ejemplo de uso:
     * 
     * use App\Helpers\NotificationHelper;
     * 
     * $result = NotificationHelper::notifyAdmins(
     *     'Nuevo Servicio',
     *     'Se ha creado un nuevo servicio TAG250311001',
     *     ['service_id' => 123, 'folio' => 'TAG250311001']
     * );
     * 
     * // Verificar resultado
     * if ($result['success'] > 0) {
     *     Log::info("Notificación enviada a {$result['success']} usuarios");
     * }
     * 
     * if ($result['failed'] > 0) {
     *     Log::warning("Falló envío a {$result['failed']} usuarios", $result['errors']);
     * }
     * 
     * @param string $title Título de la notificación
     * @param string $body Mensaje de la notificación
     * @param array $data Data adicional (opcional)
     * @return array Estadísticas del envío ['success' => int, 'failed' => int, 'total' => int, 'errors' => array]
     */
    public static function notifyAdmins(string $title, string $body, array $data = []): array
    {
        $fcmService = app(FcmService::class);
        
        // Consultar usuarios Administrador (1) y Dirección (7)
        $users = User::where('zombie', 0)
            ->where('active', 1)
            ->whereIn('role_id', [1, 7])
            ->whereNotNull('fcm_token')
            ->get();
        
        $success = 0;
        $failed = 0;
        $errors = [];
        
        foreach ($users as $user) {
            try {
                $fcmService->sendToToken($user->fcm_token, $title, $body, $data);
                $success++;
            } catch (Exception $e) {
                $failed++;
                $errors[] = [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return [
            'success' => $success,
            'failed' => $failed,
            'total' => $users->count(),
            'errors' => $errors
        ];
    }
}
