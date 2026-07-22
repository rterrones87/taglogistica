-- Insertar permiso roles.manage_permissions
INSERT INTO permissions (name, created_at, updated_at)
VALUES ('roles.manage_permissions', NOW(), NOW());

-- Obtener el ID del permiso recién creado y asignarlo al rol Administrador (role_id = 1)
INSERT INTO role_permission (role_id, permission_id, created_at, updated_at)
SELECT 1, id, NOW(), NOW()
FROM permissions
WHERE name = 'roles.manage_permissions'
LIMIT 1;

-- Verificar que se insertó correctamente
SELECT * FROM permissions WHERE name = 'roles.manage_permissions';
SELECT rp.*, r.name as role_name, p.name as permission_name 
FROM role_permission rp
JOIN roles r ON rp.role_id = r.id
JOIN permissions p ON rp.permission_id = p.id
WHERE p.name = 'roles.manage_permissions';
