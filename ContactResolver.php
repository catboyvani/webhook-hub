<?php

namespace App\Services\Contacts;

use App\Models\Contact;

class ContactResolver
{
    public function resolve(?string $phone, ?string $email, ?string $name = null): ?Contact
    {
        if (!$phone && !$email) {
            return null;
        }

        $contact = Contact::query()
            ->when($phone, fn ($q) => $q->orWhere('phone', $phone))
            ->when($email, fn ($q) => $q->orWhere('email', $email))
            ->first();

        if (!$contact) {
            return Contact::create(['phone' => $phone, 'email' => $email, 'name' => $name]);
        }

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
}