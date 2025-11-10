<?php

# expanded the namespace to offer protection for utility classes below
namespace LanternTest\Unit\FeaturesTest;

use Lantern\Features\Action;
use Lantern\Features\ConstraintsBuilder;
use Lantern\Features\Feature;
use Lantern\Features\FeatureRegistry;
use Lantern\Lantern;
use Lantern\LanternException;
use LanternTest\TestCase;
use PHPUnit\Framework\Attributes\Test;

class FeaturesTest extends TestCase
{
    #[Test]
    public function aFeatureMustExtendTheBaseFeatureClassAndHaveOtherFeaturesOrActions()
    {
        $this->expectException(LanternException::class);
        $this->expectExceptionMessage('does not extend the base Feature class');

        Lantern::setUp(BadFeatureNoBaseClass::class);
    }

    #[Test]
    public function featureWithinAFeatureMustExist()
    {
        $this->expectException(LanternException::class);
        $this->expectExceptionMessage('Feature class not found');

        Lantern::setUp(BadFeaturePointingToUnknownFeature::class);
    }

    #[Test]
    public function aFeatureCannotBeEmpty()
    {
        $this->expectException(LanternException::class);
        $this->expectExceptionMessage('Feature does not contain any other Feature or Action');

        Lantern::setUp(BadFeatureEmpty::class);
    }

    #[Test]
    public function aFeatureCanDeclareActions()
    {
        Lantern::setUp(GoodFeatureWithAction::class);
        $features = FeatureRegistry::featuresForAction(new GoodAction);

        $this->assertCount(1, $features);
        $this->assertTrue(in_array(new GoodFeatureWithAction, $features));
    }

    #[Test]
    public function aFeatureCanOtherFeaturesWithActions()
    {
        Lantern::setUp(GoodFeatureWithAnotherFeature::class);
        $features = FeatureRegistry::featuresForAction(new GoodAction);

        $this->assertCount(2, $features);
        $this->assertTrue(in_array(new GoodFeatureWithAction, $features));
        $this->assertTrue(in_array(new GoodFeatureWithAnotherFeature, $features));
    }

    #[Test]
    public function aFeatureWithAFailingConstraintStopsTheAvailabilityOfAnyRelatedActions()
    {
        Lantern::setUp(GoodFeatureWithActionButFailingConstraint::class);
        $this->assertFalse(AnotherGoodAction::make()->available());
    }

    #[Test]
    public function aFeatureWithAPassingConstraintDoesNotStopTheAvailabilityOfAnyRelatedActions()
    {
        Lantern::setUp(GoodFeatureWithAction::class);
        $this->assertTrue(GoodAction::make()->available());
    }

    #[Test]
    public function aStackedFeatureCanHaveNestedFeatureWithoutIdCollision()
    {
        // Before the fix, "Identity" and "IdentityFeatures" would both have ID 'identity'
        // after stripping the "Features" suffix, causing a collision.
        // Now that we don't strip suffixes, they have different IDs and work fine.
        Lantern::setUp(Identity::class);

        // The action from the nested feature should be available
        $this->assertTrue(IdentityAction::make()->available());

        // Verify both features are registered
        $features = FeatureRegistry::featuresForAction(new IdentityAction);
        $this->assertCount(2, $features);
    }

    #[Test]
    public function featuresWithExplicitSameIdInSameStackThrowException()
    {
        // This tests that when two features explicitly set the same ID in the same stack,
        // an exception is thrown (as IDs must be unique)
        $this->expectException(LanternException::class);
        $this->expectExceptionMessage('Feature already declared with this ID (duplicate-id)');

        Lantern::setUp(ParentWithDuplicateId::class);
    }
}

class BadFeatureNoBaseClass
{

}

class BadFeaturePointingToUnknownFeature extends Feature
{
    const FEATURES = [
        'sfsdfdfsdkjfhsakdfhkjfhakljfhdkghdkjfh',
    ];
}

class BadFeatureEmpty extends Feature
{

}

class GoodAction extends Action
{
    const GUEST_USERS = true;
}

class AnotherGoodAction extends Action
{
    const GUEST_USERS = true;
}

class GoodFeatureWithAction extends Feature
{
    protected function constraints(ConstraintsBuilder $constraints)
    {
        $firstExtension = get_loaded_extensions()[0];
        $constraints->extensionIsLoaded($firstExtension);
    }

    const ACTIONS = [
        GoodAction::class,
    ];
}

class GoodFeatureWithActionButFailingConstraint extends Feature
{
    protected function constraints(ConstraintsBuilder $constraints)
    {
        $constraints->extensionIsLoaded('some_random_extension_that_surely_wont_exist_for_lantern_testing');
    }

    const ACTIONS = [
        AnotherGoodAction::class,
    ];
}

class GoodFeatureWithAnotherFeature extends Feature
{
    const FEATURES = [
        GoodFeatureWithAction::class,
    ];
}

class IdentityAction extends Action
{
    const GUEST_USERS = true;
}

// Now that we don't strip "Features" suffix, these have different IDs:
// - Identity -> 'identity'
// - IdentityFeatures -> 'identity-features'
class IdentityFeatures extends Feature
{
    const ACTIONS = [
        IdentityAction::class,
    ];
}

class Identity extends Feature
{
    const STACK = 'identity';

    const FEATURES = [
        IdentityFeatures::class,
    ];
}

// Test classes for explicit duplicate ID scenario
class ChildWithDuplicateId extends Feature
{
    const ID = 'duplicate-id';
    const ACTIONS = [IdentityAction::class];
}

class ParentWithDuplicateId extends Feature
{
    const ID = 'duplicate-id';
    const STACK = 'test';
    const FEATURES = [ChildWithDuplicateId::class];
}
