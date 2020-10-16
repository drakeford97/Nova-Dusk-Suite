<?php

namespace Laravel\Nova\Tests\Browser;

use App\Models\Post;
use App\Models\User;
use Laravel\Dusk\Browser;
use Laravel\Nova\Tests\Browser\Components\IndexComponent;
use Laravel\Nova\Tests\DuskTestCase;

class IndexBelongsToFieldTest extends DuskTestCase
{
    /**
     * @test
     */
    public function belongs_to_field_navigates_to_parent_resource_when_clicked()
    {
        $this->setupLaravel();

        $user = User::find(1);
        $user->posts()->save($post = factory(Post::class)->create());

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs(User::find(1))
                    ->visit(new Pages\Index('posts'))
                    ->waitFor('@posts-index-component', 10)
                    ->within(new IndexComponent('posts'), function ($browser) use ($user) {
                        $browser->clickLink($user->name);
                    })
                    ->pause(250)
                    ->assertPathIs('/nova/resources/users/'.$user->id);

            $browser->blank();
        });
    }
}
