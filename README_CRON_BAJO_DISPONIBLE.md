# Sistema de Notificaciones Automáticas de Bajo Disponible

## 📋 Descripción

Este sistema permite revisar automáticamente todos los clientes principales que tengan un disponible por debajo del 10% de su cupo y enviarles notificaciones push para alertarlos. **Además, también notifica a todos los usuarios con perfil de super admin** para que estén informados sobre clientes con bajo disponible.

## 🚀 Archivos Creados

### **Sistema Principal (Diario - Clientes + Super Admins):**

#### 1. `cron_check_bajo_disponible.php`
Script principal que se ejecuta diariamente. Revisa todos los clientes y envía notificaciones tanto a clientes como a super admins.

#### 2. `test_cron_bajo_disponible.php`
Script de prueba para verificar que todo funciona correctamente antes de configurar el cron job principal.

#### 3. `app/Console/Commands/CheckBajoDisponibleCommand.php`
Comando de Laravel Artisan (alternativa al script independiente).

### **Sistema de Super Admins (Cada Hora - Solo Super Admins):**

#### 4. `cron_check_bajo_disponible_admin.php`
Script específico para super admins que se ejecuta cada hora. Envía **una sola notificación consolidada** con todos los clientes con bajo disponible.

#### 5. `test_cron_bajo_disponible_admin.php`
Script de prueba específico para el sistema de super admins.

#### 6. `app/Console/Commands/CheckBajoDisponibleAdminCommand.php`
Comando de Laravel Artisan para el sistema de super admins.

#### 7. `clientes_bajo_disponible_screen.dart` (Flutter)
Pantalla dedicada para mostrar los detalles de todos los clientes con bajo disponible, con filtros y navegación directa desde la notificación.

#### 8. `cron_config_examples.txt`
Ejemplos de configuración para diferentes horarios de ejecución.

## 🛠️ Configuración en cPanel

### **Sistema Principal (Diario):**

#### Paso 1: Probar el Sistema Principal
Antes de configurar el cron job, ejecuta la prueba:

```bash
php test_cron_bajo_disponible.php
```

#### Paso 2: Configurar Cron Job Principal
1. Ir a **Cron Jobs** en cPanel
2. Crear un nuevo cron job
3. Configurar la frecuencia (recomendado: Diario a las 8:00 AM)
4. Comando:
```bash
/usr/bin/php /home/tu_usuario/public_html/tucombustible/cron_check_bajo_disponible.php
```

### **Sistema de Super Admins (Cada Hora):**

#### Paso 3: Probar el Sistema de Super Admins
```bash
php test_cron_bajo_disponible_admin.php
```

#### Paso 4: Configurar Cron Job de Super Admins
1. Ir a **Cron Jobs** en cPanel
2. Crear un **segundo** cron job
3. Configurar la frecuencia: **Cada hora**
4. Comando:
```bash
/usr/bin/php /home/tu_usuario/public_html/tucombustible/cron_check_bajo_disponible_admin.php
```

### Paso 5: Verificar Funcionamiento
- Revisar los logs en `storage/logs/laravel.log`
- Probar con `--dry-run` para ver qué haría sin enviar notificaciones

## ⚙️ Opciones de Configuración

### **Frecuencias Recomendadas:**

#### **Sistema Principal (Clientes + Super Admins):**
- **Diario a las 8:00 AM**: Para alertar antes del horario laboral
- **Diario a las 6:00 PM**: Para alertar al final del día
- **Dos veces al día**: 8:00 AM y 6:00 PM
- **Lunes a Viernes**: Solo días laborales

#### **Sistema de Super Admins (Solo Super Admins):**
- **Cada hora**: Para monitoreo continuo
- **Cada 2 horas**: Para reducir frecuencia
- **Cada 4 horas**: Para monitoreo básico
- **Solo horario laboral**: 8:00 AM - 6:00 PM cada hora

### **Comandos Útiles:**

#### **Sistema Principal:**
```bash
# Ejecución normal
php cron_check_bajo_disponible.php

# Modo prueba (sin enviar notificaciones)
php cron_check_bajo_disponible.php --dry-run

# Comando Laravel Artisan
php artisan check:bajo-disponible

# Ver ayuda
php cron_check_bajo_disponible.php --help
```

