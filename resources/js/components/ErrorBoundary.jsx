import React from 'react';
import { AlertTriangle, RefreshCw, Home } from 'lucide-react';

export default class ErrorBoundary extends React.Component {
  constructor(props) {
    super(props);
    this.state = { hasError: false, error: null, errorInfo: null };
  }

  static getDerivedStateFromError(error) {
    return { hasError: true, error };
  }

  componentDidCatch(error, errorInfo) {
    console.error('[Edusfera ErrorBoundary Caught Error]:', error, errorInfo);
    this.setState({ errorInfo });
  }

  handleReload = () => {
    window.location.reload();
  };

  handleGoHome = () => {
    window.location.href = '/cabinet';
  };

  render() {
    if (this.state.hasError) {
      if (this.props.fallback) {
        return this.props.fallback;
      }

      return (
        <div className="min-h-screen w-full flex items-center justify-center p-6 bg-[#0A0A0C] text-white">
          <div className="max-w-md w-full p-8 rounded-3xl bg-[#121216] border border-white/10 shadow-2xl text-center space-y-6">
            <div className="w-16 h-16 mx-auto rounded-2xl bg-rose-500/10 border border-rose-500/20 flex items-center justify-center text-rose-400">
              <AlertTriangle className="w-8 h-8" />
            </div>

            <div className="space-y-2">
              <h2 className="text-xl font-black font-rimma tracking-tight text-white">
                Произошла непредвиденная ошибка
              </h2>
              <p className="text-xs text-slate-400 leading-relaxed">
                Интерфейс не смог отобразиться из-за сбоя в браузере. Мы сохранили ваши данные, вы можете перезагрузить страницу.
              </p>
            </div>

            {this.state.error && (
              <div className="p-3 rounded-xl bg-black/40 border border-white/5 text-left overflow-x-auto text-[11px] font-mono text-rose-300/80">
                {this.state.error.toString()}
              </div>
            )}

            <div className="flex items-center gap-3 pt-2">
              <button
                type="button"
                onClick={this.handleReload}
                className="flex-1 py-3 px-4 rounded-xl bg-[#C6FF33] hover:bg-[#b5f024] text-black font-extrabold text-xs flex items-center justify-center gap-2 shadow-lg shadow-[#C6FF33]/20 active:scale-95 transition"
              >
                <RefreshCw className="w-4 h-4 stroke-[2.5]" />
                <span>Перезагрузить</span>
              </button>
              <button
                type="button"
                onClick={this.handleGoHome}
                className="py-3 px-4 rounded-xl bg-white/10 hover:bg-white/15 text-white font-bold text-xs flex items-center justify-center gap-2 active:scale-95 transition"
              >
                <Home className="w-4 h-4" />
                <span>В начало</span>
              </button>
            </div>
          </div>
        </div>
      );
    }

    return this.props.children;
  }
}
