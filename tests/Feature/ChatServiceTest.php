<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Pages\MessagesPage;
use App\Models\Conversation;
use App\Models\Lesson;
use App\Models\TutorProfile;
use App\Models\User;
use App\Notifications\ChatBypassAttemptNotification;
use App\Services\ChatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class ChatServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_conversation_and_sends_message(): void
    {
        Notification::fake();

        $tutor = User::factory()->create([
            'role' => 'tutor',
            'phone' => '+375291111111',
        ]);

        $student = User::factory()->create([
            'role' => 'student',
            'phone' => '+375292222222',
        ]);

        $service = app(ChatService::class);

        $conversation = $service->getOrCreateConversation($tutor->id, $student->id);
        $result = $service->sendMessage($conversation->id, $student->id, 'Здравствуйте, хочу обсудить занятие.');

        $this->assertInstanceOf(Conversation::class, $conversation);
        $this->assertSame('Здравствуйте, хочу обсудить занятие.', $result['message']->message);
        $this->assertNull($result['warning']);

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $student->id,
            'is_read' => false,
        ]);
    }

    public function test_it_marks_messages_as_read(): void
    {
        $tutor = User::factory()->create([
            'role' => 'tutor',
            'phone' => '+375293333333',
        ]);

        $student = User::factory()->create([
            'role' => 'student',
            'phone' => '+375294444444',
        ]);

        $service = app(ChatService::class);
        $conversation = $service->getOrCreateConversation($tutor->id, $student->id);
        $service->sendMessage($conversation->id, $student->id, 'Первое сообщение');
        $service->markAsRead($tutor->id, $conversation->id);

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $student->id,
            'is_read' => true,
        ]);
    }

    public function test_it_allows_free_contact_sharing_in_saas_model(): void
    {
        Notification::fake();

        $tutor = User::factory()->create([
            'role' => 'tutor',
            'phone' => '+375295555555',
        ]);

        $student = User::factory()->create([
            'role' => 'student',
            'phone' => '+375296666666',
        ]);

        $service = app(ChatService::class);
        $conversation = $service->getOrCreateConversation($tutor->id, $student->id);

        $result = $service->sendMessage($conversation->id, $student->id, 'Напишите мне на +375291234567');

        $this->assertNull($result['warning']);
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $student->id,
            'message' => 'Напишите мне на +375291234567',
        ]);
        $this->assertDatabaseMissing('messages', [
            'conversation_id' => $conversation->id,
            'is_system' => true,
        ]);
    }

    public function test_it_allows_contacts_after_first_booking(): void
    {
        Notification::fake();

        $tutor = User::factory()->create([
            'role' => 'tutor',
            'phone' => '+375291010101',
        ]);

        $student = User::factory()->create([
            'role' => 'student',
            'phone' => '+375292020202',
        ]);

        $conversation = Conversation::query()->create([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'last_message_at' => now(),
        ]);

        Lesson::query()->forceCreate([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'start_time' => now()->addDay(),
            'end_time' => now()->addDay()->addHour(),
            'duration_minutes' => 60,
            'price' => '40.00',
            'platform_commission' => '6.00',
            'net_amount' => '34.00',
            'status' => Lesson::STATUS_PENDING,
            'payment_status' => Lesson::PAYMENT_PAID,
        ]);

        $result = app(ChatService::class)->sendMessage($conversation->id, $student->id, 'Мой номер +375291234567');

        $this->assertNull($result['warning']);
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $student->id,
            'message' => 'Мой номер +375291234567',
        ]);
    }

    public function test_it_allows_sharing_payment_details_and_bank_cards(): void
    {
        Notification::fake();

        $tutor = User::factory()->create([
            'role' => 'tutor',
            'phone' => '+375291010101',
        ]);

        $student = User::factory()->create([
            'role' => 'student',
            'phone' => '+375292020202',
        ]);

        $conversation = Conversation::query()->create([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'last_message_at' => now(),
        ]);

        $result = app(ChatService::class)->sendMessage($conversation->id, $student->id, 'Номер карты: 9112 3800 2624 3678, счет BY20ALFA1234567890');

        $this->assertNull($result['warning']);
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $student->id,
            'message' => 'Номер карты: 9112 3800 2624 3678, счет BY20ALFA1234567890',
        ]);
        $this->assertDatabaseMissing('messages', [
            'conversation_id' => $conversation->id,
            'is_system' => true,
        ]);
    }

    public function test_it_reuses_existing_conversation_for_new_lessons_with_same_student(): void
    {
        $tutor = User::factory()->create(['role' => 'tutor']);
        $student = User::factory()->create(['role' => 'student']);

        $lesson1 = Lesson::query()->forceCreate([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'start_time' => now()->addDay(),
            'end_time' => now()->addDay()->addHour(),
            'duration_minutes' => 60,
            'price' => '40.00',
            'platform_commission' => '6.00',
            'net_amount' => '34.00',
            'status' => Lesson::STATUS_CONFIRMED,
            'payment_status' => Lesson::PAYMENT_PAID,
        ]);

        $lesson2 = Lesson::query()->forceCreate([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'start_time' => now()->addDays(2),
            'end_time' => now()->addDays(2)->addHour(),
            'duration_minutes' => 60,
            'price' => '40.00',
            'platform_commission' => '6.00',
            'net_amount' => '34.00',
            'status' => Lesson::STATUS_CONFIRMED,
            'payment_status' => Lesson::PAYMENT_PAID,
        ]);

        $service = app(ChatService::class);

        // Lesson 1
        $conv1 = $service->getOrCreateConversation($tutor->id, $student->id, $lesson1->id);
        $this->assertSame($lesson1->id, $conv1->lesson_id);

        // Lesson 2 with same student
        $conv2 = $service->getOrCreateConversation($tutor->id, $student->id, $lesson2->id);

        // Must be the EXACT same conversation ID, not a duplicate!
        $this->assertSame($conv1->id, $conv2->id);
        $this->assertSame($lesson2->id, $conv2->fresh()->lesson_id);

        // Count of conversations in DB must still be 1
        $this->assertSame(1, Conversation::query()->where('tutor_id', $tutor->id)->where('student_id', $student->id)->count());
    }

    public function test_it_does_not_penalize_tutor_for_sharing_contacts_in_saas_model(): void
    {
        Notification::fake();

        $admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'moderation@example.test',
        ]);

        $tutor = User::factory()->create([
            'role' => 'tutor',
            'phone' => '+375291010101',
        ]);

        TutorProfile::query()->create([
            'user_id' => $tutor->id,
            'headline' => 'Подготовка к ЦТ',
            'bio' => 'Опытный преподаватель',
            'subjects' => ['Математика'],
            'price_per_hour' => '40.00',
            'experience_years' => 5,
            'is_verified' => true,
            'verification_status' => 'approved',
            'legal_status' => 'npd',
        ]);

        $student = User::factory()->create([
            'role' => 'student',
            'phone' => '+375292020202',
        ]);

        $conversation = app(ChatService::class)->getOrCreateConversation($tutor->id, $student->id);

        foreach (range(1, 3) as $attempt) {
            app(ChatService::class)->sendMessage($conversation->id, $tutor->id, 'Мой telegram t.me/example и телефон +375291234567');
        }

        $profile = $tutor->tutorProfile()->firstOrFail();

        $this->assertSame(0, $profile->contact_bypass_attempts);
        $this->assertNull($profile->search_penalized_until);
        Notification::assertNotSentTo($admin, ChatBypassAttemptNotification::class);
    }

    public function test_messages_page_does_not_open_foreign_conversation(): void
    {
        $tutor = User::factory()->create([
            'role' => 'tutor',
            'phone' => '+375297777777',
        ]);

        $student = User::factory()->create([
            'role' => 'student',
            'phone' => '+375298888888',
        ]);

        $stranger = User::factory()->create([
            'role' => 'student',
            'phone' => '+375299999999',
        ]);

        $service = app(ChatService::class);
        $conversation = $service->getOrCreateConversation($tutor->id, $student->id);
        $service->sendMessage($conversation->id, $student->id, 'Секретное сообщение');

        Livewire::actingAs($stranger)
            ->test(MessagesPage::class, ['conversation' => $conversation->id])
            ->assertSet('conversationId', null)
            ->assertDontSee('Секретное сообщение');
    }

    public function test_viewing_tutor_profile_does_not_create_conversation_implicitly(): void
    {
        $tutor = User::factory()->create([
            'role' => 'tutor',
            'is_verified' => true,
        ]);

        $student = User::factory()->create([
            'role' => 'student',
        ]);

        $profile = TutorProfile::query()->create([
            'user_id' => $tutor->id,
            'headline' => 'Подготовка к ЦТ',
            'bio' => 'Опытный преподаватель',
            'subjects' => ['Математика'],
            'price_per_hour' => '40.00',
            'experience_years' => 5,
            'is_verified' => true,
            'verification_status' => 'approved',
            'legal_status' => 'npd',
        ]);

        $this->actingAs($student)
            ->get(route('tutors.show', $profile))
            ->assertOk();

        $this->assertDatabaseCount('conversations', 0);
    }

    public function test_user_can_close_conversation_to_return_to_list(): void
    {
        $tutor = User::factory()->create([
            'role' => 'tutor',
        ]);

        $student = User::factory()->create([
            'role' => 'student',
        ]);

        $service = app(ChatService::class);
        $conversation = $service->getOrCreateConversation($tutor->id, $student->id);

        Livewire::actingAs($tutor)
            ->test(MessagesPage::class, ['conversation' => $conversation->id])
            ->assertSet('conversationId', $conversation->id)
            ->call('closeConversation')
            ->assertSet('conversationId', null);
    }

    public function test_messages_page_optimistic_sending_and_delta_sync(): void
    {
        $tutor = User::factory()->create(['role' => 'tutor']);
        $student = User::factory()->create(['role' => 'student']);

        $service = app(ChatService::class);
        $conversation = $service->getOrCreateConversation($tutor->id, $student->id);

        $component = Livewire::actingAs($student)
            ->test(MessagesPage::class, ['conversation' => $conversation->id]);

        // 1. Send optimistic message
        $clientId = 'c_test_123';
        $result = $component->instance()->sendOptimisticMessage('Привет! Это оптимистичное сообщение.', $clientId);

        $this->assertNotNull($result);
        $this->assertSame($clientId, $result['client_id']);
        $this->assertNotNull($result['id']);
        $this->assertSame('Привет! Это оптимистичное сообщение.', $result['message']);

        // Check DB
        $this->assertDatabaseHas('messages', [
            'id' => $result['id'],
            'conversation_id' => $conversation->id,
            'message' => 'Привет! Это оптимистичное сообщение.',
        ]);

        // 2. Ping typing
        $component->instance()->pingTyping();
        $this->assertTrue(cache()->has("chat_typing_{$conversation->id}_{$student->id}"));

        // 3. Tutor checks delta messages
        $tutorComponent = Livewire::actingAs($tutor)
            ->test(MessagesPage::class, ['conversation' => $conversation->id]);

        $delta = $tutorComponent->instance()->checkNewMessages(0);
        $this->assertCount(1, $delta['messages']);
        $this->assertSame('Привет! Это оптимистичное сообщение.', $delta['messages'][0]['message']);
        $this->assertTrue($delta['is_typing']);
    }
}
