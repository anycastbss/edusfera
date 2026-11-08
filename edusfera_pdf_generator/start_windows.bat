@echo off
cd /d %~dp0
where python >nul 2>nul || (echo Python 3.10+ not found. Install from https://www.python.org/downloads/ & pause & exit /b 1)
if not exist .venv python -m venv .venv
call .venv\Scripts\activate.bat
python -m pip install --upgrade pip
python -m pip install -r requirements.txt
python server.py
pause
