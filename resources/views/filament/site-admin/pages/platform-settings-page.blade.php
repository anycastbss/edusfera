<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <div style="display: flex; gap: 12px; margin-top: 24px; align-items: center;">
            <button type="submit" class="fi-btn fi-btn-size-lg" style="background: #7D39EB; color: #fff; padding: 12px 28px; border-radius: 12px; font-weight: 700; font-size: 14px; border: none; cursor: pointer; box-shadow: 0 4px 16px rgba(125, 57, 235, 0.4);">
                💾 Сохранить все параметры платформы
            </button>

            <button type="button" wire:click="testGeminiConnection" class="fi-btn fi-btn-size-lg" style="background: rgba(255, 255, 255, 0.08); color: #fff; padding: 12px 20px; border-radius: 12px; font-weight: 600; font-size: 13px; border: 1px solid rgba(255, 255, 255, 0.2); cursor: pointer;">
                🤖 Тест связи с Gemini API
            </button>
        </div>
    </form>
</x-filament-panels::page>
