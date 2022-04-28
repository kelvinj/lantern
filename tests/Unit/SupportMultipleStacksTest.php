<?php

# expanded the namespace to offer protection for utility classes below
namespace LanternTest\Unit\SupportMultipleStacksTest;

use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Lantern\Features\Action;
use Lantern\Features\AvailabilityBuilder;
use Lantern\Features\Feature;
use Lantern\Lantern;
use Lantern\LanternException;
use LanternTest\TestCase;

/**
 * A vendor who is using Lantern must be able to declare their Features separately from the Main app.
 * This is done by providing a prefix key for the `Features` and `Actions` stack
 */
class SupportMultipleStacksTest extends TestCase
{
    /** @test */
    public function twoStacksCanBeAddedProvidedTheyHaveDifferentStackNames()
    {
        Lantern::setUp(VendorFeatures::class);
        Lantern::setUp(AppFeatures::class);

        $vendorAction = VendorAction::make();
        $this->assertFalse($vendorAction->available());

        $appAction = AppAction::make();
        $this->assertTrue($appAction->available());

        /** @var GateContract $gate */
        $gate = app(GateContract::class);

        // vendor action should be unavailable
        $this->assertFalse($gate->check('vendor-name.my-action'));

        // whilst the app action should be available
        $this->assertTrue($gate->check('my-action'));
    }

    /** @test */
    public function subFeaturesCannotDeclareTheirOwnStack()
    {
        $this->expectException(LanternException::class);
        $this->expectExceptionCode(104);

        Lantern::setUp(FeaturesWithSubFeaturesWhereStackDefined::class);
    }
}

class AppAction extends Action
{
    const ID = 'my-action';
    const GUEST_USERS = true;

    protected function availability(AvailabilityBuilder $availabilityBuilder)
    {
        $availabilityBuilder->assertTrue(true);
    }
}

class AppFeatures extends Feature
{
    const ACTIONS = [
        AppAction::class,
    ];
}

class VendorAction extends Action
{
    const ID = 'my-action'; // shares the same ID as above
    const GUEST_USERS = true;

    protected function availability(AvailabilityBuilder $availabilityBuilder)
    {
        $availabilityBuilder->assertTrue(false);
    }
}

class VendorSubAction extends Action
{
    const ID = 'my-subaction'; // shares the same ID as above
    const GUEST_USERS = true;

    protected function availability(AvailabilityBuilder $availabilityBuilder)
    {
        $availabilityBuilder->assertTrue(true);
    }
}

class SubFeatures extends Feature
{
    const STACK = 'sub';

    const ACTIONS = [
        VendorSubAction::class,
    ];
}

class VendorFeatures extends Feature
{
    const STACK = 'vendor-name';

    const ACTIONS = [
        VendorAction::class,
    ];
}

class FeaturesWithSubFeaturesWhereStackDefined extends Feature
{
    const STACK = 'vendor-name';

    const ACTIONS = [
        VendorAction::class,
    ];

    public const FEATURES = [
        SubFeatures::class,
    ];
}