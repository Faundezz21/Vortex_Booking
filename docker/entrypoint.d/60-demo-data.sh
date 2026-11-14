#!/bin/sh
# Carga datos de demostración después de las migraciones automáticas de la imagen.
#  - Primera vez (BD vacía): siempre carga los datos.
#  - DEMO_RESEED_ON_BOOT=true: reinicia la BD con datos frescos en cada despliegue,
#    útil para que las reservas de ejemplo siempre caigan en los próximos días.
set -e
cd /var/www/html

if [ "${DEMO_RESEED_ON_BOOT:-false}" = "true" ]; then
    echo "[demo] Recargando datos de demostración..."
    php artisan demo:seed --fresh
else
    php artisan demo:seed --if-empty
fi
