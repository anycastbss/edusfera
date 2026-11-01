<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\Subscription\Services\SubscriptionFeatureGate;
use App\Models\Lesson;
use App\Models\User;

class WhiteLabelService
{
    public function __construct(private readonly SubscriptionFeatureGate $featureGate) {}

    /**
     * Check if white label mode is active for the current context/user.
     */
    public function isWhiteLabelActive(?User $user = null): bool
    {
        return $this->getActiveWhiteLabelTutor($user) !== null;
    }

    /**
     * Resolve the active white-label tutor for the current student or visitor.
     */
    public function getActiveWhiteLabelTutor(?User $user = null): ?User
    {
        $user = $user ?? auth()->user();

        // 1. If user is an authenticated student or parent with exactly one tutor in DB
        if ($user && ($user->isStudent() || $user->isParent())) {
            $tutorIds = Lesson::query()
                ->where('student_id', $user->id)
                ->whereNotNull('tutor_id')
                ->distinct()
                ->pluck('tutor_id');

            if ($tutorIds->count() === 1) {
                $tutor = User::with(['tutorProfile', 'subscription'])->find($tutorIds->first());
                if ($tutor && $tutor->tutorProfile?->is_white_label && $this->featureGate->canUseWhiteLabel($tutor)) {
                    return $tutor;
                }
            }
        }

        // 2. Check session if visitor navigated via white-label tutor's direct link
        $sessionTutorId = session('white_label_tutor_id');
        if ($sessionTutorId) {
            $tutor = User::with(['tutorProfile', 'subscription'])->find($sessionTutorId);
            if ($tutor && $tutor->tutorProfile?->is_white_label && $this->featureGate->canUseWhiteLabel($tutor)) {
                return $tutor;
            }
        }

        return null;
    }

    /**
     * Remember white label tutor in session.
     */
    public function setSessionWhiteLabelTutor(int $tutorId): void
    {
        session(['white_label_tutor_id' => $tutorId]);
    }

    /**
     * Clear session white label tutor.
     */
    public function clearSessionWhiteLabel(): void
    {
        session()->forget('white_label_tutor_id');
    }
}
