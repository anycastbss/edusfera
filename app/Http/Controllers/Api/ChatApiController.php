<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Lesson;
use App\Models\Message;
use App\Models\User;
use App\Services\ChatService;
use App\Services\ChatUnreadCounter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;

class ChatApiController extends Controller
{
    public function __construct(
        protected ChatService $chatService,
        protected ChatUnreadCounter $unreadCounter
    ) {}

    /**
     * Получить список диалогов текущего пользователя.
     */
    public function getConversations(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Не авторизован.'], 401);
        }

        $query = $this->authorizedConversationsQuery($user)
            ->with([
                'lesson.tutor.tutorProfile',
                'tutor.tutorProfile',
                'student',
                'messages' => fn ($q) => $q->latest()->limit(1),
            ])
            ->withCount([
                'messages as unread_count' => function (Builder $inner) use ($user): Builder {
                    return $inner
                        ->where(function (Builder $b) use ($user): Builder {
                            return $b->whereNull('sender_id')->orWhere('sender_id', '!=', $user->id);
                        })
                        ->where('is_read', false);
                },
            ])
            ->orderByDesc('last_message_at');

        $conversations = $query->get();

        // Уникализируем пару tutor_id - student_id
        $conversations = $conversations->unique(function (Conversation $c): string {
            return $c->tutor_id . '_' . $c->student_id;
        })->values();

        $search = mb_strtolower(trim((string) $request->input('search', '')));
        if ($search !== '') {
            $conversations = $conversations->filter(function (Conversation $conv) use ($search, $user): bool {
                $other = $this->getOtherUser($conv, $user);
                $subject = $this->getSubject($conv);

                return str_contains(mb_strtolower((string) $other?->name), $search)
                    || str_contains(mb_strtolower($subject), $search);
            })->values();
        }

        $filter = (string) $request->input('filter', 'all');
        $conversations = match ($filter) {
            'unread' => $conversations->filter(fn (Conversation $c): bool => (int) $c->unread_count > 0)->values(),
            'paid' => $conversations->filter(fn (Conversation $c): bool => $c->lesson?->payment_status === Lesson::PAYMENT_PAID)->values(),
            default => $conversations,
        };

        $conversations = $conversations
            ->sortByDesc(fn (Conversation $c): int => (int) $c->unread_count > 0 ? 1 : 0)
            ->sortByDesc(fn (Conversation $c): int => optional($c->last_message_at)?->timestamp ?? 0)
            ->values();

        $displayTz = config('booking.display_timezone', 'Europe/Minsk');

        $data = $conversations->map(function (Conversation $c) use ($user, $displayTz) {
            $other = $this->getOtherUser($c, $user);
            $lastMsg = $c->messages->first();

            $upcomingLesson = null;
            if ($c->lesson && in_array($c->lesson->status, [Lesson::STATUS_PENDING, Lesson::STATUS_CONFIRMED], true)) {
                $upcomingLesson = [
                    'id' => $c->lesson->id,
                    'status' => $c->lesson->status,
                    'start_time' => $c->lesson->start_time?->setTimezone($displayTz)->format('d.m H:i'),
                    'is_paid' => $c->lesson->payment_status === Lesson::PAYMENT_PAID,
                    'classroom_url' => $c->lesson->status === Lesson::STATUS_CONFIRMED ? route('classroom.show', $c->lesson) : null,
                ];
            }

            return [
                'id' => $c->id,
                'subject' => $this->getSubject($c),
                'other_user' => [
                    'id' => $other?->id,
                    'name' => $other?->name ?? 'Пользователь',
                    'avatar' => $other?->getFilamentAvatarUrl() ?? null,
                    'role' => $other?->role?->value ?? ($other?->role ?? 'user'),
                    'is_tutor' => $other?->isTutor() ?? false,
                    'phone' => $other?->phone,
                    'telegram' => $other?->tutorProfile?->telegram_username,
                ],
                'last_message' => $lastMsg ? [
                    'id' => $lastMsg->id,
                    'text' => $lastMsg->message,
                    'sender_id' => $lastMsg->sender_id,
                    'is_mine' => $lastMsg->sender_id === $user->id,
                    'is_read' => $lastMsg->is_read,
                    'time' => $lastMsg->created_at?->setTimezone($displayTz)->format('H:i'),
                    'date' => $lastMsg->created_at?->setTimezone($displayTz)->format('d.m'),
                ] : null,
                'unread_count' => (int) $c->unread_count,
                'upcoming_lesson' => $upcomingLesson,
                'updated_at' => $c->last_message_at?->toIso8601String(),
            ];
        });

