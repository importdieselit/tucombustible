<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eliminación de Datos | Impormotor, C.A.</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; margin: 0; padding: 0; background-color: #f4f4f4; color: #333; }
        .container { max-width: 900px; margin: 40px auto; padding: 30px; background-color: #fff; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); border-radius: 8px; }
        h1 { color: #1a237e; border-bottom: 3px solid #0d47a1; padding-bottom: 15px; margin-bottom: 25px; text-align: center; }
        h2 { color: #3949ab; margin-top: 30px; border-left: 5px solid #3f51b5; padding-left: 10px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], input[type="email"], textarea, select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { background-color: #e74c3c; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; margin-top: 10px; }
        button:hover { background-color: #c0392b; }
        .alert-success { background-color: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin-bottom: 20px; }
        .alert-error { background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 20px; }
        .warning { color: #c0392b; font-weight: bold; border: 1px solid #e74c3c; padding: 10px; background-color: #fceae9; border-radius: 4px; }
        .policy-section { border-top: 1px dashed #ccc; padding-top: 20px; margin-top: 20px; }
    </style>
</head>
<body>

<div class="container">
    <h1>Solicitud de Eliminación de Datos</h1>

    @if(session('success'))
        <div class="alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert-error">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert-error">Por favor, corrija los siguientes errores de validación.</div>
    @endif

    <div class="policy-section">
        <h2>Política de Eliminación de Cuentas y Datos</h2>
        <p>Esta política complementa la Política de Privacidad principal de <strong>Impormotor, C.A.</strong> y se aplica a la aplicación <strong>[Nombre de tu Aplicación en Play Store]</strong>, desarrollada por <strong>Impormotor, C.A.</strong> (el "Desarrollador").</p>

        <h3>1. Datos Sujetos a Eliminación</h3>
        <p>La eliminación de la cuenta conlleva la eliminación de los siguientes datos personales y sensibles que la aplicación haya recopilado:</p>
        <ul>
            <li>**Datos de Identificación de Contacto:** Identificadores únicos de usuario (ej. ID de Telegram, correo electrónico), nombre de usuario, e ID de cuenta en el sistema de Impormotor.</li>
            <li>**Datos Operacionales Vinculados:** Cualquier configuración o dato de contexto almacenado en el perfil de la aplicación/bot.</li>
        </ul>

        <h3>2. Datos Conservados y Períodos de Retención</h3>
        <p class="warning">Ciertos datos deben ser retenidos por la Compañía para propósitos de cumplimiento regulatorio, fiscal, legal y auditoría.</p>
        <p>Los tipos de datos que se conservan son:</p>
        <ul>
            <li>**Datos Transaccionales/Operacionales:** Registros históricos de transacciones o comandos que afecten directamente el inventario, cupos o facturación. **Periodo de Retención:** 7 años o el periodo exigido por la legislación vigente.</li>
        </ul>
        
        <hr>
    </div>

    <div class="form-section">
        <h2>Inicie su Solicitud</h2>
        <p>Complete el formulario a continuación para registrar formalmente su solicitud de eliminación de datos. La solicitud será procesada tras la verificación administrativa.</p>

        <form method="POST" action="{{ route('data.deletion.submit') }}">
            @csrf <div class="form-group">
                <label for="type">Tipo de Identificador:</label>
                <select id="type" name="type" required>
                    <option value="">-- Seleccione el Tipo --</option>
                    <option value="telegram">ID de Usuario </option>
                    <option value="email">Correo Electrónico Registrado</option>
                </select>
                @error('type') <span class="alert-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label for="identifier">Su Identificador (ID o Email):</label>
                <input type="text" id="identifier" name="identifier" value="{{ old('identifier') }}" placeholder="Ej: nombreusuario o su.email@ejemplo.com" required>
                @error('identifier') <span class="alert-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label for="reason">Razón de la Solicitud (Opcional):</label>
                <textarea id="reason" name="reason" rows="4" placeholder="Indique la razón para la eliminación de la cuenta.">{{ old('reason') }}</textarea>
                @error('reason') <span class="alert-error">{{ $message }}</span> @enderror
            </div>

            <button type="submit">Enviar Solicitud de Eliminación</button>
        </form>
    </div>
</div>

</body>
</html>