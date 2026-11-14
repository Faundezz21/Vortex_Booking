#!/bin/sh
# Scheduler de Laravel (libera no-shows cada minuto).
# Lo ideal es correrlo como un servicio aparte con el comando `php artisan schedule:work`.
# En planes gratuitos con un solo servicio, RUN_SCHEDULER=true lo inicia dentro de este contenedor.
if [ "${RUN_SCHEDULER:-false}" = "true" ]; then
    echo "[scheduler] Iniciando php artisan schedule:work en segundo plano"
    cd /var/www/html
    nohup php artisan schedule:work > /proc/1/fd/1 2>&1 &
fi
