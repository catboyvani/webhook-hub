<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>Журнал вебхуков</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body { font-family: -apple-system, Arial, sans-serif; margin: 0; padding: 24px; background: #f5f5f5; color: #222; }
        h1 { font-size: 20px; margin-bottom: 16px; }
        .filters { display: flex; gap: 8px; margin-bottom: 16px; flex-wrap: wrap; }
        .filters select, .filters input, .filters button { padding: 6px 8px; }
        table { width: 100%; border-collapse: collapse; background: #fff; }
        th, td { text-align: left; padding: 8px 10px; border-bottom: 1px solid #e0e0e0; font-size: 14px; }
        th { cursor: pointer; background: #fafafa; user-select: none; }
        tr:hover { background: #f9f9f9; cursor: pointer; }
        .badge { padding: 2px 8px; border-radius: 10px; font-size: 12px; }
        .badge-pending { background: #fff3cd; color: #856404; }
        .badge-processed { background: #d4edda; color: #155724; }
        .badge-failed { background: #f8d7da; color: #721c24; }
        .pagination { margin-top: 12px; display: flex; gap: 6px; }
        .pagination button { padding: 4px 10px; }
        .modal-backdrop { position: fixed; inset: 0; background: rgba(0,0,0,.4); display: none; align-items: center; justify-content: center; }
        .modal { background: #fff; width: 640px; max-width: 90vw; max-height: 80vh; overflow: auto; border-radius: 6px; padding: 20px; }
        .modal pre { background: #f5f5f5; padding: 10px; overflow: auto; font-size: 12px; white-space: pre-wrap; word-break: break-word; }
        .modal-actions { margin-top: 14px; display: flex; gap: 8px; align-items: center; }
        .modal-error { color: #b02a37; font-size: 13px; }
        .close-btn { float: right; cursor: pointer; }
    </style>
</head>
<body>
    <h1>Журнал входящих событий</h1>

    <div class="filters">
        <select id="filter-source">
            <option value="">Источник: все</option>
            @foreach ($sources as $source)
                <option value="{{ $source }}">{{ $source }}</option>
            @endforeach
        </select>
        <select id="filter-status">
            <option value="">Статус: все</option>
            @foreach ($statuses as $status)
                <option value="{{ $status }}">{{ $status }}</option>
            @endforeach
        </select>
        <input type="date" id="filter-date-from">
        <input type="date" id="filter-date-to">
        <button id="apply-filters">Применить</button>
    </div>

    <table>
        <thead>
            <tr>
                <th data-sort="source">Источник</th>
                <th data-sort="status">Статус</th>
                <th>External ID</th>
                <th data-sort="received_at">Получено</th>
                <th>Обработано</th>
            </tr>
        </thead>
        <tbody id="events-body"></tbody>
    </table>

    <div class="pagination" id="pagination"></div>

    <div class="modal-backdrop" id="modal-backdrop">
        <div class="modal">
            <span class="close-btn" id="modal-close">&times;</span>
            <div id="modal-body"></div>
        </div>
    </div>

    <script src="{{ asset('js/events.js') }}"></script>
</body>
</html>