        return response()->json([
            'success' => true,
            'conversations' => $data,
            'total_unread' => $this->unreadCounter->countForUser($user),
        ]);
    }

    /**
     * Получить сообщения конкретного диалога.
     */
    public function getMessages(Request $request, Conversation $conversation): JsonResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (! $user || ! $this->canAccessConversation($user, $conversation)) {
            return response()->json(['success' => false, 'message' => 'Доступ запрещён.'], 403);
        }

        // Автоматически помечаем прочитанным при открытии
        $this->chatService->markAsRead($user->id, $conversation->id);

        $sinceId = (int) $request->input('since_id', 0);
        $displayTz = config('booking.display_timezone', 'Europe/Minsk');

        $query = Message::query()
            ->with('sender')
            ->where('conversation_id', $conversation->id);

        if ($sinceId > 0) {
            $query->where('id', '>', $sinceId)->oldest('created_at')->limit(50);
        } else {
            $query->latest('created_at')->limit(100);
        }

        $rawMessages = $query->get();
        if ($sinceId === 0) {
            $rawMessages = $rawMessages->sortBy('created_at')->values();
        }

        $messages = $rawMessages->map(function (Message $m) use ($user, $displayTz) {
            return [
                'id' => $m->id,
                'conversation_id' => $m->conversation_id,
                'sender_id' => $m->sender_id,
                'is_mine' => $m->sender_id === $user->id,
                'is_system' => (bool) $m->is_system,
                'message' => $m->message,
                'formatted_html' => $this->formatMessageContent($m->message),
                'meta' => $m->meta,
                'is_read' => (bool) $m->is_read,
                'time' => $m->created_at?->setTimezone($displayTz)->format('H:i') ?? '',
                'date' => $m->created_at?->setTimezone($displayTz)->format('d.m.Y') ?? '',
                'created_at_iso' => $m->created_at?->toIso8601String(),
            ];
        })->values();

        $other = $this->getOtherUser($conversation, $user);

        // Инфо о ближайшем активном уроке
        $lessonBanner = null;
        if ($conversation->lesson && in_array($conversation->lesson->status, [Lesson::STATUS_PENDING, Lesson::STATUS_CONFIRMED], true)) {
            $lesson = $conversation->lesson;
            $lessonBanner = [
                'id' => $lesson->id,
                'status' => $lesson->status,
                'status_label' => match ($lesson->status) {
                    Lesson::STATUS_CONFIRMED => 'Занятие подтверждено',
                    Lesson::STATUS_PENDING => 'Ожидает оплаты',
                    default => $lesson->status,
                },
                'start_time' => $lesson->start_time?->setTimezone($displayTz)->format('d.m.Y H:i'),
                'is_paid' => $lesson->payment_status === Lesson::PAYMENT_PAID,
                'can_enter_class' => $lesson->status === Lesson::STATUS_CONFIRMED,
                'classroom_url' => $lesson->status === Lesson::STATUS_CONFIRMED ? route('classroom.show', $lesson) : null,
                'checkout_url' => $lesson->payment_status !== Lesson::PAYMENT_PAID ? route('checkout.show', $lesson) : null,
            ];
        }

        // Быстрые шаблоны ответов
        $quickReplies = [];
        if ($user->isTutor()) {
            $quickReplies = [
                'Здравствуйте! Рад познакомиться. С какой темы хотите начать?',
                'Да, этот слот свободен. Можно подтверждать бронирование.',
                'Отправил материалы к уроку во вложении 📎',
            ];
        }

        return response()->json([
            'success' => true,
            'messages' => $messages,
            'other_user' => [
                'id' => $other?->id,
                'name' => $other?->name ?? 'Пользователь',
                'avatar' => $other?->getFilamentAvatarUrl() ?? null,
                'role' => $other?->role?->value ?? ($other?->role ?? 'user'),
                'is_tutor' => $other?->isTutor() ?? false,
                'subject' => $this->getSubject($conversation),
                'phone' => $other?->phone,
                'telegram' => $other?->tutorProfile?->telegram_username,
            ],
            'lesson_banner' => $lessonBanner,
            'quick_replies' => $quickReplies,
        ]);
    }

    /**
     * Отправить сообщение.
     */
    public function sendMessage(Request $request, Conversation $conversation): JsonResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (! $user || ! $this->canAccessConversation($user, $conversation)) {
            return response()->json(['success' => false, 'message' => 'Доступ запрещён.'], 403);
        }

        $text = (string) $request->input('message', '');
        $clientId = (string) $request->input('client_id', '');
        $meta = $request->input('meta');
        if (! is_array($meta)) {
            $meta = null;
        }

        try {
            $result = $this->chatService->sendMessage(
                conversationId: $conversation->id,
                senderId: $user->id,
                message: $text,
                meta: $meta,
            );
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first() ?? 'Ошибка отправки.',
                'errors' => $e->errors(),
            ], 422);
        }

        /** @var Message $created */
        $created = $result['message'];
        $displayTz = config('booking.display_timezone', 'Europe/Minsk');

        $this->chatService->markAsRead($user->id, $conversation->id);

        return response()->json([
            'success' => true,
            'client_id' => $clientId,
            'message' => [
                'id' => $created->id,
                'conversation_id' => $created->conversation_id,
                'sender_id' => $created->sender_id,
                'is_mine' => true,
                'is_system' => false,
                'message' => $created->message,
                'formatted_html' => $this->formatMessageContent($created->message),
                'meta' => $created->meta,
                'is_read' => false,
                'time' => $created->created_at?->setTimezone($displayTz)->format('H:i') ?? '',
                'date' => $created->created_at?->setTimezone($displayTz)->format('d.m.Y') ?? '',
                'created_at_iso' => $created->created_at?->toIso8601String(),
            ],
            'warning' => $result['warning'] ?? null,
        ]);
    }

    /**
     * Загрузить вложение (картинку или учебный документ) для чата.
     * Автоматически оптимизирует и сжимает изображения (WebP/JPEG 82%, макс 1920px),
     * экономя до 90% дискового пространства на сервере.
     */
    public function uploadAttachment(Request $request, Conversation $conversation): JsonResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (! $user || ! $this->canAccessConversation($user, $conversation)) {
            return response()->json(['success' => false, 'message' => 'Доступ запрещён.'], 403);
        }

        $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:jpeg,png,jpg,webp,gif,pdf,docx,doc,xls,xlsx,pptx,txt,zip',
                'max:25600',
            ],
        ], [
            'file.required' => 'Выберите файл для отправки.',
            'file.mimes' => 'Поддерживаются изображения (JPG, PNG, WEBP, GIF) и документы (PDF, DOCX, DOC, XLS, XLSX, PPTX, TXT, ZIP).',
            'file.max' => 'Размер файла не должен превышать 25 МБ.',
        ]);

        $file = $request->file('file');
        if (! $file || ! $file->isValid()) {
            return response()->json(['success' => false, 'message' => 'Некорректный файл.'], 422);
        }

        $origName = $file->getClientOriginalName();
        $ext = mb_strtolower($file->getClientOriginalExtension());
        $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true);

        // Оптимизируем и сжимаем изображения (кроме анимированных GIF)
        if ($isImage && $ext !== 'gif') {
            $attachmentData = $this->processAndCompressImage($file, $conversation->id);
        } else {
            $filename = 'chat_' . $conversation->id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $path = $file->storeAs('chat_attachments', $filename, 'public');
            $url = Storage::disk('public')->url($path);
            $attachmentData = [
                'type' => $isImage ? 'image' : 'file',
                'url' => $url,
                'file_name' => $origName,
                'file_size' => $file->getSize(),
                'ext' => $ext,
            ];
        }

        return response()->json([
            'success' => true,
            'attachment' => $attachmentData,
        ]);
    }

    /**
     * Сжать и оптимизировать изображение (PHP GD).
     * Автоповорот по EXIF, пропорциональное масштабирование до 1920px, WebP/JPEG 82%.
     */
    private function processAndCompressImage(\Illuminate\Http\UploadedFile $file, int $conversationId): array
    {
        $realPath = $file->getRealPath();
        $ext = mb_strtolower($file->getClientOriginalExtension());
        $origName = $file->getClientOriginalName();

        // Проверяем наличие библиотеки GD
        if (! extension_loaded('gd') || ! function_exists('imagecreatefromstring')) {
            $filename = 'chat_' . $conversationId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $path = $file->storeAs('chat_attachments', $filename, 'public');
            return [
                'type' => 'image',
                'url' => Storage::disk('public')->url($path),
                'file_name' => $origName,
                'file_size' => $file->getSize(),
                'ext' => $ext,
            ];
        }

        try {
            $imageData = @file_get_contents($realPath);
            if (! $imageData) {
                throw new \RuntimeException('Cannot read image data');
            }

            $srcImage = @imagecreatefromstring($imageData);
            if (! $srcImage) {
                throw new \RuntimeException('Invalid image resource');
            }

            // Автоповорот по EXIF ориентации для фото с телефонов
            if (function_exists('exif_read_data') && in_array($ext, ['jpg', 'jpeg'], true)) {
                $exif = @exif_read_data($realPath);
                if (! empty($exif['Orientation'])) {
                    $srcImage = match ((int) $exif['Orientation']) {
                        3 => imagerotate($srcImage, 180, 0),
                        6 => imagerotate($srcImage, -90, 0),
                        8 => imagerotate($srcImage, 90, 0),
                        default => $srcImage,
                    };
                }
            }

            $origW = imagesx($srcImage);
            $origH = imagesy($srcImage);
            $maxDim = 1920;

            if ($origW > $maxDim || $origH > $maxDim) {
                if ($origW >= $origH) {
                    $newW = $maxDim;
                    $newH = (int) round(($origH * $maxDim) / $origW);
                } else {
                    $newH = $maxDim;
                    $newW = (int) round(($origW * $maxDim) / $origH);
                }

                $dstImage = imagecreatetruecolor($newW, $newH);
                imagealphablending($dstImage, false);
                imagesavealpha($dstImage, true);
                imagecopyresampled($dstImage, $srcImage, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
                imagedestroy($srcImage);
                $finalImage = $dstImage;
            } else {
                $finalImage = $srcImage;
            }

            // Сохраняем в WebP (если поддерживается) или JPEG с качеством 82%
            $useWebp = function_exists('imagewebp');
            $targetExt = $useWebp ? 'webp' : 'jpg';
            $filename = 'chat_' . $conversationId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $targetExt;
            $relativeDir = 'chat_attachments';
            $fullDir = Storage::disk('public')->path($relativeDir);
            if (! is_dir($fullDir)) {
                @mkdir($fullDir, 0755, true);
            }
            $targetFullPath = $fullDir . '/' . $filename;

            if ($useWebp) {
                imagewebp($finalImage, $targetFullPath, 82);
            } else {
                imagejpeg($finalImage, $targetFullPath, 82);
            }
            imagedestroy($finalImage);

            $storedSize = @filesize($targetFullPath) ?: $file->getSize();
            $path = $relativeDir . '/' . $filename;
            $url = Storage::disk('public')->url($path);

            return [
                'type' => 'image',
                'url' => $url,
                'file_name' => preg_replace('/\.[^.]+$/', '', $origName) . '.' . $targetExt,
                'file_size' => $storedSize,
                'ext' => $targetExt,
            ];
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Chat image compression fallback: ' . $e->getMessage());
            $filename = 'chat_' . $conversationId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $path = $file->storeAs('chat_attachments', $filename, 'public');
            return [
                'type' => 'image',
                'url' => Storage::disk('public')->url($path),
                'file_name' => $origName,
                'file_size' => $file->getSize(),
                'ext' => $ext,
            ];
        }
    }

    /**
     * Пометить диалог как прочитанный.
     */
    public function markAsRead(Request $request, Conversation $conversation): JsonResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (! $user || ! $this->canAccessConversation($user, $conversation)) {
            return response()->json(['success' => false, 'message' => 'Доступ запрещён.'], 403);
        }

        $this->chatService->markAsRead($user->id, $conversation->id);

        return response()->json([
            'success' => true,
            'total_unread' => $this->unreadCounter->countForUser($user),
        ]);
    }

    private function canAccessConversation(User $user, Conversation $conversation): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return in_array($user->id, [$conversation->tutor_id, $conversation->student_id], true);
    }

    private function authorizedConversationsQuery(User $user): Builder
    {
        $query = Conversation::query();

        if ($user->isAdmin()) {
            return $query;
        }

        return $query->where(function (Builder $b) use ($user): Builder {
            return $b->where('tutor_id', $user->id)->orWhere('student_id', $user->id);
        });
    }

    private function getOtherUser(Conversation $c, User $currentUser): ?User
    {
        return $currentUser->id === $c->tutor_id
            ? $c->student
            : $c->tutor;
    }

    private function getSubject(Conversation $c): string
    {
        if ($c->lesson?->tutor?->tutorProfile?->subjects) {
            return (string) ($c->lesson->tutor->tutorProfile->subjects[0] ?? 'Урок');
        }

        $subjects = $c->tutor?->tutorProfile?->subjects;

        return (string) ($subjects[0] ?? 'Общий диалог');
    }

    private function formatMessageContent(string $message): string
    {
        $clean = e($this->chatService->sanitizeMessageForDisplay($message));

        $withLinks = preg_replace(
            '/(https?:\/\/[^\s<]+)/iu',
            '<a href="$1" target="_blank" rel="noopener noreferrer" class="underline text-violet-400 hover:text-violet-300 break-all">$1</a>',
            $clean
        ) ?? $clean;

        return nl2br($withLinks);
    }
}
