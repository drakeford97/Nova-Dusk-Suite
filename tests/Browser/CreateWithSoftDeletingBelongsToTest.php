<?php

namespace Laravel\Nova\Tests\Browser;

use App\Models\Sail;
use Database\Factories\DockFactory;
use Database\Factories\ShipFactory;
use Laravel\Dusk\Browser;
use Laravel\Nova\Testing\Browser\Pages\Create;
use Laravel\Nova\Testing\Browser\Pages\Detail;
use Laravel\Nova\Tests\DuskTestCase;

class CreateWithSoftDeletingBelongsToTest extends DuskTestCase
{
    /**
     * @test
     */
    public function test_parent_select_is_locked_when_creating_child_of_soft_deleted_resource()
    {
        $dock = DockFactory::new()->create(['deleted_at' => now()]);

        $this->browse(function (Browser $browser) use ($dock) {
            $browser->loginAs(1)
                    ->visit(new Detail('docks', $dock->id))
                    ->runCreateRelation('ships')
                    ->assertDisabled('select[dusk="dock"]')
                    ->type('@name', 'Test Ship')
                    ->create();

            $this->assertSame(1, $dock->loadCount('ships')->ships_count);

            $browser->blank();
        });
    }

    /**
     * @test
     */
    public function select_belongs_to_respects_with_trashed_checkbox_state()
    {
        $ship = ShipFactory::new()->create(['deleted_at' => now()]);
        $ship2 = ShipFactory::new()->create();

        $this->browse(function (Browser $browser) use ($ship, $ship2) {
            $browser->loginAs(1)
                    ->visit(new Create('sails'))
                    ->whenAvailable('select[dusk="ship"]', function ($browser) use ($ship, $ship2) {
                        $browser->assertSelectMissingOption('', $ship->id)
                                ->assertSelectHasOption('', $ship2->id);
                    })
                    ->withTrashedRelation('ships')
                    ->assertSelectHasOption('select[dusk="ship"]', $ship->id)
                    ->assertSelectHasOption('select[dusk="ship"]', $ship2->id)
                    ->selectRelation('ship', $ship->id)
                    ->type('@inches', 25)
                    ->create();

            $this->assertSame(1, $ship->loadCount('sails')->sails_count);

            $browser->blank();
        });
    }

    /**
     * @test
     */
    public function unable_to_uncheck_with_trashed_if_currently_selected_non_searchable_parent_is_trashed()
    {
        $ship = ShipFactory::new()->create(['deleted_at' => now()]);

        $this->browse(function (Browser $browser) use ($ship) {
            $browser->loginAs(1)
                    ->visit(new Create('sails'))
                    ->withTrashedRelation('ships')
                    ->selectRelation('ship', $ship->id)
                    ->withoutTrashedRelation('ships')
                    // Ideally would use assertChecked here but RemoteWebDriver
                    // returns unchecked when it clearly is checked?
                    ->type('@inches', 25)
                    ->create();

            $this->assertSame(1, Sail::whereBelongsTo($ship)->count());

            $browser->blank();
        });
    }

    /**
     * @test
     */
    public function searchable_belongs_to_respects_with_trashed_checkbox_state()
    {
        $this->defineApplicationStates('searchable');

        $this->browse(function (Browser $browser) {
            $dock = DockFactory::new()->create(['deleted_at' => now()]);

            $browser->loginAs(1)
                    ->visit(new Create('ships'))
                    ->searchRelation('docks', '1')
                    ->pause(1500)
                    ->assertNoRelationSearchResults('docks')
                    ->withTrashedRelation('docks')
                    ->searchFirstRelation('docks', '1')
                    ->type('@name', 'Test Ship')
                    ->create();

            $this->assertSame(1, $dock->loadCount('ships')->ships_count);

            $browser->blank();
        });
    }
}
