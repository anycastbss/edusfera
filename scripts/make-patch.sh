#!/usr/bin/env bash
set -e

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

OUTPUT_ZIP="${1:-edusfera-patch.zip}"
rm -f "$OUTPUT_ZIP"

echo "🔍 Поиск измененных рабочих файлов..."

FILES=()

while IFS= read -r file; do
    # Исключаем тесты, документацию, git, node_modules, storage, env, архивы, маркетинговые скриншоты, playwright
    if [[ "$file" =~ ^(tests/|docs/|scratch/|ad-assets/|public/ad-assets/|\.marketing/|\.env|\.git|\.codegraph|\.playwright-cli/|node_modules/|storage/) ]]; then
        continue
    fi
    if [[ "$file" =~ \.(tar\.gz|zip)$ ]]; then
        continue
    fi
    if [ -d "$file" ]; then
        while IFS= read -r subfile; do
            if [ -f "$subfile" ]; then
                FILES+=("$subfile")
            fi
        done < <(find "$file" -type f)
    elif [ -f "$file" ]; then
        FILES+=("$file")
    fi
done < <(git status --porcelain | awk '{print $2}')

# Добавляем все файлы из последнего коммита
while IFS= read -r commit_file; do
    if [[ "$commit_file" =~ ^(tests/|docs/|scratch/|ad-assets/|public/ad-assets/|\.marketing/|\.env|\.git|\.codegraph|\.playwright-cli/|node_modules/|storage/) ]]; then
        continue
    fi
    if [ -f "$commit_file" ]; then
        FILES+=("$commit_file")
    fi
done < <(git diff-tree --no-commit-id --name-only -r HEAD)

