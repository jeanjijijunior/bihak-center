# Bihak Center - Brand Color Fix Script
# Replaces all purple/indigo colors with brand blues and removes fancy emojis

$rootPath = "c:\xampp\htdocs\bihak-center"

# Color mappings (old -> new)
$colorReplacements = @{
    '#6366f1' = '#1cabe2'
    '#8b5cf6' = '#147ba5'
    '#667eea' = '#1cabe2'
    '#764ba2' = '#147ba5'
    'rgba(99, 102, 241' = 'rgba(28, 171, 226'
    'rgba(139, 92, 246' = 'rgba(20, 123, 165'
    'rgba(102, 126, 234' = 'rgba(28, 171, 226'
}

# Emoji patterns to remove
$emojiPattern = '[🎯🚀💡✨🎓🌟📚💼🔥⚡👥📊🎨🏆💪🎉📈🔔💬📝🔍⭐]'

# Get all PHP files (excluding vendor and node_modules)
$phpFiles = Get-ChildItem -Path $rootPath -Recurse -Include *.php -Exclude vendor,node_modules |
    Where-Object { $_.FullName -notmatch 'vendor|node_modules' }

$filesModified = 0
$colorsReplaced = 0
$emojisRemoved = 0

Write-Host "Starting brand color fix..." -ForegroundColor Cyan
Write-Host "Found $($phpFiles.Count) PHP files to process`n" -ForegroundColor Yellow

foreach ($file in $phpFiles) {
    $content = Get-Content $file.FullName -Raw -Encoding UTF8
    $originalContent = $content
    $fileChanged = $false

    # Replace colors
    foreach ($oldColor in $colorReplacements.Keys) {
        $newColor = $colorReplacements[$oldColor]
        if ($content -match [regex]::Escape($oldColor)) {
            $matchCount = ([regex]::Matches($content, [regex]::Escape($oldColor))).Count
            $content = $content -replace [regex]::Escape($oldColor), $newColor
            $colorsReplaced += $matchCount
            $fileChanged = $true
            Write-Host "  $($file.Name): Replaced $matchCount instances of $oldColor" -ForegroundColor Green
        }
    }

    # Remove emojis
    if ($content -match $emojiPattern) {
        $emojiMatches = ([regex]::Matches($content, $emojiPattern)).Count
        $content = $content -replace $emojiPattern, ''
        $emojisRemoved += $emojiMatches
        $fileChanged = $true
        Write-Host "  $($file.Name): Removed $emojiMatches emojis" -ForegroundColor Magenta
    }

    # Save if changed
    if ($fileChanged) {
        Set-Content -Path $file.FullName -Value $content -Encoding UTF8 -NoNewline
        $filesModified++
    }
}

Write-Host "`n=== Summary ===" -ForegroundColor Cyan
Write-Host "Files modified: $filesModified" -ForegroundColor Yellow
Write-Host "Colors replaced: $colorsReplaced" -ForegroundColor Green
Write-Host "Emojis removed: $emojisRemoved" -ForegroundColor Magenta
Write-Host "`nBrand fix complete!" -ForegroundColor Green
