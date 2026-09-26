<?php

namespace App\Console\Commands;

use App\Jobs\ProcessInboundEvent;
use App\Models\InboundEvent;
use Illuminate\Console\Command;

class ReplayWebhookEvent extends Command
{
    protected $signature = 'webhooks:replay {inbound_event_id}';

    protected $description = 'Переобработать inbound-событие по id';

    public function handle(): int
    {
        $event = InboundEvent::find((int) $this->argument('inbound_event_id'));

        if (!$event) {
            $this->error('Событие не найдено');
            return self::FAILURE;
        }

        if (!$event->signature_valid) {
            $this->error('Нельзя переобработать событие с невалидной подписью');
            return self::FAILURE;
        }

        $event->update(['status' => InboundEvent::STATUS_PENDING, 'processed_at' => null]);

        // dispatchSync намеренно: команда предполагается для ручного разового запуска
        // с консоли, где ожидание результата - это то, что нужно. Если обработка вдруг
        // окажется медленной (внешние вызовы в будущем адаптере), CLI просто подождёт,
        // это не веб-запрос с таймаутом.
        ProcessInboundEvent::dispatchSync($event->id);

        $this->info("Событие {$event->id} переобработано");
        return self::SUCCESS;
    }
}