# Дополнительно гарантируем ключевые файлы промокодов, миграций, ИИ и кабинетов
CRITICAL_FILES=(
    "docs/EDUSFERA_DESIGN_SYSTEM.md"
    "docs/PROJECT_CONTEXT_AND_WORK_LOG.md"
    "docs/2026-10-16_otchet_o_vypolnennoj_rabote.md"
    "database/migrations/2026_10_16_170000_create_user_audit_logs_table.php"
    "app/Models/UserAuditLog.php"
    "app/Http/Controllers/LessonSchedulePrintController.php"
    "resources/views/lessons/print-weekly.blade.php"
    "app/Http/Controllers/CouncilCertificatePrintController.php"
    "resources/views/council/print-certificate.blade.php"
    "app/Services/PlatformConfigService.php"
    "app/Filament/SiteAdmin/Pages/SystemTopologyPage.php"
    "resources/views/filament/site-admin/pages/system-topology-page.blade.php"
    "app/Filament/SiteAdmin/Pages/SystemLogsPage.php"
    "resources/views/filament/site-admin/pages/system-logs-page.blade.php"
    "app/Filament/SiteAdmin/Pages/PlatformSettingsPage.php"
    "resources/views/filament/site-admin/pages/platform-settings-page.blade.php"
    "app/Filament/SiteAdmin/Pages/SystemOperationsPage.php"
    "resources/views/filament/site-admin/pages/system-operations-page.blade.php"
    "app/Filament/Pages/MessagesPage.php"
    "app/Filament/Pages/TutorAvailabilityPage.php"
    "app/Filament/Resources/TutorProfileResource.php"
    "app/Services/HelpCallbackService.php"
    "app/Services/WhiteLabelService.php"
    "resources/views/partials/filament-tutor-help-button.blade.php"
    "database/migrations/2026_10_15_070000_add_privacy_and_white_label_to_tutor_profiles.php"
    "database/migrations/2026_10_15_070100_add_ai_generations_tracking_to_subscriptions.php"
    "database/migrations/2026_10_15_080000_add_methodical_council_to_tutor_profiles.php"
    "app/Models/TutorProfile.php"
    "app/Filament/Widgets/TutorHeroWidget.php"
    "resources/views/filament/widgets/tutor-hero-widget.blade.php"
    "resources/views/filament/widgets/partials/tutor-dashboard-styles.blade.php"
    "resources/views/for-tutors.blade.php"
    "resources/views/catalog/index.blade.php"
    "resources/views/catalog/show.blade.php"
    "app/Http/Controllers/Api/TutorOnboardingApiController.php"
    "resources/views/auth/onboarding.blade.php"
    "resources/js/components/TutorOnboarding/TutorOnboardingFlow.jsx"
    "database/migrations/2026_10_11_185000_seed_edusfera2026_promo_code.php"
    "app/Services/Ai/GeminiService.php"
    "app/Services/Classroom/AiService.php"
    "app/Http/Controllers/AiCopilotController.php"
    "resources/views/partials/ai-copilot-widget.blade.php"
    "app/Filament/Pages/TutorAiAssistantPage.php"
    "resources/views/filament/pages/tutor-ai-assistant-page.blade.php"
    "resources/js/app.jsx"
    "resources/css/app.css"
    "resources/css/floating-copilot.css"
    "resources/js/components/FloatingAiCopilot/FloatingAiCopilot.jsx"
    "resources/js/components/TutorAiAssistant/TutorAiAssistant.jsx"
    "resources/js/components/TutorAiAssistant/AiFormattedOutput.jsx"
    "resources/js/components/TutorAiAssistant/HistoryDrawer.jsx"
    "resources/js/components/TutorAiAssistant/SettingsDrawer.jsx"
    "resources/js/components/TutorAiAssistant/LessonPlanTab.jsx"
    "resources/js/components/TutorAiAssistant/QuizGeneratorTab.jsx"
    "resources/js/components/TutorAiAssistant/MethodistChatTab.jsx"
    "resources/js/components/TutorAiAssistant/PromptLibraryModal.jsx"
    "app/Filament/Pages/TutorNpdPage.php"
    "resources/views/filament/pages/tutor-npd-page.blade.php"
    "app/Filament/Widgets/TutorFinanceOverview.php"
    "app/Filament/Widgets/CommissionLadderWidget.php"
    "app/Filament/Pages/WalletPage.php"
    "app/Filament/Resources/TransactionResource.php"
    "app/Filament/Resources/LessonResource.php"
    "app/Services/PromoCodeService.php"
    "app/Filament/Pages/TutorSubscriptionPage.php"
    "app/Filament/Resources/PromoCodeResource.php"
    "app/Filament/Resources/PromoCodeResource/Pages/CreatePromoCode.php"
    "app/Filament/Resources/PromoCodeResource/Pages/EditPromoCode.php"
    "app/Filament/Resources/PromoCodeResource/Pages/ListPromoCodes.php"
    "app/Models/PromoCode.php"
    "app/Models/PromoCodeUsage.php"
    "app/Domain/Subscription/Models/SubscriptionInvoice.php"
    "app/Domain/Subscription/Models/Subscription.php"
    "app/Domain/Subscription/Enums/SubscriptionPlan.php"
    "app/Domain/Subscription/Services/SubscriptionService.php"
    "app/Http/Middleware/EnsureTutorHasActiveSubscription.php"
    "app/Services/Payment/AlfaBankPaymentGateway.php"
    "app/Providers/AppServiceProvider.php"
    "database/migrations/2026_09_13_142311_add_is_onboarded_to_subscriptions_table.php"
    "database/migrations/2026_09_13_193000_add_npd_fields_to_lessons_table.php"
    "database/migrations/2026_09_18_170519_create_promo_codes_and_usages_tables.php"
    "database/migrations/2026_09_18_210500_add_subscription_grant_fields_to_promo_codes_table.php"
    "resources/views/filament/pages/tutor-subscription-page.blade.php"
    "public/migrate.php"
    "public/manifest.json"
    "public/manifest.webmanifest"
    "public/sw.js"
    "public/offline.html"
    "public/apple-touch-icon.png"
    "public/icons/apple-touch-icon.png"
    "public/icons/icon-192x192.png"
    "public/icons/icon-512x512.png"
    "public/icons/icon-maskable-192x192.png"
    "public/icons/icon-maskable-512x512.png"
    "resources/views/partials/pwa-meta.blade.php"
    "resources/views/partials/pwa-prompt.blade.php"
    "resources/views/partials/cookie-banner.blade.php"
    "resources/views/legal/cookie-policy.blade.php"
    "app/Providers/Filament/AdminPanelProvider.php"
    "app/Providers/Filament/SiteAdminPanelProvider.php"
    "app/Http/Controllers/PwaController.php"
    "app/Models/PushSubscription.php"
    "database/migrations/2026_09_19_180000_create_push_subscriptions_table.php"
    "nginx/nginx.conf"
    "nginx/nginx-bare-metal.conf"
    "config/classroom.php"
    "app/Services/Classroom/LiveKitService.php"
    "app/Console/Commands/PrepareClassroomTestCommand.php"
    "lang/ru/validation.php"
    "lang/en/validation.php"
    "resources/views/partials/filament-mobile-bottom-nav.blade.php"
    "resources/views/partials/accessibility-font-toggle.blade.php"
    "resources/views/partials/filament-mobile-ergonomics.blade.php"
    "resources/views/filament/resources/lesson-resource/components/mobile-lesson-agenda.blade.php"
    "resources/views/filament/resources/lesson-resource/pages/list-lessons.blade.php"
    "app/Filament/Pages/TutorSettingsPage.php"
    "resources/views/filament/pages/tutor-settings-page.blade.php"
    "app/Models/PaymentWebhookLog.php"
    "database/migrations/2026_09_19_213000_rename_webpay_webhook_logs_table.php"
    "app/Http/Controllers/ClassroomController.php"
    "app/Filament/Pages/MicroservicesDashboard.php"
    "resources/views/filament/pages/microservices-dashboard.blade.php"
    "resources/js/classroom.js"
    "resources/js/classroom-app.jsx"
    "resources/js/components/Classroom/ClassroomApp.jsx"
    "resources/js/components/Classroom/ClassroomOnboardingTour.jsx"
    "resources/views/classroom/show.blade.php"
    "resources/css/classroom.css"
    "resources/js/excalidraw-wrapper.jsx"
    "routes/web.php"
    "resources/views/filament/widgets/partials/student-dashboard-styles.blade.php"
    "app/Filament/Pages/Dashboard.php"
    "app/Http/Middleware/TrackActiveSession.php"
    "app/Filament/Widgets/AdminWelcomeWidget.php"
    "resources/views/filament/widgets/admin-welcome-widget.blade.php"
    "app/Filament/Widgets/StudentWelcomeWidget.php"
    "app/Filament/Widgets/StudentUpcomingLessonsWidget.php"
    "app/Filament/Widgets/StudentTutorsWidget.php"
    "resources/views/filament/widgets/student-welcome-widget.blade.php"
    "resources/views/filament/widgets/student-upcoming-lessons-widget.blade.php"
    "app/Models/Lesson.php"
    "app/Http/Controllers/LessonBookingController.php"
    "app/Services/BookingService.php"
    "app/Notifications/LessonBookedTutorNotification.php"
    "app/Notifications/LessonBookedStudentNotification.php"
    "resources/views/catalog/show.blade.php"
    "app/Filament/Resources/LessonRequestResource.php"
    "app/Providers/RouteServiceProvider.php"
    "database/seeders/PlatformTrainingArticlesSeeder.php"
    "resources/views/news/show.blade.php"
    "resources/views/news/index.blade.php"
    "database/migrations/2026_10_20_170000_create_platform_telemetry_table.php"
    "app/Models/PlatformTelemetry.php"
    "app/Http/Middleware/CapturePlatformTelemetry.php"
    "app/Http/Controllers/Api/TelemetryEventController.php"
    "app/Console/Commands/PruneTelemetryCommand.php"
    "app/Filament/SiteAdmin/Pages/SystemTelemetryPage.php"
    "resources/views/filament/site-admin/pages/system-telemetry-page.blade.php"
    "resources/views/partials/telemetry-beacon.blade.php"
    "bootstrap/app.php"
    "config/services.php"
    "app/Http/Controllers/Mobile/MobileCabinetController.php"
    "app/Traits/DetectsMobileDevice.php"
    "app/Http/Middleware/RedirectMobileToCabinetMiddleware.php"
    "app/Http/Controllers/Auth/AuroraAuthController.php"
    "app/Http/Controllers/Auth/SocialAuthController.php"
    "resources/views/auth/select-role.blade.php"
    "app/Filament/Pages/Auth/Login.php"
    "resources/views/mobile/cabinet.blade.php"
    "resources/views/cabinet/index.blade.php"
    "resources/js/components/MobileCabinet/UserAvatar.jsx"
    "resources/js/components/MobileCabinet/DesktopSidebar.jsx"
    "resources/js/components/MobileCabinet/DesktopHeader.jsx"
    "resources/js/components/MobileCabinet/CreateLessonBottomSheet.jsx"
    "resources/js/components/MobileCabinet/LessonDetailBottomSheet.jsx"
    "resources/js/components/MobileCabinet/AvailabilityBottomSheet.jsx"
    "resources/js/components/MobileCabinet/MobileOnboardingTour.jsx"
    "resources/js/components/MobileCabinet/MobileHeader.jsx"
    "resources/js/components/MobileCabinet/MobileBottomNav.jsx"
    "resources/js/components/MobileCabinet/DesktopSidebar.jsx"
    "resources/js/components/MobileCabinet/DesktopHeader.jsx"
    "resources/js/components/MobileCabinet/DesktopOnboardingTour.jsx"
    "resources/js/components/MobileCabinet/MobileOnboardingTour.jsx"
    "resources/js/components/MobileCabinet/TabLessons.jsx"
    "resources/js/components/MobileCabinet/TabChat.jsx"
    "resources/js/components/MobileCabinet/TabFinance.jsx"
    "resources/js/components/MobileCabinet/TabProfile.jsx"
    "resources/js/components/MobileCabinet/MobileCabinetApp.jsx"
    "resources/js/components/MobileCabinet/SubscriptionPlansModal.jsx"
    "resources/js/components/MobileCabinet/GoogleCalendarView.jsx"
    "resources/js/components/MobileCabinet/NotificationsSheet.jsx"
    "resources/js/components/MobileCabinet/TabAiAgent.jsx"
    "resources/js/components/MobileCabinet/OfflineStatusBar.jsx"
    "resources/js/components/MobileCabinet/TabStudentLessons.jsx"
    "resources/js/components/MobileCabinet/TabStudentHomework.jsx"
    "resources/js/components/MobileCabinet/TabStudentWallet.jsx"
    "resources/views/partials/public-header.blade.php"
    "resources/views/partials/site-nav.blade.php"
    "app/Http/Controllers/GeneratorCheckoutController.php"
    "app/Models/GeneratorOrder.php"
    "database/migrations/2026_11_06_090239_create_generator_orders_table.php"
    "resources/js/components/AntiCheatGeneratorLanding.jsx"
    "resources/views/generator.blade.php"
    "resources/views/generator/pdf-template.blade.php"
    "resources/views/generator/pdf-standard-template.blade.php"
    "app/Services/Exam/ExamDocumentParser.php"
    "app/Services/Exam/PythonExamGeneratorService.php"
    "resources/views/generator/success.blade.php"
    "resources/views/payments/generator-hosted.blade.php"
)

