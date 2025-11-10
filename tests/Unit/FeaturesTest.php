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
    public function featuresWithSameIdInSameStackThrowException()
    {
        // This tests that when two features end up with the same ID in the same stack,
        // an exception is thrown (as IDs must be unique)
        $this->expectException(LanternException::class);
        $this->expectExceptionMessage('Feature already declared with this ID (identity)');

        Lantern::setUp(Identity::class);
    }

    #[Test]
    public function aStackedFeatureCanHaveNestedFeatureWithExplicitDifferentId()
    {
        // This tests the fix: explicitly set a different ID to avoid conflicts
        Lantern::setUp(IdentityWithExplicitId::class);

        // The action from the nested feature should be available
        $this->assertTrue(IdentityAction2::make()->available());

        // Verify both features are registered
        $features = FeatureRegistry::featuresForAction(new IdentityAction2);
        $this->assertCount(2, $features);
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

class IdentityAction2 extends Action
{
    const GUEST_USERS = true;
}

// This class name will have "Features" stripped, resulting in ID = 'identity'
class IdentityFeatures extends Feature
{
    const ACTIONS = [
        IdentityAction::class,
    ];
}

// This class name will have "Features" stripped, resulting in ID = 'identity'
// But we explicitly set a different ID to avoid conflict
class IdentityFeaturesWithExplicitId extends Feature
{
    const ID = 'identity-features'; // Explicitly different ID

    const ACTIONS = [
        IdentityAction2::class,
    ];
}

// This class name "Identity" (no Feature/Features suffix) will have ID = 'identity'
// Combined with STACK = 'identity', both features end up with same ID in same stack
class Identity extends Feature
{
    const STACK = 'identity';

    const FEATURES = [
        IdentityFeatures::class,
    ];
}

// Fixed version with explicit ID on nested feature
class IdentityWithExplicitId extends Feature
{
    const STACK = 'identity';

    const FEATURES = [
        IdentityFeaturesWithExplicitId::class,
    ];
}