#### **Sistema de Super Admins:**
```bash
# Ejecución normal
php cron_check_bajo_disponible_admin.php

# Modo prueba (sin enviar notificaciones)
php cron_check_bajo_disponible_admin.php --dry-run

# Comando Laravel Artisan
php artisan check:bajo-disponible-admin

# Ver ayuda
php cron_check_bajo_disponible_admin.php --help
```

## 📊 Funcionamiento

### Lógica del Sistema:
1. **Obtiene clientes principales** (parent = 0)
2. **Filtra clientes con disponible > 0** y cupo > 0
3. **Calcula porcentaje** de disponible vs cupo
4. **Identifica clientes con < 10%** de disponible
5. **Envía notificación push al cliente** identificado
6. **Envía notificación push a todos los super admins** sobre el cliente con bajo disponible
7. **Registra logs** de todas las acciones

### Criterios de Selección:
- ✅ Cliente principal (parent = 0)
- ✅ Disponible > 0 litros
- ✅ Cupo > 0 litros
- ✅ Porcentaje < 10%

### Notificaciones Enviadas:

#### **Al Cliente:**
- **Título**: "⚠️ Bajo Disponible"
- **Mensaje**: "Tu disponible actual es de X litros (Y% de tu cupo). Se recomienda tomar previsiones."
- **Datos**: Información detallada del cliente y porcentaje

#### **A Super Admins:**
- **Título**: "🚨 Alerta: Cliente con Bajo Disponible"
- **Mensaje**: "El cliente 'Nombre Cliente' tiene disponible bajo: X litros (Y% de su cupo de Z litros)."
- **Datos**: Información del cliente, disponible actual, cupo total y porcentaje

## 📝 Logs y Monitoreo

### Logs Automáticos:
- Todas las ejecuciones se registran en `storage/logs/laravel.log`
- Incluye: clientes revisados, notificaciones enviadas, errores
- Timestamp de cada ejecución

### Información Registrada:
```json
{
  "clientes_revisados": 25,
  "clientes_con_bajo_disponible": 3,
  "notificaciones_enviadas": 3,
  "notificaciones_super_admins": 3,
  "errores": 0,
  "fecha_ejecucion": "2025-09-14 08:00:00",
  "modo_dry_run": false
}
```

## 🔧 Solución de Problemas

### Error: "No se puede conectar a la base de datos"
- Verificar configuración de base de datos en `.env`
- Comprobar que el servidor MySQL esté funcionando

### Error: "FCM Project ID no configurado"
- Verificar configuración en `config/services.php`
- Comprobar archivo de credenciales en `storage/`

### Error: "Archivo de credenciales no encontrado"
- Verificar que el archivo JSON de Firebase esté en `storage/`
- Comprobar permisos del archivo

### No se envían notificaciones:
- Verificar que los clientes tengan tokens FCM válidos
- Comprobar configuración de Firebase
- Revisar logs para errores específicos

## 🧪 Pruebas

### Prueba Básica:
```bash
php test_cron_bajo_disponible.php
```

### Prueba con Dry-Run:
```bash
php cron_check_bajo_disponible.php --dry-run
```

### Prueba Manual:
1. Crear un cliente de prueba con bajo disponible
2. Ejecutar el script
3. Verificar que se envíe la notificación

## 📱 Notificaciones

### Clientes que Reciben Notificaciones:
- Solo clientes principales (parent = 0)
- Con disponible < 10% de su cupo
- Con token FCM válido

### Super Admins que Reciben Notificaciones:
- Usuarios con perfil de super admin (id_perfil = 1)
- Con token FCM válido
- Reciben alerta sobre cualquier cliente con bajo disponible

### Clientes que NO Reciben Notificaciones:
- Sucursales (parent > 0)
- Clientes sin disponible
- Clientes sin cupo definido
- Clientes sin token FCM

### Super Admins que NO Reciben Notificaciones:
- Super admins sin token FCM
- Super admins con perfil diferente a 1

## 🔒 Seguridad

