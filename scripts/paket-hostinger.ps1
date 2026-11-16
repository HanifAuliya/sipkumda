<#
    Membuat paket SIFKUMDA siap unggah untuk hosting bersama (Hostinger Web Hosting) yang tidak
    punya Node.js: vendor tanpa paket pengembangan, aset hasil build, dan font Bookman sudah di dalamnya.

    Isi paket diambil dari commit terakhir (git archive), jadi perubahan yang belum di-commit tidak ikut.
    .env tidak ikut; buat di server dari .env.example (lihat docs/DEPLOY.md bagian Hostinger).

        powershell -ExecutionPolicy Bypass -File scripts\paket-hostinger.ps1
        powershell -ExecutionPolicy Bypass -File scripts\paket-hostinger.ps1 -Ref v2/matriks

    Hasil: dist\sifkumda-<commit>.zip
#>
param(
    [string]$Ref = 'HEAD',
    [string]$Keluar = 'dist'
)

$ErrorActionPreference = 'Stop'
$akar = Resolve-Path (Join-Path $PSScriptRoot '..')
Set-Location $akar

function Jalankan([string]$perintah) {
    Write-Host "> $perintah" -ForegroundColor DarkGray
    cmd /c $perintah
    if ($LASTEXITCODE -ne 0) { throw "Gagal: $perintah" }
}

if (git status --porcelain --untracked-files=no) {
    Write-Warning 'Ada perubahan yang belum di-commit; perubahan itu TIDAK ikut dalam paket.'
}

$commit = (git rev-parse --short $Ref).Trim()
$kerja = Join-Path $env:TEMP "sifkumda-paket-$commit"
if (Test-Path $kerja) { Remove-Item $kerja -Recurse -Force }
New-Item -ItemType Directory -Path $kerja | Out-Null

Write-Host "Menyiapkan $Ref ($commit) di $kerja" -ForegroundColor Cyan
$arsip = "$kerja.zip"
Jalankan "git archive --format=zip -o `"$arsip`" $Ref"
Jalankan "tar -xf `"$arsip`" -C `"$kerja`""
Remove-Item $arsip

Push-Location $kerja
try {
    Jalankan 'composer install --no-dev --optimize-autoloader --no-interaction --no-progress'
    Jalankan 'npm ci --no-audit --no-fund'
    Jalankan 'npm run build'
    Remove-Item node_modules -Recurse -Force
} finally {
    Pop-Location
}

# Font berlisensi tidak ada di repo; salin dari komputer ini bila tersedia.
$font = Get-ChildItem (Join-Path $akar 'resources\fonts') -Filter '*.TTF' -ErrorAction SilentlyContinue
if ($font) {
    $tujuan = New-Item -ItemType Directory -Path (Join-Path $kerja 'resources\fonts') -Force
    $font | Copy-Item -Destination $tujuan.FullName
} else {
    Write-Warning 'Font Bookman (resources\fonts\BOOKOS*.TTF) tidak ditemukan; PDF akan memakai Times New Roman.'
}

# Tidak dibutuhkan di server.
foreach ($buang in 'tests', '.github', 'docs\panduan\*.psd') {
    $jalur = Join-Path $kerja $buang
    if (Test-Path $jalur) { Remove-Item $jalur -Recurse -Force }
}

New-Item -ItemType Directory -Path (Join-Path $akar $Keluar) -Force | Out-Null
$zip = Join-Path (Resolve-Path (Join-Path $akar $Keluar)) "sifkumda-$commit.zip"
if (Test-Path $zip) { Remove-Item $zip }
# tar bawaan Windows menulis ZIP dengan pemisah "/" sehingga terbaca benar di Linux. Isinya disebut
# satu per satu (bukan ".") supaya nama di dalam ZIP tidak diawali "./".
$isi = (Get-ChildItem $kerja -Force | ForEach-Object { "`"$($_.Name)`"" }) -join ' '
Jalankan "tar -a -c -f `"$zip`" -C `"$kerja`" $isi"
Remove-Item $kerja -Recurse -Force

$ukuran = [math]::Round((Get-Item $zip).Length / 1MB, 1)
Write-Host "Selesai: $zip ($ukuran MB)" -ForegroundColor Green
