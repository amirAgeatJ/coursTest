<?php

namespace Tests\Unit;

use App\Models\Item;
use App\Models\TodoList;
use App\Models\User;
use App\Services\EmailSenderService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class TodoListTest extends TestCase
{
    use RefreshDatabase;

    public function test_save_item_leve_une_exception(): void
    {
        $list = new TodoList;
        $item = new Item;

        $this->expectException(\RuntimeException::class);

        $list->saveItem($item);
    }

    public function test_save_item_est_appele_lors_de_ladd(): void
    {
        $user = User::create([
            'firstname' => 'Jean',
            'lastname' => 'Dupont',
            'email' => 'jean@example.com',
            'password' => 'Password1',
            'birthdate' => '2000-01-01',
        ]);

        $realList = TodoList::create(['user_id' => $user->id]);

        // Partial mock: overrides only saveItem, everything else uses real Eloquent
        $list = Mockery::mock(TodoList::class.'[saveItem]');
        $list->setRawAttributes($realList->getAttributes());
        $list->exists = true;

        $item = new Item(['name' => 'Mon item', 'content' => 'Contenu']);

        $list->shouldReceive('saveItem')->once()->with($item);

        $result = $list->add($item);

        $this->assertTrue($result);
    }

    public function test_email_envoye_au_8eme_item_avec_mock(): void
    {
        $user = User::create([
            'firstname' => 'Jean',
            'lastname' => 'Dupont',
            'email' => 'jean@example.com',
            'password' => 'Password1',
            'birthdate' => '2000-01-01',
        ]);

        $realList = TodoList::create(['user_id' => $user->id]);

        for ($i = 1; $i <= 7; $i++) {
            $existing = new Item(['todo_list_id' => $realList->id, 'name' => "Item $i", 'content' => 'x']);
            $existing->created_at = Carbon::now()->subHours($i * 2);
            $existing->save();
        }

        // Partial mock: overrides saveItem (to avoid the RuntimeException)
        $list = Mockery::mock(TodoList::class.'[saveItem]');
        $list->setRawAttributes($realList->getAttributes());
        $list->exists = true;
        $list->shouldReceive('saveItem')->once();

        $mockEmail = Mockery::mock(EmailSenderService::class);
        $mockEmail->shouldReceive('sendEmail')
            ->once()
            ->with(Mockery::on(fn ($u) => $u->id === $user->id));

        $list->setEmailSenderService($mockEmail);

        $item = new Item(['name' => 'Item 8', 'content' => 'Contenu']);
        $result = $list->add($item);

        $this->assertTrue($result);
    }
}
