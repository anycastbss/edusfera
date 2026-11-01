<?php

declare(strict_types=1);

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Consolidate duplicate conversations between the same tutor and student
        $duplicatePairs = Conversation::query()
            ->select('tutor_id', 'student_id')
            ->groupBy('tutor_id', 'student_id')
            ->havingRaw('count(*) > 1')
            ->get();

        foreach ($duplicatePairs as $pair) {
            $convs = Conversation::query()
                ->where('tutor_id', $pair->tutor_id)
                ->where('student_id', $pair->student_id)
                ->orderByDesc('last_message_at')
                ->orderByDesc('id')
                ->get();

            if ($convs->count() <= 1) {
                continue;
            }

            $canonical = $convs->first();
            $duplicates = $convs->slice(1);

            foreach ($duplicates as $duplicate) {
                // Move all messages from the duplicate to the canonical conversation
                Message::query()
                    ->where('conversation_id', $duplicate->id)
                    ->update(['conversation_id' => $canonical->id]);

                // Safely delete duplicate conversation
                $duplicate->delete();
            }

            // Update canonical conversation's last_message_at to the latest message date
            $latestMessage = Message::query()
                ->where('conversation_id', $canonical->id)
                ->latest('created_at')
                ->first();

            if ($latestMessage) {
                $canonical->update(['last_message_at' => $latestMessage->created_at]);
            }
        }
    }

    public function down(): void
    {
        // No reversal needed for consolidation
    }
};
