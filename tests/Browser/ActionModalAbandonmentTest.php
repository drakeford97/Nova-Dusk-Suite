<?php

namespace Laravel\Nova\Tests\Browser;

use App\Models\User;
use Database\Factories\RoleFactory;
use Laravel\Dusk\Browser;
use Laravel\Nova\Testing\Browser\Components\IndexComponent;
use Laravel\Nova\Testing\Browser\Pages\Detail;
use Laravel\Nova\Tests\DuskTestCase;

class ActionModalAbandonmentTest extends DuskTestCase
{
    /**
     * @test
     */
    public function modal_shows_exit_warning_dialog_if_form_has_changes()
    {
        $user = User::find(1);
        $role = RoleFactory::new()->create();
        $user->roles()->attach($role);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit(new Detail('users', $user->id))
                    ->within(new IndexComponent('roles'), function ($browser) {
                        $browser->waitForTable()
                            ->clickCheckboxForId(1)
                            ->selectAction('update-required-pivot-notes', function ($browser) {
                                $browser->elsewhere('', function ($browser) {
                                    $browser->whenAvailable('.modal[data-modal-open=true]', function ($browser) {
                                        $browser->type('@notes', 'Custom Notes');
                                    })
                                    ->assertPresent('.modal[data-modal-open=true]')
                                    ->keys('', '{escape}')
                                    ->assertDialogOpened('Do you really want to leave? You have unsaved changes.')
                                    ->acceptDialog()
                                    ->pause(100)
                                    ->assertMissing('.modal[data-modal-open=true]');
                                });
                            });
                    });

            $browser->blank();
        });
    }

    /**
     * @test
     */
    public function it_doesnt_show_exit_warning_if_modal_has_changes_when_clicking_cancel()
    {
        $user = User::find(1);
        $role = RoleFactory::new()->create();
        $user->roles()->attach($role);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit(new Detail('users', $user->id))
                    ->within(new IndexComponent('roles'), function ($browser) {
                        $browser->waitForTable()
                            ->clickCheckboxForId(1)
                            ->selectAction('update-required-pivot-notes', function ($browser) {
                                $browser->elsewhere('', function ($browser) {
                                    $browser->whenAvailable('.modal[data-modal-open=true]', function ($browser) {
                                        $browser->type('@notes', 'Custom Notes')
                                                ->click('@cancel-action-button');
                                    })
                                    ->pause(100)
                                    ->assertMissing('.modal[data-modal-open=true]');
                                });
                            });
                    });

            $browser->blank();
        });
    }
}
