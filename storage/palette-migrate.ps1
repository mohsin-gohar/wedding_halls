# VenueCloud 4-Color Palette Migration
# Palette: Obsidian #171513 | Champagne #C6A15B | Ivory #F8F5EF | White #FFFFFF (+ tonal shades only)

$root = 'F:\laravelproject\Wedding_hall'

$hexMap = @{
    '0B0908'='171513'; '141210'='171513'; '1C1816'='171513'; '0E0C0A'='171513'
    '14110F'='171513'; '1A1815'='171513'; '1A1614'='171513'; '1F1B18'='171513'
    '26211E'='171513'; '1C1917'='171513'; '0F1E36'='171513'; '1A202C'='171513'
    'D4AF37'='C6A15B'; '9A7B1C'='8F7239'; 'E2BE4B'='D9C088'; 'F4E8C1'='E8DCBE'
    'E2CA76'='DCC796'; 'F8ECC7'='EFE4C6'; 'A68019'='8F7239'; 'B4833E'='C6A15B'
    'FAF9F6'='F8F5EF'; 'F5F2EB'='F8F5EF'; 'F7F5EE'='F8F5EF'; 'FAF8F4'='F8F5EF'
    'EFECE4'='F8F5EF'
    '718096'='78716C'; '4A5568'='44403C'; '2D3748'='292524'; 'E2E8F0'='E7E5E4'
    'EDF2F7'='F5F5F4'; 'F7FAFC'='FAFAF9'; 'CBD5E0'='D6D3D1'; 'A0AEC0'='78716C'
    'FAFAFA'='FFFFFF'; 'F1F5F9'='F5F5F4'; 'F0F0F0'='F5F5F4'; 'E0E0E0'='E7E5E4'
    'C53030'='171513'; '9B2C2C'='171513'; '991B1B'='171513'; '276749'='8F7239'
    '065F46'='8F7239'; '1E40AF'='171513'; 'BFDBFE'='F8F5EF'; 'EFF6FF'='F8F5EF'
    'ECFDF5'='F8F5EF'; 'A7F3D0'='C6A15B'; 'FEF2F2'='F8F5EF'; 'FECACA'='E7E5E4'
    'FDE68A'='C6A15B'; 'FED7D7'='E7E5E4'; 'FFFBEB'='F8F5EF'; '92400E'='8F7239'
}

