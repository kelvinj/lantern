<?php

namespace Lantern\Features;

use Illuminate\Support\Str;

abstract class Feature
{
    use HasSystemConstraintsTrait;

    /**
     * Stacks give a way of splitting out the Actions into a group.
     */
    const STACK = null;

    /**
     * Used to identify the feature or feature group – the ID must be unique across all Lantern
     * features , even if they're in different namespaces.
     *
     * redeclare this constant in a child class to set your own custom ID if the default is not what you want
     */
    const ID = null;

    /**
     * redeclare this constant in a child class to set a description
     */
    const DESCRIPTION = null;

    /**
     * array of actions that this feature provides
     */
    const ACTIONS = [];

    /**
     * array of sub-features that this feature provides
     */
    const FEATURES = [];

    final public function __construct()
    {
    }

    public static function id()
    {
        if (static::ID) {
            return static::ID;
        }

        $shortClass = collect(explode('\\', static::class))->last();

        return Str::snake($shortClass, '-');
    }

    public static function description()
    {
        return static::DESCRIPTION;
    }
}
