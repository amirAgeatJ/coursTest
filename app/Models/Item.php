<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $fillable = ['todo_list_id', 'name', 'content'];

    public function todoList()
    {
        return $this->belongsTo(TodoList::class);
    }
}
