<?php

namespace App\Services\Contacts;

use App\Models\Contact;
use Illuminate\Database\QueryException;

class ContactResolver
{
    /**
     * Резолвит контакт по телефону или email, создавая его при отсутствии.
     *
     * Намеренное решение: если контакт найден по телефону, а пришедший email отличается
     * от уже сохранённого (или наоборот), существующее значение не перезаписывается -
     * только пустые поля дозаполняются. Смена email/телефона у контакта - это отдельная
     * операция, которую вебхук сам по себе решать не должен.
     */
    public function resolve(?string $phone, ?string $email, ?string $name = null): ?Contact
    {
        if (!$phone && !$email) {
            return null;
        }

        try {
            $contact = $this->findExisting($phone, $email);

            if (!$contact) {
                $contact = Contact::create(['phone' => $phone, 'email' => $email, 'name' => $name]);
            }
        } catch (QueryException $e) {
            // Гонка: два воркера одновременно не нашли контакт и оба пытаются его создать.
            // Один упадёт на unique(phone)/unique(email) - тогда просто ищем ещё раз
            // вместо того чтобы ронять джобу и полагаться на retry с задержкой.
            if (!$this->isUniqueConstraintViolation($e)) {
                throw $e;
            }

            $contact = $this->findExisting($phone, $email);

            if (!$contact) {
                throw $e;
            }
        }

        return $this->fillMissingFields($contact, $phone, $email, $name);
    }

    private function findExisting(?string $phone, ?string $email): ?Contact
    {
        return Contact::query()
            ->when($phone, fn ($q) => $q->orWhere('phone', $phone))
            ->when($email, fn ($q) => $q->orWhere('email', $email))
            ->first();
    }

    private function fillMissingFields(Contact $contact, ?string $phone, ?string $email, ?string $name): Contact
    {
        $dirty = false;

        if ($phone && !$contact->phone) {
            $contact->phone = $phone;
            $dirty = true;
        }

        if ($email && !$contact->email) {
            $contact->email = $email;
            $dirty = true;
        }

        if ($name && !$contact->name) {
            $contact->name = $name;
            $dirty = true;
        }

        if ($dirty) {
            $contact->save();
        }

        return $contact;
    }

    private function isUniqueConstraintViolation(QueryException $e): bool
    {
        // SQLSTATE 23000 - integrity constraint violation, общий код и для MySQL, и для SQLite.
        return $e->getCode() === '23000';
    }
}