# Добавляем модуль edusfera_pdf_generator
if [ -d "edusfera_pdf_generator" ]; then
    while IFS= read -r pdfgenfile; do
        if [[ ! "$pdfgenfile" =~ (\.venv/|__pycache__/|output/|\.DS_Store) ]] && [ -f "$pdfgenfile" ]; then
            FILES+=("$pdfgenfile")
        fi
    done < <(find edusfera_pdf_generator -type f)
fi

# Добавляем все модульные компоненты генератора
if [ -d "resources/js/components/generator" ]; then
    while IFS= read -r genfile; do
        if [ -f "$genfile" ]; then
            FILES+=("$genfile")
        fi
    done < <(find resources/js/components/generator -type f)
fi

# Добавляем скриншоты статей
if [ -d "public/images/articles" ]; then
    while IFS= read -r imgfile; do
        if [ -f "$imgfile" ]; then
            FILES+=("$imgfile")
        fi
    done < <(find public/images/articles -type f)
fi

for cf in "${CRITICAL_FILES[@]}"; do
    if [ -f "$cf" ]; then
        FILES+=("$cf")
    fi
done

# Добавляем скомпилированные ассеты из public/build, если они есть
if [ -d "public/build" ]; then
    while IFS= read -r bfile; do
        if [ -f "$bfile" ]; then
            FILES+=("$bfile")
        fi
    done < <(find public/build -type f)
fi

# Удаляем дубликаты
UNIQUE_FILES=($(printf "%s\n" "${FILES[@]}" | sort -u))

echo "📦 Упаковка ${#UNIQUE_FILES[@]} файлов в легковесный патч $OUTPUT_ZIP..."
zip -q -9 "$OUTPUT_ZIP" "${UNIQUE_FILES[@]}"

TAR_GZ="${OUTPUT_ZIP%.*}.tar.gz"
tar -czf "$TAR_GZ" "${UNIQUE_FILES[@]}"

# Гарантируем копирование в папку public/ для прямого доступа через migrate.php
cp -f "$OUTPUT_ZIP" "public/$OUTPUT_ZIP"
cp -f "$TAR_GZ" "public/$TAR_GZ"

echo "✅ Легковесный архив обновлений готов (в корне и в public/):"
ls -lh "$OUTPUT_ZIP" "public/$OUTPUT_ZIP" "$TAR_GZ" "public/$TAR_GZ"
