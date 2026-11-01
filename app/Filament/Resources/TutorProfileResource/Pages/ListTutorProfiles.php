<?php

namespace App\Filament\Resources\TutorProfileResource\Pages;

use App\Filament\Resources\TutorProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTutorProfiles extends ListRecords
{
    protected static string $resource = TutorProfileResource::class;

    public function mount(): void
    {
        $user = auth()->user();
        if ($user?->isTutor() && $user->tutorProfile) {
            $this->redirect(TutorProfileResource::getUrl('edit', ['record' => $user->tutorProfile->id]));

            return;
        }

        parent::mount();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Начать настройку профиля')
                ->visible(function (): bool {
                    $user = auth()->user();

                    return ($user?->isTutor() ?? false) && ! $user->tutorProfile()->exists();
                }),
        ];
    }
}
