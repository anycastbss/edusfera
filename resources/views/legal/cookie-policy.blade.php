@extends('legal.layout')

@section('title', 'Политика в отношении обработки файлов cookie | Edusfera')
@section('heading', 'Политика в отношении обработки файлов cookie')
@section('updated_at', '2026 г.')
@section('subtitle', 'Порядок, цели и правовые основания использования файлов cookie на платформе Edusfera.by в соответствии с Законом Республики Беларусь № 99-З «О защите персональных данных»')

@section('content')
    <p class="text-base leading-relaxed text-gray-700 mb-6">
        Настоящая Политика использования файлов cookie (далее — <strong>«Политика»</strong>) разработана в соответствии с <strong>Законом Республики Беларусь от 7 мая 2021 г. № 99-З «О защите персональных данных»</strong> и разъяснениями Национального центра защиты персональных данных Республики Беларусь (НЦЗПД). Документ определяет порядок и цели сбора, хранения и обработки файлов cookie обществом с ограниченной ответственностью «Эдусфера» при посещении пользователями веб-сайта <a href="{{ route('home') }}">edusfera.by</a>.
    </p>

    <h2>1. Общие сведения об Операторе</h2>
    <p>Оператором обработки персональных данных, включая данные, получаемые посредством файлов cookie, является:</p>
    <ul>
        <li><strong>Наименование:</strong> Общество с ограниченной ответственностью «Эдусфера» (ООО «Эдусфера»);</li>
        <li><strong>УНП:</strong> 192854899;</li>
        <li><strong>Государственная регистрация:</strong> зарегистрировано Минским горисполкомом 04.05.2026 г.;</li>
        <li><strong>Юридический адрес:</strong> 220100, Республика Беларусь, г. Минск, ул. Веры Хоружей, д. 6А, пом. 29;</li>
        <li><strong>Электронная почта для обращений:</strong> <a href="mailto:edusferaby@gmail.com">edusferaby@gmail.com</a>;</li>
        <li><strong>Телефон горячей линии:</strong> <a href="tel:+375295190821">+375 (29) 519-08-21</a>.</li>
    </ul>

    <h2>2. Что такое файлы cookie и для чего они используются</h2>
    <p>
        <strong>Файлы cookie (куки)</strong> — это небольшие текстовые фрагменты данных, отправляемые веб-сервером и сохраняемые на устройстве пользователя (компьютере, смартфоне, планшете) при посещении страниц сайта. При повторном посещении веб-сайт считывает эти файлы для распознавания браузера пользователя.
    </p>
    <p>
        ООО «Эдусфера» использует файлы cookie для:
    </p>
    <ul>
        <li>Обеспечения стабильного и безопасного функционирования веб-платформы, авторизации пользователей и защиты от межсайтовой подделки запросов (CSRF);</li>
        <li>Сохранения пользовательских настроек (выбранная роль, параметры интерфейса, звуковые уведомления интерактивного класса);</li>
        <li>Сбора обобщённой статистической информации о взаимодействии с платформой для повышения скорости загрузки страниц и улучшения интерфейса;</li>
        <li>Обеспечения корректной работы сервисов интернет-эквайринга ЗАО «Альфа-Банк» и 3D-Secure 2.0 при оплате занятий.</li>
    </ul>

    <h2>3. Классификация используемых файлов cookie</h2>
    <p>На сайте edusfera.by используются следующие категории файлов cookie:</p>

    <h3>3.1. Технические (строго необходимые) файлы cookie</h3>
    <p>
        Данные файлы необходимы для надлежащей работы ключевых функций платформы (вход в личный кабинет, навигация, безопасность транзакций, работа видеосвязи и интерактивной доски). 
        <strong>В соответствии с законодательством Республики Беларусь на обработку технических файлов cookie согласие пользователя не требуется</strong>, так как без них функционирование сервиса технически невозможно. Отключение данных файлов в браузере может привести к неработоспособности сайта.
    </p>

    <h3>3.2. Функциональные файлы cookie</h3>
    <p>
        Позволяют запоминать персональные параметры и предпочтения пользователя (например, факт закрытия информационных уведомлений, статус согласия на cookie, настройки отображения расписания). Они активируются только с вашего предварительного согласия.
    </p>

    <h3>3.3. Аналитические (статистические) файлы cookie</h3>
    <p>
        Используются для сбора обезличенной аналитической информации (количество просмотров страниц, источники перехода, средняя длительность сессии, популярные разделы каталога). Эти файлы помогают оптимизировать работу платформы и устранять технические ошибки. Обработка аналитических cookies осуществляется исключительно на основании добровольного согласия пользователя.
    </p>

    <h2>4. Перечень файлов cookie, используемых на платформе</h2>
    <div class="my-6 overflow-x-auto border border-gray-200 rounded-xl shadow-xs">
        <table class="w-full text-xs text-left border-collapse">
            <thead>
                <tr class="bg-violet-50/70 text-gray-900 border-b border-gray-200">
                    <th class="p-3 font-bold">Имя файла cookie</th>
                    <th class="p-3 font-bold">Тип / Категория</th>
                    <th class="p-3 font-bold">Провайдер</th>
                    <th class="p-3 font-bold">Назначение</th>
                    <th class="p-3 font-bold">Срок хранения</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 text-gray-600">
                <tr>
                    <td class="p-3 font-mono font-semibold text-gray-800">laravel_session</td>
                    <td class="p-3"><span class="px-2 py-0.5 rounded bg-slate-100 text-slate-700 font-semibold">Технический</span></td>
                    <td class="p-3">edusfera.by</td>
                    <td class="p-3">Идентификация текущей защищённой сессии пользователя на сервере</td>
                    <td class="p-3">До закрытия браузера (сессионный)</td>
                </tr>
                <tr>
                    <td class="p-3 font-mono font-semibold text-gray-800">XSRF-TOKEN</td>
                    <td class="p-3"><span class="px-2 py-0.5 rounded bg-slate-100 text-slate-700 font-semibold">Технический</span></td>
                    <td class="p-3">edusfera.by</td>
                    <td class="p-3">Защита от межсайтовой подделки запросов (Cross-Site Request Forgery)</td>
                    <td class="p-3">2 часа</td>
                </tr>
                <tr>
                    <td class="p-3 font-mono font-semibold text-gray-800">edusfera_cookie_consent</td>
                    <td class="p-3"><span class="px-2 py-0.5 rounded bg-violet-50 text-violet-700 font-semibold">Функциональный</span></td>
                    <td class="p-3">edusfera.by (localStorage)</td>
                    <td class="p-3">Хранение выбора пользователя относительно категорий файлов cookie</td>
                    <td class="p-3">12 месяцев</td>
                </tr>
                <tr>
                    <td class="p-3 font-mono font-semibold text-gray-800">edusfera_active_role</td>
                    <td class="p-3"><span class="px-2 py-0.5 rounded bg-violet-50 text-violet-700 font-semibold">Функциональный</span></td>
                    <td class="p-3">edusfera.by (localStorage)</td>
                    <td class="p-3">Запоминание активного профиля мульти-аккаунта (ученик, родитель, репетитор)</td>
                    <td class="p-3">12 месяцев</td>
                </tr>
                <tr>
                    <td class="p-3 font-mono font-semibold text-gray-800">_ym_uid, _ym_d</td>
                    <td class="p-3"><span class="px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 font-semibold">Аналитический</span></td>
                    <td class="p-3">Яндекс.Метрика</td>
                    <td class="p-3">Анонимная статистика посещаемости и взаимодействия со страницами</td>
                    <td class="p-3">12 месяцев</td>
                </tr>
                <tr>
                    <td class="p-3 font-mono font-semibold text-gray-800">_ga, _ga_*</td>
                    <td class="p-3"><span class="px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 font-semibold">Аналитический</span></td>
                    <td class="p-3">Google Analytics 4</td>
                    <td class="p-3">Обезличенный подсчёт уникальных сессий и стабильности интерфейса</td>
                    <td class="p-3">12 месяцев</td>
                </tr>
            </tbody>
        </table>
    </div>

    <h2>5. Управление согласием и отзыв согласия</h2>
    <p>
        При первом посещении сайта пользователю отображается всплывающий баннер, позволяющий предоставить согласие на использование всех категорий файлов cookie либо ограничить использование только технически необходимыми.
    </p>
    <p>
        Вы имеете право в любой момент изменить свой выбор или полностью отозвать ранее предоставленное согласие одним из следующих способов:
    </p>
    <ul>
        <li>
            <strong>Через интерфейс сайта:</strong> нажмите кнопку ниже, чтобы сбросить сохранённые настройки и вызвать баннер выбора заново:
            <div class="my-4 not-prose">
                <button type="button" onclick="if(window.__openCookieBanner){window.__openCookieBanner();}else{localStorage.removeItem('edusfera_cookie_consent');window.location.reload();}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-900 text-white hover:bg-slate-800 font-semibold text-xs shadow-sm transition-all cursor-pointer">
                    <svg class="w-4 h-4 text-[#C6FF33]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path></svg>
                    <span>Изменить настройки файлов cookie</span>
                </button>
            </div>
        </li>
        <li>
            <strong>Через настройки вашего интернет-браузера:</strong> вы можете заблокировать или удалить сохранённые файлы cookie в настройках конфиденциальности используемого браузера:
            <ul class="mt-2 space-y-1">
                <li><a href="https://support.google.com/chrome/answer/95647" target="_blank" rel="noopener">Инструкция для Google Chrome</a></li>
                <li><a href="https://support.apple.com/guide/safari/manage-cookies-sfri11471/mac" target="_blank" rel="noopener">Инструкция для Apple Safari</a></li>
                <li><a href="https://support.mozilla.org/kb/enhanced-tracking-protection-firefox-desktop" target="_blank" rel="noopener">Инструкция для Mozilla Firefox</a></li>
                <li><a href="https://support.microsoft.com/microsoft-edge/delete-cookies-in-microsoft-edge-63947406-40ac-c3b8-57b9-2a946a29ae09" target="_blank" rel="noopener">Инструкция для Microsoft Edge</a></li>
                <li><a href="https://yandex.ru/support/browser/personal-data-protection/cookies.html" target="_blank" rel="noopener">Инструкция для Яндекс.Браузера</a></li>
            </ul>
        </li>
    </ul>

    <h2>6. Последствия отказа от файлов cookie</h2>
    <p>
        Отказ от аналитических и функциональных файлов cookie не влияет на возможность использования сайта и доступа к материалам, однако сервис не сможет сохранять ваши индивидуальные предпочтения.
    </p>
    <p>
        Полная блокировка технических cookies на уровне браузера приведёт к невозможности авторизации в личном кабинете, бронирования занятий и проведения уроков в виртуальном классе.
    </p>

    <h2>7. Контакты и направление обращений</h2>
    <p>
        По всем вопросам, связанным с обработкой персональных данных и использованием файлов cookie, вы можете обратиться к уполномоченному лицу ООО «Эдусфера» по адресу электронной почты <a href="mailto:edusferaby@gmail.com">edusferaby@gmail.com</a> либо направить письменное заявление по адресу: <strong>220100, Республика Беларусь, г. Минск, ул. Веры Хоружей, д. 6А, пом. 29</strong>.
    </p>
@endsection
