<?php

namespace App\Models;

use App\Services\EmailSenderService;
use Illuminate\Database\Eloquent\Model;

class TodoList extends Model
{
    protected $fillable = ['user_id'];

    protected $table = 'todo_lists';

    private ?EmailSenderService $emailSenderService = null;

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(Item::class, 'todo_list_id');
    }

    public function setEmailSenderService(EmailSenderService $emailSenderService): void
    {
        $this->emailSenderService = $emailSenderService;
    }

    public function add(Item $item): bool
    {
        if (! $this->user->isValid()) {
            return false;
        }

        $count = $this->items()->count();

        if ($count >= 10) {
            return false;
        }

        if ($this->items()->where('name', $item->name)->exists()) {
            return false;
        }

        if (strlen($item->content) > 1000) {
            return false;
        }

        $lastItem = $this->items()->latest('created_at')->first();
        if ($lastItem) {
            $diff = (new \DateTime)->diff(new \DateTime($lastItem->created_at));
            $minutes = ($diff->days * 24 * 60) + ($diff->h * 60) + $diff->i;
            if ($minutes < 30) {
                return false;
            }
        }

        $item->todo_list_id = $this->id;
        $this->saveItem($item);

        if ($count === 7) {
            $emailService = $this->emailSenderService ?? new EmailSenderService;
            $emailService->sendEmail($this->user);
        }

        return true;
    }

    public function saveItem(Item $item): void
    {
        throw new \RuntimeException('Not implemented - use a mock for testing');
    }
}
