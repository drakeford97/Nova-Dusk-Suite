<?php

namespace Laravel\Nova\Tests\Browser;

use App\Models\Role;
use App\Models\User;
use Laravel\Dusk\Browser;
use Laravel\Nova\Tests\Browser\Components\IndexComponent;
use Laravel\Nova\Tests\DuskTestCase;

class AttachTest extends DuskTestCase
{
    /**
     * @test
     */
    public function resource_can_be_attached()
    {
        $this->setupLaravel();

        $role = factory(Role::class)->create();

        $this->browse(function (Browser $browser) use ($role) {
            $browser->loginAs(User::find(1))
                    ->visit(new Pages\Detail('users', 1))
                    ->waitFor('@roles-index-component', 10)
                    ->within(new IndexComponent('roles'), function ($browser) {
                        $browser->click('@attach-button');
                    })
                    ->on(new Pages\Attach('users', 1, 'roles'))
                    ->waitFor('.content form', 10)
                    ->selectAttachable($role->id)
                    ->clickAttach();

            $this->assertDatabaseHas('role_user', [
                'user_id' => '1',
                'role_id' => '1',
                'notes' => null,
            ]);

            $browser->blank();
        });
    }

    /**
     * @test
     */
    public function fields_on_intermediate_table_should_be_stored()
    {
        $this->setupLaravel();

        $role = factory(Role::class)->create();

        $this->browse(function (Browser $browser) use ($role) {
            $browser->loginAs(User::find(1))
                    ->visit(new Pages\Detail('users', 1))
                    ->waitFor('@roles-index-component', 10)
                    ->within(new IndexComponent('roles'), function ($browser) {
                        $browser->click('@attach-button');
                    })
                    ->on(new Pages\Attach('users', 1, 'roles'))
                    ->waitFor('.content form', 10)
                    ->selectAttachable($role->id)
                    ->type('@notes', 'Test Notes')
                    ->clickAttach()
                    ->waitFor('[dusk="roles-index-component"] table', 60);

            $this->assertDatabaseHas('role_user', [
                'user_id' => '1',
                'role_id' => '1',
                'notes' => 'Test Notes',
            ]);

            $browser->blank();
        });
    }

    /**
     * @test
     */
    public function validation_errors_are_displayed()
    {
        $this->setupLaravel();

        $role = factory(Role::class)->create();

        $this->browse(function (Browser $browser) {
            $browser->loginAs(User::find(1))
                    ->visit(new Pages\Detail('users', 1))
                    ->waitFor('@roles-index-component', 10)
                    ->within(new IndexComponent('roles'), function ($browser) {
                        $browser->click('@attach-button');
                    })
                    ->on(new Pages\Attach('users', 1, 'roles'))
                    ->waitFor('.content form', 10)
                    ->clickAttach()
                    ->waitForText('The role field is required.');

            $this->assertDatabaseMissing('role_user', [
                'user_id' => '1',
                'role_id' => '1',
            ]);

            $browser->blank();
        });
    }
}
