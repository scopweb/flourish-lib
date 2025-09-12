# Flourish Documentation Local Server
# PowerShell script to start local web server

Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host " Flourish Documentation Local Server" -ForegroundColor Green  
Write-Host "========================================" -ForegroundColor Green
Write-Host ""
Write-Host "Starting server at http://localhost:8000" -ForegroundColor Yellow
Write-Host ""
Write-Host "Available URLs:" -ForegroundColor Cyan
Write-Host "  • Main Site: http://localhost:8000/" -ForegroundColor White
Write-Host "  • API Docs:  http://localhost:8000/api/" -ForegroundColor White
Write-Host "  • Offline:   http://localhost:8000/offline_docs/flourish_v0.9.0.html" -ForegroundColor White
Write-Host ""
Write-Host "Press Ctrl+C to stop the server" -ForegroundColor Red
Write-Host ""

# Start PHP built-in server
php -S localhost:8000