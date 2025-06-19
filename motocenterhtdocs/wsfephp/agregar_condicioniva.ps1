# Script para agregar CondicionIVAReceptor a todos los archivos PHP que usan $regfe

$folder = "."  # Cambia si tu carpeta wsfephp está en otro lugar
$archivos = Get-ChildItem -Path $folder -Filter "*.php" -Recurse

foreach ($archivo in $archivos) {
    $contenido = Get-Content $archivo.FullName
    $nuevoContenido = @()
    $agregado = $false

    for ($i = 0; $i -lt $contenido.Count; $i++) {
        $linea = $contenido[$i]
        $nuevoContenido += $linea

        # Busca la última asignación al array $regfe
        if ($linea -match "^\s*\$regfe\[[^\]]+\]\s*=") {
            # Mira si la próxima línea ya tiene CondicionIVAReceptor
            if ($i+1 -lt $contenido.Count -and $contenido[$i+1] -match "CondicionIVAReceptor") {
                continue
            }
            # Si no se agregó aún, agrégalo aquí
            if (-not $agregado) {
                $nuevoContenido += "    \$regfe['CondicionIVAReceptor'] = 1; # 1=Responsable Inscripto, 4=Consumidor Final, etc."
                $agregado = $true
            }
        }
    }

    if ($agregado) {
        Set-Content $archivo.FullName $nuevoContenido
        Write-Host "Modificado: $($archivo.FullName)"
    }
}
