[CmdletBinding()]
param(
    [Parameter(Mandatory = $true)]
    [string] $SourcePath,

    [string] $OgrInfoPath = 'C:\Program Files\QGIS 4.0.2\bin\ogrinfo.exe',
    [string] $Ogr2OgrPath = 'C:\Program Files\QGIS 4.0.2\bin\ogr2ogr.exe'
)

$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest

$source = (Resolve-Path -LiteralPath $SourcePath).Path
if ([IO.Path]::GetExtension($source) -ne '.gpkg') {
    throw 'The MPDO source must be a GeoPackage (.gpkg).'
}
if (-not (Test-Path -LiteralPath $OgrInfoPath -PathType Leaf)) {
    throw "ogrinfo was not found at $OgrInfoPath"
}
if (-not (Test-Path -LiteralPath $Ogr2OgrPath -PathType Leaf)) {
    throw "ogr2ogr was not found at $Ogr2OgrPath"
}

$repositoryRoot = (Resolve-Path -LiteralPath (Join-Path $PSScriptRoot '..')).Path
$temporaryDirectory = Join-Path ([IO.Path]::GetTempPath()) ("civiclear-mpdo-" + [guid]::NewGuid())
$barangayOutput = Join-Path $repositoryRoot 'public\gis\santa_cruz_barangays.geojson'
$municipalOutput = Join-Path $repositoryRoot 'public\gis\santa_cruz_municipality.geojson'
$acceptanceOutput = Join-Path $repositoryRoot 'tests\Fixtures\mpdo_barangay_acceptance_points.geojson'

$expectedNames = @(
    'Alipit', 'Bagumbayan', 'Bubukal', 'Calios', 'Duhat', 'Gatid', 'Jasaan',
    'Labuin', 'Malinao', 'Oogong', 'Pagsawitan', 'Palasan', 'Patimbao',
    'Poblacion I', 'Poblacion II', 'Poblacion III', 'Poblacion IV', 'Poblacion V',
    'San Jose', 'San Juan', 'San Pablo Norte', 'San Pablo Sur', 'Santisima Cruz',
    'Santo Angel Central', 'Santo Angel Norte', 'Santo Angel Sur'
)

New-Item -ItemType Directory -Path $temporaryDirectory | Out-Null

try {
    $validation = & $OgrInfoPath -q $source -dialect SQLite -sql 'SELECT COUNT(*) AS total_count, SUM(ST_IsValid(geom)) AS valid_count, SUM(ST_IsEmpty(geom)) AS empty_count, COUNT(DISTINCT name) AS unique_name_count FROM [barangay-boundary]' 2>&1 | Out-String
    if ($LASTEXITCODE -ne 0) {
        throw "Unable to validate the barangay-boundary layer.`n$validation"
    }

    foreach ($requiredResult in @(
        'total_count \(Integer\) = 26',
        'valid_count \(Integer\) = 26',
        'empty_count \(Integer\) = 0',
        'unique_name_count \(Integer\) = 26'
    )) {
        if ($validation -notmatch $requiredResult) {
            throw "MPDO geometry validation failed. Expected: $requiredResult`n$validation"
        }
    }

    $temporaryBarangays = Join-Path $temporaryDirectory 'barangays.geojson'
    $temporaryMunicipality = Join-Path $temporaryDirectory 'municipality.geojson'
    $temporaryAcceptance = Join-Path $temporaryDirectory 'acceptance.geojson'

    & $Ogr2OgrPath -f GeoJSON -t_srs EPSG:4326 -lco RFC7946=YES -lco COORDINATE_PRECISION=7 -select PSGC,name,area $temporaryBarangays $source 'barangay-boundary'
    if ($LASTEXITCODE -ne 0) { throw 'Barangay conversion failed.' }

    & $Ogr2OgrPath -f GeoJSON -t_srs EPSG:4326 -lco RFC7946=YES -lco COORDINATE_PRECISION=7 -select PSGC,name,area $temporaryMunicipality $source 'municipal-boundary'
    if ($LASTEXITCODE -ne 0) { throw 'Municipal conversion failed.' }

    & $Ogr2OgrPath -f GeoJSON -t_srs EPSG:4326 -lco RFC7946=YES -lco COORDINATE_PRECISION=7 -dialect SQLite -sql 'SELECT name, PSGC, ST_PointOnSurface(geom) AS geometry FROM [barangay-boundary]' $temporaryAcceptance $source
    if ($LASTEXITCODE -ne 0) { throw 'Acceptance-point conversion failed.' }

    $converted = Get-Content -LiteralPath $temporaryBarangays -Raw | ConvertFrom-Json
    $actualNames = @($converted.features | ForEach-Object { $_.properties.name } | Sort-Object)
    $expectedSorted = @($expectedNames | Sort-Object)

    if ($converted.type -ne 'FeatureCollection' -or $converted.features.Count -ne 26) {
        throw 'Converted barangay GeoJSON must contain exactly 26 features.'
    }
    if (Compare-Object -ReferenceObject $expectedSorted -DifferenceObject $actualNames) {
        throw 'The MPDO barangay names do not match the CIVICLEAR barangay list.'
    }
    if (@($converted.features | Where-Object { $_.geometry.type -ne 'MultiPolygon' }).Count -gt 0) {
        throw 'Every barangay geometry must be a MultiPolygon.'
    }

    New-Item -ItemType Directory -Force -Path (Split-Path $barangayOutput), (Split-Path $acceptanceOutput) | Out-Null
    Move-Item -LiteralPath $temporaryBarangays -Destination $barangayOutput -Force
    Move-Item -LiteralPath $temporaryMunicipality -Destination $municipalOutput -Force
    Move-Item -LiteralPath $temporaryAcceptance -Destination $acceptanceOutput -Force

    $sourceHash = (Get-FileHash -LiteralPath $source -Algorithm SHA256).Hash
    Write-Host "Imported 26 validated MPDO barangays. Source SHA-256: $sourceHash"
    Write-Host "GeoJSON: $barangayOutput"
    Write-Host "Municipality: $municipalOutput"
    Write-Host "Acceptance points: $acceptanceOutput"
}
finally {
    if (Test-Path -LiteralPath $temporaryDirectory) {
        Remove-Item -LiteralPath $temporaryDirectory -Recurse -Force
    }
}