$classMap = @{
    'bg-emerald-50'='bg-[#C6A15B]/15'; 'bg-emerald-50/70'='bg-[#C6A15B]/20'
    'bg-emerald-100'='bg-[#C6A15B]/20'; 'bg-emerald-200/80'='bg-[#C6A15B]/35'
    'bg-emerald-400'='bg-[#C6A15B]'; 'bg-emerald-500'='bg-[#C6A15B]'
    'bg-emerald-600'='bg-[#C6A15B]'; 'bg-emerald-700'='bg-[#C6A15B]'
    'bg-emerald-800'='bg-[#C6A15B]'; 'bg-emerald-900/40'='bg-[#171513]/85'
    'bg-emerald-950'='bg-[#171513]'; 'bg-emerald-950/20'='bg-[#171513]/85'
    'bg-emerald-950/40'='bg-[#171513]/85'; 'bg-emerald-950/60'='bg-[#171513]/85'
    'bg-emerald-950/70'='bg-[#171513]/85'; 'bg-emerald-950/80'='bg-[#171513]/90'
    'text-emerald-100'='text-[#E8DCBE]'; 'text-emerald-200'='text-[#E8DCBE]'
    'text-emerald-300'='text-[#C6A15B]'; 'text-emerald-400'='text-[#8F7239]'
    'text-emerald-600'='text-[#8F7239]'; 'text-emerald-700'='text-[#8F7239]'
    'text-emerald-800'='text-[#8F7239]'; 'text-emerald-900'='text-[#8F7239]'
    'text-emerald-950'='text-[#8F7239]'
    'border-emerald-300'='border-[#C6A15B]/60'; 'border-emerald-400'='border-[#C6A15B]/60'
    'border-emerald-500'='border-[#C6A15B]/60'; 'border-emerald-500/30'='border-[#C6A15B]/40'
    'border-emerald-500/40'='border-[#C6A15B]/45'; 'border-emerald-500/50'='border-[#C6A15B]/50'
    'border-emerald-500/60'='border-[#C6A15B]/60'; 'border-emerald-600/50'='border-[#C6A15B]/50'
    'border-emerald-700'='border-[#C6A15B]/70'
    'bg-rose-50'='bg-[#171513]/8'; 'bg-rose-50/70'='bg-[#171513]/10'
    'bg-rose-100'='bg-[#171513]/10'; 'bg-rose-400'='bg-[#171513]'
    'bg-rose-500'='bg-[#171513]'; 'bg-rose-600'='bg-[#171513]'
    'bg-rose-700'='bg-[#171513]'; 'bg-rose-800'='bg-[#171513]'
    'bg-rose-900'='bg-[#171513]'; 'bg-rose-900/80'='bg-[#171513]/90'
    'bg-rose-950/40'='bg-[#171513]/85'; 'bg-rose-950/60'='bg-[#171513]/85'
    'bg-rose-950/70'='bg-[#171513]/85'; 'bg-rose-950/80'='bg-[#171513]/90'
    'text-rose-200'='text-[#F8F5EF]'; 'text-rose-300'='text-[#F8F5EF]'
    'text-rose-400'='text-[#171513]'; 'text-rose-500'='text-[#171513]'
    'text-rose-600'='text-[#171513]'; 'text-rose-700'='text-[#171513]'
    'text-rose-800'='text-[#171513]'
    'border-rose-200'='border-[#C6A15B]/40'; 'border-rose-300'='border-[#C6A15B]/50'
    'border-rose-400'='border-[#C6A15B]/60'; 'border-rose-500'='border-[#C6A15B]/60'
    'border-rose-500/30'='border-[#C6A15B]/40'; 'border-rose-500/40'='border-[#C6A15B]/45'
    'border-rose-500/50'='border-[#C6A15B]/50'; 'border-rose-500/60'='border-[#C6A15B]/60'
    'border-rose-600/50'='border-[#C6A15B]/50'; 'border-rose-700'='border-[#C6A15B]/70'
    'border-rose-900/50'='border-[#171513]/40'
    'bg-amber-50'='bg-[#C6A15B]/15'; 'bg-amber-50/40'='bg-[#C6A15B]/15'
    'bg-amber-50/50'='bg-[#C6A15B]/18'; 'bg-amber-50/60'='bg-[#C6A15B]/20'
    'bg-amber-50/70'='bg-[#C6A15B]/20'; 'bg-amber-100'='bg-[#C6A15B]/20'
    'bg-amber-500'='bg-[#C6A15B]'; 'bg-amber-500/20'='bg-[#C6A15B]/30'
    'bg-amber-600'='bg-[#C6A15B]'; 'bg-amber-700'='bg-[#C6A15B]'
    'bg-amber-950/40'='bg-[#171513]/85'; 'bg-amber-950/70'='bg-[#171513]/85'
    'text-amber-200'='text-[#C6A15B]'; 'text-amber-300'='text-[#C6A15B]'
    'text-amber-400'='text-[#C6A15B]'; 'text-amber-600'='text-[#8F7239]'
    'text-amber-700'='text-[#8F7239]'; 'text-amber-800'='text-[#8F7239]'
    'text-amber-900'='text-[#8F7239]'
    'border-amber-200'='border-[#C6A15B]/50'; 'border-amber-300'='border-[#C6A15B]/60'
    'border-amber-400'='border-[#C6A15B]/60'; 'border-amber-500/40'='border-[#C6A15B]/45'
    'border-amber-500/50'='border-[#C6A15B]/50'; 'border-amber-600/50'='border-[#C6A15B]/50'
    'border-amber-700'='border-[#C6A15B]/70'
    'bg-blue-100'='bg-[#C6A15B]/15'; 'bg-blue-950/70'='bg-[#171513]/85'
    'text-blue-300'='text-[#C6A15B]'; 'text-blue-700'='text-[#8F7239]'
    'text-blue-800'='text-[#8F7239]'; 'text-blue-900'='text-[#8F7239]'
    'border-blue-300'='border-[#C6A15B]/60'; 'border-blue-600/50'='border-[#C6A15B]/50'
    'bg-purple-100'='bg-[#C6A15B]/15'; 'bg-purple-950/70'='bg-[#171513]/85'
    'text-purple-300'='text-[#C6A15B]'; 'text-purple-900'='text-[#8F7239]'
    'border-purple-300'='border-[#C6A15B]/60'; 'border-purple-600/50'='border-[#C6A15B]/50'
    'text-orange-700'='text-[#8F7239]'
    'dark:text-emerald-400'='dark:text-[#C6A15B]'; 'dark:text-emerald-300'='dark:text-[#C6A15B]'
    'dark:text-emerald-100'='dark:text-[#E8DCBE]'; 'dark:text-emerald-200'='dark:text-[#E8DCBE]'
    'dark:text-rose-400'='dark:text-[#F8F5EF]'; 'dark:text-rose-300'='dark:text-[#F8F5EF]'
    'dark:text-rose-200'='dark:text-[#F8F5EF]'
    'dark:text-amber-400'='dark:text-[#C6A15B]'; 'dark:text-amber-300'='dark:text-[#C6A15B]'
    'dark:text-blue-300'='dark:text-[#C6A15B]'; 'dark:text-purple-300'='dark:text-[#C6A15B]'
    'dark:border-emerald-600/50'='dark:border-[#C6A15B]/50'; 'dark:border-emerald-700'='dark:border-[#C6A15B]/70'
    'dark:border-amber-600/50'='dark:border-[#C6A15B]/50'; 'dark:border-amber-700'='dark:border-[#C6A15B]/70'
    'dark:border-rose-600/50'='dark:border-[#C6A15B]/50'; 'dark:border-rose-500'='dark:border-[#C6A15B]/60'
    'dark:border-rose-700'='dark:border-[#C6A15B]/70'; 'dark:border-rose-900/50'='dark:border-[#171513]/40'
    'dark:border-blue-600/50'='dark:border-[#C6A15B]/50'; 'dark:border-purple-600/50'='dark:border-[#C6A15B]/50'
    'dark:bg-emerald-950/40'='dark:bg-[#171513]/85'; 'dark:bg-emerald-950/60'='dark:bg-[#171513]/85'
    'dark:bg-emerald-950/70'='dark:bg-[#171513]/85'; 'dark:bg-emerald-950/80'='dark:bg-[#171513]/90'
    'dark:bg-emerald-800'='dark:bg-[#C6A15B]'
    'dark:bg-rose-950/40'='dark:bg-[#171513]/85'; 'dark:bg-rose-950/60'='dark:bg-[#171513]/85'
    'dark:bg-rose-950/80'='dark:bg-[#171513]/90'
    'dark:bg-amber-950/40'='dark:bg-[#171513]/85'; 'dark:bg-amber-950/70'='dark:bg-[#171513]/85'
    'dark:bg-blue-950/70'='dark:bg-[#171513]/85'; 'dark:bg-purple-950/70'='dark:bg-[#171513]/85'
}

