<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Políticas de Privacidad - TAG Logística</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-800">
    <div class="max-w-4xl mx-auto px-4 py-8 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Políticas de Privacidad</h1>
            <p class="text-sm text-gray-600">Última actualización: {{ date('d/m/Y') }}</p>
        </div>

        <!-- Content -->
        <div class="bg-white rounded-lg shadow-sm p-6 space-y-6">
            <!-- Introducción -->
            <section>
                <h2 class="text-2xl font-semibold text-gray-900 mb-3">1. Introducción</h2>
                <p class="text-gray-700 leading-relaxed">
                    TAG Logística es un sistema de gestión de operaciones logísticas de uso interno empresarial. 
                    Esta política de privacidad describe cómo recopilamos, utilizamos y protegemos la información 
                    personal de nuestros usuarios (empleados y operadores) dentro de nuestra plataforma web y 
                    aplicación móvil.
                </p>
            </section>

            <!-- Información que Recopilamos -->
            <section>
                <h2 class="text-2xl font-semibold text-gray-900 mb-3">2. Información que Recopilamos</h2>
                <p class="text-gray-700 leading-relaxed mb-3">
                    Para el correcto funcionamiento de la plataforma, recopilamos la siguiente información:
                </p>
                
                <div class="space-y-3 ml-4">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900">Datos de Identificación</h3>
                        <ul class="list-disc ml-6 text-gray-700 space-y-1">
                            <li>Nombre completo</li>
                            <li>Correo electrónico corporativo</li>
                            <li>Fotografía de perfil (opcional)</li>
                        </ul>
                    </div>

                    <div>
                        <h3 class="text-lg font-medium text-gray-900">Datos de Autenticación y Seguridad</h3>
                        <ul class="list-disc ml-6 text-gray-700 space-y-1">
                            <li>Contraseña (encriptada mediante algoritmos seguros)</li>
                            <li>Tokens de sesión (Laravel Sanctum)</li>
                            <li>Estado de cuenta (activo/inactivo)</li>
                        </ul>
                    </div>

                    <div>
                        <h3 class="text-lg font-medium text-gray-900">Datos del Rol Empresarial</h3>
                        <ul class="list-disc ml-6 text-gray-700 space-y-1">
                            <li>Rol asignado dentro de la organización</li>
                            <li>Permisos y accesos específicos</li>
                        </ul>
                    </div>

                    <div>
                        <h3 class="text-lg font-medium text-gray-900">Datos Técnicos</h3>
                        <ul class="list-disc ml-6 text-gray-700 space-y-1">
                            <li>Tokens FCM (Firebase Cloud Messaging) para notificaciones push</li>
                            <li>Información de dispositivo para funcionalidad de la aplicación móvil</li>
                        </ul>
                    </div>

                    <div>
                        <h3 class="text-lg font-medium text-gray-900">Datos Operacionales</h3>
                        <ul class="list-disc ml-6 text-gray-700 space-y-1">
                            <li>Servicios logísticos asignados (importación/exportación)</li>
                            <li>Registros de mantenimiento de unidades</li>
                            <li>Ubicaciones de entrega y recolección</li>
                            <li>Gestión de contenedores y carga</li>
                            <li>Gastos y costos asociados a operaciones</li>
                            <li>Evidencias fotográficas de servicios</li>
                        </ul>
                    </div>
                </div>
            </section>

            <!-- Uso de la Información -->
            <section>
                <h2 class="text-2xl font-semibold text-gray-900 mb-3">3. Uso de la Información</h2>
                <p class="text-gray-700 leading-relaxed mb-3">
                    Utilizamos la información recopilada exclusivamente para los siguientes propósitos internos:
                </p>
                <ul class="list-disc ml-6 text-gray-700 space-y-2">
                    <li><strong>Autenticación y Control de Acceso:</strong> Verificar la identidad de los usuarios y gestionar el acceso a la plataforma.</li>
                    <li><strong>Coordinación Operativa:</strong> Asignar servicios logísticos, rutas, unidades y operadores.</li>
                    <li><strong>Comunicación Interna:</strong> Enviar notificaciones push sobre servicios asignados, actualizaciones de estado y alertas operativas.</li>
                    <li><strong>Gestión de Recursos:</strong> Administrar inventarios, mantenimientos de unidades y gastos operacionales.</li>
                    <li><strong>Seguimiento y Trazabilidad:</strong> Rastrear el progreso de servicios, entregas y movimientos de carga.</li>
                    <li><strong>Mejora de Procesos:</strong> Analizar datos operacionales para optimizar rutas y procesos logísticos.</li>
                    <li><strong>Sistema de Aprobaciones:</strong> Gestionar autorizaciones de gastos, diesel y solicitudes de mantenimiento.</li>
                </ul>
            </section>

            <!-- Seguridad de los Datos -->
            <section>
                <h2 class="text-2xl font-semibold text-gray-900 mb-3">4. Seguridad de los Datos</h2>
                <p class="text-gray-700 leading-relaxed mb-3">
                    Implementamos medidas de seguridad técnicas y organizativas para proteger su información:
                </p>
                <ul class="list-disc ml-6 text-gray-700 space-y-2">
                    <li>Las contraseñas se almacenan mediante algoritmos de hash seguros (bcrypt).</li>
                    <li>Utilizamos tokens de autenticación encriptados (Laravel Sanctum).</li>
                    <li>Comunicación segura mediante protocolo HTTPS.</li>
                    <li>Acceso restringido basado en roles y permisos.</li>
                    <li>Base de datos protegida con credenciales seguras.</li>
                    <li>Respaldos regulares de información.</li>
                </ul>
            </section>

            <!-- Acceso y Almacenamiento -->
            <section>
                <h2 class="text-2xl font-semibold text-gray-900 mb-3">5. Acceso y Almacenamiento</h2>
                <p class="text-gray-700 leading-relaxed">
                    Su información está almacenada en servidores seguros y solo es accesible por personal autorizado 
                    de TAG Logística que requiere acceso para cumplir con sus funciones operativas. Los datos se 
                    conservan mientras su cuenta permanezca activa o según sea necesario para cumplir con obligaciones 
                    legales y operativas.
                </p>
            </section>

            <!-- Compartición de Información -->
            <section>
                <h2 class="text-2xl font-semibold text-gray-900 mb-3">6. Compartición de Información</h2>
                <p class="text-gray-700 leading-relaxed">
                    <strong>No compartimos, vendemos ni divulgamos su información personal a terceros.</strong> 
                    Toda la información recopilada se utiliza exclusivamente para operaciones internas de TAG Logística. 
                    La única excepción sería en caso de requerimientos legales por autoridades competentes.
                </p>
            </section>

            <!-- Servicios de Terceros -->
            <section>
                <h2 class="text-2xl font-semibold text-gray-900 mb-3">7. Servicios de Terceros</h2>
                <p class="text-gray-700 leading-relaxed mb-3">
                    Utilizamos los siguientes servicios de terceros para el funcionamiento de la plataforma:
                </p>
                <ul class="list-disc ml-6 text-gray-700 space-y-2">
                    <li><strong>Firebase Cloud Messaging (FCM):</strong> Para el envío de notificaciones push a dispositivos móviles.</li>
                    <li><strong>Servicios de Hosting:</strong> Para el almacenamiento y procesamiento de datos.</li>
                </ul>
                <p class="text-gray-700 leading-relaxed mt-3">
                    Estos servicios están sujetos a sus propias políticas de privacidad y cumplen con estándares 
                    de seguridad reconocidos internacionalmente.
                </p>
            </section>

            <!-- Derechos del Usuario -->
            <section>
                <h2 class="text-2xl font-semibold text-gray-900 mb-3">8. Derechos del Usuario</h2>
                <p class="text-gray-700 leading-relaxed mb-3">
                    Como usuario de la plataforma, usted tiene derecho a:
                </p>
                <ul class="list-disc ml-6 text-gray-700 space-y-2">
                    <li><strong>Acceso:</strong> Solicitar información sobre los datos personales que tenemos sobre usted.</li>
                    <li><strong>Rectificación:</strong> Solicitar la corrección de datos inexactos o incompletos.</li>
                    <li><strong>Actualización:</strong> Mantener actualizada su información de perfil.</li>
                    <li><strong>Privacidad:</strong> Sus datos operacionales solo son visibles para usuarios con permisos correspondientes.</li>
                </ul>
                <p class="text-gray-700 leading-relaxed mt-3">
                    Para ejercer cualquiera de estos derechos, contacte al administrador del sistema o al departamento 
                    de recursos humanos de TAG Logística.
                </p>
            </section>

            <!-- Cambios a esta Política -->
            <section>
                <h2 class="text-2xl font-semibold text-gray-900 mb-3">9. Cambios a esta Política</h2>
                <p class="text-gray-700 leading-relaxed">
                    TAG Logística se reserva el derecho de modificar esta política de privacidad en cualquier momento. 
                    Los cambios serán notificados a través de la plataforma y entrarán en vigor inmediatamente después 
                    de su publicación. La fecha de última actualización se indica al inicio de este documento.
                </p>
            </section>

            <!-- Contacto -->
            <section>
                <h2 class="text-2xl font-semibold text-gray-900 mb-3">10. Contacto</h2>
                <p class="text-gray-700 leading-relaxed mb-3">
                    Si tiene preguntas, comentarios o inquietudes sobre esta política de privacidad o sobre el 
                    manejo de su información personal, puede contactarnos a través de:
                </p>
                <div class="bg-gray-50 rounded-lg p-4 ml-4">
                    <p class="text-gray-700"><strong>TAG Logística</strong></p>
                    <p class="text-gray-700">Departamento de Administración de Sistemas</p>
                    <p class="text-gray-700">O a través del administrador del sistema</p>
                </div>
            </section>

            <!-- Consentimiento -->
            <section class="bg-blue-50 rounded-lg p-4 border-l-4 border-blue-500">
                <h2 class="text-xl font-semibold text-blue-900 mb-2">Consentimiento</h2>
                <p class="text-blue-800 leading-relaxed">
                    Al utilizar la plataforma y aplicación móvil de TAG Logística, usted reconoce haber leído 
                    y comprendido esta política de privacidad y acepta el procesamiento de su información personal 
                    según lo descrito en este documento.
                </p>
            </section>
        </div>

        <!-- Footer -->
        <div class="text-center mt-8 text-sm text-gray-600">
            <p>&copy; {{ date('Y') }} TAG Logística. Todos los derechos reservados.</p>
        </div>
    </div>
</body>
</html>
