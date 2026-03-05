<?php
declare(strict_types=1);

return [
    // URL внешнего API расписания.
    // Если содержит {doctor_id}, он будет заменен на ID врача.
    // Иначе doctor_id будет добавлен query-параметром.
    'endpoint' => '',

    // Имя query-параметра для doctor_id, если нет {doctor_id} в endpoint.
    'doctor_id_param' => 'doctor_id',

    // Таймаут HTTP-запроса в секундах.
    'timeout_sec' => 15,

    // Дополнительные HTTP-заголовки.
    // Пример: ['Authorization: Bearer YOUR_TOKEN']
    'headers' => [],
];