# Bare classes that sit on dark backgrounds in specific files
$fileOverrides = @{
    'admin-dashboard.blade.php' = @{
        'text-rose-400'='text-[#F8F5EF]'
        'text-emerald-400'='text-[#C6A15B]'
    }
}

$files = @()
$files += Get-ChildItem "$root\resources\views" -Recurse -Filter *.blade.php
$files += Get-ChildItem "$root\resources\css" -Filter *.css
$files += Get-ChildItem "$root\resources\js" -Filter *.js -ErrorAction SilentlyContinue

$utf8NoBom = New-Object System.Text.UTF8Encoding($false)
$changed = 0

foreach ($f in $files) {
    $text = [System.IO.File]::ReadAllText($f.FullName)
    $orig = $text

    # 1) Hex colors (6-digit, case-insensitive lookup)
    $text = [regex]::Replace($text, '#(?<h>[0-9A-Fa-f]{6})\b', {
        param($m)
        $k = $m.Groups['h'].Value.ToUpper()
        if ($hexMap.ContainsKey($k)) { '#' + $hexMap[$k] } else { $m.Value }
    })

    # 2) rgb/rgba triples
    $text = [regex]::Replace($text, '(rgba?\(\s*)212\s*,\s*175\s*,\s*55', '$1198, 161, 91')
    $text = [regex]::Replace($text, '(rgba?\(\s*)154\s*,\s*123\s*,\s*28', '$1143, 114, 57')
    $text = [regex]::Replace($text, '(rgba?\(\s*)11\s*,\s*9\s*,\s*8\b', '$123, 21, 19')
    $text = [regex]::Replace($text, '(rgba?\(\s*)28\s*,\s*24\s*,\s*22', '$123, 21, 19')

    # 3) Tailwind color classes (longest key first so bg-emerald-500 beats bg-emerald-50)
    $map = $classMap
    if ($fileOverrides.ContainsKey($f.Name)) {
        $map = $classMap.Clone()
        foreach ($kv in $fileOverrides[$f.Name].GetEnumerator()) { $map[$kv.Key] = $kv.Value }
    }
    $keys = $map.Keys | Sort-Object { -$_.Length }
    $alt = ($keys | ForEach-Object { [regex]::Escape($_) }) -join '|'
    $text = [regex]::Replace($text, $alt, {
        param($m)
        if ($map.ContainsKey($m.Value)) { $map[$m.Value] } else { $m.Value }
    })

    if ($text -ne $orig) {
        [System.IO.File]::WriteAllText($f.FullName, $text, $utf8NoBom)
        $changed++
    }
}

Write-Output "PALETTE MIGRATION DONE - files changed: $changed / $($files.Count)"
