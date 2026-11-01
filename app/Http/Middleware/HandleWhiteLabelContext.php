<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Subscription\Services\SubscriptionFeatureGate;
use App\Models\TutorProfile;
use App\Models\User;
use App\Services\WhiteLabelService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class HandleWhiteLabelContext
{
    public function __construct(
        private readonly WhiteLabelService $whiteLabelService,
        private readonly SubscriptionFeatureGate $featureGate
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tutorIdParam = $request->query('tutor') ?? $request->query('ref');

        if ($tutorIdParam) {
            $tutorUser = is_numeric($tutorIdParam)
                ? User::find((int) $tutorIdParam)
                : null;

            if ($tutorUser && $tutorUser->isTutor()) {
                $profile = $tutorUser->tutorProfile;
                if ($profile && $profile->is_white_label && $this->featureGate->canUseWhiteLabel($tutorUser)) {
                    $this->whiteLabelService->setSessionWhiteLabelTutor($tutorUser->id);
                }
            }
        }

        // Also check if accessing /tutors/{tutorProfile} directly
        $routeTutor = $request->route('tutor');
        if ($routeTutor instanceof TutorProfile) {
            $tutorUser = $routeTutor->user;
            if ($routeTutor->is_white_label && $tutorUser && $this->featureGate->canUseWhiteLabel($tutorUser)) {
                $this->whiteLabelService->setSessionWhiteLabelTutor($tutorUser->id);
            }
        }

        $isWhiteLabel = $this->whiteLabelService->isWhiteLabelActive();
        View::share('isWhiteLabelActive', $isWhiteLabel);

        return $next($request);
    }
}
