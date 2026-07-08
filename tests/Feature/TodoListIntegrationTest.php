<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\TodoList;
use App\Models\User;
use App\Services\EmailSenderService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class PersistingTodoList extends TodoList
{
    protected $table = 'todo_lists';

    public function saveItem(Item $item): void
    {
        $item->save();
    }
}

class TodoListIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private function validUser(): User
    {
        return User::create([
            'firstname' => 'Jean',
            'lastname' => 'Dupont',
            'email' => 'jean.dupont@example.com',
            'password' => 'Password1',
            'birthdate' => '2000-01-01',
        ]);
    }

    public function test_add_item_valide_est_sauvegarde_en_base(): void
    {
        $user = $this->validUser();
        $list = PersistingTodoList::create(['user_id' => $user->id]);
        $item = new Item(['name' => 'Mon item', 'content' => 'Contenu de test']);

        $result = $list->add($item);

        $this->assertTrue($result);
        $this->assertDatabaseHas('items', [
            'name' => 'Mon item',
            'todo_list_id' => $list->id,
        ]);
    }

    public function test_add_retourne_false_si_utilisateur_invalide(): void
    {
        $user = User::create([
            'firstname' => '',
            'lastname' => 'Dupont',
            'email' => 'jean.dupont@example.com',
            'password' => 'Password1',
            'birthdate' => '2000-01-01',
        ]);
        $list = PersistingTodoList::create(['user_id' => $user->id]);
        $item = new Item(['name' => 'Mon item', 'content' => 'Contenu']);

        $result = $list->add($item);

        $this->assertFalse($result);
        $this->assertDatabaseMissing('items', ['name' => 'Mon item']);
    }

    public function test_add_retourne_false_si_liste_a_deja_10_items(): void
    {
        $user = $this->validUser();
        $list = PersistingTodoList::create(['user_id' => $user->id]);

        for ($i = 1; $i <= 10; $i++) {
            $existing = new Item([
                'todo_list_id' => $list->id,
                'name' => "Item $i",
                'content' => 'Contenu',
            ]);
            $existing->created_at = Carbon::now()->subHours($i * 2);
            $existing->save();
        }

        $result = $list->add(new Item(['name' => 'Item 11', 'content' => 'Contenu']));

        $this->assertFalse($result);
        $this->assertEquals(10, $list->items()->count());
    }

    public function test_add_retourne_false_si_nom_duplique(): void
    {
        $user = $this->validUser();
        $list = PersistingTodoList::create(['user_id' => $user->id]);
        $existing = new Item(['todo_list_id' => $list->id, 'name' => 'Mon item', 'content' => 'Contenu']);
        $existing->created_at = Carbon::now()->subHour();
        $existing->save();

        $result = $list->add(new Item(['name' => 'Mon item', 'content' => 'Autre contenu']));

        $this->assertFalse($result);
        $this->assertEquals(1, $list->items()->count());
    }

    public function test_add_retourne_false_si_content_depasse_1000_caracteres(): void
    {
        $user = $this->validUser();
        $list = PersistingTodoList::create(['user_id' => $user->id]);
        $item = new Item(['name' => 'Mon item', 'content' => str_repeat('x', 1001)]);

        $result = $list->add($item);

        $this->assertFalse($result);
        $this->assertDatabaseMissing('items', ['name' => 'Mon item']);
    }

    public function test_add_retourne_false_si_dernier_item_ajoute_depuis_moins_de_30_minutes(): void
    {
        $user = $this->validUser();
        $list = PersistingTodoList::create(['user_id' => $user->id]);
        $existing = new Item(['todo_list_id' => $list->id, 'name' => 'Premier item', 'content' => 'Contenu']);
        $existing->created_at = Carbon::now()->subMinutes(10);
        $existing->save();

        $result = $list->add(new Item(['name' => 'Deuxième item', 'content' => 'Contenu']));

        $this->assertFalse($result);
    }

    public function test_add_accepte_nouvel_item_apres_30_minutes(): void
    {
        $user = $this->validUser();
        $list = PersistingTodoList::create(['user_id' => $user->id]);
        $existing = new Item(['todo_list_id' => $list->id, 'name' => 'Premier item', 'content' => 'Contenu']);
        $existing->created_at = Carbon::now()->subMinutes(31);
        $existing->save();

        $result = $list->add(new Item(['name' => 'Deuxième item', 'content' => 'Contenu']));

        $this->assertTrue($result);
        $this->assertDatabaseHas('items', [
            'name' => 'Deuxième item',
            'todo_list_id' => $list->id,
        ]);
    }

    public function test_add_premier_item_sans_contrainte_de_delai(): void
    {
        $user = $this->validUser();
        $list = PersistingTodoList::create(['user_id' => $user->id]);

        $result = $list->add(new Item(['name' => 'Premier item', 'content' => 'Contenu']));

        $this->assertTrue($result);
        $this->assertDatabaseHas('items', ['name' => 'Premier item']);
    }

    public function test_email_envoye_lors_de_lajout_du_8eme_item(): void
    {
        $user = $this->validUser();
        $list = PersistingTodoList::create(['user_id' => $user->id]);

        for ($i = 1; $i <= 7; $i++) {
            $existing = new Item(['todo_list_id' => $list->id, 'name' => "Item $i", 'content' => 'x']);
            $existing->created_at = Carbon::now()->subHours($i * 2);
            $existing->save();
        }

        $mockEmail = Mockery::mock(EmailSenderService::class);
        $mockEmail->shouldReceive('sendEmail')
            ->once()
            ->with(Mockery::on(fn ($u) => $u->id === $user->id));

        $list->setEmailSenderService($mockEmail);

        $result = $list->add(new Item(['name' => 'Item 8', 'content' => 'Contenu']));

        $this->assertTrue($result);
    }

    public function test_email_non_envoye_pour_les_autres_items(): void
    {
        $user = $this->validUser();
        $list = PersistingTodoList::create(['user_id' => $user->id]);

        $mockEmail = Mockery::mock(EmailSenderService::class);
        $mockEmail->shouldReceive('sendEmail')->never();

        $list->setEmailSenderService($mockEmail);

        $list->add(new Item(['name' => 'Premier item', 'content' => 'Contenu']));
    }
}
