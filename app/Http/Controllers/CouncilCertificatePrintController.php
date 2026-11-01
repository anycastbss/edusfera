<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\TutorProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CouncilCertificatePrintController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = Auth::user();
        abort_unless($user !== null && $user->isTutor(), 403);

        $tutorProfile = $user->tutorProfile;
        if (! $tutorProfile) {
            $tutorProfile = TutorProfile::query()->firstOrCreate(
                ['user_id' => $user->id],
                ['is_public' => false]
            );
        }

        if (! $tutorProfile->isCouncilExpert()) {
            $tutorProfile->joinCouncil();
        }

        $councilNumber = $tutorProfile->council_number ?: 1;
        $councilCertNumber = $tutorProfile->council_certificate_number
            ?? ('МС-2026/'.str_pad((string) $councilNumber, 2, '0', STR_PAD_LEFT));
        $councilJoinedAt = $tutorProfile->council_joined_at ?? now();

        return view('council.print-certificate', [
            'user' => $user,
            'tutorFullName' => (string) $user->name,
            'councilCertNumber' => $councilCertNumber,
            'councilJoinedAt' => $councilJoinedAt,
            'tutorProfile' => $tutorProfile,
        ]);
    }
}
