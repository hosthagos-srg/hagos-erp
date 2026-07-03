#!/usr/bin/env bash
#
# deploy.sh — update aplikasi ERP HAGOS di server (jalankan via SSH di folder aplikasi).
# Pemakaian:  bash deploy.sh
#
# Catatan aset front-end:
#   - Bila server punya Node/npm  -> skrip ini build aset di server otomatis.
#   - Bila server TANPA Node      -> build di LOKAL (npm run build), lalu commit folder
#     public/build (hapus baris "/public/build" dari .gitignore) supaya ikut ter-pull.
#
set -e  # berhenti jika ada langkah yang gagal

echo ">> [1/7] Menarik kode terbaru dari Git..."
git pull origin main

echo ">> [2/7] Mode maintenance ON..."
php artisan down || true

echo ">> [3/7] Update dependency PHP (tanpa dev)..."
composer install --no-dev --optimize-autoloader

echo ">> [4/7] Migrasi database..."
php artisan migrate --force

echo ">> [5/7] Build aset front-end (jika Node tersedia)..."
if command -v npm >/dev/null 2>&1; then
    npm ci
    npm run build
else
    echo "   (npm tidak ada — lewati; aset diharapkan sudah di-commit dari lokal)"
fi

echo ">> [6/7] Refresh cache produksi..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo ">> [7/7] Mode maintenance OFF..."
php artisan up

echo ">> Selesai. Aplikasi sudah diperbarui."
