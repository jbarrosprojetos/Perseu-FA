@echo off
REM ============================================================
REM  Sincroniza o mirror do plugin (Windows) para o WSL
REM  e limpa o cache do Laravel via ddev.
REM
REM  Uso: dar duplo clique neste arquivo, ou rodar pelo
REM  PowerShell/CMD dentro desta mesma pasta.
REM
REM  Ajuste os caminhos abaixo se a estrutura mudar (distro WSL,
REM  usuario, nome do projeto).
REM ============================================================
setlocal

set "ORIGEM=C:\Perseu\PerseuFA_comercial"
set "DESTINO=\\wsl.localhost\ddev\home\projeto_studio\Perseu-FA\plugins\perseu\comercial"
set "DISTRO=ddev"
set "PROJETO_WSL=/home/projeto_studio/Perseu-FA"

echo.
echo === Copiando "%ORIGEM%" -^> "%DESTINO%" ===
robocopy "%ORIGEM%" "%DESTINO%" /E

REM robocopy usa codigos de saida 0-7 como sucesso (com ou sem
REM arquivos copiados); 8 ou mais indica erro real.
if %ERRORLEVEL% GEQ 8 (
    echo.
    echo *** ERRO no robocopy ^(codigo %ERRORLEVEL%^). Nada foi limpo no ddev. ***
    pause
    exit /b %ERRORLEVEL%
)

echo.
echo === Atualizando autoload do Composer ^(ddev composer dump-autoload^) ===
wsl -d %DISTRO% -- bash -lc "cd '%PROJETO_WSL%' && ddev composer dump-autoload"

if %ERRORLEVEL% NEQ 0 (
    echo.
    echo *** O comando ddev composer dump-autoload retornou erro ^(codigo %ERRORLEVEL%^). ***
    pause
    exit /b %ERRORLEVEL%
)

echo.
echo === Limpando cache do Laravel ^(ddev artisan optimize:clear^) ===
wsl -d %DISTRO% -- bash -lc "cd '%PROJETO_WSL%' && ddev artisan optimize:clear"

if %ERRORLEVEL% NEQ 0 (
    echo.
    echo *** O comando ddev retornou erro ^(codigo %ERRORLEVEL%^). Confira se o ddev esta rodando e se DISTRO/PROJETO_WSL acima estao corretos. ***
    pause
    exit /b %ERRORLEVEL%
)

echo.
echo === Concluido: mirror sincronizado e cache limpo. ===
pause