- El script solo lee datos de clientes
- No modifica información de la base de datos
- Logs detallados para auditoría
- Modo dry-run para pruebas seguras

## 🆕 Nuevas Funcionalidades

### **Sistema Dual de Notificaciones**

#### **Sistema Principal (Diario):**
- **Frecuencia**: Diario (recomendado 8:00 AM)
- **Destinatarios**: Clientes + Super Admins
- **Propósito**: Notificación completa a todos los involucrados

#### **Sistema de Super Admins (Cada Hora):**
- **Frecuencia**: Cada hora
- **Destinatarios**: Solo Super Admins (id_perfil = 1)
- **Propósito**: Monitoreo continuo para super admins

### **Configuración en cPanel - Ejemplos:**

#### **Cron Job 1 - Sistema Principal (Diario):**
```
Frecuencia: 0 8 * * *
Comando: /usr/bin/php /home/tu_usuario/public_html/tucombustible/cron_check_bajo_disponible.php
```

#### **Cron Job 2 - Sistema de Super Admins (Cada Hora):**
```
Frecuencia: 0 * * * *
Comando: /usr/bin/php /home/tu_usuario/public_html/tucombustible/cron_check_bajo_disponible_admin.php
```

#### **Opciones de Frecuencia en cPanel:**
- `0 * * * *` = Cada hora
- `0 */2 * * *` = Cada 2 horas
- `0 */4 * * *` = Cada 4 horas
- `0 8-18 * * *` = Cada hora de 8 AM a 6 PM
- `0 8 * * *` = Diario a las 8 AM
- `0 8,18 * * *` = Diario a las 8 AM y 6 PM

### **Beneficios del Sistema Dual:**
- **Monitoreo continuo**: Super admins reciben alertas cada hora
- **Notificación completa**: Clientes reciben notificación diaria
- **Flexibilidad**: Diferentes frecuencias según necesidades
- **Redundancia**: Si un sistema falla, el otro continúa funcionando

### **🆕 Nueva Funcionalidad: Notificación Consolidada**

#### **¿Qué es?**
El sistema de super admins ahora envía **una sola notificación consolidada** en lugar de múltiples notificaciones individuales cuando hay varios clientes con bajo disponible.

#### **Beneficios:**
- **Menos spam**: Una sola notificación en lugar de múltiples
- **Mejor UX**: Modal con opción "Ver Detalles" que navega a pantalla dedicada
- **Información completa**: Todos los clientes con bajo disponible en una vista
- **Filtros avanzados**: Por estado (Crítico, Bajo, Normal)
- **Acciones rápidas**: Contactar cliente, ver historial

#### **Flujo de Usuario:**
1. **Notificación**: "🚨 Alerta" (título corto)
2. **Modal**: "Múltiples Clientes con Bajo Disponible\n5 clientes tienen disponible bajo. Toca para ver detalles."
3. **Botón**: "Ver Detalles" (en lugar de "Entendido")
4. **Pantalla**: Navegación directa a lista completa con filtros y detalles
5. **Acciones**: Contactar, ver historial, filtrar por estado

### **Ejemplos de Notificaciones:**

#### **Notificación Consolidada (Nueva):**
```
🚨 Alerta
Múltiples Clientes con Bajo Disponible
5 clientes tienen disponible bajo. Toca para ver detalles.
```

#### **Notificación Individual (Sistema Principal):**
```
🚨 Alerta: Cliente con Bajo Disponible
El cliente 'Empresa ABC' tiene disponible bajo: 150.5 litros (8.3% de su cupo de 1800 litros).
```

### **Logs Mejorados**
Los logs ahora incluyen información separada sobre:
- Notificaciones enviadas a clientes
- Notificaciones enviadas a super admins
- Errores específicos para cada tipo de notificación
- Identificación del tipo de cron job (principal/admin_hourly)

## 📞 Soporte

Si encuentras problemas:
1. Revisar logs en `storage/logs/laravel.log`
2. Ejecutar `test_cron_bajo_disponible.php` para diagnóstico
3. Verificar configuración de Firebase y base de datos
4. Probar con `--dry-run` antes de ejecución real
5. Verificar que los super admins tengan tokens FCM válidos
