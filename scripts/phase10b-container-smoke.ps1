param([switch]$KeepRunning)

$ErrorActionPreference = 'Stop'
$repository = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$composeFile = Join-Path $repository 'compose.phase10b.yaml'

if (-not (Get-Command docker -ErrorAction SilentlyContinue)) {
    throw 'Docker is not installed. Use the approved remote GitHub Actions build path.'
}

docker info *> $null
if ($LASTEXITCODE -ne 0) {
    throw 'The Docker engine is not running.'
}

Push-Location $repository
try {
    docker compose -f $composeFile build --pull
    if ($LASTEXITCODE -ne 0) { throw 'A Phase 10B container image failed to build.' }

    docker compose -f $composeFile up -d --wait --wait-timeout 180
    if ($LASTEXITCODE -ne 0) { throw 'The Phase 10B smoke stack did not become healthy.' }

    $laravel = Invoke-WebRequest 'http://127.0.0.1:8000/up' -UseBasicParsing -TimeoutSec 15
    $fastApiReady = Invoke-RestMethod 'http://127.0.0.1:9000/ready' -TimeoutSec 15
    if ($laravel.StatusCode -ne 200 -or $fastApiReady.ready -ne $true) {
        throw 'The Phase 10B container smoke contract failed.'
    }
}
finally {
    if (-not $KeepRunning) {
        docker compose -f $composeFile down --remove-orphans *> $null
    }
    Pop-Location
}
