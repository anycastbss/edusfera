<x-filament-widgets::widget>
    <div class="student-card" style="margin-bottom: 24px;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px;">
            <div>
                <h3 style="margin: 0; font-size: 1.15rem; font-weight: 800; color: var(--stu-text-main); display: flex; align-items: center; gap: 8px;">
                    <span>👨‍🏫</span>
                    <span>Мои преподаватели</span>
                </h3>
                <p style="margin: 4px 0 0; font-size: 0.85rem; color: var(--stu-text-muted);">
                    Репетиторы, с которыми вы занимаетесь на платформе
                </p>
            </div>

            <a href="/tutors" class="student-chip-btn" style="font-size: 12px; padding: 4px 10px;">
                <span>+ Добавить</span>
            </a>
        </div>

        @if ($tutors->isEmpty())
            <div style="text-align: center; padding: 32px 16px; border: 1px dashed var(--stu-card-border); border-radius: 14px; background: var(--stu-surface-subtle);">
                <div style="font-size: 32px; margin-bottom: 8px;">🎓</div>
                <h4 style="margin: 0 0 6px; font-size: 1rem; font-weight: 800; color: var(--stu-text-main);">
                    У вас пока нет репетиторов
                </h4>
                <p style="margin: 0 auto 16px; font-size: 0.85rem; color: var(--stu-text-muted); max-width: 320px;">
                    Перейдите в каталог, чтобы подобрать проверенного преподавателя под вашу цель.
                </p>
                <a href="/tutors" class="student-chip-btn student-chip-btn--primary">
                    <span>🔍</span>
                    <span>Перейти в каталог репетиторов</span>
                </a>
            </div>
        @else
            <div style="display: flex; flex-direction: column; gap: 10px;">
                @foreach ($tutors as $tutor)
                    @php
                        $profile = $tutor->tutorProfile;
                        $tutorName = $tutor->name;
                        $tutorInitial = mb_strtoupper(mb_substr($tutorName, 0, 1));
                        $subjects = !empty($profile?->subjects) ? (is_array($profile->subjects) ? implode(', ', $profile->subjects) : (string)$profile->subjects) : 'Репетитор';
                    @endphp

                    <div class="student-list-item">
                        <div style="display: flex; align-items: center; gap: 14px; min-width: 0;">
                            <div class="student-tutor-avatar-wrap">
                                <div class="student-tutor-avatar-lg" style="width: 46px; height: 46px; font-size: 17px; background: linear-gradient(135deg, #7D39EB, #9047FF);">
                                    {{ $tutorInitial }}
                                </div>
                                <span class="student-tutor-online-badge"></span>
                            </div>
                            <div style="min-width: 0;">
                                <div style="display: flex; align-items: center; gap: 6px;">
                                    <span style="font-size: 0.98rem; font-weight: 800; color: var(--stu-text-main); line-height: 1.2;">
                                        {{ $tutorName }}
                                    </span>
                                    <span style="display: inline-flex; align-items: center; justify-content: center; width: 16px; height: 16px; border-radius: 9999px; background: #7D39EB; color: #FFFFFF; font-size: 10px; font-weight: 900;" title="Проверенный преподаватель Edusfera">✓</span>
                                </div>
                                <div style="font-size: 0.84rem; color: var(--stu-text-muted); margin-top: 3px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    {{ $subjects }}
                                </div>
                            </div>
                        </div>

                        <div style="display: flex; align-items: center; gap: 8px;">
                            <a href="/admin/messages?user={{ $tutor->id }}" class="student-chip-btn student-chip-btn--primary" style="padding: 7px 12px; font-size: 12px; border-radius: 12px;" title="Написать сообщение">
                                <span>💬 Чат</span>
                            </a>
                            <a href="/admin/lessons" class="student-chip-btn" style="padding: 7px 12px; font-size: 12px; border-radius: 12px;" title="История и расписание">
                                <span>Занятия</span>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-filament-widgets::widget>
